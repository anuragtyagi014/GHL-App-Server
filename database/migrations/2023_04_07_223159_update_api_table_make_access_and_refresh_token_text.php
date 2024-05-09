<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateApiTableMakeAccessAndRefreshTokenText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE apis MODIFY COLUMN access_token TEXT DEFAULT NULL');
        DB::statement('ALTER TABLE apis MODIFY COLUMN refresh_token TEXT DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apis', function (Blueprint $table) {
            //
        });
    }
}
