<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAi\OpenaiService;
use Illuminate\Http\Request;
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

        // Save the uploaded file
        $photo = $request->file('photo');
        $fileInfo = $this->saveUploadedFile($photo);
        
        try {
            // Crear un run para procesar el mensaje
            $run = $client->threads()->createAndRun(
                [
                    'assistant_id' => 'asst_kMOfRpaCxApxktOJWKcyi1VB',
                    'thread' => [
                        'messages' =>
                            [
                                [
                                    'role' => 'user',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => 'Por favor, extrae todo el texto de esta imagen usando OCR.'
                                        ],
                                        [
                                            'type' => 'image_url',
                                            'image_url' => [
                                                'url' => $fileInfo['full_path']
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                    ],
                ],
            );

            // Esperar a que el run se complete
            $runId = $run->id;
            $threadId = $run->threadId;
            
            do {
                $runStatus = $client->threads()->runs()->retrieve($threadId, $runId);
                sleep(1); // Esperar 1 segundo antes de verificar nuevamente
            } while ($runStatus->status === 'queued' || $runStatus->status === 'in_progress');

            // Si el run se completó exitosamente, obtener los mensajes
            if ($runStatus->status === 'completed') {
                $messages = $client->threads()->messages()->list($threadId);
                $lastMessage = $messages->data[0]; // El primer mensaje es el más reciente
                
                // Extraer el contenido del mensaje de la IA
                $aiResponse = '';
                foreach ($lastMessage->content as $content) {
                    if ($content->type === 'text') {
                        $aiResponse = $content->text->value;
                        break;
                    }
                }
                
                $this->deleteFile($fileInfo['path']);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'message' => json_decode($aiResponse, true),
                        'run_id' => $runId,
                        'thread_id' => $threadId,
                        'status' => $runStatus->status
                    ],
                    'metadata' => [
                        'file_info' => $fileInfo
                    ],
                ], 200);
            } else {
                $this->deleteFile($fileInfo['path']);
                // Si el run falló o fue cancelado
                return response()->json([
                    'success' => false,
                    'error' => 'Run failed with status: ' . $runStatus->status,
                    'data' => [
                        'run_id' => $runId,
                        'thread_id' => $threadId,
                        'status' => $runStatus->status
                    ],
                    'metadata' => [
                        'file_info' => $fileInfo
                    ],
                ], 500);
            }

        } catch (\Exception $e) {
            $this->deleteFile($fileInfo['path']);
            return response()->json([
                'success' => false,
                'error' => 'Error processing image: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'code' => $e->getCode()
                ],
                'metadata' => [
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

    protected function deleteFile(string $path)
    {
        if (!$path) {
            return '';
        }

        // Validate path format (should be like: uploads/filename.ext)
        if (!preg_match('/^uploads\/[^\/]+$/', $path)) {
            return '';
        }
        
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return '';

    }
}
