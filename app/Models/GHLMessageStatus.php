<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


class GHLMessageStatus extends Model
{
    use HasFactory;

    protected $table = 'ghl_message_status';

    public function updateGHLMessageStatus(){
        $statuses = GHLMessageStatus::where("status",0)->whereNotNull("messageId")->get();
        foreach($statuses as $status){
            $expiry_date = $status->created_at;
            $now = Carbon::now();
            $di= $now->diffInSeconds($expiry_date);
            if($di < 300){
                continue;
            }
            $messageId = $status->messageId;
            $m = new Contact();
            $data = [
                "status" => "delivered",
                "error" => [
                  "code"=> "1",
                  "type"=> "saas",
                  "message"=> "There was an error from the provider"
                ]
            ];
            $p = $m->updateStatus($messageId,$data);
            Log::info('GHL Status Update:'.$messageId, ['data' =>$p]);
            $status->status = 1;
            $status->save();
        }
    }
}
