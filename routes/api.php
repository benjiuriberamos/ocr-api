<?php

use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\OcrChatbotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function() {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/chatbot', [ChatbotController::class, 'index']);
    Route::post('/ocr/chatbot', [OcrChatbotController::class, 'index']);

});

Route::post('login', [LoginController::class, 'login']);
