# Web CLI Guard

[English](./README.md) | 繁體中文

Web CLI Guard 是一個小型的開源起始專案，用來把既有 CLI 工具放到受控的 Web 介面後面。

它適合想透過 Web 檢視或操作既有 AI CLI 工作流，但又不想直接暴露原始、無限制 shell 的團隊。

它圍繞著一套務實做法：

- 用 `tmux` 保持持久 session
- 用低權限 Linux 使用者作為真正的執行邊界
- 用精簡 bridge 提供 `list / capture / send`
- 保留 audit log 與 session lock
- 對高權限指令加上 OTP 或核准流程
- 可搭配 WordPress 型態的操作介面

## 為什麼會有這個專案

很多團隊其實早就已經在使用 CLI 型工具，例如：

- `codex`
- `claude`
- 本地 shell 助手
- repo 專用腳本

真正困難的通常不是 CLI 本身，而是如何提供一個可用的 Web 入口，同時不要退化成「完全不受限制的 Web terminal」。

做得好的話，這種模式可以讓操作人員：

- 從 Web 檢視目前 AI CLI 的工作狀態
- 在不登入 SSH 的情況下查看持續中的 session 輸出
- 在外部環境查看 AI agent 正在做什麼
- 透過瀏覽器執行有限且受控的遠端操作
- 用比一般 Web shell 更安全的遠端操作介面
- 把真正的安全邊界放在 OS 最小權限與 sandbox
- 對高風險操作加入 OTP 或額外核准

這個專案整理的是一套帶有明確立場的實作方式：

1. 把 CLI 跑在指定的 `tmux` session 裡
2. 用受限制的 OS 使用者執行該 session
3. 只暴露精簡的 Web bridge
4. 加上 audit log 與 per-session locking
5. 對高權限指令要求額外驗證

## About

Web CLI Guard 可以理解成「既有 CLI runtime 的受控操作視窗」。

它適合用在以下情境：

- 在辦公室外觀看進行中的 `codex`、`claude` 或 shell-based AI session
- 讓員工在沒有直接 SSH 權限的情況下查看輸出並送出少量允許指令
- 把長時間 AI 任務保留在持續運行的 `tmux` session 裡，同時只暴露狹窄的瀏覽器控制面
- 在敏感操作前加入 step-up verification，例如 service restart 或維運類指令
- 把 Web 驗證、操作審計與 OS 層級 sandbox 串成一個工作流

核心概念很簡單：

- 瀏覽器負責可視化與受控輸入
- OS 帳號或 sandbox 才是真正的執行邊界
- 高風險操作不應只信任 UI，而要加上額外驗證

## 範圍

這個 repository 是 starter，不是完整成品。

目前包含：

- 架構與安全文件
- `systemd` 範例單元
- helper script 範例
- 一個具互動性的 WordPress demo plugin
- 一個用於 bridge/runtime 設定展示的 WordPress settings page demo
- 一個不依賴 WordPress 的純 PHP 互動 demo

目前不包含：

- production-ready 的 bridge package
- `codex`、`claude` 或其他 CLI 的 provider adapter
- 完整設定頁 UX
- 安裝器或封裝好的發佈包

## Repository 結構

- `docs/architecture.md`
  高階請求流與元件邊界
- `docs/threat-model.md`
  這種模式能降低哪些風險，以及不能解決哪些問題
- `docs/roadmap.md`
  把它發展成公開專案的建議階段
- `docs/release-checklist.md`
  發佈到 GitHub 前的基本檢查
- `examples/systemd/`
  範例 service units
- `examples/scripts/`
  範例 bootstrap/helper scripts
- `wordpress-plugin/web-cli-guard/`
  最小 WordPress plugin 起始版
- `php-demo/`
  不依賴 WordPress 的純 PHP demo

## 安全模型

主要信任邊界應該是 runtime OS user，而不是 Web UI。

建議基線：

- 讓受控 shell 跑在專用帳號，例如 `tmuxsvc`
- 不要賦予 `sudo`
- 盡量縮小可寫路徑
- 把 `tmux` session 放進 allowlist
- 記錄每一次 send action
- 對高風險指令要求 OTP 或額外核准

運作模型可參考 [SECURITY.md](./SECURITY.md)。

換句話說，目標不是讓瀏覽器更強，而是讓瀏覽器成為一個通往「已受限制 runtime」的受控窗口。

## 典型架構

`Browser -> Web App -> Narrow Bridge -> tmux -> CLI Process`

瀏覽器本身不應直接執行 shell 指令。

這種架構適合拿來做 AI CLI session 的 Web 可視化，同時把真正執行保留在受限 OS 帳號或 sandbox 環境內。

Web app 應該負責：

- 驗證操作人員身份
- 授權 session 存取
- 執行 session lock
- 分類高風險指令
- 只代理受允許的 bridge actions

bridge 則應該只提供有限指令，例如：

- `list-sessions`
- `capture-pane`
- `send-keys`

## WordPress 使用情境

這個 repository 內含最小的 WordPress plugin scaffold，因為很多團隊本來就有內部 WordPress 環境，而且通常會想要：

- 一個簡單的員工操作介面
- 直接沿用登入/權限機制
- 一個熟悉的 admin 或 portal 入口

這個 plugin 是刻意維持精簡的公開起點，不是把內部 production portal 原樣丟出來。

目前公開 demo plugin 展示的內容包含：

- session 切換
- line-console 風格輸出
- 模擬的指令送出流程
- bridge/runtime 相關設定頁

## 適合的場景

- 內部工程控制台
- 一到兩台主機的 support/admin 工作流
- 讓沒有 SSH 權限的人使用 AI CLI
- 遠端觀看進行中的 AI 維運、調查或排錯工作
- 對敏感操作加上 Web 核准層
- 需要可審計與操作核准的團隊

## 不適合的場景

- 公開匿名 shell
- 完整多租戶隔離
- 沒有額外 OS/container hardening 卻要求高保證 sandbox
- 預設就需要 root 級能力的環境

## 開始使用

1. 先讀 [architecture.md](./docs/architecture.md)
2. 再讀 [threat-model.md](./docs/threat-model.md)
3. 查看 `examples/` 下面的範例檔案
4. 以 WordPress plugin scaffold 為起點，或自行做 Web UI
5. 不要把 secrets 放進 repository

## 發佈建議

把這種類型的專案推到 GitHub 前，至少先做：

1. 移除環境專屬 branding
2. 替換真實 domain、mail host、token
3. 確認 runtime 帳號沒有額外高權限
4. 重新檢查 release checklist

可參考 [release-checklist.md](./docs/release-checklist.md)。

## 建議截圖

如果你要公開這個 repo，第一批最有用的截圖通常是：

1. WordPress demo console
2. WordPress settings page
3. 純 PHP demo console
4. 高權限操作的 approval / OTP flow

請參考 [screenshots.md](./docs/screenshots.md) 了解 caption 建議與避免曝光的資訊。
檔名與輸出步驟請看 [screenshot-checklist.md](./docs/screenshot-checklist.md)。
實拍畫面規劃請看 [screenshot-shot-plan.md](./docs/screenshot-shot-plan.md)。

## 截圖區

加入實際圖片後，下方區塊可以直接成為 GitHub 首頁的展示區。

### WordPress Demo Console

![WordPress demo console showing web-based visibility into an AI CLI session](./assets/screenshots/wp-demo-console.png)

### WordPress Settings Page

![WordPress settings page for bridge URL, runtime user, and allowed sessions](./assets/screenshots/wp-settings-page.png)

### Plain PHP Demo

![Plain PHP demo console for a tmux-backed AI CLI operator flow](./assets/screenshots/php-demo-console.png)

### Approval Flow

![Approval or OTP flow for elevated remote actions](./assets/screenshots/approval-flow.png)

## Release 文案

第一版 release 草稿可參考 [release-notes-v0.1.md](./docs/release-notes-v0.1.md)。
針對公開版補強的 `v0.1.1` 可參考 [release-notes-v0.1.1.md](./docs/release-notes-v0.1.1.md)。

## GitHub About 建議

repository description 與 topics 可參考 [github-publish-notes.md](./docs/github-publish-notes.md)。
最終可直接貼上的版本可參考 [github-about-final.md](./docs/github-about-final.md)。

## Push 範本

當你的 GitHub repository 建好之後，可以用：

```bash
cd /var/www/html/server/oss/web-cli-guard
git remote add origin <your-github-repo-url>
git push -u origin main
```

## License

此 scaffold 使用 MIT License。詳見 [LICENSE](./LICENSE)。
