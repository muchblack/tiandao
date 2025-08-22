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
- `php artisan lottery:test` - Test lottery functionality
- `php artisan monte-carlo:test` - Test Monte Carlo prediction

## Architecture Overview

### LINE Bot Integration
The application primarily functions as a LINE webhook receiver:

- **Entry Point**: `LineCallBackController@index` receives webhooks from LINE platform
- **Message Processing**: `LineService` parses incoming messages and routes to appropriate handlers
- **Command Pattern**: Each divination service implements the `Command` interface with a `replyCommand()` method

### Core Services Structure

#### KeyWords Directory (`app/KeyWords/`)
Contains divination command handlers:
- `QiGua.php` - I-Ching divination using traditional coin-throwing simulation
- `Lottery.php` - Taiwan Big Lottery number analysis and statistics
- `MonteCarlo.php` - Monte Carlo prediction methods
- `ShengSheQian.php` - Temple fortune stick divination
- `Error.php` - Default error handler for unrecognized commands

#### Services Directory (`app/Services/`)
- `LineService.php` - Handles LINE API integration and webhook processing
- `CommandService.php` - Generic command execution service using dependency injection

### Message Flow
1. LINE platform sends webhook to `/` route
2. `LineCallBackController` delegates to `LineService`
3. `LineService` parses message and matches against command keywords:
   - "起卦" → QiGua (I-Ching)
   - "抽神社籤" → ShengSheQian (Temple fortune)
   - "大樂透號碼" → Lottery (Lottery analysis)
   - "蒙地卡羅預測" → MonteCarlo (Monte Carlo prediction)
4. `CommandService` executes the matched command and sends reply via LINE API

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

### Lottery Analysis
The Lottery class provides statistical analysis across multiple time periods:
- Last 10 draws, 1 year, 3 years, 5 years, and historical data
- Identifies most/least frequent numbers for each period
- Uses Taiwan Minguo calendar system (民國年) for date calculations

### LINE API Integration
- Uses LINE Bot SDK v9.5 for webhook processing and message sending
- Supports text message responses with structured fortune-telling content
- Webhook signature verification for security