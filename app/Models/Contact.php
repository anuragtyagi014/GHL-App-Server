<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Contact extends Model
{
    use HasFactory;

    public function handleGHLCreatedContactWebhook($data){

        Log::info('Webhook data received', ['data' => $data]);
        if(!empty($data["id"]) && $data['type'] == 'contactCreatedCustomWebhook'){
            $contactExists = Contact::where("uuid",$data["id"])->exists();
            if($contactExists){
                return "ok";
            }
            $this->createContact($data);
        }
        return "ok";
        
    }

    /**
     * Create Contact
     * @param array $data
     * @param object|null $contact
     * @return null
     */
    public function createContact($data,$contact = NULL){
        if(empty($contact->id)){
            $contact = new Contact();
        }else{
            $contact->updated = true;
        }
        $contact->type = $data["type"];
        $contact->locationId = $data["locationId"];
        $contact->uuid = $data["id"];
        $contact->address1 = !empty($data["address1"]) ? $data["address1"] : null;
        if(!empty($data["agent_email"])){
            $contact->agent_email = $data["agent_email"];
        }
        $contact->city = !empty($data["city"]) ? $data["city"] : null;
        $contact->state = !empty($data["state"]) ? $data["state"] : null;
        $contact->companyName = !empty($data["companyName"]) ? $data["companyName"] : null;
        $contact->country = !empty($data["country"]) ? $data["country"] : null;
        $contact->source = !empty($data["source"]) ? $data["source"] : null;
        $contact->dateAdded = !empty($data["dateAdded"]) ? Carbon::parse($data["dateAdded"]) : null;
        $contact->dateOfBirth = !empty($data["dateOfBirth"]) ? $data["dateOfBirth"] : null;
        $contact->dnd = !empty($data["dnd"]) ? $data["dnd"] : true;
        $contact->email = !empty($data["email"]) ? $data["email"] : "";
        $contact->firstName = !empty($data["firstName"]) ? $data["firstName"] : "";
        $contact->lastName = !empty($data["lastName"]) ? $data["lastName"] : "";
        $contact->name = !empty($data["name"]) ? $data["name"] : $contact->firstName." ".$contact->lastName;
        if(!empty($data["phone"])){
            $contact->phone = $data["phone"];
            $contact->phone = $this->toAustralianNumber($contact);
            $contact->agent_email = $contact->agent_email ? $contact->agent_email : $this->getAgentEmail($contact);
        }
        $contact->postalCode = !empty($data["postalCode"]) ? $data["postalCode"] : "";
        $contact->tags = !empty($data["tags"]) ? json_encode($data["tags"]) : null;
        $contact->website = !empty($data["website"]) ? $data["website"] : "";
        $contact->attachments = !empty($data["attachments"]) ? json_encode($data["attachments"]) : null;
        $contact->assignedTo = !empty($data["assignedTo"]) ? $data["assignedTo"] : "";
        $contact->save();
        return $contact;
    }

    public function createAircallContact($data){
            $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/contacts';
            $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                        ->post($endpoint, $data); 
            return $response->json();
    }

    public function updateAircallContact($id,$data){
            $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/contacts/'.$id;
            $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                        ->post($endpoint, $data); 
            return $response->json();
    }

    public function syncCreatedContacts(){
        $notSynced = Contact::whereNull("aircall_id")->where("phone","<>","")->get();
        foreach($notSynced as $contact){
            $data = [];
            if(empty($contact->phone)){
                continue;
            }
            if(!empty($contact->firstName)){
                $data["first_name"] = $contact->firstName;
            }
            if(!empty($contact->lastName)){
                $data["last_name"] = $contact->lastName;
            }
            if(!empty($contact->uuid)){
                $data["information"] = "ghl_contact_id:".$contact->uuid;
            }
            $data["phone_numbers"][0]["label"] = "Work";
            $pn = str_replace(' ', '', $contact->phone);
            $pn = str_replace('(', '', $pn);
            $pn = str_replace(')', '', $pn);
            if($contact->country = "AU" && substr($pn, 0, 1) =="0"){
                $pn = "+61".substr($pn, 1);
            }
            $data["phone_numbers"][0]["value"] = str_replace(' ', '', $pn);
            if(!empty($contact->email)){
                $data["emails"][0]["label"] = "Office";
                $data["emails"][0]["value"] = $contact->email;
            }
            $response = $this->createAircallContact($data);
            Log::info('Create contact response', ['data' => $response]);
            if(!empty($response['contact'])){
                $contact->aircall_id = $response["contact"]['id'];
                $contact->save();
                
                //if user email is set then add to dialer
                if(!empty($contact->agent_email)){
                    // assign
                    $findAgent=Agent::where("email",$contact->agent_email)->first();
                    if(!empty($findAgent->id)){
                        $dialer_data=[];
                        $dialer_data["phone_numbers"][0]=$pn;
                        $assign_response = $this->addPhoneNumberToAircallDialer($findAgent->aircall_id,$dialer_data);
                        Log::info('Response to add phone number to dialer', ['data' => $assign_response->json()]);
                        if($assign_response->failed()){
                            $assign_response_plus_create = $this->addPhoneNumberPlusCreateAircallDialer($findAgent->aircall_id,$dialer_data);
                            Log::info('Response to create dialer and add phone number to dialer', ['data' => $assign_response_plus_create->json()]);
                            if($assign_response_plus_create->successful()){
                                $contact->agent_aircall_id = $findAgent->aircall_id;
                                $contact->save();
                            }
                        }
                        if($assign_response->successful()){
                            $contact->agent_aircall_id = $findAgent->aircall_id;
                            $contact->save();
                        }
                    }
                }
            }else{
                $contact->aircall_id = "failed";
                $contact->save();
            }
            //then create contact on Sakari
            $sakariData = [
                "id" => $contact->id,
                "email" => $contact->email,
                "firstName" => $contact->firstName,
                "lastName" => $contact->lastName,
                "mobile" => [
                    "country" => "AU",
                    "number" =>$pn
                ],
                "tags" => [
                    [
                        "tag" => "ghl",
                        "visible" => true
                    ]
                ],
                "attributes" => []
            ];
            $sakari = $this->createSakariContact($sakariData);
            if(!empty($sakari['data']) && !empty($sakari['data']['id'])){
                $contact->sakari_id = $sakari['data']['id'];
                $contact->save();
            }else{
                $contact->sakari_id = "failed";
                $contact->save();
            }
        }
    }

    public function syncUpdatedContacts()
    {
        $notSynced = Contact::where("updated", true)->where("phone", "<>", "")->get();
        foreach ($notSynced as $contact) {
            $data = [];
            if (empty($contact->phone)) {
                continue;
            }
            $pn = str_replace([' ', '(', ')'], '', $contact->phone);
            if ($contact->country = "AU" && substr($pn, 0, 1) == "0") {
                $pn = "+61" . substr($pn, 1);
            }
            if ($contact->aircall_id) {
                if (!empty($contact->firstName)) {
                    $data["first_name"] = $contact->firstName;
                }
                if (!empty($contact->lastName)) {
                    $data["last_name"] = $contact->lastName;
                }
                if (!empty($contact->uuid)) {
                    $data["information"] = "ghl_contact_id:" . $contact->uuid;
                }
                $data["phone_numbers"][0]["label"] = "Work";

                $data["phone_numbers"][0]["value"] = str_replace(' ', '', $pn);
                if (!empty($contact->email)) {
                    $data["emails"][0]["label"] = "Office";
                    $data["emails"][0]["value"] = $contact->email;
                }
                $response = $this->updateAircallContact($contact->aircall_id, $data);
                Log::info('Update Aircall contact response', ['data' => $response]);
            }
            if ($contact->sakari_id && $contact->sakari_id != "failed") {
                $sakariData = [
                    "email" => $contact->email,
                    "firstName" => $contact->firstName,
                    "lastName" => $contact->lastName,
                    "mobile" => [
                        "country" => "AU",
                        "number" => $pn
                    ],
                    "tags" => [
                        [
                            "tag" => "ghl",
                            "visible" => true
                        ]
                    ],
                    "attributes" => []
                ];
                $sakari = $this->updateSakariContact($contact->sakari_id, $sakariData);
                Log::info('Update Sakari contact response', ['data' => $sakari]);
            }
        }
    }

    public function getAircallAgents(){
        $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/users';
        $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                    ->get($endpoint); 
        return $response->json();
    }

    public function syncAgents(){
        $agents = $this->getAircallAgents();
        if(empty($agents) || empty($agents["users"])){
            return;
        }
        foreach($agents["users"] as $agent){
            $agentExist = Agent::where("aircall_id",$agent["id"])->first();
            if(empty($agentExist->id)){
                $model = new Agent();
                $model->email = $agent["email"];
                $model->aircall_id = $agent["id"];
                $model->name = $agent["name"];
                $model->save();
            }
        }
    }

    public function addPhoneNumberToAircallDialer($agent_aircall_id,$data){
        $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/users/'.$agent_aircall_id.'/dialer_campaign/phone_numbers';
        $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                    ->post($endpoint, $data); 
        return $response;    
    }

    public function addPhoneNumberPlusCreateAircallDialer($agent_aircall_id,$data){
        $endpoint = config('app.aircall.AIRCALL_API_ENDPOINT').'/users/'.$agent_aircall_id.'/dialer_campaign';
        $response = Http::withBasicAuth(config('app.aircall.AIRCALL_API_ID'), config('app.aircall.AIRCALL_API_TOKEN'))
                    ->post($endpoint, $data); 
        return $response;    
    }

    public function createSakariContact($data){
        $endpoint = config('app.sakari.SAKARI_API_ENDPOINT').'/accounts/'.config('app.sakari.SAKARI_ACCOUNT_ID').'/contacts';
        $api = new Api();
        $response = Http::withToken($api->getSakariToken())
                    ->post($endpoint, $data); 
        return $response->json();
    }

    public function updateSakariContact($id,$data){
        $endpoint = config('app.sakari.SAKARI_API_ENDPOINT').'/accounts/'.config('app.sakari.SAKARI_ACCOUNT_ID').'/contacts/'.$id;
        $api = new Api();
        $response = Http::withToken($api->getSakariToken())
                    ->put($endpoint, $data); 
        return $response->json();
    }

    public function sendSakariSMS($data){
        $endpoint = config('app.sakari.SAKARI_API_ENDPOINT').'/accounts/'.config('app.sakari.SAKARI_ACCOUNT_ID').'/messages';
        $api = new Api();
        $response = Http::withToken($api->getSakariToken())
                    ->post($endpoint, $data); 
        return $response->json();
    }

    public function handleGHLSendSMSWebhook($data){
        Log::info('Send SMS Webhook data received', ['data' => $data]);
        if(!empty($data["tp"]) && $data['type'] == 'sendSMS'){
            $campaign_name = !empty($data["campaign_name"]) ? "[".$data["campaign_name"]."]" : "[Sent From Triage Workflow]";
            $only_start_date = !empty($data["only_start_date"]) ? $data["only_start_date"] : "";
            $only_start_time = !empty($data["only_start_time"]) ? $data["only_start_time"] : "";
            if($only_start_time){
                $dateTime = new \DateTime($only_start_time);
                $only_start_time = $dateTime->format("h:i A"); 
            }
            $contactExists = Contact::where("uuid",$data["id"])->first();
            $template = SmsTemplate::where("uuid",$data["tp"])->first();
            $group_id = '';
            if(!empty($contactExists->agent_email)){
                $agent = Agent::where("email",$contactExists->agent_email)->first();
                if(!empty($agent->id)){
                    $group_id=$agent->group_id;
                }else{
                    $group_id = '642d1940de3bf6dabb6f7823';
                }
            }
            if(!empty($template->template)){
                $phone = !empty($contactExists->phone) ? $contactExists->phone : $data['phone'];
                $phone = str_replace(" ","",$phone);
                $phone = str_replace(")","",$phone);
                $phone = str_replace("(","",$phone);
                if(!empty($contactExists->country) && $contactExists->country = "AU" && substr($phone, 0, 1) =="0"){
                    $phone = "+61".substr($phone, 1);
                }
                $country = !empty($contactExists->country) && $contactExists->country != "1" && $contactExists->country != "0" ? $contactExists->country : "AU";
                $agent_name = !empty($agent->name) ? $agent->name: "Michael";
                $agent_name_explode = explode(" ",$agent_name);
                $name = !empty($agent_name_explode[0]) ? $agent_name_explode[0]: "Michael";
                $fn = !empty($contactExists->firstName) ? $contactExists->firstName: "";
                $ln = !empty($contactExists->lastName) ? $contactExists->lastName: "";
                $usedTemplate = str_replace("{{{ firstName }}}",$name,$template->template);
                $usedTemplate = str_replace("{{{firstName}}}",$name,$usedTemplate);
                $usedTemplate = str_replace("{{{customerFirstName}}}",$fn,$usedTemplate);
                $usedTemplate = str_replace(" {{{customerFirstName}}} ",$fn,$usedTemplate);
                $usedTemplate = str_replace("{{{customerLastName}}}",$ln,$usedTemplate);
                $usedTemplate = str_replace(" {{{customerLastName}}} ",$ln,$usedTemplate);
                $usedTemplate = str_replace("{{{appointment.only_start_date}}}",$only_start_date,$usedTemplate);
                $usedTemplate = str_replace("{{{appointment.only_start_time}}}",$only_start_time,$usedTemplate);
            
                $sakariData = [
                    "contacts" => [
                        [
                            "mobile" => [
                                "number" => $phone,
                                "country" => $country
                            ]
                        ]
                    ],
                    "template" => $usedTemplate
                ];
                if(!empty($group_id)){
                    $sakariData["phoneNumberFilter"]["group"]["id"] = $group_id;
                }
                $sent = $this->sendSakariSMS($sakariData);
                $ghlid = !empty($contactExists->uuid) ? $contactExists->uuid : $data['id'];
                //create the same in GHL
                $ghl_data =[
                    "type" => "SMS",
                    "contactId" => $ghlid,
                    "message" =>  $campaign_name." ".$usedTemplate
                ];

                $ghlsms = $this->sendGHLSMS($ghl_data);
                if(!empty($ghlsms['messageId'])){
                    $gms = new GHLMessageStatus();
                    $gms->messageId = $ghlsms['messageId'];
                    $gms->save();
                }


                if(!empty($ghlid) && !empty($contactExists) && !empty($template)){
                    $smsTemplateLog =new SmsTemplateLog();
                    $smsTemplateLog->contact_id = $contactExists->id;
                    $smsTemplateLog->template_id = $template->id;
                    $smsTemplateLog->log = json_encode($sent);
                    $smsTemplateLog->save();
                }
                return "ok";
            }
        }
    }

    public function sendGHLSMS($data){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/conversations/messages';

        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->asForm()->post($endpoint, $data);

        return $response->json(); 
    }

    public function inboundGHLSMS($data,$returnStatus = false){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/conversations/messages/inbound';
        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->asForm()->post($endpoint, $data);

        if($returnStatus){
            return $response->status();
        }

        return $response->json();         
    }

    public function handleSakariSMS($data){
        if(!empty($data['eventType']) && !empty($data['payload'])){
            $contact_email= (!empty($data['payload']['contact']) && !empty($data['payload']['contact']['email'])) ? $data['payload']['contact']['email']: "";
            $contact_number= (!empty($data['payload']['contact']) && !empty($data['payload']['contact']['mobile'])) ? $data['payload']['contact']['mobile']['number'] :"";
            $message = !empty($data['payload']['message']) ? $data['payload']['message']: "";
            $id = !empty($data['payload']['id']) ? $data['payload']['id']: "";
            $firstName =(!empty($data['payload']['contact']) && !empty($data['payload']['contact']['firstName'])) ? $data['payload']['contact']['firstName']: "";
            $lastName =(!empty($data['payload']['contact']) && !empty($data['payload']['contact']['lastName'])) ? $data['payload']['contact']['lastName']: "";
            $agent_group_id =(!empty($data['payload']['group']) && !empty($data['payload']['group']['id'])) ? $data['payload']['group']['id']: "";
            $agent_name =(!empty($data['payload']['group']) && !empty($data['payload']['group']['name'])) ? $data['payload']['group']['name']: "";
            if(!empty($contact_email)){
                $contact = Contact::where("email",$contact_email)->orderBy("id","desc")->first();
            }
            if(empty($contact)){
                $dt = Contact::where("firstName",$firstName)->where("lastName",$lastName)->orderBy("id","desc")->get();
                foreach($dt as $dt_m){
                    $dt_m_phone = $dt_m->phone;
                    $dt_m_phone = str_replace(' ', '', $dt_m_phone);
                    $dt_m_phone = str_replace('(', '', $dt_m_phone);
                    $dt_m_phone = str_replace(')', '', $dt_m_phone);
                    if(substr($dt_m_phone, 0, 1) == "0"){
                        $dt_m_phone = "+61".substr($dt_m_phone, 1);
                    }
                    if($dt_m_phone == $contact_number){
                        $contact = $dt_m;
                        break;
                    }
                }
            }
            if(empty($contact) && !empty($contact_number)){
                $y = new Contact();
                $data['phone'] = $contact_number;
                $response =$y->getSearchContact($data);
                if(!empty($response['contact']) && $response['contact']["id"]){
                    $response['contact']['type'] = "created_from_sakari";
                    $m = Contact::where("uuid",$response['contact']["id"])->first();
                    if(!$m){
                        $contact = $y->createContact($response['contact']);
                    }
                }
            }
            $message_type = 0;
            $alreadyLogged = Message::where("messageId",$id)->first();
            if(!empty($contact)){
                if($data['eventType'] == 'message-received'){
                    $message_type = 1;
                    $conversationId = $contact->conversationId;
                    if(empty($conversationId)){
                        $convo_data['contactId'] = $contact->uuid;
                        $convo = $this->getSearchConversation($convo_data);
                        if(!empty($convo['conversations']) && !empty($convo['conversations'][0])){
                            $conversationId = $convo['conversations'][0]['id'];
                        }
                    }
    
                    if(empty($conversationId)){
                        $create_convo_data['contactId'] =  $contact->uuid;
                        $convo = $this->createConversation($create_convo_data);
                        if(!empty($convo['conversation'])){
                            $conversationId = $convo['conversation']['id'];
                        }
                    }
                    
                    if($conversationId){
                        $inbound_data = [
                            'type' => 'SMS',
                            'conversationId' => $conversationId,
                            'message' => $message
                        ];
                        $this->inboundGHLSMS($inbound_data);
                    }
                }

                if($data['eventType'] == 'message-sent'){
                    $temple = [
                        'Mate just',
                        'Big fella,',
                        'Hey mate',
                        'Thanks for',
                        'If that',
                        'Our team',
                        'In the',
                        'Is this',
                        'I get',
                        'Guessing you',
                        'I know',
                        "We're sorry",
                        'Just got',
                        'Not convinced',
                        'Hey mate,',
                        'When it',
                        'None of',
                        'No doubt',
                        "I'm guessing",
                        "What was"
                    ];
                    $templateKeyword = "UPA Team"; // The keyword or identifier in your template
                    $message_exploded = explode(" ",$message);
                    $wordsToExclude = 5;
                    $extractedPart = implode(' ', array_slice($message_exploded, $wordsToExclude));
                    if(empty($alreadyLogged) && empty($message_exploded[1]) || (empty($alreadyLogged) && !empty($message_exploded[1]) && !in_array($message_exploded[0]." ".$message_exploded[1],$temple) && $extractedPart != ' Our team will get back to you as soon as we can' && $extractedPart != 'Our team will get back to you as soon as we can' && strpos($message, $templateKeyword) === false)){
                        $ghl_data =[
                            "type" => "SMS",
                            "contactId" =>$contact->uuid,
                            "message" => "[Sent From Sakari] ".$message
                        ];
        
                        $ghlsms = $this->sendGHLSMS($ghl_data);
                        if(!empty($ghlsms['messageId'])){
                            $gms = new GHLMessageStatus();
                            $gms->messageId = $ghlsms['messageId'];
                            $gms->save();
                        }
                    }
                }

                //check for agents
                if(!empty($agent_group_id)){
                    $check_agent = Agent::where('group_id',$agent_group_id)->first();
                    if(empty($check_agent->id) && !empty($agent_name)){
                        $na = Agent::where('name',$agent_name)->first();
                        if(!empty($na->id)){
                            $na->group_id = $agent_group_id;
                            $na->save();
                        }
                    }
                }
            }

            if(empty($alreadyLogged)){
                $message_log = new Message();
                $message_log->messageId = $id;
                $message_log->message_type = $message_type;
                $message_log->message = $message;
                $message_log->contactId = !empty($contact) && !empty($contact->id)? $contact->id : "";
                $message_log->save();
            }

            return "ok";
        }
    }

    public function getSearchConversation($data){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/conversations/search?locationId='.$api->locationId.'&contactId='.$data['contactId'];
        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->get($endpoint); 
        return $response->json();
    }

    public function createConversation($data){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $data["locationId"] = $api->locationId;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/conversations/';
        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->asForm()->post($endpoint, $data);

        return $response->json();         
    }

    public function updateStatus($messageId,$data){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/conversations/messages/'.$messageId.'/status';
        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->asForm()->put($endpoint, $data);

        return $response->json();         
    }

    public function handleAircallHooks($data){
        if(!empty($data['event']) && $data['event'] =="call.ended" && !empty($data['data']) && !empty($data['data']['id']) && !empty($data['data']['recording'])){
            $raw_digits = $data['data']['raw_digits'];
            $timestamp  = $data['timestamp'];
            $call_recording = new CallRecording();
            $call_recording->call_id = $data['data']['id'];
            $call_recording->raw_digits = $raw_digits;
            $call_recording->call_timestamp = $timestamp;
            $call_recording->save();
        }

        return "ok";
    }

    public function conversationProvider($data){
        if(!empty($data['message']) && !empty($data['contactId'])){
            $message = new Message();
            $message->ghl_messageId = $data['messageId'];
            $message->message = $data['message'];
            $message->conversationId = !empty($data['conversationId']) ? $data['conversationId'] : NULL;
            $message->attachments = !empty($data['attachments']) ? json_encode($data['attachments']) : NULL;
            $contactExists = Contact::where("uuid",$data["contactId"])->orderBy("id","DESC")->first();
            if(!empty($contactExists->id)){
                $message->contactId = $contactExists->id;
            }
            $message->message_type = 0;
            $message->phone = $data["phone"];
            $needle1   = '[Sent From Triage Workflow]';
            $needle2   = '[Sent From Sakari]';
  
            if (!str_contains($message->message, $needle1) && !str_contains($message->message, $needle2) && !preg_match("/\[(.*?)\]/", $message->message, $matches)) {
                $message->sent_from = 1;
            }
            $message->save();
        }
        return "ok";
    }

    public function getSearchContact($data){
        $m =new Api();
        $api = $m->getGHLToken();
        $access_token = $api->access_token;
        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/contacts/search/duplicate?locationId='.$api->locationId.'&number='.$data['phone'];
        $response = Http::withToken($access_token)->withHeaders([
            'Version' => "2021-04-15"
        ])->get($endpoint); 
        return $response->json();
    }

    public function handleGHLUpdatedContactWebhook($data){
        // Log::info('GHL Updated Webhook data received', ['data' => $data]);
        if(!empty($data["id"]) && $data['type'] == 'contactUpdatedCustomWebhook'){
            $contact = Contact::where("uuid",$data["id"])->first();
            if(empty($contact->id)){
                return "ok";
            }
            $this->createContact($data,$contact);
        }
        return "ok";
        
    }

    // format the phone and add australian code
    public function toAustralianNumber(Contact $contact){
        $pn = str_replace(' ', '', $contact->phone);
        $pn = str_replace('(', '', $pn);
        $pn = str_replace(')', '', $pn);
        return ($contact->country = "AU" && substr($pn, 0, 1) =="0") ? "+61".substr($pn, 1): $pn;
    }

    public function getAgentEmail(Contact $contact){
        $agent = Agent::where("ghl_id", $contact->assignedTo)->first();
        return $agent ? $agent->email :  "michael@upacoaching.com.au";
    }
}
