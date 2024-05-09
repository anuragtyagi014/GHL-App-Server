<?php

namespace App\Console\Commands;

use App\Models\CallRecording;
use Illuminate\Console\Command;

class sendCallRecordingsToGHL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:recordings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Aircall recordings to GHL';

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
        $model = new CallRecording();
        $model->sendRecording();
    }
}
