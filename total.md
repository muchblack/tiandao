# LineService 重構總結

## 重構目標
將原本違反 SOLID 原則的 LineService 重構為更易維護、擴展的架構。

## 主要變更

### 1. 新增檔案
```
app/Services/CommandRegistry.php     - 命令註冊與管理
app/Services/EventProcessor.php     - Line 事件處理邏輯
app/Factories/LineClientFactory.php - Line API 客戶端工廠
```

### 2. 修改檔案
```
app/Services/LineService.php        - 簡化為 webhook 入口點
app/Services/CommandService.php     - 重構為純回覆服務
app/KeyWords/Error.php              - 修正實作 Command 介面
```

## 架構改進

### Before (舊架構)
```
LineService → 直接處理所有邏輯 (SRP 違反)
├── 建立 Line 客戶端
├── 解析 webhook 事件  
├── 硬編碼命令選擇 (OCP 違反)
└── 發送回覆
```

### After (新架構)
```
LineService → webhook 入口
└── EventProcessor → 事件處理
    ├── CommandRegistry → 命令管理
    ├── Command 介面 → 統一命令處理
    └── CommandService → 回覆發送
```

## SOLID 原則改進

### ✅ Single Responsibility Principle (SRP)
- `LineService` 現在只負責 webhook 入口點
- `EventProcessor` 專門處理事件邏輯
- `CommandRegistry` 管理命令註冊與查找
- `CommandService` 專門處理回覆邏輯

### ✅ Open/Closed Principle (OCP)
- 透過 `CommandRegistry` 可輕鬆新增命令
- 不需修改現有程式碼即可擴展功能

### ✅ Dependency Inversion Principle (DIP)
- 使用依賴注入，可在測試時模擬依賴
- `LineClientFactory` 集中管理 Line API 客戶端建立

### ✅ 介面一致性
- `Error` 類別現在正確實作 `Command` 介面

## 部署注意事項

1. **測試環境先部署**: 確保 Line webhook 功能正常
2. **檢查 config/line.php**: 確認設定檔路徑正確
3. **命令擴展**: 新增命令只需實作 `Command` 介面並註冊到 `CommandRegistry`
4. **錯誤處理**: 新增了 try-catch 和日誌記錄
5. **向後相容**: `Error` 類別功能保持不變，只是介面實作方式調整

## 測試建議

```bash
# 1. 檢查語法錯誤
composer install
php artisan config:cache

# 2. 測試 webhook 端點
# 發送測試訊息到 Line Bot 確認回覆正常

# 3. 檢查日誌
tail -f storage/logs/laravel.log
```

## CommandRegistry 使用方式

### 基本使用
```php
// 建立註冊器實例
$commandRegistry = new CommandRegistry();

// 註冊新命令
$commandRegistry->register(new 起卦Command());
$commandRegistry->register(new 抽神社籤Command());
$commandRegistry->register(new 大樂透Command());

// 根據訊息內容找到對應的命令
$command = $commandRegistry->findCommand('起卦');
```

### 新增命令範例

1. **建立命令類別**：
```php
// app/KeyWords/起卦.php
class 起卦 implements Command
{
    public function canHandle(string $message): bool
    {
        return in_array($message, ['起卦', '占卜', '問卦']);
    }

    public function handle(MessageEvent $event): array
    {
        return [(new TextMessage(['text' => '您的卦象是...']))->setType('text')];
    }

    public function getDescription(): string
    {
        return '易經起卦功能';
    }
}
```

2. **註冊到 CommandRegistry**：
```php
// 在 CommandRegistry.php 的 registerDefaultCommands() 方法中
private function registerDefaultCommands(): void
{
    $this->register(new \App\KeyWords\起卦());
    $this->register(new \App\KeyWords\抽神社籤());
    $this->register(new \App\KeyWords\大樂透());
}
```

## 擴展優勢

### 新增命令只需兩步驟：
1. 建立實作 `Command` 介面的類別
2. 在 `CommandRegistry::registerDefaultCommands()` 中註冊

### 架構優勢
- ✅ 更容易測試（可注入模擬物件）
- ✅ 更容易擴展新命令
- ✅ 錯誤處理更完善
- ✅ 程式碼更清晰且符合 SOLID 原則
- ✅ 依賴注入支援
- ✅ 統一的命令介面

## 總結

此重構提升了程式碼的可測試性、可維護性和擴展性，完全符合 SOLID 原則。新架構讓開發者可以輕鬆新增功能而不影響現有程式碼，是一個更專業且可持續發展的解決方案。