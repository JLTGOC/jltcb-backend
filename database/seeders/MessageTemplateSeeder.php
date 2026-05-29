<?php

namespace Database\Seeders;

use App\Models\QuotationTemplateConfig\MessageTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messages = [
            'MSG 1' => 'Thank you for your for considering Jill L. Tolentino Customs Brokerage for your accreditation requirement.
            
            We are pleased to offer our rate proposal for the accreditation of your company as a new exporter with PhilExport',

            'MSG 2' => "We appreciate your interest in Jill L. Tolentino Customs Brokerage for your accreditation requirements.
            
            Kindly find our proposed rates for assisting your company in securing accreditation as a new exporter with PhilExport.",

            'MSG 3' => 'Greetings from Jill L. Tolentino Customs Brokerage, and thank you for considering our services for your accreditation needs.
            
            We are pleased to submit our rate proposal for your company’s registration as a new exporter with PhilExport.'
        ];

        foreach($messages as $name => $message) {
            MessageTemplate::create([
                'template_name' => $name,
                'message' => $message
            ]);
        }
    }
}
