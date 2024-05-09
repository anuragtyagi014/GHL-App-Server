<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class syncUpdatedContacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:updatedcontacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync updated contacts';

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
        $sync->syncUpdatedContacts();
    }
}
