<?php

namespace Database\Seeders;

use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Traits\SeederFileTrait;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    use SeederFileTrait;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $clients = User::role('Client')->get();

        foreach($clients as $client) {
            // Direct Conversations
            $quotations = Quotation::where('client_id', $client->id)
                ->where('status', 'RESPONDED')
                ->orderBy('created_at', 'asc')
                ->get();
            
            if (! $quotations->isEmpty()) {
                $date = Carbon::now()->subDays($quotations->count());

                foreach($quotations as $quotation) {
                    $conversation = $this->generateConversation($quotation, 'DIRECT');
                    $this->generateMessageCard($conversation, $quotation, $date);

                    $hasChatsAlready = fake()->boolean(75);

                    if ($hasChatsAlready) {
                        $this->generateRandomMessages($conversation, $date);
                    }

                    $date = $date->addDays();
                }
            }   

            // Group Conversation
            $shipments = Shipment::where('client_id', $client->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach($shipments as $shipment) {
                $date = Carbon::now()->subDays(rand(5, 30));
                $conversation = $this->generateConversation($shipment, 'GROUP');
                $this->generateMessageCard($conversation, $shipment, $date);
                $this->generateRandomMessages($conversation, $date);
            }
        }
    }

    private function generateConversation($model, string $type) {
        $clientId = $model->client_id;
        $leadAsId = $model->as_id;

        $conversation = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $clientId))
            ->whereHas('participants', fn($q) => $q->where('user_id', $leadAsId))
            ->first();

        if (!$conversation || $model instanceof Shipment) {
            $conversation = Conversation::create([
                'type' => $type,
                'name' => $type === 'GROUP' ? $model->reference_number : null,
                'last_message_at' => Carbon::now()
            ]);
            $conversation->participants()->attach([$clientId, $leadAsId]);
        }

        return $conversation;
    }

    private function generateMessageCard(Conversation $conversation, $model, $date) {
        $message = $conversation->messages()->create([
            'sender_id' => null, // <--- FIX: SET TO NULL (System)
            'type' => $model instanceof Quotation ? 'QUOTATION_CARD' : 'SHIPMENT_CARD',
            'content' => null,
            'reference_id' => $model->id,
            'reference_type' => $model instanceof Quotation ? Quotation::class : Shipment::class,
        ]);

        $message->update([
            'created_at' => $date
        ]);
    }

    private function generateRandomMessages(Conversation $conversation, Carbon $date) {
        $messageCount = fake()->numberBetween(3, 20);
        $participants = $conversation->participants()->get()->pluck('id');

        for($i=1; $i<$messageCount; $i++) {
            $senderId = fake()->randomElement($participants);

            $fileName = null;
            $filePath = null;

            $messageType = fake()->randomElement(['TEXT', 'FILE', 'IMAGE']);

            if ($messageType !== 'TEXT') {

                $attachments = [
                    'IMAGE' => [
                        'location' => 'articles',
                        'items' => [
                            'sample1.jpg',
                            'sample2.jpg',
                            'sample3.jpg',
                            'sample4.jpg',
                            'sample5.jpg',
                            'sample6.jpg',
                            'sample7.jpg',
                            'sample8.jpg',
                        ],
                    ],
                    'FILE' => [
                        'location' => 'files',
                        'items' => [
                            'clientDoc.pdf',
                            'QuotationFile.pdf',
                            'clientDoc1.pdf',
                        ],
                    ],
                ];

                $type = $messageType === 'IMAGE' ? 'IMAGE' : 'FILE';

                $fileName = fake()->randomElement($attachments[$type]['items']);
                $filePath = str_replace(
                    'storage/',
                    '',
                    $this->copySeederFile($attachments[$type]['location'], $fileName)
                );
            }

            $message = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $messageType === 'TEXT' ? fake()->sentence() : null,
                'type' => $messageType,
                'file_name' => $fileName,
                'attachment_path' => $filePath
            ]);

            $newDate = $date->addMinutes($i);
            
            $conversation->participants()->updateExistingPivot(
                $senderId, ['last_read_at' => $newDate]
            );

            $message->update(['created_at' => $newDate]);

            $conversation->update([
                'last_message_at' => $newDate
            ]);
        }
    }
}
