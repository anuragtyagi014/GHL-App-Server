<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CorrectTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SmsTemplate::where('uuid','no_answer_1')->update(['uuid' => 'tp_no_answer_1','name' => 'tp_no_answer_1']);
        SmsTemplate::where('uuid','no_answer_3')->update(['uuid' => 'tp_no_answer_3','name' => 'tp_no_answer_3']);
        SmsTemplate::where('uuid','no_answer_5')->update(['uuid' => 'tp_no_answer_5','name' => 'tp_no_answer_5']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            //
        });
    }
}
