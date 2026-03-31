# Web CLI Guard v0.2.1

`v0.2.1` 進一步補強了 `web-cli-guard` 的 Node.js 路線，讓偏好 JavaScript 維運與部署方式的團隊，也能沿用同一套狹窄 tmux bridge 模型。

建議 GitHub Release 標題：

`v0.2.1 - Node bridge and dual-runtime operator path`

## 重點

- 新增零依賴的 `node-demo/` 操作介面
- Node demo 新增 `.env` 設定支援與內建 bridge 測試區塊
- 新增最小可用的 `node-bridge/`，提供：
  - `GET /health`
  - `GET /sessions`
  - `GET /capture`
  - `POST /send-text`
  - `POST /send-key`
- 新增 `node-bridge` 的 `systemd` service 範例
- 更新 quickstart 與 README，讓 Python / Node 兩條部署路線更清楚
- CI 現在會同時檢查 `node-demo/server.js` 與 `node-bridge/server.js`

## 為什麼這版重要

`web-cli-guard` 的定位，是提供一種比較務實的方式，讓人能從 Web 安全地接近既有 CLI 環境，不論該環境在家中、辦公室，或內部主機上。

到 `v0.2.1` 為止，這個 repository 已經同時提供：

- Python bridge 路線
- Node bridge 路線
- PHP 與 Node.js 的 demo UI
- WordPress 型態的 operator UI starter

這讓不同 runtime 習慣的團隊，能沿用同一套安全模型而不必重做架構：

- 低權限 OS 使用者
- narrow bridge
- tmux session allowlist
- 高風險操作留在 web layer 做 approval 或 OTP

## 建議直接貼到 Release 的內容

```md
`v0.2.1` 進一步補強了 `web-cli-guard` 的 Node.js 路線，讓偏好 JavaScript 維運與部署方式的團隊，也能沿用同一套狹窄 tmux bridge 模型。

### 重點

- 新增零依賴的 `node-demo/` 操作介面
- Node demo 新增 `.env` 設定支援與內建 bridge 測試區塊
- 新增最小可用的 `node-bridge/`，提供：
  - `GET /health`
  - `GET /sessions`
  - `GET /capture`
  - `POST /send-text`
  - `POST /send-key`
- 新增 `node-bridge` 的 `systemd` service 範例
- 更新 quickstart 與 README，讓 Python / Node 兩條部署路線更清楚
- CI 現在會同時檢查 `node-demo/server.js` 與 `node-bridge/server.js`

### 為什麼這版重要

`web-cli-guard` 的定位，是提供一種比較務實的方式，讓人能從 Web 安全地接近既有 CLI 環境，不論該環境在家中、辦公室，或內部主機上。

到 `v0.2.1` 為止，這個 repository 已經同時提供：

- Python bridge 路線
- Node bridge 路線
- PHP 與 Node.js 的 demo UI
- WordPress 型態的 operator UI starter

這讓不同 runtime 習慣的團隊，能沿用同一套安全模型而不必重做架構：

- 低權限 OS 使用者
- narrow bridge
- tmux session allowlist
- 高風險操作留在 web layer 做 approval 或 OTP
```

## 驗證

- `php -l` on the public PHP demo and WordPress plugin demo
- `python3 -m py_compile` on the Python bridge files
- `node --check node-demo/server.js`
- `node --check node-bridge/server.js`

## 比較範圍

- `v0.2.0...v0.2.1`

## 建議下一步

1. 從 `docs/quickstart.md` 開始
2. 在 `python-bridge/` 與 `node-bridge/` 之間擇一
3. 再接 `php-demo/`、`node-demo/` 或 `wordpress-plugin/web-cli-guard/`
