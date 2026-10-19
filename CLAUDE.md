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
- `XiaoLiuRen` — "小六壬" — 小六壬六宮占卜（單獨關鍵字依農曆月日時起卦；`小六壬 N1 N2 N3` 依三個 1–100 整數報數起卦）
- `Tarot` — "每日塔羅" / "塔羅三牌陣" — RWS 塔羅占卜，每日一張回 ImageMessage+TextMessage，三牌陣回 FlexCarousel+TextMessage
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
`XiaoLiuRen` 支援 `小六壬`、`起小六壬`、`六壬` 三種前綴；比對前去除頭尾 ASCII 空白與全形空格。關鍵字後須有分隔符才辨識為報數模式，`六壬神課`、`小六壬435` 不匹配。

兩種模式共用私有 `castNumbers()`，六宮索引依序為 **大安 → 留連 → 速喜 → 赤口 → 小吉 → 空亡**（0–5）：
- 初宮 = `(N1 - 1) % 6`
- 中宮 = `(初宮 + N2 - 1) % 6`
- 末宮 = `(中宮 + N3 - 1) % 6`

單獨輸入關鍵字時，使用 `6tail/lunar-php` 取得當下農曆資訊，以 `abs(農曆月)`、農曆日、`時辰序 + 1` 作為三數（時辰序為子=0…亥=11）。時間模式維持三則 `TextMessage`：時宮（主斷）、日宮（雙宮並斷）、月宮（整體背景），順序定義於 `REPLY_ORDER`，起算文字維持農曆月、農曆日、時辰與原本的 0-based 序號。

報數用法為 `小六壬 N1 N2 N3`，例如 `小六壬 4 3 5`。必須恰好三個純數字字串，每數為 1–100；允許前置零、全形數字。分隔符為半形空格、全形空格、`,`、`，`、`、`，可混用及連續使用；換行與 Tab 不算分隔符。無效輸入只回覆一則用法提示。報數結果不依賴時間，依序回覆末宮（主斷）、中宮（雙宮並斷）、初宮（整體背景），起算文字分別是第三數、第二數、第一數。

兩模式都沿用 `data/xiaoLiuRen/palaces.json`，每宮包含五行／方位／神煞／主數／卦辭及事業／愛情／出行／失物／疾病／求財六面向。`cast(DateTime)` 保持公開，回傳 `indices` 為 `[月, 日, 時]` 順序及三個 `header`；`replyCommand()` 保持時間模式，供舊呼叫端使用。

### Tarot
The `Tarot` class provides two divination flows backed by `data/tarot/cards.json` (78 cards × upright/reversed):
- `每日塔羅` → `drawDaily()` returns `[ImageMessage, TextMessage]` (2 messages)
- `塔羅三牌陣` → `drawThreeCard()` returns `[FlexMessage(carousel of 3 bubbles), TextMessage]` (2 messages, well within LINE's 5-message reply cap)

Card images live under `public/img/tarot/00.jpg ~ 77.jpg` (Pamela Colman Smith 1909, public domain). URL is generated by `asset('img/tarot/NN.jpg')` so production needs `APP_URL` set to a HTTPS host LINE can reach.

`pickIndex` / `pickThreeUnique` / `pickReversed` are `protected` to support deterministic testing via subclass override — do not inline `random_int` calls without preserving this seam.

Card data structure (in `cards.json`): each card has `keywords` (modern Traditional Chinese), `summary` (~25 chars), `meaning` (~100 chars, weaving 事業/感情/求財), and `waite_original` (English keyword list directly from A.E. Waite's 1911 *The Pictorial Key to the Tarot*, PD, kept for historical fidelity but not surfaced in LINE replies).

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
