<?php

use App\Models\Agent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAgentsTableAddGroupId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string("group_id")->nullable();
        });
        $ricki = Agent::where("email","support@upacoaching.com.au")->first();
        if($ricki){
            $ricki->group_id = "642d191d16729626795d21e3";
            $ricki->save();
        }

        $mike = Agent::where("email","michael@upacoaching.com.au")->first();
        if($mike){
            $mike->group_id = "642d1940de3bf6dabb6f7823";
            $mike->save();
        }
        $sam = Agent::where("email","sam@upacoaching.com.au")->first();
        if($sam){
            $sam->group_id = "642d192d3ce3d821a9b25567";
            $sam->save();            
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn("group_id");
        });
    }
}
