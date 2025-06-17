<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAi\OpenaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{

    public function index(Request $request)
    {
        // $message = $request->message;
        $chat = $request->chat;
        $message = $request->request->get('message', '');
        $openia = new OpenaiService();

        return response()->json([
            'message' => $openia->process($message, $chat),
        ], 200);
    }
}
