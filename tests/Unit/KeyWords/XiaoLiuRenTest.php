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

    public function test_reply_command_messages_carry_palace_labels_in_order(): void
    {
        $messages = $this->command->replyCommand();

        $this->assertStringContainsString('[月宮]', $messages[0]->getText());
        $this->assertStringContainsString('[日宮]', $messages[1]->getText());
        $this->assertStringContainsString('[時宮]', $messages[2]->getText());
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
}
