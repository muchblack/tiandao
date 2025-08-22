<?php
namespace App\KeyWords;

use Illuminate\Support\Facades\File;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Webhook\Model\MessageEvent;

class ShengSheQian implements Command
{
    public function canHandle(string $message): bool
    {
        return in_array($message, ['抽神社籤', '神社籤', '求籤', '占卜籤']);
    }

    public function handle(MessageEvent $event): array
    {
        return $this->replyCommand();
    }

    public function getDescription(): string
    {
        return '神社籤詩占卜 - 隨機抽取神社籤詩提供指導建議';
    }

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
