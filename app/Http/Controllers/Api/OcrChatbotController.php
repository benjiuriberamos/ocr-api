<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAi\OpenaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OcrChatbotController extends Controller
{
    
    public function index(Request $request)
    {
        $openai = new OpenaiService();
        $client = $openai->getClient();

        // Validar que se envió una imagen
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // max 10MB
        ]);

        // Obtener o crear thread
        if (!$threadId = $request->input('thread_id')) {
            $thread = $client->threads()->create([]);
            $threadId = $thread->id;
        } else {
            $threadId = $request->input('thread_id');
        }

        // Save the uploaded file
        $photo = $request->file('photo');
        $fileInfo = $this->saveUploadedFile($photo);

        try {

            // Crear un run para procesar el mensaje
            $run = $client->threads()->runs()->create($threadId, [
                'assistant_id' => 'asst_kMOfRpaCxApxktOJWKcyi1VB',
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $fileInfo['full_path']
                    ]
                ],
                [
                    'type' => 'text',
                    'text' => 'Por favor, extrae todo el texto de esta imagen usando OCR.'
                ]
            ]);

            // Esperar a que el run se complete
            do {
                sleep(1);
                $run = $client->threads()->runs()->retrieve($threadId, $run->id);
            } while (in_array($run->status, ['queued', 'in_progress']));

            if ($run->status === 'completed') {
                // Obtener los mensajes del thread
                $messages = $client->threads()->messages()->list($threadId);
                
                // El primer mensaje debería ser la respuesta del assistant
                $extractedText = '';
                if (!empty($messages->data)) {
                    $latestMessage = $messages->data[0];
                    if ($latestMessage->role === 'assistant' && !empty($latestMessage->content)) {
                        $extractedText = $latestMessage->content[0]->text->value;
                    }
                }

                return response()->json([
                    'success' => true,
                    'extracted_text' => $extractedText,
                    'metadata' => [
                        'conversation_id' => $threadId,
                        'file_info' => $fileInfo,
                        'run_id' => $run->id,
                        'assistant_id' => 'asst_kMOfRpaCxApxktOJWKcyi1VB'
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to process image: ' . $run->status,
                    'metadata' => [
                        'conversation_id' => $threadId,
                        'file_info' => $fileInfo,
                        'run_status' => $run->status
                    ],
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error processing image: ' . $e->getMessage(),
                'metadata' => [
                    'conversation_id' => $threadId,
                    'file_info' => $fileInfo
                ],
            ], 500);
        }
    }

    protected function saveUploadedFile($file)
    {
        // Generate unique filename with original extension
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        // Store file in public/uploads directory
        $path = $file->storeAs('uploads', $filename, 'public');
        
        return [
            'filename' => $filename,
            'path' => $path,
            'full_path' => asset('storage/' . $path),
            'absolute_path' => storage_path('app/public/' . $path)
        ];
    }


    protected function deleteFile(Request $request)
    {
        $path = $request->input('path');
        
        if (!$path) {
            return response()->json([
                'error' => 'Path is required'
            ], 400);
        }

        // Remove 'storage/' from the beginning of the path if it exists
        $path = str_replace('storage/', '', $path);
        
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            
            return response()->json([
                'message' => 'File deleted successfully',
                'path' => $path
            ], 200);
        }

        return response()->json([
            'error' => 'File not found',
            'path' => $path
        ], 404);
    }
}
