<?php
namespace app\Services;

class QiGuaService{

    public function qiGua()
    {
        $baGua = [];
        for($i=0; $i<6; $i++)
        {
            $temp = $this->_getOne();
            switch($temp)
            {
                case 13:
                    $baGua[] = '+*';
                    break;
                case 17:
                    $baGua[] = '-';
                    break;
                case 21:
                    $baGua[] = '+';
                    break;
                case 25:
                    $baGua[] = '-*';
                    break;
            }
        }
        dump($baGua);

        return $baGua;
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
