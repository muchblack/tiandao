<?php

namespace App\Services;

use App\Factories\LineClientFactory;
use Illuminate\Support\Facades\Log;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;

class EventProcessor
{
    private CommandRegistry $commandRegistry;
    private CommandService $commandService;

    public function __construct(
        CommandRegistry $commandRegistry = null, 
        CommandService $commandService = null
    ) {
        $this->commandRegistry = $commandRegistry ?: new CommandRegistry();
        $this->commandService = $commandService ?: new CommandService();
    }

    public function processWebhook($request)
    {
        try {
            $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
            $parsedEvents = EventRequestParser::parseEventRequest(
                $request->getContent(), 
                config('line.channel_secret'), 
                $signature
            );

            foreach ($parsedEvents->getEvents() as $event) {
                $this->handleEvent($event);
            }

            return response('ok');
        } catch (\Exception $e) {
            Log::error('LINE webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response('Error processing webhook', 500);
        }
    }

    private function handleEvent($event): void
    {
        if (!($event instanceof MessageEvent)) {
            Log::info('Non-message event received', ['event_type' => get_class($event)]);
            return;
        }

        $message = $event->getMessage();
        if (!($message instanceof TextMessageContent)) {
            Log::info('Non-text message received', ['message_type' => get_class($message)]);
            return;
        }

        $this->processTextMessage($event, $message->getText());
    }

    private function processTextMessage(MessageEvent $event, string $text): void
    {
        try {
            $command = $this->commandRegistry->findCommand($text);
            $bot = LineClientFactory::create();
            
            $this->commandService->executeCommand($event, $bot, $command);
            
            Log::info('Command executed successfully', [
                'command' => get_class($command),
                'message' => $text
            ]);
        } catch (\Exception $e) {
            Log::error('Command execution failed', [
                'error' => $e->getMessage(),
                'message' => $text,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}