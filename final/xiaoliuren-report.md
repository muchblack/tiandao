# 新增小六壬推算法 — Final Report

**Generated**: 2026-05-04
**Branch**: master
**Task source**: `/tp 計劃一下加入小六壬推算法`

## Task Overview

於 Laravel 11 / PHP 8.4 的天道 LINE bot 專案新增小六壬命令。依系統當下時間取農曆月日 + 時辰，順推大安/留連/速喜/赤口/小吉/空亡六宮，回月/日/時三宮各含事業/愛情/出行/失物/疾病/求財六面向解釋。

## Implementation Summary

| 領域 | 產出 |
|------|------|
| 套件 | `6tail/lunar-php v1.4.7` |
| 新增程式碼 | `app/KeyWords/XiaoLiuRen.php`（95 行） |
| 新增資料 | `data/xiaoLiuRen/palaces.json`（六宮 × 六面向） |
| 新增測試 | `tests/Unit/KeyWords/XiaoLiuRenTest.php`（18 條 / 59 斷言） |
| 修改既有 | `CommandRegistry`、`CommandRegistryTest`、`EventProcessorTest`、`CLAUDE.md`、`README.md` |
| Commits | 8 個（每步驟一個 commit） |

## Steps Executed

| # | Step | Commit |
|---|------|--------|
| 1 | 引入農曆套件 6tail/lunar-php | `d8ea5b8` |
| 2 | 建立六宮資料表 palaces.json | `6c32130` |
| 3 | 實作 XiaoLiuRen Command 類別 | `78e3b09` |
| 4 | 於 CommandRegistry 註冊 XiaoLiuRen | `60203cd` |
| 5 | 撰寫 XiaoLiuRen 單元測試 | `bf6ad0c` |
| 6 | 補強 CommandRegistryTest / EventProcessorTest | `e227efe` |
| 7 | 更新 CLAUDE.md 與 README.md | `f39a38d` |
| 8 | 回歸全測試套件 | `90644ef` |

## Key Decisions

1. **時辰來源用系統時間**（皇上選 Recommended）— 與既有 QiGua/ShengSheQian 一致，無需處理使用者輸入解析。
2. **農曆轉換用 `6tail/lunar-php`**（皇上選 Recommended）— 直接提供 `getTimeZhiIndex()` 取時辰序，避免自寫 23:00-00:59 跨日邏輯。
3. **回覆三宮 + 六面向**（皇上選 Recommended）— 風格與 ShengSheQian 一致，使用者可看完整。
4. **`cast()` 設為 public**（執行時決策）— 為了測試固定 DateTime 釘死算法輸出。CLAUDE.md 已註明禁止改 private 而不更新測試。
5. **時辰序 0-based 修正**（執行時發現）— 原 plan 假設 1-based，套件實際為 0-based（子=0），算法調整 `時宮 = (日宮 + index) % 6`，閏月以 `abs()` 處理。

## Issues Encountered & Resolutions

| Issue | Resolution |
|-------|-----------|
| `data/` 整個被 .gitignore 排除（升級時的副作用） | Step 2 順手收斂為 `/data/knowledge-graph.db*`，僅排除本地 MCP 工具 DB |
| `getTimeZhiIndex()` 0-based vs plan 假設 1-based | Step 1 即測時發現，修正設計後寫入 plan_log，Step 3 算法直接套正確值 |
| Reviewer (codex/mlx) 全程未掛載 | 依 all-plan 規範跳過 Phase 4，皇上計劃審查時親裁通過 |

## Final Verification Results

- ✅ `composer show 6tail/lunar-php` v1.4.7
- ✅ `app/KeyWords/XiaoLiuRen.php` 實作 Command 介面
- ✅ `CommandRegistry::registerDefaultCommands()` 包含 `new XiaoLiuRen()`
- ✅ 三個關鍵字（小六壬/起小六壬/六壬）皆能路由
- ✅ 回覆 3 條 TextMessage，分別為月宮/日宮/時宮，每宮含六面向
- ✅ `php artisan test`：**56 passed (114 assertions)**
- ✅ CLAUDE.md 與 README.md 已更新

### 測試覆蓋率變化

| 階段 | Tests | Assertions |
|------|-------|------------|
| 升級 L11 後基線 | 34 | 49 |
| 新增小六壬後 | **56** | **114** |
| 增量 | +22 | +65 |

## Recommendations

非阻塞的後續觀察：

1. **PHPUnit 12 deprecation**：`@dataProvider` doc-comment 將於 PHPUnit 12 棄用。建議擇日將既有 `CommandRegistryTest`、`EventProcessorTest`、`XiaoLiuRenTest` 改為 `#[DataProvider]` PHP 8 attributes。
2. **真機驗證**：自動化測試 mock 了 LINE 回覆。建議將 webhook 部署至 staging，於 LINE 用戶端實測「小六壬」三個關鍵字回覆是否正確、時區是否真的取 Asia/Taipei、連續三條 TextMessage 是否都送達。
3. **小六壬卦意內容**：`palaces.json` 為傳統卦辭 + 現代解釋，文化詮釋可能因流派而異；可後續迭代調整，不影響演算法。
4. **未來擴充**：`6tail/lunar-php` 已備齊干支、節氣、八字所需資料。若日後新增「八字命盤」等命令可重用。

---

**任務狀態**: ✅ 完成（驗收條件全綠，無 follow-up 任務）
