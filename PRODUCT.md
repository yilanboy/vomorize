# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

成年與在職學習者、自我提升者、以及希望穩固日常生活與職場英語溝通能力的英語學習者。使用者在通勤零碎時間或專注學習時段，需要一套具備科學根據、無壓力、不反覆遺忘的單字記憶系統。

## Product Purpose

Vomorize 是一套基於艾賓浩斯遺忘曲線與間隔重複系統 (Spaced Repetition System, SRS) 的高品質現代英文單字學習平台。目標是透過精準的間隔複習節奏、即時語音發音與多階段回想測驗，協助學習者高效率建立並長期保留 7,000 個高頻實用單字。

## Positioning

專注於精心策劃的日常生活 7,000 核心單字，以極簡、乾淨、無干擾的現代 SaaS 介面呈現。不同於傳統補習班或升學導向工具，Vomorize 不使用升學考試術語，不設置複雜的 CEFR 等級分類，以純粹直觀的 7 大等級與 8-Card 均衡網格，提供零摩擦、免登入即可立即體驗的沉浸式學習路徑。

## Operating Context

- 桌面版與行動版現代瀏覽器（跨平台 Web SPA，採用 Inertia.js v3 + Svelte 5 + Tailwind CSS v4）。
- 每日 5～15 分鐘的短節奏高效學習，支援鍵盤快捷鍵、觸控滑動與即時語音朗讀（Web Speech API / 音訊資源）。
- 支援淺色 (Light) 與深色 (Dark) 低視覺疲勞模式。

## Capabilities and Constraints

- **7,000 單字固定分層**：7 個難度等級，每個等級 1,000 單字（100 組，每組 10 字）。
- **無自訂單字功能**：專注官方題庫，確保所有單字皆具備完整的 IPA 音標、詞性、繁中釋義、例句與發音支援。
- **嚴禁提及升學考詞彙**：全站嚴格禁止出現「大考中心」、學測、指考等升學考試字眼。
- **嚴禁 CEFR 標籤**：完全移除 A1、B2、C1 等符號標籤。
- **嚴禁紫色系**：全站色彩系統採用核心純藍 (`#2563eb`)、成就翡翠綠 (`#059669`) 與石板灰/鋅灰中性色，警示使用紅 (`#dc2626`)，嚴格禁止紫色/紫羅蘭色（Purple / Violet）。
- **最小字級約束**：全站最低字階為 `text-sm` (14px)，嚴禁使用 `text-xs` (12px)。
- **國際化 (i18n)**：全站文案統一管理於 `lang/{locale}/ui.php`，嚴禁在元件中硬編碼顯示字串。

## Brand Commitments

- **名稱**：Vomorize（Vocabulary + Memorize）。
- **視覺氣質**：洗鍊現代、極簡俐落、科技感與學術嚴謹兼具，無廉價遊戲化雜訊。
- **佈局約束**：首頁與等級頁面採用 4 欄 × 2 列（共 8 格）的平衡網格，第 8 格為專屬導航卡「瀏覽全部等級」。

## Evidence on Hand

- Vomorize 英文單字學習平台 系統規格書 v1.0.0 (Linear Document `34938c1662af`)
- UI/UX 設計系統原則與協作規範 (Linear Document `3364b08d5ce0`)
- Google Stitch 專案 `4567702977063161754` 視覺原型與畫面（特別是 Desktop Landing Page `77930f29beec4c74b021731db1f506c2`）
- 既有實作之 Svelte 5 前端頁面與元件 (`resources/js/pages/Welcome.svelte`)

## Product Principles

1. **科學記憶優於死記硬背 (Cognitive Science Over Cramming)**：以數據可視化與艾賓浩斯遺忘曲線對比，證實間隔重複的 95%+ 長期記憶保留率。
2. **零摩擦即刻體驗 (Zero Friction Entry)**：訪客在首頁即可直接試聽發音、翻閱單字卡、探索等級，無需註冊即可感受核心價值。
3. **極致清晰與專注 (Radical Clarity & Restraint)**：剔除冗餘裝飾與干擾性橫幅，維持純淨排版與高易讀性。
4. **無障礙與跨情境易讀 (Accessible by Design)**：強制最小 14px 字階、高對比度、雙色主題原生支援。

## Accessibility & Inclusion

- 全站最低字階限制為 14px (`text-sm`)，確保通勤或小螢幕裝置上的舒適閱讀。
- 色彩對比度符合 WCAG AA 級標準，深淺主題平滑切換。
- 全鍵盤可操作性與焦點狀態清晰呈現。
