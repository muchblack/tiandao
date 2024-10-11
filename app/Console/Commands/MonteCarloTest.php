<?php

namespace App\Console\Commands;

use App\KeyWords\MonteCarlo;
use Illuminate\Console\Command;
use Monolog\Logger;

class MonteCarloTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'montecarlo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $monteCarlo = new MonteCarlo();
        $monteCarlo->replyCommand();
    }
}
