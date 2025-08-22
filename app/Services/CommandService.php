<?php

namespace App\Services;

use App\KeyWords\Command;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Webhook\Model\MessageEvent;

class CommandService
{
    public function executeCommand(MessageEvent $event, MessagingApiApi $bot, Command $command): void
    {
        $replyMessages = $command->handle($event);
        
        $this->sendReply($event, $bot, $replyMessages);
    }

    private function sendReply(MessageEvent $event, MessagingApiApi $bot, array $messages): void
    {
        $bot->replyMessage(new ReplyMessageRequest([
            'replyToken' => $event->getReplyToken(),
            'messages' => $messages
        ]));
    }
}
