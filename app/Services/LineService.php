<?php
namespace App\Services;

class LineService
{
    private EventProcessor $eventProcessor;

    public function __construct(EventProcessor $eventProcessor = null)
    {
        $this->eventProcessor = $eventProcessor ?: new EventProcessor();
    }

    public function webhook($request)
    {
        return $this->eventProcessor->processWebhook($request);
    }
}
