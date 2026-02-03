<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use App\Models\Quotation;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationChatController extends Controller
{
    // POST /api/quotations/send-card
    public function sendQuotationCard(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'quotation_id' => 'required|exists:quotations,id',
        ]);

        $quotation = Quotation::findOrFail($request->quotation_id);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => auth()->id(),
            'type' => 'QUOTATION_CARD',
            'content' => "Quotation #{$quotation->reference_no}",
            'reference_id' => $quotation->id,
            'reference_type' => Quotation::class,
        ]);

        $message->conversation->update(['last_message_at' => now()]);

        return response()->json($message->load('reference'));
    }

    // POST /api/quotations/{id}/approve
    public function approveQuotation($id)
    {
        return DB::transaction(function () use ($id) {
            $quotation = Quotation::findOrFail($id);
            $quotation->update(['status' => 'APPROVED']);

            // Create Operations Group Chat
            $opsGroup = Conversation::create([
                'type' => 'GROUP',
                'name' => "OPS: " . $quotation->reference_no,
                'last_message_at' => now(),
            ]);

            // Add Members: Client + Lead (You)
            // Note: Add your logic here to add other Operations Staff IDs
            $members = [$quotation->client_user_id, auth()->id()];

            $opsGroup->participants()->attach(array_unique($members));

            // System Message
            $opsGroup->messages()->create([
                'sender_id' => null,
                'type' => 'SYSTEM',
                'content' => "Operations Channel created for {$quotation->reference_no}.",
            ]);

            return response()->json([
                'message' => 'Quotation Approved',
                'new_conversation_id' => $opsGroup->id
            ]);
        });
    }

    /**
     * "CHAT PIC" BUTTON ACTION
     * 1. Finds the conversation between Client and the (Lead AS).
     * 2. Always sends the Quotation Card as a new message (to provide context).
     * 3. Returns the conversation ID for redirection.
     */
    public function chatWithQuotation(Quotation $quotation)
    {
        $clientId = auth()->id();

        // 1. Get the Lead (Receiver)
        $leadAsUser = User::where('email', 'accountspecialist@gmail.com')->first();
        if (!$leadAsUser) return response()->json(['error' => 'Lead AS account missing'], 404);
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
                    'sender_id'       => null, // <--- FIX: SET TO NULL (System)
                    'type'            => 'QUOTATION_CARD',
                    'content'         => null, 
                    'reference_id'    => $quotation->id,
                    'reference_type'  => Quotation::class,
                ]);

                $conversation->update(['last_message_at' => now()]);
            }

            return response()->json([
                'message' => $alreadySent ? 'Navigating to chat' : 'Connected to Lead AS',
                'conversation_id' => $conversation->id
            ]);
        });
    }
}
