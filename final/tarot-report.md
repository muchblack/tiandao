# 加入塔羅占卜 — Final Report

**Generated**: 2026-05-04
**Branch**: master
**Task source**: `/tp 加入塔羅占卜`

## Task Overview

於 Laravel 11 / PHP 8.4 的天道 LINE bot 新增第六個占卜命令——塔羅。雙關鍵字（每日塔羅／塔羅三牌陣）對應兩種抽牌玩法，並引入 ImageMessage 與 FlexMessage Carousel 兩種視覺訊息形式。78 張牌庫資料親撰，牌面圖採用 RWS 1909 公有領域版本。

## Implementation Summary

| 領域     | 產出                                                             |
| -------- | ---------------------------------------------------------------- |
| 牌庫資料 | `data/tarot/cards.json`（78 張 × 4 欄 × 2 方向）                 |
| 牌面圖   | `public/img/tarot/00.jpg ~ 77.jpg`（25MB total）                 |
| 下載腳本 | `scripts/fetch-tarot-images.sh`（macOS sips / ImageMagick 通用） |
| PD 聲明  | `public/img/tarot/README.md`                                     |
| Command  | `app/KeyWords/Tarot.php`（225 行）                               |
| 單元測試 | `tests/Unit/KeyWords/TarotTest.php`（19 條 / 130 斷言）          |
| 修改既有 | `CommandRegistry`、`Error.php`、整合測試、CLAUDE.md、README.md   |

## Steps Executed

| #    | Step                            | Commit     |
| ---- | ------------------------------- | ---------- |
| 1    | 撰寫 78 張塔羅卡資料 cards.json | `03f8578`  |
| 2-3  | 圖片下載腳本 + 78 張 PD 圖      | (combined) |
| 4-5  | PD README + Tarot Command       | (combined) |
| 6-8  | 註冊 + 完整測試覆蓋             | `6178a55`  |
| 9-10 | Error.php / 文件                | `cca8d94`  |
| 11   | 回歸全測試套件                  | (verified) |

## Key Decisions

1. **78 張全牌庫 + 含正逆位**（皇上選 Recommended）— 完整塔羅體驗。
2. **B-Inspired + waite_original 雙欄策略**——
    - 對外（LINE）：現代繁體中文 keywords/summary/meaning，使用者體驗為主
    - 對內（資料層）：waite_original 直引 A.E. Waite 1911 PKT（PD）原典關鍵字，供查證
3. **三牌陣 Flex Carousel 設計**——避開 LINE replyMessage 5 條上限。
    - 每日塔羅：ImageMessage + TextMessage = 2 條
    - 三牌陣：FlexMessage(carousel of 3 bubbles) + TextMessage = 2 條
4. **圖片 ID-檔名映射規則**——
    - 00-21 = Major Arcana（檔名 `RWS_Tarot_NN_Name.jpg`）
    - 22-77 = Minor Arcana 14×4（檔名 `<Suit>NN.jpg`，Wands/Cups/Swords/Pents）
5. **Random source seam**——`pickIndex` / `pickThreeUnique` / `pickReversed` 設為 protected，便於測試覆寫釘死隨機。CLAUDE.md 已註明禁止內聯為 `random_int` 而不維持此 seam。
6. **三牌陣不重複守護**——50 次抽樣統計測試，確保 `array_rand` 邏輯正確。

## Issues Encountered & Resolutions

| Issue                         | Resolution                                                    |
| ----------------------------- | ------------------------------------------------------------- |
| 主機無 ImageMagick            | 偵測 magick / convert / sips 三種工具，主機用 macOS 內建 sips |
| Sacred Texts 被 Cloudflare 擋 | 改用 Wikimedia Commons API + 直接 URL 命名規則驗證            |
| Wikimedia 限速（429）         | 1.5s sleep + 指數退避重試 3 次                                |
| 權杖六 image 筆誤（26→27）    | 結構驗證腳本即時抓出並修正                                    |
| Reviewer 全程未掛載           | 依 all-plan flow Notes 跳過 Phase 4，皇上計劃時親裁通過       |

## Final Verification Results

- ✅ `data/tarot/cards.json`：78 張完整、4 欄齊全、ID-image 對應全綠
- ✅ `public/img/tarot/`：78 張、25MB 總量、無超大檔
- ✅ `public/img/tarot/README.md`：PD 聲明與 Wikimedia 來源齊全
- ✅ `app/KeyWords/Tarot.php`：實作 Command 介面、煙霧測試通過
- ✅ `CommandRegistry`：註冊 Tarot、計數 5→6
- ✅ 兩個關鍵字（每日塔羅 / 塔羅三牌陣）皆路由
- ✅ `php artisan test`：**79 passed (264 assertions)**
- ✅ Error.php / CLAUDE.md / README.md 已更新
- ⏳ Manual gate：staging 真機驗證待行

### 測試覆蓋率變化

| 階段             | Tests  | Assertions |
| ---------------- | ------ | ---------- |
| 小六壬完成後基線 | 56     | 129        |
| 塔羅完成後       | **79** | **264**    |
| 增量             | +23    | +135       |

## Recommendations

1. **真機驗證**（manual gate）：建議部署 staging 後實機輸入「每日塔羅」「塔羅三牌陣」確認：
    - 78 張圖片 LINE 端能正常載入（驗證 APP_URL 為 HTTPS 公開可達）
    - FlexCarousel 三 bubble 排版正確、aspectRatio 2:3 不裁切
    - TextMessage 卦意完整、不超 5000 字
2. **Image cache 防護**：日後若改圖，URL 加版本參數（`asset('img/tarot/NN.jpg?v=2')`）強刷 LINE 客戶端快取。
3. **PHPUnit 12 deprecation**：仍有舊 `@dataProvider` doc-comment 警告（既存問題，本次新增的 TarotTest 也沿用），可擇日統一改 `#[DataProvider]` PHP 8 attribute。
4. **小阿爾克那 Page 譯名**：本次採「侍者」，與權杖／聖杯／寶劍／錢幣四花色一致。若皇上偏好「侍從」可全域替換。
5. **未來擴充**：本架構天然支援其他塔羅陣法（凱爾特十字 10 張、關係陣 5 張等）。增加新陣法只需新增關鍵字 + drawXxx() 方法。

---

**任務狀態**: ✅ 完成（驗收條件 11/12，僅缺 manual gate 真機驗證）
