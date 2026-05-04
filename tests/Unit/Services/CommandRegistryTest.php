<?php

namespace Tests\Unit\Services;

use App\KeyWords\Error;
use App\KeyWords\Lottery;
use App\KeyWords\MonteCarlo;
use App\KeyWords\QiGua;
use App\KeyWords\ShengSheQian;
use App\Services\CommandRegistry;
use Tests\TestCase;

class CommandRegistryTest extends TestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CommandRegistry();
    }

    public static function keywordMatrix(): array
    {
        return [
            '起卦 → QiGua' => ['起卦', QiGua::class],
            '占卜 → QiGua' => ['占卜', QiGua::class],
            '問卦 → QiGua' => ['問卦', QiGua::class],
            '抽神社籤 → ShengSheQian' => ['抽神社籤', ShengSheQian::class],
            '神社籤 → ShengSheQian' => ['神社籤', ShengSheQian::class],
            '求籤 → ShengSheQian' => ['求籤', ShengSheQian::class],
            '占卜籤 → ShengSheQian' => ['占卜籤', ShengSheQian::class],
            '大樂透號碼 → Lottery' => ['大樂透號碼', Lottery::class],
            '大樂透 → Lottery' => ['大樂透', Lottery::class],
            '彩票分析 → Lottery' => ['彩票分析', Lottery::class],
            '蒙地卡羅預測 → MonteCarlo' => ['蒙地卡羅預測', MonteCarlo::class],
            '蒙地卡羅 → MonteCarlo' => ['蒙地卡羅', MonteCarlo::class],
            '彩票預測 → MonteCarlo' => ['彩票預測', MonteCarlo::class],
        ];
    }

    /**
     * @dataProvider keywordMatrix
     */
    public function test_routes_keyword_to_expected_command(string $keyword, string $expectedClass): void
    {
        $command = $this->registry->findCommand($keyword);

        $this->assertInstanceOf($expectedClass, $command);
    }

    public static function unknownInputs(): array
    {
        return [
            '空字串' => [''],
            '無關鍵字' => ['你好'],
            '近似但不相等' => ['起 卦'],
            '英文' => ['hello'],
            '部分前綴' => ['大樂'],
        ];
    }

    /**
     * @dataProvider unknownInputs
     */
    public function test_unknown_message_falls_back_to_error(string $message): void
    {
        $command = $this->registry->findCommand($message);

        $this->assertInstanceOf(Error::class, $command);
    }

    public function test_default_registration_contains_four_commands(): void
    {
        $registered = $this->registry->getAllCommands();

        $this->assertCount(4, $registered);
        $this->assertContainsOnlyInstancesOf(\App\KeyWords\Command::class, $registered);
    }

    public function test_custom_command_can_be_registered_and_takes_priority_when_matching(): void
    {
        $custom = new class implements \App\KeyWords\Command {
            public function canHandle(string $message): bool
            {
                return $message === '自訂指令';
            }

            public function handle(\LINE\Webhook\Model\MessageEvent $event): array
            {
                return [];
            }

            public function getDescription(): string
            {
                return 'test';
            }

            public function replyCommand(): array
            {
                return [];
            }
        };

        $this->registry->register($custom);

        $this->assertSame($custom, $this->registry->findCommand('自訂指令'));
        $this->assertInstanceOf(QiGua::class, $this->registry->findCommand('起卦'));
    }
}
