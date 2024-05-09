<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCallRecordings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->longText('recording')->nullable();
            $table->string('call_id');
            $table->string('call_timestamp');
            $table->string('raw_digits');
            $table->integer('status')->default(0);
            $table->string('ghl_contact_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_recordings');
    }
}
