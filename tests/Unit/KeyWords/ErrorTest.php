<?php

namespace Tests\Unit\KeyWords;

use App\KeyWords\Error;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Tests\TestCase;

class ErrorTest extends TestCase
{
    public function test_help_mentions_xiao_liu_ren_number_usage(): void
    {
        $messages = (new Error())->replyCommand();

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(TextMessage::class, $messages[0]);
        $this->assertStringContainsString('小六壬 [三個數字]', $messages[0]->getText());
    }
}
