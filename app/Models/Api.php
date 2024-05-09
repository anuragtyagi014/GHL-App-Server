<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;


class Api extends Model
{
    use HasFactory;

    public function getGHLToken(){
        $api = Api::where("userType","Location")->first();
        if(!empty($api->expires_in)){
            $expiry_date = $api->updated_at->addSeconds($api->expires_in - 3600); // refresh it an hour earlier just incase
            if($expiry_date->gt(Carbon::now())){
                return $api;
            }
        }else{
            $api = new Api();
        }

        $endpoint = config('app.ghl.GHL_API_ENDPOINT').'/oauth/token';
        $response = Http::asForm()->post($endpoint, [
            'client_id' => config('app.ghl.GHL_CLIENT_ID'),
            'client_secret' => config('app.ghl.GHL_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
            'refresh_token' => !empty($api->refresh_token) ? $api->refresh_token : config('app.ghl.GHL_REFRESH_TOKEN'),
        ]);
        $data = $response->json();
        if(!empty($data['access_token']) && !empty($data['refresh_token'])){
            $api->access_token = $data['access_token'] ;
            $api->expires_in = !empty($data['expires_in']) ? $data['expires_in'] : 86400;
            $api->refresh_token = $data['refresh_token'];
            $api->scope = !empty($data['scope']) ? $data['scope']: null;
            $api->userType = !empty($data['userType']) ? $data['userType'] : null;
            $api->locationId = !empty($data['locationId']) ? $data['locationId']: "NNvz9EPBf0XxSuDS2eNR";
            $api->hashedCompanyId = !empty($data['hashedCompanyId']) ? $data['hashedCompanyId'] : null;
            $api->save();

            return $api;
        }
    }

    public function getSakariToken(){
        $api = Api::where("userType","Sakari")->first();
        if(!empty($api->expires_in)){
            $expiry_date = $api->updated_at->addSeconds($api->expires_in - 600); // refresh it after 50 min just incase
            if($expiry_date->gt(Carbon::now())){
                return $api->access_token;
            }
        }else{
            $api = new Api();
        }
        $endpoint = 'https://api.sakari.io/oauth2/token';
        $response = Http::asForm()->post($endpoint, [
            'client_id' => config('app.sakari.SAKARI_CLIENT_ID'),
            'client_secret' => config('app.sakari.SAKARI_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);

        $data = $response->json();
        if($data){
            $api->access_token = $data['access_token'];
            $api->expires_in = 3600;
            $api->refresh_token = $data['access_token'];
            $api->userType = "Sakari";
            $api->save();
            return $api->access_token;
        }
    }
}
