<?php

namespace App\Services;

use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;

class CommandService
{


    private mixed $service;
    private mixed $bot;
    private mixed $event;

    public function __construct($event, $bot, $service)
    {
        $this->service = $service;
        $this->bot = $bot;
        $this->event = $event;
    }

    public function reply(): void
    {
        $replyText = $this->service->replyCommand();
        $this->bot->replyMessage( new ReplyMessageRequest([
            'replyToken' => $this->event->getReplyToken(),
            'messages' => $replyText
        ]));
    }
}
