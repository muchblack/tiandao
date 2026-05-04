<?php

namespace App\KeyWords;

use com\nlf\calendar\Lunar;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\File;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Webhook\Model\MessageEvent;

class XiaoLiuRen implements Command
{
    private const TIMEZONE = 'Asia/Taipei';

    private const PALACE_LABELS = ['月宮', '日宮', '時宮'];

    public function canHandle(string $message): bool
    {
        return in_array($message, ['小六壬', '起小六壬', '六壬']);
    }

    public function handle(MessageEvent $event): array
    {
        return $this->replyCommand();
    }

    public function getDescription(): string
    {
        return '小六壬占卜 - 依當下農曆月日時推算大安/留連/速喜/赤口/小吉/空亡六宮，回月日時三宮卦意';
    }

    public function replyCommand(): array
    {
        $palaces = $this->loadPalaces();
        $cast = $this->cast(new DateTime('now', new DateTimeZone(self::TIMEZONE)));

        $messages = [];
        foreach (self::PALACE_LABELS as $i => $label) {
            $palace = $palaces[$cast['indices'][$i]];
            $messages[] = (new TextMessage([
                'text' => $this->formatPalace($label, $palace, $cast['header'][$i]),
            ]))->setType('text');
        }

        return $messages;
    }

    /**
     * 起卦：依農曆月、日、時辰序計算月宮 / 日宮 / 時宮（皆為 palaces 索引 0-5）。
     */
    public function cast(DateTime $when): array
    {
        $lunar = Lunar::fromDate($when);

        $month = abs($lunar->getMonth());      // 閏月為負，取絕對值
        $day = $lunar->getDay();
        $timeIndex = $lunar->getTimeZhiIndex(); // 0-based: 子=0...亥=11

        $monthPalace = ($month - 1) % 6;
        $dayPalace = ($monthPalace + $day - 1) % 6;
        $timePalace = ($dayPalace + $timeIndex) % 6;

        return [
            'indices' => [$monthPalace, $dayPalace, $timePalace],
            'header' => [
                "農曆 {$month} 月（{$month}）",
                "農曆 {$day} 日（{$day}）",
                "{$lunar->getTimeZhi()}時（時辰序 {$timeIndex}）",
            ],
        ];
    }

    private function loadPalaces(): array
    {
        return File::json(base_path('data/xiaoLiuRen/palaces.json'));
    }

    private function formatPalace(string $label, array $palace, string $header): string
    {
        $aspects = $palace['aspects'];

        $txt = "[{$label}] {$palace['name']}（{$palace['wuxing']}・{$palace['direction']}）\n";
        $txt .= "起算：{$header}\n";
        $txt .= "[卦辭]\n{$palace['summary']}\n";
        $txt .= "[事業] {$aspects['事業']}\n";
        $txt .= "[愛情] {$aspects['愛情']}\n";
        $txt .= "[出行] {$aspects['出行']}\n";
        $txt .= "[失物] {$aspects['失物']}\n";
        $txt .= "[疾病] {$aspects['疾病']}\n";
        $txt .= "[求財] {$aspects['求財']}";

        return $txt;
    }
}
