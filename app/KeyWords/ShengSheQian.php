<?php
namespace App\KeyWords;

use Illuminate\Support\Facades\File;
use LINE\Clients\MessagingApi\Model\TextMessage;

class ShengSheQian implements Command
{
    public function replyCommand(): array
    {
        $qianShi = File::json(base_path('yiJing/Divination.json'));

        $getIndex = mt_rand(0,99);
        $rightQian = $qianShi[$getIndex];

        $txt = '第 '.$rightQian['index'].' 籤  '. $rightQian['lucky']."\n";
        $txt.= "[籤詩]\n".$rightQian['content']."\n";
        $txt.= "[解釋]\n".$rightQian['explain']."\n";
        $txt.= "[所求]\n".$rightQian['result']."\n";

        return [(new TextMessage(['text'=>$txt]))->setType('text')];

    }
}
