# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 10 application called "天道" (Tiandao) - a LINE bot that provides Chinese divination services including I-Ching readings, lottery predictions, and temple fortune sticks. The bot responds to specific Chinese commands and returns traditional fortune-telling results.

## Development Commands

### Basic Laravel Commands
- `php artisan serve` - Start the development server
- `php artisan migrate` - Run database migrations
- `php artisan tinker` - Open Laravel's interactive shell
- `php artisan config:clear` - Clear configuration cache
- `php artisan route:list` - List all routes

### Testing
- `vendor/bin/phpunit` or `php artisan test` - Run PHPUnit tests
- `vendor/bin/phpunit tests/Unit` - Run unit tests only
- `vendor/bin/phpunit tests/Feature` - Run feature tests only

### Code Quality
- `vendor/bin/pint` - Run Laravel Pint for code formatting (if available)

### Frontend Assets
- `npm run dev` - Start Vite development server
- `npm run build` - Build assets for production

### Custom Artisan Commands
- `php artisan lottery` - Test lottery functionality (`app/Console/Commands/LotteryTest.php`)
- `php artisan montecarlo` - Test Monte Carlo prediction (`app/Console/Commands/MonteCarloTest.php`)

## Architecture Overview

### Routes
- `POST /api/message` → `LineCallBackController@index` — LINE webhook entry, guarded by `LineBotWebHook` middleware (signature verification)
- `GET /` and `GET /shenshe` → `SheShiController` — local debug routes that `dump()` a single divination result; not part of the LINE flow

### LINE Bot Pipeline
The webhook flow is split across small collaborators rather than a single service:

1. `LineCallBackController@index` (`app/Http/Controllers/`) delegates to `LineService::webhook()`.
2. `LineService` hands the request to `EventProcessor::processWebhook()` (`app/Services/EventProcessor.php`), which:
   - Verifies the LINE signature and parses events via `EventRequestParser`.
   - Filters to `MessageEvent` + `TextMessageContent`.
   - Logs and swallows non-text/non-message events instead of erroring.
3. `EventProcessor` asks `CommandRegistry::findCommand($text)` (`app/Services/CommandRegistry.php`) for the first command whose `canHandle()` matches; falls back to `Error` when nothing matches.
4. `CommandService::executeCommand()` invokes the command and replies via the LINE bot client built by `LineClientFactory` (`app/Factories/LineClientFactory.php`).

### Command Pattern (`app/KeyWords/`)
Each divination service implements `Command` (`app/KeyWords/Command.php`):
- `canHandle(string $message): bool` — keyword match used by `CommandRegistry`
- `handle(MessageEvent $event): array` — primary entry point
- `getDescription(): string`
- `replyCommand(): array` — **deprecated**, kept for `SheShiController` debug routes; new code should call `handle()`

Registered defaults (in `CommandRegistry::registerDefaultCommands()`):
- `QiGua` — "起卦" — I-Ching divination (coin/yarrow-stalk simulation)
- `ShengSheQian` — "抽神社籤" — Temple fortune stick
- `Lottery` — "大樂透號碼" — Taiwan Big Lottery statistics
- `MonteCarlo` — "蒙地卡羅預測" — Monte Carlo prediction
- `XiaoLiuRen` — "小六壬" — 小六壬六宮占卜（依當下農曆月日時推大安/留連/速喜/赤口/小吉/空亡，回月/日/時三宮）
- `Error` — default fallback when no command matches (returned by registry, not registered)

To add a new divination, implement `Command` in `app/KeyWords/` and register it in `CommandRegistry::registerDefaultCommands()`.

### Data Sources
- `yiJing/yiJing.json` - I-Ching hexagram interpretations and meanings
- `yiJing/Divination.json` - Additional divination data
- `BigLottery` model - Taiwan lottery historical data for statistical analysis

### Configuration
- LINE Bot credentials configured in `config/line.php`
- Database supports both main app data and separate lottery database
- Chinese database name "天道" indicates traditional Chinese cultural focus

## Key Implementation Notes

### I-Ching Algorithm
The QiGua class implements traditional yarrow stalk divination simulation:
- Uses 49 "stalks" (大衍) with mathematical probability calculations
- Generates changing lines (爻變) to determine hexagram transformations
- Returns both original hexagram (本卦) and transformed hexagram (變卦) when applicable

### Xiao Liu Ren (小六壬) Algorithm
The `XiaoLiuRen` class uses `6tail/lunar-php` to convert system time to lunar month/day + 時辰序 (0-based: 子=0...亥=11), then computes three palaces:
- 月宮 = (abs(lunarMonth) - 1) % 6
- 日宮 = (月宮 + lunarDay - 1) % 6
- 時宮 = (日宮 + 時辰序) % 6

Six palaces (cyclic order): **大安 → 留連 → 速喜 → 赤口 → 小吉 → 空亡**, defined in `data/xiaoLiuRen/palaces.json` with 事業/愛情/出行/失物/疾病/求財 aspects per palace. Replies as 3 separate `TextMessage` (one per palace).

`cast()` is intentionally `public` to support deterministic unit testing with fixed `DateTime` fixtures — do not mark private without updating tests.

### Lottery Analysis
The Lottery class provides statistical analysis across multiple time periods:
- Last 10 draws, 1 year, 3 years, 5 years, and historical data
- Identifies most/least frequent numbers for each period
- Uses Taiwan Minguo calendar system (民國年) for date calculations

### LINE API Integration
- LINE Bot SDK `linecorp/line-bot-sdk:^9.5` for webhook parsing and message sending
- Bot client construction is centralized in `LineClientFactory` — do not instantiate the LINE SDK directly elsewhere
- Webhook signature verification happens in two places: the `LineBotWebHook` middleware on the route, and again inside `EventProcessor` via `EventRequestParser`
- Credentials come from `config/line.php` (`channel_secret`, `channel_token`)