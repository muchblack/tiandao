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

    /**
     * 訊息回覆順序（傳統「以時宮為主，日宮+時宮雙宮並斷」之排法）。
     * key: cast indices 對應的位置（month=0, day=1, time=2）
     */
    private const REPLY_ORDER = [
        ['cast_pos' => 2, 'label' => '時宮', 'role' => '主斷', 'note' => '小六壬以時宮為主要占斷'],
        ['cast_pos' => 1, 'label' => '日宮', 'role' => '雙宮並斷', 'note' => '日宮+時宮並讀以加強主斷'],
        ['cast_pos' => 0, 'label' => '月宮', 'role' => '整體背景', 'note' => '本月整體運勢背景'],
    ];

    public function canHandle(string $message): bool
    {
        return preg_match('/\A(?:起小六壬|小六壬|六壬)(?:\z|[ 　,，、])/u', $this->trimMessage($message)) === 1;
    }

    public function handle(MessageEvent $event): array
    {
        $text = $this->trimMessage($event->getMessage()->getText());
        if (in_array($text, ['小六壬', '起小六壬', '六壬'], true)) {
            return $this->replyCommand();
        }

        $input = preg_replace('/\A(?:起小六壬|小六壬|六壬)/u', '', $text);
        $input = strtr($input, ['０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9']);
        $tokens = preg_split('/[ 　,，、]+/u', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $valid = count($tokens) === 3;
        foreach ($tokens as $token) {
            if (!ctype_digit($token) || (int) $token < 1 || (int) $token > 100) {
                $valid = false;
                break;
            }
        }
        if (!$valid) {
            return [(new TextMessage(['text' => '格式：小六壬 N1 N2 N3，每個數字須為 1–100 的整數。例如：小六壬 4 3 5']))->setType('text')];
        }
        $numbers = array_map('intval', $tokens);

        return $this->buildReply([
            'indices' => $this->castNumbers(...$numbers),
            'header' => ["第一數 {$numbers[0]}", "第二數 {$numbers[1]}", "第三數 {$numbers[2]}"],
        ], [
            ['cast_pos' => 2, 'label' => '末宮', 'role' => '主斷', 'note' => '小六壬以末宮為主要占斷'],
            ['cast_pos' => 1, 'label' => '中宮', 'role' => '雙宮並斷', 'note' => '中宮與末宮並讀以加強主斷'],
            ['cast_pos' => 0, 'label' => '初宮', 'role' => '整體背景', 'note' => '本次報數起卦的整體背景'],
        ]);
    }

    public function getDescription(): string
    {
        return '小六壬占卜 - 依當下農曆月日時推算大安/留連/速喜/赤口/小吉/空亡六宮，依時宮主斷、日宮並讀、月宮為背景的傳統排法回覆；也可輸入「小六壬 N1 N2 N3」報數起卦，每數為 1–100';
    }

    public function replyCommand(): array
    {
        return $this->buildReply($this->cast(new DateTime('now', new DateTimeZone(self::TIMEZONE))), self::REPLY_ORDER);
    }

    private function buildReply(array $cast, array $order): array
    {
        $palaces = $this->loadPalaces();

        $messages = [];
        foreach ($order as $entry) {
            $pos = $entry['cast_pos'];
            $palace = $palaces[$cast['indices'][$pos]];
            $messages[] = (new TextMessage([
                'text' => $this->formatPalace(
                    $entry['label'],
                    $entry['role'],
                    $entry['note'],
                    $palace,
                    $cast['header'][$pos]
                ),
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

        return [
            'indices' => $this->castNumbers($month, $day, $timeIndex + 1),
            'header' => [
                "農曆 {$month} 月",
                "農曆 {$day} 日",
                "{$lunar->getTimeZhi()}時（序 {$timeIndex}）",
            ],
        ];
    }

    private function castNumbers(int $first, int $second, int $third): array
    {
        $initial = ($first - 1) % 6;
        $middle = ($initial + $second - 1) % 6;

        return [$initial, $middle, ($middle + $third - 1) % 6];
    }

    private function loadPalaces(): array
    {
        return File::json(base_path('data/xiaoLiuRen/palaces.json'));
    }

    private function trimMessage(string $message): string
    {
        return preg_replace('/\A[\x09-\x0D 　]+|[\x09-\x0D 　]+\z/u', '', $message) ?? '';
    }

    private function formatPalace(string $label, string $role, string $note, array $palace, string $header): string
    {
        $aspects = $palace['aspects'];
        $luckyNumbers = implode('、', $palace['lucky_numbers']);

        $txt = "【{$role}】{$label} → {$palace['name']}\n";
        $txt .= "（{$note}）\n";
        $txt .= "起算：{$header}\n";
        $txt .= "─────────\n";
        $txt .= "五行：{$palace['wuxing']}　方位：{$palace['direction']}　神煞：{$palace['spirit']}\n";
        $txt .= "主數：{$luckyNumbers}\n";
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
