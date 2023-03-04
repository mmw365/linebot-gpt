<?php

namespace App\Http\Controllers;

use App\Events\ReplyTextMessageCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    function webhook(Request $request) {
        $messagType = $request->input('events.0.message.type');
        if($messagType == 'text') {
            $replyToken = $request->input('events.0.replyToken');
            $text = $request->input('events.0.message.text');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('app.openai_api_key'),
            ])->post(config('app.openai_endpoint_url_chat'), [
                'model' => config('app.openai_model_chat'),
                'messages' => [
                    ["role" => "system", "content" => "あなたはいつも50文字以内で答えてくれます。"],
                    ["role" => "user", "content" => $text],
                ],
            ]);
            ReplyTextMessageCreated::dispatch($replyToken, $response['choices'][0]['message']['content']);
        }
        return response()->json([]);
    }
}
