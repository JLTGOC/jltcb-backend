<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Events\ChatEvent;
use App\Events\InboxUpdatedEvent;
use App\Models\Quotation;
use App\Models\Participant;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Spatie\Searchable\Search;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\MessageResource;

class ChatController extends Controller
{

    public function __construct()
    {
        // Policy methods located in ChatPolicy
        $this->authorizeResource(Conversation::class, 'conversation');
        $this->middleware('can:sendMessage,conversation')->only('sendMessage');
        $this->middleware('can:indexMessages,conversation')->only('indexMessages');
        
        // located in QuotationPolicy
        $this->middleware('can:chatWithQuotation,quotation')->only('chatWithQuotation');
    }

    /**
     * Index Chats
     * 
     * Inbox of the user's conversations
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $request->validate([
            'search' => 'string|nullable',
        ]);

        $conversationIds = Auth::user()->conversations()->pluck('conversations.id');
        
        if ($search) {
            // Search in Conversations, Messages, and Participants
            $results = (new Search())
                ->registerModel(Conversation::class, ['id', 'name'])
                ->registerModel(Message::class, function ($modelSearchAspect) use ($search, $userId) {
                    $modelSearchAspect->addSearchableAttribute('content')
                        ->orWhereHas('sender', function ($senderQuery) use ($search, $userId) {
                            $senderQuery->where('full_name', 'like', "%{$search}%")
                                        ->where('id', '!=', $userId);
                        });
                })
                ->registerModel(Participant::class, function ($modelSearchAspect) use ($search, $userId) {
                    $modelSearchAspect->addExactSearchableAttribute('user_id')
                        ->orWhereHas('user', function ($userQuery) use ($search, $userId) {
                            $userQuery->where('full_name', 'like', "%{$search}%")
                                      ->where('id', '!=', $userId);
                        });
                })
                ->search($search);

            $matchingConversationIds = collect();

            foreach ($results as $result) {
                $model = $result->searchable;

                if ($model instanceof Conversation) {
                    $matchingConversationIds->push($model->id);
                } elseif ($model instanceof Message) {
                    // If message matches, include its conversation
                    $matchingConversationIds->push($model->conversation_id);
                } elseif ($model instanceof Participant) {
                    // If participant matches, include their conversation
                    $matchingConversationIds->push($model->conversation_id);
                }
            }

            // Filter to only conversations the user is in AND match the search
            $conversationIds = $conversationIds->intersect($matchingConversationIds->unique());
        }

        $conversations = Conversation::whereIn('id', $conversationIds)
            ->with(['lastMessage', 'participants'])
            ->orderByDesc('last_message_at')
            ->get();

        if ($conversations->isEmpty()) {
            return $this->success('No conversations found.');
        }

        return $this->success(
            'Conversations retrieved sucessfully.', 
            ConversationResource::collection($conversations)    
        );
    }

    /**
     * Index Conversation Messages
     * 
     * Fetch messages in a conversation
     */
    public function indexMessages(Conversation $conversation)
    {
        // Mark as read
        $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        // Fetch messages
        $messages = $conversation->messages()
            ->with(['sender:id,full_name,image_path', 'reference'])
            ->orderBy('created_at', 'asc') // Oldest first (Standard Chat UI)
            ->get();

        // Use the Resource
        return $this->success(
            'Messages retrieved successfully.',
            MessageResource::collection($messages)
        );
    }

    /**
     * Chat With Quotation
     * 
     * Sends a quotation card in a conversation
     */
    public function chatWithQuotation(Quotation $quotation)
    {
        $clientId = auth()->id();

        // 1. Get the Lead (Receiver)
        $leadAsUser = User::where('email', 'accountspecialist@gmail.com')->first();
        if (!$leadAsUser)
            return $this->error('Lead AS account missing', 404);
        $leadAsId = $leadAsUser->id;

        return DB::transaction(function () use ($clientId, $leadAsId, $quotation) {

            // 2. Find or Create Conversation (Client <-> Lead)
            $conversation = Conversation::where('type', 'DIRECT')
                ->whereHas('participants', fn($q) => $q->where('user_id', $clientId))
                ->whereHas('participants', fn($q) => $q->where('user_id', $leadAsId))
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create(['type' => 'DIRECT']);
                $conversation->participants()->attach([$clientId, $leadAsId]);
            }

            // 3. CHECK: Prevent Duplicate Cards
            // Scan history to see if this Quotation Card was EVER sent
            $alreadySent = $conversation->messages()
                ->where('type', 'QUOTATION_CARD')
                ->where('reference_id', $quotation->id)
                ->where('reference_type', Quotation::class)
                ->exists();

            // 4. SEND SYSTEM MESSAGE (If not duplicate)
            if (!$alreadySent) {
                $conversation->messages()->create([
                    'sender_id' => null, // <--- FIX: SET TO NULL (System)
                    'type' => 'QUOTATION_CARD',
                    'content' => null,
                    'reference_id' => $quotation->id,
                    'reference_type' => Quotation::class,
                ]);

                $conversation->update(['last_message_at' => now()]);
            }

            return $this->success(
                $alreadySent ? 'Navigating to chat' : 'Connected to Lead AS',
                ["conversation_id" => $conversation->id],
                200
            );
        });
    }

    /**
     * Send Message 
     * 
     * Send message to either GROUP or DIRECT
     */
    public function sendMessage(Request $request, Conversation $conversation) {
        $request->validate([
            'type' => 'required|in:TEXT,IMAGE,FILE',
            'content' => 'required_if:type,TEXT|nullable|string',
            'file' => 'required_if:type,IMAGE,FILE|max:5120', // Max 5MB
        ]);

        // Set user's last_read_at pivot to now as they send a message
        $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        $conversationType = $conversation->type;
        if ($conversationType === 'DIRECT') {
            return $this->sendMessageToUser($request, $conversation);
        } else if ($conversationType === 'GROUP') {
            return $this->sendMessageToGroup($request, $conversation);
        }
    }

    /**
     * Send Message to Group
     * 
     * Reply to a group conversation
     */
    private function sendMessageToGroup(Request $request, Conversation $conversation)
    {
        $senderId = Auth::id();

        // 1. HANDLE FILE UPLOADS (Same logic as sendMessageToUser)
        $attachmentPath = null;

        // CASE A: IMAGE (Use the Helper)
        if ($request['type'] === 'IMAGE') {
            // 'file' is the key name, 'chat_images' is the folder
            $path = upload_image($request, 'file', 'chat_images');

            if ($path) {
                // Prepend 'storage/' to match symlink structure
                $attachmentPath = 'storage/' . $path;
            }
        }
        // CASE B: FILE (PDF, Docs - Standard Upload)
        elseif ($request['type'] === 'FILE') {
            if ($request->hasFile('file')) {
                // Store in 'chat_files' folder
                $path = $request->file('file')->store('chat_files', 'public');
                $attachmentPath = 'storage/' . $path;
            }
        }

        // 2. CREATE MESSAGE
        $message = DB::transaction(function () use ($conversation, $request, $senderId, $attachmentPath) {
            $msg = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $request['content'],
                'type' => $request['type'],
                'attachment_path' => $attachmentPath,
            ]);

            // Update timestamp to bump conversation to top
            $conversation->update(['last_message_at' => now()]);

            return $msg->load('sender');
        });

        // 3. FORMAT RESPONSE (Matched with User logic)
        $attachmentUrl = $message->attachment_path ? asset($message->attachment_path) : null;
        $fileName = $message->attachment_path ? basename($message->attachment_path) : null;

        $formattedMessage = [
            'id' => $message->id,
            'type' => $message->type,
            'content' => $message->content,
            'attachment_url' => $attachmentUrl,
            'file_name' => $fileName,
            'conversation_id' => $message->conversation_id,
            'created_at' => $message->created_at->toDateTimeString(),
            'sender' => [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name,
                'image_path' => $message->sender->image_path ? asset($message->sender->image_path) : null,
            ],
        ];

        $this->broadcastChatEvents($conversation, $formattedMessage);

        return $this->success('Message sent successfully.', $formattedMessage, 201);
    }

    /**
     * Send Message to User
     * 
     * Reply to a user
     */
    private function sendMessageToUser(Request $request, Conversation $conversation)
    {
        // Direct message to other participant in the conversation
        $senderId = Auth::id();
        $receiverId = $conversation->participants()->whereNot('user_id', $senderId)->pluck('user_id');

        // Find or Create Conversation logic...
        $conversation = Conversation::where('type', 'DIRECT')
            ->whereHas('participants', fn($q) => $q->where('user_id', $senderId))
            ->whereHas('participants', fn($q) => $q->where('user_id', $receiverId))
            ->first();

        if (!$conversation) {
            $conversation = DB::transaction(function () use ($senderId, $receiverId) {
                $c = Conversation::create(['type' => 'DIRECT']);
                $c->participants()->attach([$senderId, $receiverId]);
                return $c;
            });
        }

        // 2. HANDLE FILE UPLOADS (Separated Logic)
        $attachmentPath = null;

        // CASE A: IMAGE (Use the Helper)
        if ($request->type === 'IMAGE') {
            // 'file' is the key name in the request
            // 'chat_images' is the folder name inside storage/app/public/
            $path = upload_image($request, 'file', 'chat_images');

            if ($path) {
                // Prepend 'storage/' so it matches your symlink structure
                $attachmentPath = 'storage/' . $path;
            }
        }
        // CASE B: FILE (PDF, Docs - Standard Upload)
        elseif ($request->type === 'FILE') {
            if ($request->hasFile('file')) {
                // Store in 'chat_files' folder
                $path = $request->file('file')->store('chat_files', 'public');
                $attachmentPath = 'storage/' . $path;
            }
        }

        // 3. CREATE MESSAGE
        $message = DB::transaction(function () use ($conversation, $request, $senderId, $attachmentPath) {
            $msg = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $request['content'],
                'type' => $request['type'],
                'attachment_path' => $attachmentPath,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg->load('sender');
        });

        // 4. FORMAT RESPONSE
        // (Using manual array construction as requested, though Resource is better)
        $attachmentUrl = $message->attachment_path ? asset($message->attachment_path) : null;
        $fileName = $message->attachment_path ? basename($message->attachment_path) : null;

        $formattedMessage = [
            'id' => $message->id,
            'type' => $message->type,
            'content' => $message->content,
            'attachment_url' => $attachmentUrl,
            'file_name' => $fileName,
            'conversation_id' => $message->conversation_id,
            'created_at' => $message->created_at->toDateTimeString(),
            'sender' => [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name,
                'image_path' => $message->sender->image_path ? asset($message->sender->image_path) : null,
            ],
        ];

        $this->broadcastChatEvents($conversation, $formattedMessage);

        return $this->success('Message sent successfully.', $formattedMessage, 201);
    }

    private function broadcastChatEvents(Conversation $conversation, array $formattedMessage) {
        $participants = $conversation->participants()->get();

        foreach($participants as $participant) {
            broadcast(new InboxUpdatedEvent($participant->id, $conversation));
        }

        broadcast(new ChatEvent($formattedMessage));
    }
}
