---
name: Vomorize
description: 高品質科學間隔記憶英文單字學習平台設計系統
colors:
    primary: '#2563eb'
    primary-hover: '#1d4ed8'
    primary-light: '#eff6ff'
    mastery: '#059669'
    mastery-light: '#ecfdf5'
    alert: '#dc2626'
    alert-light: '#fef2f2'
    neutral-bg: '#ffffff'
    neutral-card: '#ffffff'
    neutral-dark-bg: '#09090b'
    neutral-dark-card: '#18181b'
    neutral-border: '#e4e4e7'
    neutral-dark-border: '#27272a'
    text-primary: '#09090b'
    text-muted: '#71717a'
typography:
    display:
        fontFamily: "Inter, 'Noto Sans TC', sans-serif"
        fontSize: '3.75rem'
        fontWeight: 800
        lineHeight: 1.12
        letterSpacing: '-0.025em'
    headline:
        fontFamily: "Inter, 'Noto Sans TC', sans-serif"
        fontSize: '2.25rem'
        fontWeight: 800
        lineHeight: 1.25
        letterSpacing: '-0.02em'
    title:
        fontFamily: "Inter, 'Noto Sans TC', sans-serif"
        fontSize: '1.25rem'
        fontWeight: 700
        lineHeight: 1.4
        letterSpacing: '-0.01em'
    body:
        fontFamily: "Inter, 'Noto Sans TC', sans-serif"
        fontSize: '1rem'
        fontWeight: 400
        lineHeight: 1.6
        letterSpacing: '0em'
    label:
        fontFamily: "Inter, 'Noto Sans TC', sans-serif"
        fontSize: '0.875rem'
        fontWeight: 600
        lineHeight: 1.4
        letterSpacing: '0.025em'
rounded:
    sm: '6px'
    md: '10px'
    lg: '16px'
    xl: '24px'
spacing:
    xs: '4px'
    sm: '8px'
    md: '16px'
    lg: '24px'
    xl: '32px'
    2xl: '48px'
components:
    button-primary:
        backgroundColor: '{colors.primary}'
        textColor: '#ffffff'
        rounded: '{rounded.md}'
        padding: '12px 24px'
    card-default:
        backgroundColor: '{colors.neutral-card}'
        rounded: '{rounded.lg}'
        padding: '24px'
---

# Design System: Vomorize

## Overview

**Creative North Star: "The Cognitive Precision Instrument (認知科學精密儀表)"**

Vomorize 的視覺識別定位為兼具學術嚴謹感與現代數位工具俐落感的單字記憶平台。介面捨棄廉價浮誇的遊戲化裝飾、漸層炫光與干擾性的行銷貼圖，專注於提供極致純淨、高易讀性與觸覺回饋明確的學習介面。

全站設計遵循「科學記憶、直覺操作、極致清晰」的核心準則。透過純粹的層次對比、緊湊舒適的留白與清晰的狀態反饋，讓學習者在每一次翻卡、發音與測驗中，體驗零負擔的心流狀態。

**Key Characteristics:**

- **純淨雙色焦點**：核心品牌純藍 (`#2563eb`) 引導主要行動與核心導航；成就翡翠綠 (`#059669`) 標誌 SRS 95%+ 長期記憶保留與測驗通關；警示紅 (`#dc2626`) 僅用於遺忘與錯誤校正。全站嚴格禁用任何紫色或紫羅蘭色（Purple / Violet）。
- **無障礙高易讀階層**：全站最低字階嚴格限制為 14px (`text-sm`)，嚴禁使用 12px (`text-xs`)，確保跨裝置與全年齡層的絕佳閱讀舒適度。
- **4×2 平衡 8 卡片網格**：等級與導覽頁面固定採用 4 欄 × 2 列的對稱佈局，第 8 格作為專屬導航卡，消除孤立元素。

## Colors

全站色彩系統以純淨冷調中性色為畫布，賦予單純明晰的語意分工：

- **Primary Blue (`#2563eb`)**：品牌核心色、主要行動按鈕 (CTA)、聚焦狀態、目標英文單字。
- **Mastery Emerald (`#059669`)**：長期記憶成就色、熟練回想按鈕、進度條填滿、測驗答對徽章。
- **Alert Red (`#dc2626`)**：遺忘臨界提示、記憶衰退曲線對照、測驗答錯即時校準。
- **Neutral Surface**：純白 (`#ffffff`) 與深黑灰 (`#09090b` / `#18181b`) 低刺激背景，支援深淺色無縫平滑切換。
- **禁忌色彩**：嚴禁使用 Purple、Violet、Indigo 或飽和彩虹漸層。

## Typography

- **字體選用**：英文字母、數字與 IPA 音標全面採用 `Inter`；繁體中文全面採用 `Noto Sans TC`。
- **最小字級約束**：最低字階為 `text-sm` (14px)。所有輔助說明、標籤、音標與徽章均不低於 14px。
- **標題風格**：大標題採用極度緊湊字距 (`tracking-tight` / `-0.025em`) 與堅定字重 (`font-extrabold`)，展現權威與信賴感。

## Layout

- **結構節奏**：各區塊之間維持充裕垂直節奏 (`py-16 sm:py-24` 至 `py-20 sm:py-28`)，群組內部採用緊湊親密性排列。
- **8 卡片平衡矩陣**：首頁與等級目錄統一採用 4 欄 × 2 列（lg 斷點）等高網格。
- **讀取寬度**：文章與說明文案限制在 65–75ch（`max-w-2xl` 至 `max-w-3xl`），確保最佳閱讀行長。

## Elevation & Depth

- **實體觸感**：陰影採用自然柔和的向下偏移（`shadow-md`, `shadow-xl shadow-slate-900/5`），杜絕無偏移的彩色發光暈圈。
- **邊框細節**：卡片與按鈕具備明確的細緻邊框 (`border border-border/80`)，在深淺模式下皆能精確勾勒元件邊界。

## Shapes

- **圓角規格**：小標籤與按鈕採用 `rounded-xl` (12px) 或 `rounded-full`；主要卡片與展示容器採用 `rounded-2xl` (16px) 或 `rounded-3xl` (24px)。
- **無幾何遮罩**：不使用偽圓形或多邊形裁切遮罩，保持直角邊界與標準圓角矩形之工業感。

## Components

- **互動單字卡 (Tactile Flashcard)**：仿實體卡片質感，整合發音按鈕、音標、釋義、情境例句與雙向記憶狀態切換。
- **主要按鈕 (Primary Button)**：採用深藍背景、純白文字與柔和投射陰影，支援全鍵盤聚焦環 (`focus-visible:ring-2 focus-visible:ring-blue-500`)。
- **次要與幽靈按鈕 (Outline / Ghost Button)**：細邊框與柔和懸停背景，保持主要 CTA 之視覺層級主導性。
- **進度指示條 (Progress Bar)**：深色背景軌道搭配翡翠綠進度填滿，支援流暢寬度過渡動畫。

## Do's and Don'ts

### Do

- 優先使用 Svelte 5 原生物件語法 `class={{ '...': true, '...': condition }}` 控制動態樣式。
- 所有字階一律維持 >= 14px (`text-sm`)。
- 在各個按鈕與可點擊元件上落實完整的 `:hover`、`:focus-visible` 與 `:active` 狀態。
- 所有文字一律定義於 `lang/{locale}/ui.php`，前端透過 `t('ui....')` 取得。

### Don't

- 嚴禁在標題上方添加多餘的 Pill Badge 或裝飾性 Eyebrow 標籤。
- 嚴禁使用紫色、紫羅蘭色或未經定義的漸層文字。
- 嚴禁在 class 屬性中使用三元運算子或字串模板拼接。
- 嚴禁在 UI 中提及「大考中心」、學測、指考或 CEFR 等級標籤。
