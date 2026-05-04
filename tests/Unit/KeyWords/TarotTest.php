<?php

namespace Tests\Unit\KeyWords;

use App\KeyWords\Tarot;
use LINE\Clients\MessagingApi\Model\FlexBubble;
use LINE\Clients\MessagingApi\Model\FlexMessage;
use LINE\Clients\MessagingApi\Model\ImageMessage;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Tests\TestCase;

class TarotTest extends TestCase
{
    private Tarot $command;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://example.test']);
        $this->command = new Tarot();
    }

    public static function recognizedKeywords(): array
    {
        return [
            ['每日塔羅'],
            ['塔羅三牌陣'],
        ];
    }

    /**
     * @dataProvider recognizedKeywords
     */
    public function test_can_handle_recognized_keywords(string $keyword): void
    {
        $this->assertTrue($this->command->canHandle($keyword));
    }

    public static function unrecognizedKeywords(): array
    {
        return [
            ['塔羅'],
            ['每日'],
            ['塔羅占卜'],
            ['抽塔羅'],
            ['每日 塔羅'],
            [''],
            ['起卦'],
        ];
    }

    /**
     * @dataProvider unrecognizedKeywords
     */
    public function test_does_not_handle_other_keywords(string $keyword): void
    {
        $this->assertFalse($this->command->canHandle($keyword));
    }

    public function test_draw_daily_returns_image_message_followed_by_text(): void
    {
        $messages = $this->command->drawDaily();

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(ImageMessage::class, $messages[0]);
        $this->assertInstanceOf(TextMessage::class, $messages[1]);
    }

    public function test_draw_daily_image_url_points_to_tarot_assets(): void
    {
        $messages = $this->command->drawDaily();
        $url = $messages[0]->getOriginalContentUrl();

        $this->assertStringContainsString('/img/tarot/', $url);
        $this->assertMatchesRegularExpression('#/\d{2}\.jpg$#', $url);
        $this->assertSame($url, $messages[0]->getPreviewImageUrl());
    }

    public function test_draw_daily_text_includes_orientation_label(): void
    {
        $messages = $this->command->drawDaily();
        $text = $messages[1]->getText();

        $this->assertStringContainsString('每日塔羅', $text);
        $this->assertTrue(
            str_contains($text, '正位') || str_contains($text, '逆位'),
            'expected text to label orientation, got: ' . $text
        );
    }

    public function test_draw_three_card_returns_flex_carousel_then_text(): void
    {
        $messages = $this->command->drawThreeCard();

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(FlexMessage::class, $messages[0]);
        $this->assertInstanceOf(TextMessage::class, $messages[1]);
    }

    public function test_three_card_carousel_has_exactly_three_bubbles(): void
    {
        $messages = $this->command->drawThreeCard();
        $carousel = $messages[0]->getContents();
        $bubbles = $carousel->getContents();

        $this->assertCount(3, $bubbles);
        foreach ($bubbles as $bubble) {
            $this->assertInstanceOf(FlexBubble::class, $bubble);
        }
    }

    public function test_three_card_text_labels_past_present_future(): void
    {
        $messages = $this->command->drawThreeCard();
        $text = $messages[1]->getText();

        $this->assertStringContainsString('過去', $text);
        $this->assertStringContainsString('現在', $text);
        $this->assertStringContainsString('未來', $text);
    }

    public function test_three_card_picks_three_unique_cards_across_many_runs(): void
    {
        // Run drawThreeCard 50 times; each draw must contain 3 unique cards.
        for ($run = 0; $run < 50; $run++) {
            $cmd = new Tarot();
            $messages = $cmd->drawThreeCard();
            $text = $messages[1]->getText();

            // Extract card names by 【position】name（orientation） pattern.
            preg_match_all('/【[^】]+】([^（]+)（/u', $text, $matches);
            $names = $matches[1];
            $this->assertCount(3, $names, "run {$run}: expected 3 cards, got: " . implode(',', $names));
            $this->assertCount(3, array_unique($names), "run {$run}: duplicate cards in: " . implode(',', $names));
        }
    }

    public function test_handle_routes_three_card_keyword_to_three_card_draw(): void
    {
        $event = $this->makeMessageEvent('塔羅三牌陣');
        $messages = $this->command->handle($event);

        $this->assertInstanceOf(FlexMessage::class, $messages[0]);
    }

    public function test_handle_routes_daily_keyword_to_daily_draw(): void
    {
        $event = $this->makeMessageEvent('每日塔羅');
        $messages = $this->command->handle($event);

        $this->assertInstanceOf(ImageMessage::class, $messages[0]);
    }

    public function test_get_description_is_non_empty(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    private function makeMessageEvent(string $text): \LINE\Webhook\Model\MessageEvent
    {
        $message = \Mockery::mock(\LINE\Webhook\Model\TextMessageContent::class);
        $message->shouldReceive('getText')->andReturn($text);

        $event = \Mockery::mock(\LINE\Webhook\Model\MessageEvent::class);
        $event->shouldReceive('getMessage')->andReturn($message);

        return $event;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
