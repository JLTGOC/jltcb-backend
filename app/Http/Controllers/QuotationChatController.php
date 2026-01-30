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
            'quotation_id'    => 'required|exists:quotations,id',
        ]);

        $quotation = Quotation::findOrFail($request->quotation_id);

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id'       => auth()->id(),
            'type'            => 'QUOTATION_CARD', 
            'content'         => "Quotation #{$quotation->reference_no}", 
            'reference_id'    => $quotation->id,
            'reference_type'  => Quotation::class,
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
     * 1. Finds the conversation between Client and the PIC (Lead AS).
     * 2. Always sends the Quotation Card as a new message (to provide context).
     * 3. Returns the conversation ID for redirection.
     */
    public function chatLeadAs(Quotation $quotation)
    {
        $clientId = auth()->id();

        // for the meantime, use LeadAs
        $leadAsUser = User::where('email', 'accountspecialist@gmail.com')->first();
        
        // Safety check: Ensure the Lead account exists before proceeding
        if (!$leadAsUser) {
             return response()->json(['error' => 'Lead AS account not found'], 404);
        }
        
        $leadAsId = $leadAsUser->id;

        return DB::transaction(function () use ($clientId, $leadAsId, $quotation) {
            
            // 1. Find or Create the Direct Conversation
            $conversation = Conversation::where('type', 'DIRECT')
                ->whereHas('participants', fn($q) => $q->where('user_id', $clientId))
                ->whereHas('participants', fn($q) => $q->where('user_id', $leadAsId))
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create(['type' => 'DIRECT']);
                $conversation->participants()->attach([$clientId, $leadAsId]);
            }
            
            // 2. CHECK: Is the LAST message already this exact card?
            $lastMessage = $conversation->lastMessage; 

            $alreadySent = $lastMessage 
                && $lastMessage->type === 'QUOTATION_CARD' 
                && (int)$lastMessage->reference_id === (int)$quotation->id
                && $lastMessage->reference_type === Quotation::class;

            // 3. ONLY send if it wasn't just sent
            if (!$alreadySent) {
                $conversation->messages()->create([
                    'sender_id'       => $clientId,
                    'type'            => 'QUOTATION_CARD',
                    'content'         => null,
                    'reference_id'    => $quotation->id,
                    'reference_type'  => Quotation::class,
                ]);

                // Bump the timestamp so it goes to the top of the inbox
                $conversation->update(['last_message_at' => now()]);
            }

            // --- FIX ENDS HERE ---

            // Return the ID so the frontend can navigate: /chats/{id}
            return response()->json([
                'message' => $alreadySent ? 'Navigating to chat' : 'Connected to Lead AS',
                'conversation_id' => $conversation->id
            ]);
        });
    }
}
