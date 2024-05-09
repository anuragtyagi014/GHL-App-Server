<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSmsTemplateLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sms_template_logs', function (Blueprint $table) {
            $table->integer("message_id")->nullable();
        });
        DB::statement('ALTER TABLE sms_template_logs MODIFY COLUMN contact_id INT(10) DEFAULT NULL');
        DB::statement('ALTER TABLE sms_template_logs MODIFY COLUMN template_id INT(10) DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms_template_logs', function (Blueprint $table) {
            $table->dropColumn("message_id");
        });
    }
}
