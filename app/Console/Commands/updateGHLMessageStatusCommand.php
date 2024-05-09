<?php

namespace App\Console\Commands;

use App\Models\GHLMessageStatus;
use Illuminate\Console\Command;

class updateGHLMessageStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GHL Status Update';

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
        $m = new GHLMessageStatus();
        $m->updateGHLMessageStatus();
    }
}
