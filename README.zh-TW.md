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
- `docs/bridge-api.md` 的 bridge contract 文件
- 一個可實際接 `tmux` 的最小 `python-bridge/`
- 一個可實際接 `tmux` 的最小 `node-bridge/`
- `systemd` 範例單元
- helper script 範例
- 一個可切換 `demo / bridge` 的 WordPress plugin demo
- 一個用於 bridge/runtime 設定展示的 WordPress settings page demo
- 一個可切換 `demo / bridge` 的純 PHP 互動 demo
- 一個零依賴的 Node.js 互動 demo
- `quickstart` 與 compose 範例
- GitHub Actions 基本 CI

目前不包含：

- production-ready 的 bridge package
- `codex`、`claude` 或其他 CLI 的 provider adapter
- 完整設定頁 UX
- 安裝器或封裝好的發佈包

## Repository 結構

- `docs/architecture.md`
  高階請求流與元件邊界
- `docs/quickstart.md`
  從 clone 到 real tmux bridge 的最短路徑
- `docs/bridge-api.md`
  狹窄 tmux bridge 的最小 HTTP 契約
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
- `examples/docker-compose.yml.example`
  bridge 的 container 化範例
- `python-bridge/`
  使用 Python 標準函式庫實作的最小 real bridge
- `node-bridge/`
  使用 Node.js 標準函式庫實作的最小 real bridge
- `node-demo/`
  零依賴的 Node.js 操作介面 demo
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

## 為什麼這會比一般 Web Shell 更安全

這個模式的重點不是瀏覽器本身多安全，而是把真正的邊界放在 runtime 上。

實務上比較重要的是：

- CLI 跑在 `tmux` 裡
- `tmux` 跑在低權限 OS 帳號下
- Web 層只暴露狹窄的 bridge action
- 高風險操作仍需在 Web 層做 OTP 或核准

所以真正要問的不是：

- 瀏覽器能畫出多少控制項

而是：

- runtime user 到底能讀什麼、寫什麼、執行什麼

如果 runtime user 沒有 `sudo`、可寫路徑很窄、session allowlist 很小，那 Web 入口實際上也會繼承這些限制。

## WordPress 使用情境

這個 repository 內含最小的 WordPress plugin scaffold，因為很多團隊本來就有內部 WordPress 環境，而且通常會想要：

- 一個簡單的員工操作介面
- 直接沿用登入/權限機制
- 一個熟悉的 admin 或 portal 入口

這個 plugin 是刻意維持精簡的公開起點，不是把內部 production portal 原樣丟出來。

目前公開 demo plugin 展示的內容包含：

- session 切換
- line-console 風格輸出
- `demo / bridge` 雙模式指令送出流程
- bridge/runtime 相關設定頁
- bridge 連線測試按鈕

## 隔離模型比較

這個 repository 的定位，主要是提供一種「安全地存取家中、辦公室或內部主機上既有 CLI 環境」的方式。

它不是要取代所有 sandbox 模型。

| 做法 | 主要邊界 | 適合 | 取捨 |
| --- | --- | --- | --- |
| 低權限 OS user + `tmux` + narrow bridge | OS 帳號與路徑權限 | 把既有 home/office CLI 環境安全地接到 Web | 維運比較簡單，但不是強多租戶隔離 |
| 每個 agent / workflow 用 Docker | Container 邊界 | 需要可重複打包、依賴隔離、快速重建 | 比 host-only 更好管理，但仍依賴 container hardening 與 volume/network policy |
| 每個 agent / trust zone 用 VM | Hypervisor / VM 邊界 | 不同工作負載或團隊間需要較高隔離 | CPU、RAM、映像檔、網路與維運成本都較高 |
| 只靠工具內建 sandbox | 工具層 policy | 單機、單人、可信環境內快速實驗 | 對遠端多使用者操作來說，通常不夠當主要邊界 |

`web-cli-guard` 的重點，是提供一個可實際落地的遠端 CLI 存取模式，而不是宣稱 `tmux` 本身就是完整沙盒。

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
2. 再照 [quickstart.md](./docs/quickstart.md) 跑一次最短流程
3. 再讀 [bridge-api.md](./docs/bridge-api.md)
4. 再讀 [threat-model.md](./docs/threat-model.md)
5. 查看 `examples/` 下面的範例檔案
6. 先試 `php-demo/`、`node-demo/`、WordPress plugin，或直接跑 `python-bridge/`
7. 以 WordPress plugin scaffold 為起點，或自行做 Web UI
8. 不要把 secrets 放進 repository

## 第一個可實際使用的 Bridge

這個 repository 現在已內含最小可用的 real bridge，位置在 [`python-bridge/`](./python-bridge/) 與 [`node-bridge/`](./node-bridge/)。

它的定位是讓使用者從：

- 只看 UI demo

往前走到：

- 可實際對 `tmux` 做 session list / capture / send 的 bridge starter

這個 bridge 刻意保持精簡：

- 只使用 Python 或 Node.js 標準函式庫
- 使用 bearer token
- 使用 session allowlist
- 提供 `health / sessions / capture / send-text / send-key`
- 不提供任意 shell execution API

如果你要讓 Node bridge 跟著系統重啟後自動回來，可參考：

- `examples/systemd/web-cli-guard-node-bridge.service.example`

## Python 與 Node 路線

兩條 runtime 路線都遵循同一套 narrow bridge 模型，差別主要在團隊習慣的 runtime 與部署方式。

| 路線 | 適合 | 主要組成 |
| --- | --- | --- |
| Python 路線 | 主機本來就有 Python 類型的維運工具 | `python-bridge/` + `php-demo/` 或 WordPress plugin |
| Node 路線 | 團隊偏好 JavaScript-only 的部署與操作介面 | `node-bridge/` + `node-demo/` |

不需要兩條都暴露。通常選一條 bridge runtime 即可，盡量維持 localhost 綁定，再把核准流程與操作人員驗證留在 web layer。

## 截圖區

加入實際圖片後，下方區塊可以直接成為 GitHub 首頁的展示區。

### WordPress Demo Console

![WordPress demo console showing web-based visibility into an AI CLI session](./assets/screenshots/wp-demo-console.png)

### WordPress Settings Page

![WordPress settings page for bridge URL, runtime user, and allowed sessions](./assets/screenshots/wp-settings-page.png)

### Plain PHP Demo

![Plain PHP demo console for a tmux-backed AI CLI operator flow](./assets/screenshots/php-demo-console.png)

### Node.js Demo

這個 repository 也包含一個最小的 Node.js 介面 [`node-demo/`](./node-demo/)。它延續與 PHP demo 相同的模式，但對偏好 JavaScript runtime 做內部操作介面的團隊會更順手。

這個 Node.js demo 也支援本地 `.env` 設定檔，讓操作人員不用每次都手動 export 環境變數，就能接到既有 bridge。

現在也內建了 bridge 測試區塊，能先確認 health 與 allowed sessions，再決定是否送出操作。

### Approval Flow

![Approval or OTP flow for elevated remote actions](./assets/screenshots/approval-flow.png)

## Release 文案

第一版 release 草稿可參考 [release-notes-v0.1.md](./docs/release-notes-v0.1.md)。
針對公開版補強的 `v0.1.1` 可參考 [release-notes-v0.1.1.md](./docs/release-notes-v0.1.1.md)。
第一個 bridge-capable starter 版本可參考 [release-notes-v0.2.0.md](./docs/release-notes-v0.2.0.md)。
雙 runtime bridge 更新可參考 [release-notes-v0.2.1.md](./docs/release-notes-v0.2.1.md)。

## License

此 scaffold 使用 MIT License。詳見 [LICENSE](./LICENSE)。
