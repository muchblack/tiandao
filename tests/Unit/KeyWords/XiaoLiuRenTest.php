<?php

namespace Tests\Unit\KeyWords;

use App\KeyWords\XiaoLiuRen;
use DateTime;
use DateTimeZone;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Tests\TestCase;

class XiaoLiuRenTest extends TestCase
{
    private XiaoLiuRen $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new XiaoLiuRen();
    }

    public static function recognizedKeywords(): array
    {
        return [
            ['小六壬'],
            ['起小六壬'],
            ['六壬'],
            ['小六壬 4 3 5'],
            ['起小六壬 4 3 5'],
            ['六壬 4 3 5'],
            ['小六壬 abc'],
            [' 小六壬 '],
            ["\t　小六壬　\r\n"],
            ['小六壬，4 3 5'],
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
            ['哈囉'],
            [''],
            ['起卦'],
            ['小六'],
            ['六壬神課'],
            ['小六壬435'],
        ];
    }

    /**
     * @dataProvider unrecognizedKeywords
     */
    public function test_does_not_handle_other_keywords(string $keyword): void
    {
        $this->assertFalse($this->command->canHandle($keyword));
    }

    /**
     * 演算法 fixtures：固定日期 → 預期 [月宮, 日宮, 時宮]。
     * 透過 6tail/lunar-php 預先計算驗證；任何更動算法導致這些斷言失敗皆視為迴歸。
     */
    public static function castFixtures(): array
    {
        return [
            '農曆正月初一 午時 → 三宮全大安' => [
                '2026-02-17 12:00:00', [0, 0, 0],
            ],
            '農曆 11/13 子時 → 全小吉' => [
                '2026-01-01 00:30:00', [4, 4, 4],
            ],
            '農曆 3/18 申時' => [
                '2026-05-04 16:00:00', [2, 1, 3],
            ],
            '農曆 5/1 辰時' => [
                '2026-06-15 08:00:00', [4, 4, 2],
            ],
            '跨日子時（23:30 屬子時序 0）' => [
                '2026-12-31 23:30:00', [4, 2, 2],
            ],
        ];
    }

    /**
     * @dataProvider castFixtures
     */
    public function test_cast_returns_expected_palace_indices(string $datetime, array $expected): void
    {
        $when = new DateTime($datetime, new DateTimeZone('Asia/Taipei'));

        $cast = $this->command->cast($when);

        $this->assertSame($expected, $cast['indices']);
        $this->assertCount(3, $cast['header']);
    }

    public function test_cast_indices_are_within_palace_range(): void
    {
        $cast = $this->command->cast(new DateTime('now', new DateTimeZone('Asia/Taipei')));

        foreach ($cast['indices'] as $idx) {
            $this->assertGreaterThanOrEqual(0, $idx);
            $this->assertLessThanOrEqual(5, $idx);
        }
    }

    public function test_reply_command_returns_three_text_messages(): void
    {
        $messages = $this->command->replyCommand();

        $this->assertCount(3, $messages);
        foreach ($messages as $msg) {
            $this->assertInstanceOf(TextMessage::class, $msg);
            $this->assertNotEmpty($msg->getText());
        }
    }

    public function test_reply_command_orders_messages_with_time_palace_first_as_primary(): void
    {
        $messages = $this->command->replyCommand();

        $this->assertStringContainsString('時宮', $messages[0]->getText());
        $this->assertStringContainsString('【主斷】', $messages[0]->getText());

        $this->assertStringContainsString('日宮', $messages[1]->getText());
        $this->assertStringContainsString('【雙宮並斷】', $messages[1]->getText());

        $this->assertStringContainsString('月宮', $messages[2]->getText());
        $this->assertStringContainsString('【整體背景】', $messages[2]->getText());
    }

    public function test_reply_command_includes_spirit_and_lucky_numbers_per_palace(): void
    {
        $messages = $this->command->replyCommand();

        foreach ($messages as $msg) {
            $text = $msg->getText();
            $this->assertStringContainsString('神煞：', $text);
            $this->assertStringContainsString('主數：', $text);
            $this->assertStringContainsString('五行：', $text);
            $this->assertStringContainsString('方位：', $text);
        }
    }

    public function test_reply_command_includes_six_aspects_per_palace(): void
    {
        $messages = $this->command->replyCommand();

        foreach ($messages as $msg) {
            $text = $msg->getText();
            foreach (['事業', '愛情', '出行', '失物', '疾病', '求財'] as $aspect) {
                $this->assertStringContainsString("[{$aspect}]", $text, "missing aspect [{$aspect}] in: {$text}");
            }
        }
    }

    public function test_get_description_is_non_empty(): void
    {
        $this->assertNotEmpty($this->command->getDescription());
    }

    public function test_description_mentions_number_usage(): void
    {
        $this->assertStringContainsString('小六壬 N1 N2 N3', $this->command->getDescription());
        $this->assertStringContainsString('1–100', $this->command->getDescription());
    }

    public static function numberFixtures(): array
    {
        return [
            ['小六壬 4 3 5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬 1 1 1', [1, 1, 1], ['大安', '大安', '大安']],
            ['小六壬 7 1 1', [7, 1, 1], ['大安', '大安', '大安']],
            ['小六壬 100 100 100', [100, 100, 100], ['赤口', '大安', '赤口']],
            ['小六壬 100 88 37', [100, 88, 37], ['大安', '大安', '赤口']],
            ['小六壬 ４ ３ ５', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['　小六壬　4　3　5　', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬 4,3,5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬 4，3，5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬 4、3、5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬  4 ，、3   5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['起小六壬 4 3 5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['六壬 4 3 5', [4, 3, 5], ['赤口', '空亡', '赤口']],
            ['小六壬,04,03,05', [4, 3, 5], ['赤口', '空亡', '赤口']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('numberFixtures')]
    public function test_handle_casts_numbers_with_complete_deterministic_replies(string $input, array $numbers, array $names): void
    {
        $messages = $this->command->handle($this->makeMessageEvent($input));
        $again = $this->command->handle($this->makeMessageEvent($input));
        $this->assertCount(3, $messages);
        $labels = ['【主斷】末宮', '【雙宮並斷】中宮', '【整體背景】初宮'];
        $headers = ["第三數 {$numbers[2]}", "第二數 {$numbers[1]}", "第一數 {$numbers[0]}"];
        foreach ($messages as $i => $message) {
            $this->assertInstanceOf(TextMessage::class, $message);
            $this->assertSame('text', $message->getType());
            $text = $message->getText();
            $this->assertStringStartsWith("{$labels[$i]} → {$names[$i]}\n", $text);
            $this->assertStringContainsString("起算：{$headers[$i]}\n", $text);
            foreach (['時宮', '日宮', '月宮'] as $label) {
                $this->assertStringNotContainsString($label, $text);
            }
            foreach (['五行：', '方位：', '神煞：', '主數：', '[卦辭]', '[事業]', '[愛情]', '[出行]', '[失物]', '[疾病]', '[求財]'] as $field) {
                $this->assertStringContainsString($field, $text);
            }
            $this->assertSame($text, $again[$i]->getText());
        }
    }

    public function test_handle_exact_keyword_preserves_time_reply(): void
    {
        $command = new class extends XiaoLiuRen {
            public function cast(DateTime $when): array
            {
                return parent::cast(new DateTime('2026-05-04 16:00:00', new DateTimeZone('Asia/Taipei')));
            }
        };
        $expected = $command->replyCommand();
        foreach (['小六壬', '起小六壬', '六壬', '　小六壬　'] as $input) {
            $messages = $command->handle($this->makeMessageEvent($input));
            $this->assertCount(3, $messages);
            foreach (['時宮', '日宮', '月宮'] as $i => $label) {
                $this->assertStringContainsString($label, $messages[$i]->getText());
                $this->assertSame($expected[$i]->getText(), $messages[$i]->getText());
            }
        }
    }

    public static function invalidNumberInputs(): array
    {
        return array_map(fn ($input) => [$input], [
            '小六壬 4 3', '小六壬 4 3 5 6', '小六壬 0 3 5',
            '小六壬 -4 3 5', '小六壬 4.5 3 5', '小六壬 abc',
            '小六壬 101 3 5', '小六壬 +4 3 5', '小六壬 😀',
            '小六壬 ,，、', '小六壬 '.str_repeat('9', 10000).' 3 5',
            "小六壬 4\n3 5", "小六壬 4\t3 5", "小六壬 \xFF",
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidNumberInputs')]
    public function test_invalid_numbers_return_one_usage_message(string $input): void
    {
        $messages = $this->command->handle($this->makeMessageEvent($input));
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(TextMessage::class, $messages[0]);
        $this->assertSame('text', $messages[0]->getType());
        $this->assertStringContainsString('小六壬 N1 N2 N3', $messages[0]->getText());
        $this->assertStringContainsString('1–100', $messages[0]->getText());
        $this->assertStringContainsString('小六壬 4 3 5', $messages[0]->getText());
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
