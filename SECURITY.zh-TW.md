# Security Notes

[English](./SECURITY.md) | 繁體中文

`Web CLI Guard` 的目標是降低操作風險，不是保證風險完全消失。

## 核心原則

Web 層不應該是你的主要信任邊界。

真正的邊界應該是：

- 低權限 Linux 使用者
- 受限制的檔案系統權限
- 受限的網路可達範圍
- 精簡的 `tmux` bridge 指令集

當你想讓操作人員透過瀏覽器檢視或引導 AI CLI 工作，但又希望真正執行仍留在 OS 層級 sandbox 內時，這種設計特別有用。

## 最低安全預設

- 使用專用 OS 帳號，例如 `tmuxsvc`
- 不要把該帳號加進 `sudo`、`wheel` 或容器管理群組
- 把可寫路徑縮到最小，只保留：
  - session logs
  - cache/state
  - 明確指定的工作目錄
- 只允許已知的 `tmux` sessions
- 記錄每一次 send action
- 加入 lock ownership，避免多人衝突操作
- 對危險指令要求 OTP 或核准

## 通常需要額外驗證的指令

- `sudo`
- `su`
- `systemctl`
- `service`
- `reboot`
- `shutdown`
- `mount`
- `umount`
- `passwd`
- `useradd`
- `usermod`
- `userdel`
- `iptables`
- `ufw`
- `killall`
- `pkill`
- `rm -rf`

## 這個專案不保證的事情

- 完美的 shell 隔離
- 能攔下所有危險指令變形
- 能防禦已被入侵的 WordPress admin 帳號
- 能防禦本機高權限攻擊者

`tmux` 提升的是持續性與可觀察性。它不能取代 OS sandbox、container isolation 或硬式權限邊界。

## Secret 處理

不要把以下內容 commit 進 repo：

- API keys
- 內部 bridge tokens
- production SMTP credentials
- LDAP credentials
- 私有 endpoints

請使用 environment variables 或 repo 外部的本地設定檔。

## 上 production 前建議檢查

- 檢查 Linux user/group 權限
- 檢查 runtime account 的 `sudo -l`
- 檢查 session allowlists
- 測試 audit logs
- 測試 OTP 或 approval 是否能被繞過
- 測試 command injection 邊界
- 測試 `tmux` crash 或不存在時的失敗模式
