# Architecture

[English](./architecture.md) | 繁體中文

## 摘要

`Web CLI Guard` 會透過受控的 Web console，暴露既有的 CLI 工具。

這個專案的主軸不是「先做一個 web shell」，而是：

- 用 Web 檢視目前 AI CLI 的工作狀態
- 讓長時間 CLI session 保持可觀察
- 允許部分受控的遠端操作
- 把安全性建立在 OS 層級的最小權限上
- 對高風險操作再額外加上驗證

設計是：

`Browser -> Web App -> Narrow Bridge -> tmux -> CLI Process`

## 元件

## 1. Browser UI

瀏覽器端應提供：

- session 選擇
- 行式輸出檢視
- readonly TUI monitor
- 受限的特殊按鍵
- 文字指令送出
- 對操作人員可見的 audit/log feedback

瀏覽器不應直接跟 `tmux` 溝通。

它更適合被視為操作儀表板與互動層，而不是主要執行環境。

## 2. Web App

Web 層應該負責：

- 驗證操作人員身份
- 授權 console 存取
- 驗證 target host 與 session
- 執行 session locks
- 判斷某個指令是否需要高權限驗證
- 只代理經核准的 bridge actions

典型 actions：

- `list`
- `capture`
- `monitor`
- `send`
- `logs`
- `lock-status`
- `release`

## 3. Narrow Bridge

bridge 是一個小型程序或 endpoint，只對 web app 暴露極少量指令。

它不應提供任意 shell 執行能力。

典型行為：

- 只接受 localhost 或受信任 reverse proxy 流量
- 要求 shared token 或 mTLS
- 只允許：
  - `list-sessions`
  - `capture-pane`
  - `send-keys`
- 拒絕未知指令

## 4. tmux Session Layer

`tmux` 提供：

- 持久的 session 狀態
- 穩定的 send/capture 目標
- Web request 與 CLI process 之間的分離點

session 名稱範例：

- `www-main`
- `svr-main`
- `agent-main`

## 5. Runtime User

CLI 應該跑在專用低權限 OS 使用者下，而不是 `root`。

這個使用者應該：

- 沒有 `sudo`
- 可寫範圍很窄
- home/config 目錄受控

真正有意義的 sandbox 邊界就在這裡。如果 runtime user 不能寫某個路徑，也不能呼叫高權限工具，那麼透過 Web 暴露的 CLI 理論上也做不到。

## 6. Audit + Locking

常見且實用的操作控制包括：

- `send-text` 與 `send-key` 的 audit log
- 每個 session 的 lock ownership
- 閒置 timeout 與 release

## 7. Elevated Verification

對高風險指令，Web app 可以要求：

- email OTP
- 主管核准
- 第二層確認流程

這些都應該在指令送進 `tmux` 之前，由 Web app 先完成。

## 請求流程

## 一般指令

1. 操作人員打開 console
2. Web app 檢查 auth 與允許的 target/session
3. 操作人員送出 `pwd`
4. Web app 記錄請求並轉發 `send`
5. Bridge 呼叫 `tmux send-keys`
6. Web app 刷新 capture output

## 高風險指令

1. 操作人員送出 `systemctl restart ...`
2. Web app 將它分類成 elevated
3. Web app 發出 OTP 或 approval challenge
4. 操作人員完成驗證
5. Web app 送出「已驗證且完整」的那條指令
6. Audit log 記錄這次操作

## 為什麼用 tmux，而不是每次 request 直接開 PTY

- 更容易保留持久 session
- 更方便操作人員交接
- 對 CLI/TUI 工具相容性更好
- 更容易做 readonly monitor capture

## 取捨

- `tmux` 本身不是 sandbox
- 瀏覽器中的 TUI 渲染只能近似
- 高風險指令判定是 policy，不是數學證明
- WordPress 很方便，但不是唯一宿主
