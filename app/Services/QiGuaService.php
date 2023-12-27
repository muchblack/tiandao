<?php
namespace App\Services;

use Illuminate\Support\Facades\File;
class QiGuaService{

    private  $_guaName = [
        '+++' => 0, //天
        '++-' => 1, //澤
        '+-+' => 2, //火
        '+--' => 3, //雷
        '-++' => 4, //風
        '-+-' => 5, //水
        '--+' => 6, //山
        '---' => 7, //地
    ];
    public function qiGua()
    {
        $oriBaGua = [];
        $chBaGua = [];
        $yiJing = File::json(base_path('yiJing/yiJing.json'));

        for ($i = 0; $i < 6; $i++) {
            $temp = $this->_getOne();
            switch ($temp) {
                case 13:
                    $oriBaGua[] = '+';
                    $chBaGua[] = '-';
                    break;
                case 17:
                    $oriBaGua[] = '-';
                    $chBaGua[] = '-';
                    break;
                case 21:
                    $oriBaGua[] = '+';
                    $chBaGua[] = '+';
                    break;
                case 25:
                    $oriBaGua[] = '-';
                    $chBaGua[] = '+';
                    break;
            }
        }

        //本卦上下卦
        $oriUpGua = $oriBaGua[3] . $oriBaGua[4] . $oriBaGua[5];
        $oriDownGua = $oriBaGua[0] . $oriBaGua[1] . $oriBaGua[2];
        //變卦上下卦
        $chUpGua = $chBaGua[3] . $chBaGua[4] . $chBaGua[5];
        $chDownGua = $chBaGua[0] . $chBaGua[1] . $chBaGua[2];

        //得出索引
        $oriUp = $this->_guaName[$oriUpGua];
        $oriDown = $this->_guaName[$oriDownGua];
        $chUp = $this->_guaName[$chUpGua];
        $chDown = $this->_guaName[$chDownGua];

        if (($oriUp == $chUp) && ($oriDown == $chDown))
        {
            return ['oriGua' => $yiJing[$oriUp][$oriDown]];
        }
        else
        {
            return [
                'oriGua' => $yiJing[$oriUp][$oriDown],
                'chGua' => $yiJing[$chUp][$chDown]
            ];
        }
    }


    private function _getOne()
    {
        $daYan = 49;
        $all = [] ;
        for ($i = 0; $i < 3; $i++)
        {
            $storage = [] ;
            //step1 先分堆
            $seed = mt_rand(5,8);
            $right = mt_rand((24-$seed), (25+$seed));
            $left = $daYan-$right;

            //step2 取數
            //step2.1 右邊取一隻
            $right = $right-1 ;
            $storage[] = 1 ;

            //step2.2 左邊取餘數
            if(($left % 4) == 0 )
            {
                //整除算4
                $storage[] = 4;
            }
            else
            {
                $storage[] = ($left % 4);
            }

            //step2.3 右邊取餘數
            if($right % 4 == 0 )
            {
                $storage[] = 4;
            }
            else
            {
                $storage[] = ($right % 4);
            }
            $all[] = array_sum($storage);
            $daYan = $daYan - array_sum($storage);
        }
        return array_sum($all);
    }
}
