<?php

namespace App\Http\Controllers;

use App\Events\ReplyTextMessageCreated;
use App\Models\MessageHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    function webhook(Request $request) {
        $messagType = $request->input('events.0.message.type');
        if($messagType == 'text') {
            $replyToken = $request->input('events.0.replyToken');
            $text = $request->input('events.0.message.text');
            $userid = $request->input('events.0.source.userId');

            $this->deleteOldMessageHistories($userid);
            $messages = $this->createMessagesToChatGpt($userid, $text);
            $respMessage = $this->callChatGptApi($messages);
            $this->saveMessageHistory($userid, $text, $respMessage);

            ReplyTextMessageCreated::dispatch($replyToken, $respMessage);
        }
        return response()->json([]);
    }

    function deleteOldMessageHistories($userid) {
        $lastMessageTime = MessageHistory::where('userid', $userid)->max('created_at');
        if(!is_null($lastMessageTime) && $lastMessageTime < Carbon::now()->addMinutes(-5)) {
            MessageHistory::where('userid', $userid)->delete();
        }
    }

    function callChatGptApi($messages) {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('app.openai_api_key'),
        ])->post(config('app.openai_endpoint_url_chat'), [
            'model' => config('app.openai_model_chat'),
            'messages' => $messages,
        ]);
        return $response['choices'][0]['message']['content'];;
    }

    function createMessagesToChatGpt($userid, $text) {
        $messages = [];
        $messages[] = ["role" => "system", "content" =>  config('app.openai_chat_system_message')];
        $messageHistories = MessageHistory::where('userid', $userid)->orderBy('id')->get();
        if(!is_null($messageHistories)) {
            foreach($messageHistories as $messageHistory) {
                $messages[] = ["role" => "user", "content" => $messageHistory->message];
                $messages[] = ["role" => "assistant", "content" => $messageHistory->response_message];
            }
        }
        $messages[] = ["role" => "user", "content" => $text];
        return $messages;
    }

    function saveMessageHistory($userid, $text, $respMessage) {
        MessageHistory::create([
            'userid' => $userid,
            'message' => $text,
            'response_message' => $respMessage,
        ]);
    }
}
