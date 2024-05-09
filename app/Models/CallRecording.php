<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class CallRecording extends Model
{
    use HasFactory;

    protected $table = 'call_recordings';

    public function sendRecording(){
        $call_recordings = CallRecording::where("status",0)->get();
        foreach($call_recordings as $call_recording){
            if(empty($call_recording->recording)){
                $call = $this->retrieveCall($call_recording->call_id);
                if(!empty($call['call']) && !empty($call['call']['asset'])){
                    $call_recording->recording = $call['call']['asset'];
                    $call_recording->save();
                } 
            }
            $raw_digits = $call_recording->raw_digits;
            $raw_digits_minus_code = str_replace("+61 ","",$raw_digits);
            $contact = Contact::where('phone','like','%'.$raw_digits_minus_code)->OrWhere('phone',$raw_digits)->first();
            if(!$contact){
                $y = new Contact();
                $data['phone'] = $raw_digits;
                $response =$y->getSearchContact($data);
                if(!empty($response['contact'])){
                    $response['contact']['type'] = "created_from_aircall";
                    $m = Contact::where("uuid",$response['contact']["id"])->first();
                    if(!$m){
                        $contact = $y->createContact($response['contact']);
                    }
                }
            }
            if($contact && !empty($call_recording->recording)){
                $convo_data['contactId'] = $contact->uuid;
                $cnt = new Contact();
                $convo = $cnt->getSearchConversation($convo_data);
                $conversationId = "";
                if(!empty($convo['conversations']) && !empty($convo['conversations'][0])){
                    $conversationId = $convo['conversations'][0]['id'];
                }

                if(empty($conversationId)){
                    $create_convo_data['contactId'] =  $contact->uuid;
                    $convo = $cnt->createConversation($create_convo_data);
                    if(!empty($convo['conversation'])){
                        $conversationId = $convo['conversation']['id'];
                    }
                }
                if(!empty($conversationId)){
                    $inbound_data = [
                        'type' => 'SMS',
                        'conversationId' => $conversationId,
                        'message' => "Click download file to listen to the recording in Aircall",
                        "attachments" => [$call_recording->recording]
                    ];
                    $sent = $cnt->inboundGHLSMS($inbound_data,true);
                    if($sent == 200 || $sent == 201){
                        $call_recording->ghl_contact_id = $contact->uuid;
                        $call_recording->status = 1;
                        $call_recording->save();
                    }else{
                        if($call_recording->retries > 2){
                            $call_recording->status = 1;
                            $call_recording->save();
                        }else{
                            $call_recording->retries = $call_recording->retries + 1;
                            $call_recording->save();
                        }
                    }
                }else{
                    if($call_recording->retries > 2){
                        $call_recording->status = 1;
                        $call_recording->save();
                    }else{
                        $call_recording->retries = $call_recording->retries + 1;
                        $call_recording->save();
                    }
                }
            }else{
                $call_recording->status = 1;
                $call_recording->save();
            }

        }
    }

    public function retrieveCall($call_id){
        $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/calls/'.$call_id;
        $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                    ->get($endpoint); 
        return $response->json();
    }
}
