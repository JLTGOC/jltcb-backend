<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Quotation;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MessageResource;

class ChatController extends Controller
{
    /**
     * Index Chats
     * 
     * Inbox of the user's conversations
     */
    public function index()
    {
        $userId = Auth::id();

        $conversations = Auth::user()->conversations()
            ->with(['lastMessage', 'participants'])
            ->orderByDesc('last_message_at')
            ->get();

        if ($conversations->isEmpty()) {
            return $this->success('No conversations found.');
        }

        $formatted = $conversations->map(function ($chat) use ($userId) {
            // Find "Other" participant for avatar/name
            $other = $chat->participants->firstWhere('id', '!=', $userId);
            $me = $chat->participants->firstWhere('id', $userId);

            // Calculate Unread Count
            $lastRead = $me?->pivot->last_read_at;
            $unread = 0;

            if ($chat->lastMessage) {
                // Base query: Messages in this chat sent by OTHERS OR SYSTEM
                $query = $chat->messages()->where(function ($q) use ($userId) {
                    $q->where('sender_id', '!=', $userId)
                        ->orWhereNull('sender_id'); // <--- Include System Messages
                });

                if (!$lastRead) {
                    // If I've never read the chat, count all messages from others/system
                    $unread = $query->count();
                } else {
                    // Count messages from others/system created AFTER my last read time
                    $unread = $query->where('created_at', '>', $lastRead)->count();
                }
            }

            if ($chat->lastMessage->type === 'TEXT') {
                $lastMessage = $chat->lastMessage->content;
            } else if ($chat->lastMessage->type === 'IMAGE') {
                $lastMessage = '[Image]';
            } else if ($chat->lastMessage->type === 'FILE') {
                $lastMessage = '[File]';
            } else if ($chat->lastMessage->type === 'QUOTATION_CARD') {
                $lastMessage = '[Quotation Card]';
            } else if ($chat->lastMessage->type === 'SHIPMENT_CARD') {
                $lastMessage = '[Shipment Card]';
            } else {
                $lastMessage = 'No message';
            }

            return [
                'id' => $chat->id,
                'type' => $chat->type,
                'title' => $chat->type === 'GROUP' ? $chat->name : ($other->full_name ?? 'User'),
                'image_path' => $chat->type === 'GROUP' ? null : asset($other->image_path),
                'last_message' => $lastMessage,
                'time' => $chat->lastMessage?->created_at?->format('h:iA'),
                'unread_count' => $unread,
            ];
        });

        return $this->success('Conversations retrieved successfully.', $formatted, 200);
    }

    /**
     * Show Converstion
     * 
     * Fetch messages in a conversation
     */
    public function show(Conversation $conversation)
    {
        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

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
     * Send Message to Group
     * 
     * Reply to a group conversation
     */
    public function sendMessageToGroup(Request $request, Conversation $conversation)
    {
        // 1. Validation (Matched with User logic)
        $request->validate([
            'type' => 'required|in:TEXT,IMAGE,FILE',
            'content' => 'required_if:type,TEXT|nullable|string',
            'file' => 'required_if:type,IMAGE,FILE|max:5120', // Max 5MB
        ]);

        // 2. Authorization
        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

        $senderId = Auth::id();

        // 3. HANDLE FILE UPLOADS (Same logic as sendMessageToUser)
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

        // 4. CREATE MESSAGE
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

        // 5. FORMAT RESPONSE (Matched with User logic)
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

        return $this->success('Message sent successfully.', $formattedMessage, 201);
    }

    /**
     * Send Message to User
     * 
     * Reply to a user
     */
    public function sendMessageToUser(Request $request, User $user)
    {
        // 1. Validation
        $request->validate([
            'type' => 'required|in:TEXT,IMAGE,FILE',
            'content' => 'required_if:type,TEXT|nullable|string',
            'file' => 'required_if:type,IMAGE,FILE|max:5120',
        ]);

        $senderId = Auth::id();

        // Direct message to LeadAS logic...
        $leadAsId = 2;
        if (!Auth::user()->conversations()->exists()) {
            $receiverId = $leadAsId;
        } else {
            $receiverId = $user->id;
        }

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

        return $this->success('Message sent successfully.', $formattedMessage, 201);
    }
}
