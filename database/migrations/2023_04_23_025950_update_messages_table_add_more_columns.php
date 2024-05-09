<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateMessagesTableAddMoreColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->integer("sent_from")->default(0);
            $table->string("conversationId")->nullable();
            $table->longText("attachments")->nullable();
            $table->integer("sent")->default(0);
            $table->string("ghl_messageId")->nullable();
            $table->string("phone")->nullable();
        });
        DB::statement('ALTER TABLE messages MODIFY COLUMN messageID VARCHAR(40) DEFAULT NULL');
        DB::statement('ALTER TABLE messages MODIFY COLUMN message_type INT(10) DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn("sent_from");
            $table->dropColumn("conversationId");
            $table->dropColumn("attachments");
            $table->dropColumn("sent");
            $table->dropColumn("ghl_messageId");
            $table->dropColumn("phone");
        });
    }
}
