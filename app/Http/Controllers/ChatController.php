<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * INBOX: List all chats
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
                $unread = (!$lastRead)
                    ? $chat->messages()->count()
                    : $chat->messages()->where('created_at', '>', $lastRead)->count();
            }

            return [
                'id' => $chat->id,
                'type' => $chat->type,
                'title' => $chat->type === 'GROUP' ? $chat->name : ($other->name ?? 'User'),
                'avatar' => $chat->type === 'GROUP' ? null : $other->avatar_url,
                'last_message' => $chat->lastMessage?->content ?? 'No message',
                'time' => $chat->lastMessage?->created_at?->diffForHumans(),
                'unread_count' => $unread,
            ];
        });

        return $this->success('Conversations retrieved successfully.', $formatted, 200);
    }

    /**
     * HISTORY: Get messages for a chat
     */
    public function show(Conversation $conversation)
    {
        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

        // Mark as read
        $conversation->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        // 1. Fetch the messages
        $messages = $conversation->messages()
            ->with(['sender:id,full_name,image_path', 'reference']) // We load full reference first
            ->orderBy('created_at', 'asc') // Oldest first
            ->get();

        // 2. Transform the data to strictly limit what is sent
        $formattedMessages = $messages->map(function ($message) {

            // Clean up the Quotation Card data if it exists
            if ($message->type === 'QUOTATION_CARD' && $message->reference) {
                return [
                    'id' => $message->id,
                    'type' => $message->type,
                    'content' => $message->content,
                    'created_at' => $message->created_at->format('m/d/Y'),
                    'sender' => [
                        'id' => $message->sender->id,
                        'full_name' => $message->sender->full_name,
                        'image_path' => $message->sender->image_path,
                    ],
                    'reference' => [
                        'id' => $message->reference->id,
                        'reference_number' => $message->reference->reference_number,
                        'commodity' => $message->reference->commodity,
                        'volume' => $message->reference->volume,
                        'date_created' => $message->reference->created_at->format('m/d/Y'),
                    ]
                ];
            }

            // Return normal text messages as is (or simplify them too if needed)
            return $message;
        });

        return $this->success('Messages retrieved successfully.', $formattedMessages);
    }

    /**
     * SEND TYPE A: Reply to an existing Conversation
     */
    public function sendMessageToGroup(Request $request, Conversation $conversation)
    {
        $request->validate(['content' => 'required|string']);

        abort_unless($conversation->participants()->where('user_id', Auth::id())->exists(), 403);

        $message = DB::transaction(function () use ($conversation, $request) {
            $msg = $conversation->messages()->create([
                'sender_id' => Auth::id(),
                'content' => $request['content'],
                'type' => 'TEXT',
            ]);
            $conversation->update(['last_message_at' => now()]);
            return $msg;
        });

        return response()->json($message, 201);
    }

    /**
     * SEND TYPE B: Message a specific User (Find or Create Chat)
     */
    public function sendMessageToUser(Request $request, User $user)
    {
        $request->validate(['content' => 'required|string']);

        $senderId = Auth::id();

        // Direct message to LeadAS
        $leadAsId = 2;

        // check for conversations
        if (!Auth::user()->conversations()->exists()) {
            // send to LeadAS
            $receiverId = $leadAsId;
        } else {
            // send to the requested user
            $receiverId = $user->id;
        }

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

        $message = DB::transaction(function () use ($conversation, $request, $senderId) {
            $msg = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $request['content'],
                'type' => 'TEXT',
            ]);
            $conversation->update(['last_message_at' => now()]);
            return $msg->load('sender');
        });

        // Format response with sender nested
        $formattedMessage = [
            'id' => $message->id,
            'content' => $message->content,
            'type' => $message->type,
            'conversation_id' => $message->conversation_id,
            'created_at' => $message->created_at->toDateTimeString(),
            'updated_at' => $message->updated_at->toDateTimeString(),
            'sender' => [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name,
                'image_path' => $message->sender->image_path,
            ],
        ];

        return $this->success('Message sent successfully.', $formattedMessage, 201);
    }
}
