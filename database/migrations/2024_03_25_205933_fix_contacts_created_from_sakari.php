<?php

use App\Models\Agent;
use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixContactsCreatedFromSakari extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $createdFromSakariContacts = Contact::where("type", "created_from_sakari")->whereNull("agent_email")->get();
        $contactModel = new Contact();
        foreach ($createdFromSakariContacts as $contact) {
            $contact->agent_email = $contactModel->getAgentEmail($contact);
            $contact->save();
            $pn = $contactModel->toAustralianNumber($contact);
            $sakariData = [
                "id" => $contact->id,
                "email" => $contact->email,
                "firstName" => $contact->firstName,
                "lastName" => $contact->lastName,
                "mobile" => [
                    "country" => "AU",
                    "number" => $pn
                ],
                "tags" => [
                    [
                        "tag" => "ghl",
                        "visible" => true
                    ],
                    [
                        "tag" => "sakari_bug",
                        "visible" => true
                    ]
                ],
                "attributes" => []
            ];
            $sakari = $contactModel->createSakariContact($sakariData);
            $contact->sakari_id = (!empty($sakari['data']) && !empty($sakari['data']['id'])) ? $sakari['data']['id'] : "failed";
            $contact->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sakari', function (Blueprint $table) {
            //
        });
    }
}
