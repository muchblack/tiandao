<?php

namespace App\KeyWords;

use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Webhook\Model\MessageEvent;

class Error implements Command
{
    public function canHandle(string $message): bool
    {
        // Error 類別作為預設處理器，接受所有訊息
        return true;
    }

    public function handle(MessageEvent $event): array
    {
        return $this->replyCommand();
    }

    public function getDescription(): string
    {
        return '錯誤處理器 - 處理無法識別的指令並提供幫助訊息';
    }

    public function replyCommand(): array
    {
        $helpText = "我不清楚你的問題，可用指令有：\n";
        $helpText .= "• 起卦 - 易經占卜\n";
        $helpText .= "• 抽神社籤 - 神社籤詩\n";
        $helpText .= "• 大樂透號碼 - 彩票分析\n";
        $helpText .= "• 蒙地卡羅預測 - 彩票預測\n";
        $helpText .= "• 小六壬 [三個數字] - 小六壬六宮占卜（省略數字依月/日/時起卦；報數範例：小六壬 4 3 5，每數 1–100）\n";
        $helpText .= "• 每日塔羅 - 塔羅占卜（單張）\n";
        $helpText .= "• 塔羅三牌陣 - 塔羅占卜（過去/現在/未來）";
        
        return [(new TextMessage(['text' => $helpText]))->setType('text')];
    }
}
