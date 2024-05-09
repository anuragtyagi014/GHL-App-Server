<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $table = 'sms_templates';


    public function getSMSTemplates(){
        $endpoint = config('app.sakari.SAKARI_API_ENDPOINT').'/accounts/'.config('app.sakari.SAKARI_ACCOUNT_ID').'/templates?limit=50';
        $api = new Api();
        $response = Http::withToken($api->getSakariToken())
                    ->get($endpoint); 
        return $response->json();
    }

    public function syncSMSTemplates(){
        $response = $this->getSMSTemplates();
        if(!empty($response) && !empty($response['success']) && !empty($response['data'])){
            $data = $response['data'];
            foreach($data as $datum){
                $template = SmsTemplate::where('uuid',$datum['name'])->first();
                if(empty($template->id)){
                    $model = new SmsTemplate();
                    $model->name = $datum['name'];
                    $model->uuid = $datum['name'];
                    $model->template = $datum['template'];
                    $model->save();
                }else{
                    $template->template = $datum['template'];
                    $template->save();
                }
            }

        }
        return $response;
    }
}
