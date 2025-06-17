<?php

namespace App\Services\OpenAi;

use OpenAI;
use OpenAI\Client;

class OpenaiService
{
    protected ?Client $client;

    public function __construct()
    {
        $yourApiKey = getenv('OPENAI_API_KEY');
        $baseuri = getenv('OPENAI_URL', 'api.openai.com/v1');
        $this->client = OpenAI::factory()
            ->withApiKey($yourApiKey)
            ->withBaseUri($baseuri)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getModel(): string
    {
        return getenv('OPENAI_MODEL');
    }

    public function process(string $message, array $chat)
    {
        if (!$message) return 'Ingrese un mensaje';

        $system = [['role' => 'system', 'content' => 'Eres una asistente de cursos cursos de Platzi.']];
        $user = [['role' => 'user', 'content' => $message]];
        $messages = array_merge($system, $chat, $user);

        // return $messages;
        $result = $this->client->chat()->create([
            'model' => getenv('OPENAI_MODEL'),
            'messages' => $messages,
            'max_tokens' => 200,
            'temperature' => 0.5
        ]);

        return $result->choices[0]->message->content;
    }
}
