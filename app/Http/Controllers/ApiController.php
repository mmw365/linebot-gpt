<?php

namespace App\Http\Controllers;

use App\Events\ReplyTextMessageCreated;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    function webhook(Request $request) {
        $messagType = $request->input('events.0.message.type');
        if($messagType == 'text') {
            $replyToken = $request->input('events.0.replyToken');
            $text = $request->input('events.0.message.text');
            ReplyTextMessageCreated::dispatch($replyToken, $text);
        }
        return response()->json([]);
    }
}
