<?php

namespace App\Services;

use App\InterFaces\Command;
use LINE\Clients\MessagingApi\Model\TextMessage;

class Error implements Command
{
    public function replyCommand(): array
    {
        // TODO: Implement replyCommand() method.
        return [(new TextMessage(['text'=>'我不清楚你的問題，可用指令有： 起卦，抽神社籤和大樂透號碼。']))->setType('text')];
    }
}
