<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public function sendMessages(){
        $messages = Message::where("sent_from",1)->where("sent",0)->get();
        foreach($messages as $message){
            $contact = Contact::find($message->contactId);
            $phone = !empty($contact->phone) ? $contact->phone : $message->phone;
            $group_id = '642d1940de3bf6dabb6f7823'; // By default send from Mike's number;
            $phone = str_replace(" ","",$phone);
            $phone = str_replace(")","",$phone);
            $phone = str_replace("(","",$phone);
            if(!empty($contact->id)){
                if($contact->country = "AU" && substr($phone, 0, 1) =="0"){
                    $phone = "+61".substr($phone, 1);
                }
                if($contact->agent_email){
                    $agent = Agent::where("email",$contact->agent_email)->first();
                    if(!empty($agent->id)){
                        $group_id = $agent->group_id;
                    }
                }
            }
            $sakariData = [
                "contacts" => [
                    [
                        "mobile" => [
                            "number" => $phone,
                            "country" => "AU"
                        ]
                    ]
                ],
                "template" => $message->message
            ];
            if(!empty($group_id)){
                $sakariData["phoneNumberFilter"]["group"]["id"] = $group_id;
            }
            if($message->attachments){
                $attachments = json_decode($message->attachments);
                foreach($attachments as $attachment){
                    $sakariData["media"][] = [
                        "url"=> $attachment
                    ];
                }
            }
            $ct = new Contact();
            $sent = $ct->sendSakariSMS($sakariData);
            if(!empty($sent['success']) && !empty($sent['data']) && !empty($sent['data']['messages'])){
                $message->messageId = $sent['data']['messages'][0]['id'];
                $message->save();
                //add to queue to set to delivered
                $gms = new GHLMessageStatus();
                $gms->messageId = $message->ghl_messageId;
                $gms->save();
            }
            $message->sent = 1;
            $message->save();
            $smsTemplateLog =new SmsTemplateLog();
            $smsTemplateLog->contact_id = !empty($contact->id) ? $contact->id : NULL;
            $smsTemplateLog->message_id = $message->id;
            $smsTemplateLog->log = json_encode($sent);
            $smsTemplateLog->save();
        }
    }
}
