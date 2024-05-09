<?php

use App\Models\Agent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAgentsTableAddColumnGhlId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string("ghl_id")->nullable();
        });

        Agent::where('email','michael@upacoaching.com.au')->update(['ghl_id' => '8zT8ify2MofYJFIUO2yf']);
        Agent::where('email','adam@upacoaching.com.au')->update(['ghl_id' => 'sWFBdNf3dDWbm0s1JfJf']);
        Agent::where('email','sam@upacoaching.com.au')->update(['ghl_id' => 'rs27bGwHP3JESjVtbioD']);
        Agent::where('email','aaron@upacoaching.com.au')->update(['ghl_id' => 'a1Dhy9IUN87WLCyaEgOQ']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn("ghl_id");
        });
    }
}
