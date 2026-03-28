<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageTemplateResource;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class MessageTemplateController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(MessageTemplate::class, 'record');
    }

    /**
     * Index Message templates
     * 
     * Display a listing of the resource.
     */
    public function index()
    {
        $messageTemplate = MessageTemplate::all(); 
        $message = $messageTemplate->isEmpty() ? 'No Message Template available' : 'Message Templates fetched successfully';

        return $this->success($message, MessageTemplateResource::collection($messageTemplate));
    }

    /**
     * Store Message Templates
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:255', 'unique:message_templates,template_name'],
            'message' => 'required|string'
        ]);

        $messageTemplate = DB::transaction(function () use ($validated) {
            return MessageTemplate::create($validated);
        });

        return $this->success('Message Template stored successfully', new MessageTemplateResource($messageTemplate));
    }

    /**
     * Show Message template
     * 
     * Display the specified resource.
     */
    public function show(MessageTemplate $message)
    {
        return $this->success(
            'Message template fetched successfully', 
            new MessageTemplateResource($message)
        );
    }

    /**
     * Update Message template
     * 
     * Update the specified resource in storage.
     */
    public function update(Request $request, MessageTemplate $message)
    {
        $validated = $request->validate([
            'template_name' => [
                'sometimes',
                'required', 
                'string', 
                'max:255', 
                Rule::unique('message_templates', 'template_name')->ignore($message)
            ],
            'message' => 'sometimes|required|string'
        ]);

        DB::transaction(function () use ($validated, $message) {
            $message->update($validated);
        });

        return $this->success('Message template updated successfully', new MessageTemplateResource($message));
    }

    /**
     * Delete Message template
     * 
     * Remove the specified resource from storage.
     */
    public function destroy(MessageTemplate $message)
    {
        $message->delete();

        return $this->success('Message template deleted successfully');
    }
}
