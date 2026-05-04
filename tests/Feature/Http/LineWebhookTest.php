<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Config;
use LINE\Constants\HTTPHeader;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    private const ENDPOINT = '/api/message';
    private const SECRET = 'test-channel-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('line.channel_secret', self::SECRET);
        Config::set('line.channel_access_token', 'test-channel-access-token');
    }

    public function test_returns_400_when_signature_header_missing(): void
    {
        $response = $this->postJson(self::ENDPOINT, ['events' => []]);

        $response->assertStatus(400);
    }

    public function test_returns_400_when_signature_is_invalid(): void
    {
        $body = json_encode(['destination' => 'U0', 'events' => []]);

        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            ['HTTP_' . str_replace('-', '_', strtoupper(HTTPHeader::LINE_SIGNATURE)) => 'invalid-signature', 'CONTENT_TYPE' => 'application/json'],
            $body
        );

        $response->assertStatus(400);
    }

    public function test_returns_400_when_payload_is_malformed_json_with_valid_signature(): void
    {
        $body = 'not-a-valid-json';
        $signature = $this->sign($body);

        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            [
                'HTTP_' . str_replace('-', '_', strtoupper(HTTPHeader::LINE_SIGNATURE)) => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $response->assertStatus(400);
    }

    public function test_accepts_valid_signature_with_empty_events(): void
    {
        $body = json_encode(['destination' => 'U0', 'events' => []]);
        $signature = $this->sign($body);

        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            [
                'HTTP_' . str_replace('-', '_', strtoupper(HTTPHeader::LINE_SIGNATURE)) => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $response->assertOk();
        $this->assertSame('ok', $response->getContent());
    }

    public function test_non_message_event_is_skipped_without_error(): void
    {
        $body = json_encode([
            'destination' => 'U0',
            'events' => [
                [
                    'type' => 'follow',
                    'mode' => 'active',
                    'timestamp' => 1234567890,
                    'webhookEventId' => '01H000000000000000000000XX',
                    'deliveryContext' => ['isRedelivery' => false],
                    'source' => ['type' => 'user', 'userId' => 'Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
                    'replyToken' => 'reply-token-xxxxxxxxxxxxxxxxxxxx',
                ],
            ],
        ]);
        $signature = $this->sign($body);

        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            [
                'HTTP_' . str_replace('-', '_', strtoupper(HTTPHeader::LINE_SIGNATURE)) => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $response->assertOk();
        $this->assertSame('ok', $response->getContent());
    }

    private function sign(string $body): string
    {
        return base64_encode(hash_hmac('sha256', $body, self::SECRET, true));
    }
}
