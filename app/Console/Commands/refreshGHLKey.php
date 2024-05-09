<?php

namespace App\Console\Commands;

use App\Models\Api;
use Illuminate\Console\Command;

class refreshGHLKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:ghlkey';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh GHL Key';

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
        $model = new Api();
        $api = $model->getGHLToken();
        dd($api);
    }
}
