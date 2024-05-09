<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("uuid");
            $table->text("template");
            $table->timestamps();
        });

        $templates = [
            [
                "[TP] No Answer 1",
                "no_answer_1",
                "Mate just tried to buzz ya. {{{ firstName }}} here from The Business Warrior, let me know when you've got 10 and I'll call you back."
            ],
            [
                "[TP] No Answer 3",
                "no_answer_3",
                "Big fella, let me know when you have 10. It's {{{ firstName }}} from The Business Warrior here. You requested a call from us the other day to help you put things in motion to scale your business without working longer hours."
            ],
            [
                "[TP] No Answer 5",
                "no_answer_5",
                "Hey mate you still keen to scale your business while working less hours?"
            ]
        ];

        foreach($templates as $template){
            $model = new SmsTemplate();
            $model->name = $template[0];
            $model->uuid = $template[1];
            $model->template = $template[2];
            $model->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_templates');
    }
}
