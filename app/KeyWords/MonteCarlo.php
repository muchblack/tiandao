<?php

namespace App\KeyWords;

use App\Models\BigLottery;
use LINE\Clients\MessagingApi\Model\TextMessage;

class MonteCarlo implements Command
{
    public function __construct()
    {
        $this->lottery = new BigLottery();
    }
    public function replyCommand()
    {
        // TODO: Implement replyCommand() method.

        $simulations = 1000000;
        $numbersToChoose = 6;

        $simulationResults = $this->monteCarloSimulation($simulations, $numbersToChoose);

        $replyText = '';
        // 輸出結果
        echo "彩票號碼預測結果 (按出現頻率排序):\n";
        $count = 0;
        foreach ($simulationResults as $number => $frequency) {
            $percentage = ($frequency / ($simulations * $numbersToChoose)) * 100;
            echo sprintf("%2d: %7d 次 (%.2f%%)\n", $number, $frequency, $percentage);
            $replyText .= sprintf("%2d: %7d 次 (%.2f%%)\n", $number, $frequency, $percentage);
            $count++;
            if ($count == 10) break;  // 只顯示前10個結果
        }

        // 預測的彩票號碼
        echo "\n預測的彩票號碼: ";
        echo implode(", ", array_slice(array_keys($simulationResults), 0, $numbersToChoose));
        $replyText .= "\n預測的彩票號碼: ";
        $replyText .= implode(", ", array_slice(array_keys($simulationResults), 0, $numbersToChoose));

        return [(new TextMessage(['text'=>$replyText]))->setType('text')];

    }

    private function monteCarloSimulation($simulations, $numbersToChoose) {
        $frequencies = $this->calculateFrequencies();
        $results = array_fill(1, 49, 0);
        for ($i = 0; $i < $simulations; $i++) {
            $draw = [];
            for ($j = 0; $j < $numbersToChoose; $j++) {
                $number = $this->weightedRandomChoice($frequencies);
                $draw[] = $number;
                $results[$number]++;
            }
        }
        arsort($results);
        return $results;
    }

    private function weightedRandomChoice($frequencies) {
        $total = array_sum($frequencies);
        $rand = mt_rand(1, $total);
        foreach ($frequencies as $number => $frequency) {
            $rand -= $frequency;
            if ($rand <= 0) {
                return $number;
            }
        }
    }

    private function calculateFrequencies() {
        $lotteryData = $this->lottery->all()->toArray();
        foreach($lotteryData as $lottery) {
            $temp = [];
            for($i = 1; $i < 7; $i++) {
                $temp[] = intval($lottery['num'.$i]);
            }
            $historicalData[] = $temp;
        }
        $frequencies = array_fill(1, 49, 0);
        foreach ($historicalData as $draw) {
            foreach ($draw as $number) {
                $frequencies[$number]++;
            }
        }
        return $frequencies;
    }
}
