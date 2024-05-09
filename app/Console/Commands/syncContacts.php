<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class syncContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:contacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to sync GHL contacts to Aircall';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sync = new Contact();
        $sync->syncCreatedContacts();
    }
}
