# Tarot 牌面圖檔說明

## 來源與著作權狀態

本目錄下的 78 張塔羅牌面圖（`00.jpg` ~ `77.jpg`）皆取自
[Wikimedia Commons](https://commons.wikimedia.org/)，為 Rider-Waite-Smith 塔羅
1909 年原版（**RWS 1909 Edition**）。

| 項目 | 內容 |
|------|------|
| 原作者 | Pamela Colman Smith (1878–1951) |
| 首次出版 | 1909 年（William Rider & Son, London） |
| 設計指導 | Arthur Edward Waite (1857–1942) |
| 著作權狀態 | **公有領域（Public Domain）** |
| 取得來源 | Wikimedia Commons（Special:FilePath） |
| 取得日期 | 2026-05 |
| Wikimedia 授權標籤 | `{{PD-old}}` / `{{PD-1923}}` |

## 公有領域法源

依各地著作權法，本作品已逾保護期：

| 管轄區 | 規則 | RWS 1909 狀態 |
|--------|------|---------------|
| 台灣 | 個人著作 life+50 | PD 自 2002（Smith 1951 卒） |
| 歐盟 / 英國 | life+70 | PD 自 2022 |
| 美國 | 1928 前出版 | PD 自 1985 |
| 全球 | — | **2026 年完全公有領域** |

## 重要區分

⚠️ **僅限 1909 原版，非後代重繪版**：
- ✅ Pamela Colman Smith 1909 原版（本目錄收錄）
- ❌ U.S. Games Systems 1971 重繪上色版（**仍有著作權**）
- ❌ U.S. Games "Pamela A" 1971 改版（**仍有著作權**）

確認方式：原作右下角有 PCS 簽名字樣（小圓圈內 PCS），且色調為 1909 原版的礦物顏料感。

## ID 對應表

| ID 範圍 | 牌組 | Wikimedia 檔名 |
|---------|------|---------------|
| 00–21 | 大阿爾克那 | `RWS_Tarot_NN_Name.jpg` |
| 22–35 | 權杖 Wands 01–14 | `Wands01.jpg` ~ `Wands14.jpg` |
| 36–49 | 聖杯 Cups 01–14 | `Cups01.jpg` ~ `Cups14.jpg` |
| 50–63 | 寶劍 Swords 01–14 | `Swords01.jpg` ~ `Swords14.jpg` |
| 64–77 | 錢幣 Pents 01–14 | `Pents01.jpg` ~ `Pents14.jpg` |

完整對應參見 `data/tarot/cards.json` 內每張卡片的 `image` 欄位。

## 處理流程

下載與壓縮由 `scripts/fetch-tarot-images.sh` 執行：

1. 自 Wikimedia Commons `Special:FilePath` 端點下載原始圖（~700KB-1.2MB / 張）
2. 以 `sips`（macOS）/ `magick` / `convert` 壓縮至 ≤1024px、JPEG quality 85
3. 輸出至本目錄 `NN.jpg`

腳本可重複執行；已存在的檔案會跳過。

## 使用範圍

本專案為非商業性質之 LINE bot 占卜服務：
- 牌面圖透過 Laravel `public/` 目錄由 Nginx 直接服務
- 經 LINE Messaging API 以 `ImageMessage` / `FlexMessage` 形式回覆使用者
- **不以塔羅牌面圖作為商品出售**（此舉雖法律上可行，但本專案不採此模式）

## 使用建議與致意

雖 RWS 1909 已屬公有領域、法律上可自由使用，但仍向：
- **Pamela Colman Smith**（生前未獲應有報酬與認可）
- **A. E. Waite**（提出符號設計）
- **Wikimedia Commons 上傳者與審核者**（提供高品質掃描）

致以敬意。建議所有使用 RWS 牌面圖的下游專案在 README 或關於頁面標明來源。
