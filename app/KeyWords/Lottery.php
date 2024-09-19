<?php

namespace App\KeyWords;

use App\Models\BigLottery;
use LINE\Clients\MessagingApi\Model\TextMessage;

/*
 * 做大樂透統計，先以前十期最常開&最不常開 開始
 */
class Lottery implements Command
{
    protected BigLottery $lottery;
    private array $oriArray = [] ;
    public function __construct()
    {
        $this->lottery = new BigLottery();
        //原始數據
        for($i = 0; $i <48; $i++) {
            $now = $i+1 ;
            if($now < 10 )
            {
                $this->oriArray['num0'.$now] = 0;
            }
            else
            {
                $this->oriArray['num'.$now] = 0;
            }
        }
    }

    /*
     * 大樂透統計
     */
    public function replyCommand(): array
    {
        //時間換算
        $thisMingGouNian = date('Y') - 1911; //今年
        $threeMingGouNian = (date('Y')-3)-1911; //三年
        $fiveMingGouNian = (date('Y')-5)-1911; //五年
        //前10期
        $lottery10 = $this->_count($this->lottery->orderBy('phase', 'DESC')->limit(10)->get());
        //一年度
        $lottery1Y = $this->_count($this->lottery->whereRaw('SUBSTR(phase,1,3) = ?', [$thisMingGouNian])->get());
        //三年
        $lottery3Y = $this->_count($this->lottery->whereRaw('(SUBSTR(phase,1,3) between ? and ? ) ', [$threeMingGouNian, $thisMingGouNian])->get());
        //五年
        $lottery5Y = $this->_count($this->lottery->whereRaw('(SUBSTR(phase,1,3) between ? and ? ) ', [$fiveMingGouNian, $thisMingGouNian])->get());
        //全部
        $lotteryAll = $this->_count($this->lottery->all());

        $text =  "結果僅供參考，勿過度投注\n";
        $text .= "前十期出現最多的六個號碼：[".implode(',', $this->_refer($lottery10, 'most'))."]\n";
        $text .= "前十期沒出現的號碼：[".implode(',', $this->_refer($lottery10, 'less'))."]\n";
        $text .= "\n";
        $text .= "過去一年出現最多的六個號碼：[".implode(',', $this->_refer($lottery1Y, 'most'))."]\n";
        $text .= "過去一年出現最少的六個號碼：[".implode(',', $this->_refer($lottery1Y, 'less'))."]\n";
        $text .= "\n";
        $text .= "過去三年出現最多的六個號碼：[".implode(',', $this->_refer($lottery3Y, 'most'))."]\n";
        $text .= "過去三年出現最少六個號碼：[".implode(',', $this->_refer($lottery3Y, 'less'))."]\n";
        $text .= "\n";
        $text .= "過去五年出現最多的六個號碼：[".implode(',', $this->_refer($lottery5Y, 'most'))."]\n";
        $text .= "過去五年出現最少六個號碼：[".implode(',', $this->_refer($lottery5Y, 'less'))."]\n";
        $text .= "\n";
        $text .= "歷史出現最多的六個號碼：[".implode(',', $this->_refer($lotteryAll, 'most'))."]\n";
        $text .= "歷史出現最少的六個號碼：[".implode(',', $this->_refer($lotteryAll, 'less'))."]\n";
        $text .= "結果僅供參考，勿過度投注\n";

        return [(new TextMessage(['text'=>$text]))->setType('text')];
    }

    private function _count($data): array
    {
        $norCnt = $this->oriArray;
        foreach ($data as $item) {
            for($i =1 ; $i < 7 ; $i++)
            {
                if(isset($norCnt['num'.$item['num'.$i]])) {
                    $norCnt['num'.$item['num'.$i]] ++;
                }
            }
        }

        return $norCnt;
    }


    private function _refer($data, $type): array
    {
        $data = collect($data);
        $returnNum = [];
        if($type === 'most')
        {
            $mostNor = $data->sortDesc()->splice(0,6);
            foreach($mostNor as $key => $value)
            {
                $returnNum[] = substr($key, -2);
            }
        }
        else
        {
            $lessNor = $data->sort()->splice(0, 6);
            foreach($lessNor as $key => $value)
            {
                $returnNum[] = substr($key, -2);
            }
        }

        return $returnNum;
    }
}

