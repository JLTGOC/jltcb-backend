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
        // return $request->header('Platform', 'mobile');

        $search = $request->input('search');


        $request->validate([
            'search' => 'string|nullable',
        ]);

        $userId = Auth::id();

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

        // Used for custom logic in Conversation Resource
        $request->merge([
            'from_index' => true
        ]);

        return $this->success(
            'Conversations retrieved sucessfully.', 
            ConversationResource::collection($conversations)    
        );
    }

    /**
     * Show Conversation
     * 
     * Show conversation data
     */
    public function show(Conversation $conversation) {
        return $this->success(
            'Conversation fetched sucessfully', new ConversationResource($conversation)
        );
    }
    

    /**
     * Index Conversation Messages
     * 
     * Fetch messages in a conversation
     */
    public function indexMessages(Request $request, Conversation $conversation)
    {
        $sortOrder = $request->input('sortOrder', 'asc');
        $perPage = $request->input('perPage', 30);

        // Fetch messages
        $paginated = $conversation->messages()
            ->with(['sender:id,full_name,image_path', 'reference'])
            ->orderBy('created_at', $sortOrder) 
            ->orderBy('id', $sortOrder) // Fallback ordering for cursor pagination 
            ->cursorPaginate($perPage);

        return $this->success(
            'Messages retrieved successfully.', 
            [
                'messages' => MessageResource::collection($paginated),
                'pagination' => [
                    'prev_cursor' => $paginated->previousCursor()?->encode(),
                    'next_cursor' => $paginated->nextCursor()?->encode(),
                    'prev_page_url' => $paginated->previousPageUrl(),
                    'next_page_url' => $paginated->nextPageUrl()
                ]
            ]
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
                // To update user unread count
                $this->markAsRead($conversation);
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
            'file' => 'required_if:type,IMAGE,FILE|file|max:5120', // Max 5MB
            'client_id' => 'sometimes|string' // For frontend's optimistic message update (sabe ni vince)
        ]);
 
        // Set user's unread count to 0 as they send a message
        $this->markAsRead($conversation);

        $senderId = Auth::id();

        $attachmentPath = null;
        $fileName = null;

        if ($request->type !== 'TEXT') {
            $fileName = $request->file('file')->getClientOriginalName();

            $attachmentPath = match($request->type) {
                'IMAGE' => upload_image($request, 'file', 'chat_images'),
                'FILE' => $request->file('file')->store('chat_files', 'public')
            };
        }

        // CREATE MESSAGE
        $message = DB::transaction(function () use ($conversation, $request, $senderId, $attachmentPath, $fileName) {
            $msg = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $request['content'],
                'type' => $request['type'],
                'file_name' => $fileName,
                'attachment_path' => $attachmentPath,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg->load('sender');
        });
        
        $this->broadcastChatEvents($conversation, $message, $request->client_id);

        return $this->success('Message sent successfully.', new MessageResource($message), 201);
    }

    /**
     * Mark Chats As Read 
     * 
     * Set chat participant's unread count to 0
     */
    public function markAsRead(Conversation $conversation) {
        $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        return $this->success('Message marked as read', ['success' => true]);
    }

    private function broadcastChatEvents(Conversation $conversation, Message $message, string $clientId) {
        $participants = $conversation->participants()->get();

        foreach($participants as $participant) {
            broadcast(new InboxUpdatedEvent($participant->id, $conversation));
        }

        broadcast(new ChatEvent($message, $clientId));
    }
}
