<?php

namespace App\KeyWords;

use Illuminate\Support\Facades\File;
use LINE\Clients\MessagingApi\Model\FlexBox;
use LINE\Clients\MessagingApi\Model\FlexBubble;
use LINE\Clients\MessagingApi\Model\FlexCarousel;
use LINE\Clients\MessagingApi\Model\FlexImage;
use LINE\Clients\MessagingApi\Model\FlexMessage;
use LINE\Clients\MessagingApi\Model\FlexText;
use LINE\Clients\MessagingApi\Model\ImageMessage;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;

class Tarot implements Command
{
    private const KEYWORD_DAILY = '每日塔羅';

    private const KEYWORD_THREE = '塔羅三牌陣';

    private const POSITIONS = ['過去', '現在', '未來'];

    public function canHandle(string $message): bool
    {
        return in_array($message, [self::KEYWORD_DAILY, self::KEYWORD_THREE], true);
    }

    public function handle(MessageEvent $event): array
    {
        $message = $event->getMessage();
        $text = $message instanceof TextMessageContent ? $message->getText() : '';

        if ($text === self::KEYWORD_THREE) {
            return $this->drawThreeCard();
        }

        return $this->drawDaily();
    }

    public function getDescription(): string
    {
        return '塔羅占卜 - 每日塔羅一張或塔羅三牌陣（過去/現在/未來），含 78 張 RWS 1909 PD 牌面';
    }

    public function replyCommand(): array
    {
        return $this->drawDaily();
    }

    /**
     * 每日塔羅：抽 1 張，回 ImageMessage + TextMessage 共 2 條。
     */
    public function drawDaily(): array
    {
        $cards = $this->loadCards();
        $idx = $this->pickIndex(count($cards));
        $reversed = $this->pickReversed();

        return [
            $this->buildImageMessage($cards[$idx]),
            (new TextMessage([
                'text' => $this->formatSingleCardText($cards[$idx], $reversed, '🔮 每日塔羅'),
            ]))->setType('text'),
        ];
    }

    /**
     * 塔羅三牌陣：抽 3 張不重複，回 FlexCarousel + TextMessage 共 2 條。
     */
    public function drawThreeCard(): array
    {
        $cards = $this->loadCards();
        $indices = $this->pickThreeUnique(count($cards));
        $reversedFlags = [
            $this->pickReversed(),
            $this->pickReversed(),
            $this->pickReversed(),
        ];

        $bubbles = [];
        $textBlocks = [];
        foreach ($indices as $i => $cardIdx) {
            $card = $cards[$cardIdx];
            $position = self::POSITIONS[$i];
            $reversed = $reversedFlags[$i];

            $bubbles[] = $this->buildBubble($card, $reversed, $position);
            $textBlocks[] = $this->formatThreeCardBlock($card, $reversed, $position);
        }

        $carousel = (new FlexCarousel([
            'contents' => $bubbles,
        ]))->setType('carousel');

        $flex = (new FlexMessage([
            'altText' => '塔羅三牌陣：過去 / 現在 / 未來',
            'contents' => $carousel,
        ]))->setType('flex');

        $combinedText = "🔮 塔羅三牌陣\n\n" . implode("\n\n", $textBlocks);

        return [
            $flex,
            (new TextMessage(['text' => $combinedText]))->setType('text'),
        ];
    }

    /**
     * 抽單張索引（0 ~ count-1）。protected 以便測試覆寫釘死隨機。
     */
    protected function pickIndex(int $count): int
    {
        return random_int(0, $count - 1);
    }

    /**
     * 抽 3 張不重複索引。protected 以便測試覆寫。
     */
    protected function pickThreeUnique(int $count): array
    {
        $picks = (array) array_rand(range(0, $count - 1), 3);
        // array_rand returns keys; on small range these are 0-based index ints already.
        // Shuffle to avoid predictable order from array_rand sorting.
        shuffle($picks);

        return $picks;
    }

    /**
     * 50% 機率逆位。protected 以便測試覆寫。
     */
    protected function pickReversed(): bool
    {
        return random_int(0, 1) === 1;
    }

    private function loadCards(): array
    {
        return File::json(base_path('data/tarot/cards.json'));
    }

    private function imageUrl(array $card): string
    {
        return asset('img/tarot/' . $card['image']);
    }

    private function buildImageMessage(array $card): ImageMessage
    {
        $url = $this->imageUrl($card);

        return (new ImageMessage([
            'originalContentUrl' => $url,
            'previewImageUrl' => $url,
        ]))->setType('image');
    }

    private function buildBubble(array $card, bool $reversed, string $position): FlexBubble
    {
        $orientation = $reversed ? 'reversed' : 'upright';
        $face = $card[$orientation];

        $hero = (new FlexImage([
            'url' => $this->imageUrl($card),
            'aspectRatio' => '2:3',
            'aspectMode' => 'cover',
            'size' => 'full',
        ]))->setType('image');

        $bodyContents = [
            (new FlexText([
                'text' => $position,
                'weight' => 'bold',
                'size' => 'lg',
                'color' => '#7B4F9C',
            ]))->setType('text'),
            (new FlexText([
                'text' => sprintf('%s（%s）', $card['name_zh'], $reversed ? '逆位' : '正位'),
                'weight' => 'bold',
                'size' => 'md',
                'wrap' => true,
            ]))->setType('text'),
            (new FlexText([
                'text' => implode(' / ', $face['keywords']),
                'size' => 'sm',
                'color' => '#888888',
                'wrap' => true,
            ]))->setType('text'),
        ];

        $body = (new FlexBox([
            'layout' => 'vertical',
            'spacing' => 'sm',
            'paddingAll' => 'md',
            'contents' => $bodyContents,
        ]))->setType('box');

        return (new FlexBubble([
            'size' => 'kilo',
            'hero' => $hero,
            'body' => $body,
        ]))->setType('bubble');
    }

    private function formatSingleCardText(array $card, bool $reversed, string $title): string
    {
        $orientation = $reversed ? 'reversed' : 'upright';
        $face = $card[$orientation];
        $orientationLabel = $reversed ? '逆位' : '正位';

        $txt = "$title\n\n";
        $txt .= sprintf("%s %s（%s）\n", $card['name_zh'], $card['name_en'], $orientationLabel);
        $txt .= '[' . implode(' / ', $face['keywords']) . "]\n\n";
        $txt .= "「{$face['summary']}」\n\n";
        $txt .= $face['meaning'];

        return $txt;
    }

    private function formatThreeCardBlock(array $card, bool $reversed, string $position): string
    {
        $orientation = $reversed ? 'reversed' : 'upright';
        $face = $card[$orientation];
        $orientationLabel = $reversed ? '逆位' : '正位';

        $txt = sprintf("【%s】%s（%s）\n", $position, $card['name_zh'], $orientationLabel);
        $txt .= '[' . implode(' / ', $face['keywords']) . "]\n";
        $txt .= "「{$face['summary']}」\n";
        $txt .= $face['meaning'];

        return $txt;
    }
}
