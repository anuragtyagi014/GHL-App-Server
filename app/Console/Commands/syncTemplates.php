<?php

namespace App\Console\Commands;

use App\Models\SmsTemplate;
use Illuminate\Console\Command;

class syncTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SMS templates from Sakari';

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
        $sync = new SmsTemplate();
        $p= $sync->syncSMSTemplates();
    }
}
