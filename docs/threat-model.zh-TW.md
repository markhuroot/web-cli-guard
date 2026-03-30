# Threat Model

[English](./threat-model.md) | 繁體中文

## 主要想降低的風險

- 一般操作人員意外執行危險指令
- 多個使用者不受控地同時操作同一個 shell session
- 在沒有 audit 歷史的情況下盲目操作
- 讓沒有 SSH 權限的使用者也能存取 AI CLI 工具
- 在使用 Web UX 的同時，仍保留 OS 層級的最小權限

## 不打算完整解決的風險

- 已完全被攻陷的 WordPress admin 環境
- kernel 層級或 root 層級的主機攻陷
- 已經以 runtime OS user 身分執行的惡意程式
- 所有 shell metacharacter 或指令混淆技巧
- 已被允許 runtime account 的所有資料外洩形式

## 重要邊界

真正關鍵的邊界是 runtime OS user。

如果 runtime user 可以讀或寫某個路徑，那 CLI 通常也可以做一樣的事。

這表示你的第一輪安全檢查應該聚焦在：

- filesystem permissions
- group membership
- `sudo` access
- network egress

## 操作風險

### 1. Bridge 太寬

如果 bridge 允許任意 shell 指令，bridge 就會變成整個產品最弱的一環。

### 2. 只有 Web 前端檢查

如果高風險驗證只存在於 JavaScript，就能被繞過。

### 3. 共用過寬的可寫目錄

如果 runtime account 和 web server user 共用大範圍可寫權限，隔離價值就會大幅下降。

### 4. 缺少 session lock

兩個操作人員可能會把同一個互動流程弄亂。

### 5. 缺少 audit trail

一旦出問題，沒人知道是哪個人送了哪條指令。

## 較安全的導入路徑

1. 先從 readonly 開始
2. 再加入受限特殊按鍵
3. 再加入一般文字送出
4. 再加上 audit logs 與 locks
5. 再加入 elevated verification
6. 最後才考慮 provider-specific helpers
