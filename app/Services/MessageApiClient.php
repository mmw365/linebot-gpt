<?php
 
namespace App\Services;

use Illuminate\Support\Facades\Http;

class MessageApiClient
{
    function sendReplyTextMessage($replyToken, $text) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('app.channel_access_token'),
        ])->post(config('app.line_endpoint_url_reply'), [
            'replyToken' => $replyToken,
            'messages' => [[
                'type' => 'text',
                'text' => $text
            ]],
        ]);
    }
}