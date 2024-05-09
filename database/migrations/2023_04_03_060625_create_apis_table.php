<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->string('refresh_token')->nullable();
            $table->string('access_token')->nullable();
            $table->string('locationId')->nullable();
            $table->string('hashedCompanyId')->nullable();
            $table->string('userType')->nullable();
            $table->text('scope')->nullable();
            $table->integer("expires_in")->nullable();
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
        Schema::dropIfExists('apis');
    }
}
