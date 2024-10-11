<?php
namespace App\Services;

use App\KeyWords\Error;
use App\KeyWords\Lottery;
use App\KeyWords\MonteCarlo;
use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;

class LineService
{
    private MessagingApiApi $_bot;

    public function __construct()
    {
        $channelToken = config('line.channel_access_token');
        $config = new Configuration();
        $config->setAccessToken($channelToken);
        $this->_bot = new MessagingApiApi(new \GuzzleHttp\Client(), $config);
    }

    public function webhook($request)
    {
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        $parsedEvents = EventRequestParser::parseEventRequest($request->getContent(), config('line.channel_secret'), $signature);
        foreach($parsedEvents->getEvents() as $event)
        {
            if(!($event instanceof  MessageEvent))
            {
                continue;
            }

            $message = $event->getMessage();
            if(!($message instanceof TextMessageContent))
            {
                continue;
            }

            $command = match ($message->getText()) {
                '起卦' => new CommandService($event, $this->_bot, new QiGua()),
                '抽神社籤' => new CommandService($event, $this->_bot, new ShengSheQian()),
                '大樂透號碼' => new CommandService($event, $this->_bot, new Lottery()),
                '蒙地卡羅預測' => new CommandService($event, $this->_bot, new MonteCarlo()),
                default => new CommandService($event, $this->_bot, new Error()),
            };

            $command->reply();
        }

        return response('ok');
    }
}
