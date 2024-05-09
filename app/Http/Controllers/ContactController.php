<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class ContactController extends Controller
{
    public function created(Request $request){
        $model = new Contact();
        $response = $model->handleGHLCreatedContactWebhook($request->all());
        return response()->json(['data' => $response], 200);
    }

    public function sakari(Request $request){
        Log::info('Sakari Webhook data received', ['data' => $request->all()]);
        $model = new Contact();
        $response = $model->handleSakariSMS($request->all());
        return response()->json(['data' => $response], 200);
    }

    public function sendsms(Request $request){
        $model = new Contact();
        $response = $model->handleGHLSendSMSWebhook($request->all());
        return response()->json(['data' => $response], 200);
    }

    public function aircall(Request $request){
        Log::info('Aircall Webhook data received', ['data' => $request->all()]);
        $model = new Contact();
        $response = $model->handleAircallHooks($request->all());
        return response()->json(['data' => $response], 200);
    }

    public function provider(Request $request){
        Log::info('SMS to be sent by provider', ['data' => $request->all()]);
        $model = new Contact();
        $response = $model->conversationProvider($request->all());
        return response()->json(['data' => $response], 200);
    }

    public function updated(Request $request){
        $model = new Contact();
        $response = $model->handleGHLUpdatedContactWebhook($request->all());
        return response()->json(['data' => $response], 200);
    }
}
