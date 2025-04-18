<?php

namespace App\Console\Commands;

use App\Models\BigLottery;
use Illuminate\Console\Command;
use App\KeyWords\Lottery;

class LotteryTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lottery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試lottery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $lotteryService = new Lottery();
        $lotteryService->replyCommand();
    }
}
