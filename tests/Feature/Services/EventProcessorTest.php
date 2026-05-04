<?php

namespace Tests\Feature\Services;

use App\KeyWords\Command;
use App\KeyWords\Lottery;
use App\KeyWords\MonteCarlo;
use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;
use App\KeyWords\Tarot;
use App\KeyWords\XiaoLiuRen;
use App\Services\CommandRegistry;
use App\Services\CommandService;
use App\Services\EventProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Constants\HTTPHeader;
use LINE\Webhook\Model\MessageEvent;
use Mockery;
use Tests\TestCase;

class EventProcessorTest extends TestCase
{
    private const SECRET = 'test-channel-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('line.channel_secret', self::SECRET);
        Config::set('line.channel_access_token', 'test-channel-access-token');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function keywordToCommand(): array
    {
        return [
            '起卦 → QiGua' => ['起卦', QiGua::class],
            '抽神社籤 → ShengSheQian' => ['抽神社籤', ShengSheQian::class],
            '大樂透號碼 → Lottery' => ['大樂透號碼', Lottery::class],
            '蒙地卡羅預測 → MonteCarlo' => ['蒙地卡羅預測', MonteCarlo::class],
            '小六壬 → XiaoLiuRen' => ['小六壬', XiaoLiuRen::class],
            '每日塔羅 → Tarot' => ['每日塔羅', Tarot::class],
        ];
    }

    /**
     * @dataProvider keywordToCommand
     */
    public function test_text_message_keyword_dispatches_to_expected_command(string $keyword, string $expectedClass): void
    {
        $captured = null;
        $commandService = Mockery::mock(CommandService::class);
        $commandService->shouldReceive('executeCommand')
            ->once()
            ->with(
                Mockery::type(MessageEvent::class),
                Mockery::type(MessagingApiApi::class),
                Mockery::on(function (Command $command) use (&$captured) {
                    $captured = $command;
                    return true;
                })
            );

        $processor = new EventProcessor(new CommandRegistry(), $commandService);
        $response = $processor->processWebhook($this->makeSignedTextRequest($keyword));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        $this->assertInstanceOf($expectedClass, $captured);
    }

    public function test_unknown_keyword_dispatches_to_error_command(): void
    {
        $captured = null;
        $commandService = Mockery::mock(CommandService::class);
        $commandService->shouldReceive('executeCommand')
            ->once()
            ->with(
                Mockery::type(MessageEvent::class),
                Mockery::type(MessagingApiApi::class),
                Mockery::on(function (Command $command) use (&$captured) {
                    $captured = $command;
                    return true;
                })
            );

        $processor = new EventProcessor(new CommandRegistry(), $commandService);
        $response = $processor->processWebhook($this->makeSignedTextRequest('哈囉'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertInstanceOf(\App\KeyWords\Error::class, $captured);
    }

    public function test_command_execution_failure_is_swallowed_and_response_is_ok(): void
    {
        $commandService = Mockery::mock(CommandService::class);
        $commandService->shouldReceive('executeCommand')
            ->once()
            ->andThrow(new \RuntimeException('LINE API down'));

        $processor = new EventProcessor(new CommandRegistry(), $commandService);
        $response = $processor->processWebhook($this->makeSignedTextRequest('起卦'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_invalid_signature_inside_processor_still_returns_ok_to_avoid_line_retries(): void
    {
        $body = $this->buildTextEventBody('起卦');
        $request = $this->buildRequest($body, 'invalid-signature');

        $commandService = Mockery::mock(CommandService::class);
        $commandService->shouldNotReceive('executeCommand');

        $processor = new EventProcessor(new CommandRegistry(), $commandService);
        $response = $processor->processWebhook($request);

        // LineBotWebHook middleware 已負責 400；EventProcessor 內部即便再次失敗也應回 200，
        // 否則 LINE 平台會視為失敗重送，造成風暴。
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    private function makeSignedTextRequest(string $text): Request
    {
        $body = $this->buildTextEventBody($text);
        $signature = base64_encode(hash_hmac('sha256', $body, self::SECRET, true));

        return $this->buildRequest($body, $signature);
    }

    private function buildRequest(string $body, string $signature): Request
    {
        $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper(HTTPHeader::LINE_SIGNATURE));

        return Request::create(
            '/api/message',
            'POST',
            [],
            [],
            [],
            [$headerKey => $signature, 'CONTENT_TYPE' => 'application/json'],
            $body
        );
    }

    private function buildTextEventBody(string $text): string
    {
        return json_encode([
            'destination' => 'U0123456789abcdef0123456789abcd',
            'events' => [
                [
                    'type' => 'message',
                    'mode' => 'active',
                    'timestamp' => 1234567890123,
                    'webhookEventId' => '01H000000000000000000000XX',
                    'deliveryContext' => ['isRedelivery' => false],
                    'source' => ['type' => 'user', 'userId' => 'Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
                    'replyToken' => 'reply-token-xxxxxxxxxxxxxxxxxxxx',
                    'message' => [
                        'id' => 'msg-id-1',
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
            ],
        ]);
    }
}
