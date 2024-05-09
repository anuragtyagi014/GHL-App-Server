<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("type");
            $table->string("locationId");
            $table->string("uuid");
            $table->string("address1")->nullable();
            $table->string("city")->nullable();
            $table->string("state")->nullable();
            $table->string("companyName")->nullable();
            $table->string("country")->nullable();
            $table->string("source")->nullable();
            $table->timestamp("dateAdded")->nullable();
            $table->string("dateOfBirth")->nullable();
            $table->boolean("dnd")->default(true);
            $table->string("email")->nullable();
            $table->string("name");
            $table->string("firstName");
            $table->string("lastName")->nullable();
            $table->string("phone")->nullable();
            $table->string("postalCode")->nullable();
            $table->json("tags")->nullable();
            $table->string("website")->nullable();
            $table->json("attachments")->nullable();
            $table->string("assignedTo")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
