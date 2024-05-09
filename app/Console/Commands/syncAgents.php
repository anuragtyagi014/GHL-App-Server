<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class syncAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:agents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all agents in Aircall';

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
       $agent = new Contact();
       $agent->syncAgents();
    }
}
