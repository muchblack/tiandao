# 天道 Tiandao

以 Laravel 10 打造的 LINE 機器人，提供傳統中華占卜服務。使用者於 LINE 對話中輸入指定關鍵字，即可取得對應的占卜結果。

## 功能

| 指令 | 服務 | 說明 |
|------|------|------|
| `起卦` | I-Ching（QiGua） | 大衍蓍草法模擬，回傳本卦與變卦 |
| `抽神社籤` | 神社籤（ShengSheQian） | 隨機抽取神社籤詩 |
| `大樂透號碼` | 大樂透統計（Lottery） | 近 10 期、1／3／5 年、歷史資料的冷熱號分析（民國年） |
| `蒙地卡羅預測` | 蒙地卡羅（MonteCarlo） | 以蒙地卡羅法推估號碼分佈 |
| `小六壬` / `起小六壬` / `六壬` | 小六壬（XiaoLiuRen） | 依當下農曆月日時推大安/留連/速喜/赤口/小吉/空亡；依傳統「以時宮為主、日宮+時宮雙宮並斷」回三段：時宮（主斷）/日宮（雙宮並斷）/月宮（整體背景），各含五行/方位/神煞/主數/卦辭與事業/愛情/出行/失物/疾病/求財六面向 |
| `每日塔羅` | 塔羅單牌（Tarot） | 抽 1 張 + 50% 正逆位，回 ImageMessage 牌面圖 + TextMessage 牌意 |
| `塔羅三牌陣` | 塔羅三牌陣（Tarot） | 抽 3 張不重複 + 各 50% 正逆位，回 FlexCarousel（過去/現在/未來三 bubbles）+ TextMessage 詳細解讀 |

非以上關鍵字會由 `Error` 命令回覆預設訊息。

## 環境需求

- PHP `^8.1`
- Laravel `^10.10`
- Node.js（前端資產建置）
- LINE Bot SDK `linecorp/line-bot-sdk:^9.5`
- 農曆／時辰計算 `6tail/lunar-php:^1.4`（小六壬使用）

## 安裝與啟動

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

於 `.env` 設定資料庫與 LINE 憑證；LINE 相關設定統一收斂於 `config/line.php`（`channel_secret`、`channel_token`）。

前端資產：

```bash
npm run dev      # 開發
npm run build    # 正式
```

## LINE Webhook 設定

於 LINE Developers Console 將 Webhook URL 指向：

```
POST https://<your-domain>/api/message
```

該路由由 `LineBotWebHook` middleware 進行簽章驗證，`EventProcessor` 內部會再以 `EventRequestParser` 二次驗證。

本地 `GET /` 與 `GET /shenshe` 為除錯端點，會直接 `dump()` 一次占卜結果，不走 LINE 流程。

## 架構

```
Webhook (POST /api/message)
        │
LineCallBackController ──► LineService ──► EventProcessor
                                                │
                                          CommandRegistry
                                                │
                          (canHandle 比對，無命中則 Error)
                                                │
                                          CommandService
                                                │
                                       LineClientFactory ──► LINE Reply
```

- `app/Http/Controllers/LineCallBackController.php`：webhook 入口
- `app/Services/LineService.php`：對外服務門面
- `app/Services/EventProcessor.php`：解析事件、過濾文字訊息、錯誤記錄
- `app/Services/CommandRegistry.php`：命令註冊與比對
- `app/Services/CommandService.php`：執行命令並回覆
- `app/Factories/LineClientFactory.php`：LINE Bot 客戶端工廠（請勿在他處直接 new SDK）
- `app/KeyWords/`：各占卜命令實作

### 新增占卜命令

1. 於 `app/KeyWords/` 新增類別並實作 `Command` 介面：
   - `canHandle(string $message): bool`
   - `handle(MessageEvent $event): array`
   - `getDescription(): string`
   - `replyCommand(): array`（已棄用，僅供除錯路由使用）
2. 於 `CommandRegistry::registerDefaultCommands()` 註冊。

## 測試與工具

```bash
php artisan test                 # 全部測試
vendor/bin/phpunit tests/Unit    # 單元測試
vendor/bin/phpunit tests/Feature # 功能測試
vendor/bin/pint                  # 程式碼格式化
```

自訂 artisan 指令（離線測試占卜邏輯，不經 LINE）：

```bash
php artisan lottery      # 測試大樂透統計
php artisan montecarlo   # 測試蒙地卡羅預測
```

## 資料來源

- `yiJing/yiJing.json`：易經卦象與卦辭
- `yiJing/Divination.json`：占卜輔助資料
- `data/xiaoLiuRen/palaces.json`：小六壬六宮資料（卦辭 + 六面向解釋）
- `data/tarot/cards.json`：塔羅 78 張牌庫（含 keywords / summary / meaning / waite_original）
- `public/img/tarot/`：塔羅牌面圖 78 張（RWS 1909 PD，自 Wikimedia Commons 取得，詳見該目錄 README）
- `scripts/fetch-tarot-images.sh`：塔羅圖片下載腳本（macOS sips / ImageMagick 自動偵測）
- `BigLottery` model：台灣大樂透歷史開獎資料（統計分析用）

## 授權

依 Laravel 框架使用 [MIT License](https://opensource.org/licenses/MIT)。
