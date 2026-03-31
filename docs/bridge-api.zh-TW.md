# Bridge API

[English](./bridge-api.md) | 繁體中文

## 目的

這份文件定義了一個給 tmux-backed Web CLI console 使用的最小 bridge 契約。

bridge 應該刻意保持狹窄。它不是通用 shell API。

建議提供的 bridge action：

- `health`
- `list-sessions`
- `capture-pane`
- `send-text`
- `send-key`

## 傳輸方式

這個 repository 的範例預設使用：

- HTTP JSON
- 只綁 localhost
- 由 web app 與 bridge 共用 bearer token

如果你要改成其他傳輸方式也可以，但應保留同樣的行為與邊界。

## 安全預期

bridge 應該：

- 除非有很強的理由，否則綁在 `127.0.0.1`
- 拒絕沒有有效 token 的請求
- 只允許已配置的 session 名稱
- 拒絕未知 action
- 拒絕未知特殊按鍵
- 永遠不要暴露任意 shell execution

bridge 依然不是主要 sandbox 邊界。

真正的邊界應該是：

- runtime OS user
- tmux session allowlist
- 有限的可寫路徑
- 沒有 `sudo`

## 驗證方式

使用 `Authorization` header：

```http
Authorization: Bearer YOUR_SHARED_TOKEN
```

## Endpoints

### `GET /health`

回傳基本 health 資訊。

範例回應：

```json
{
  "ok": true,
  "service": "web-cli-guard-python-bridge"
}
```

### `GET /sessions`

回傳目前存在、且在 allowlist 內的 tmux sessions。

範例回應：

```json
{
  "ok": true,
  "sessions": [
    {
      "name": "svr-main"
    },
    {
      "name": "www-main"
    }
  ]
}
```

### `GET /capture?session=<name>`

抓取一個允許 session 目前的 pane 輸出。

範例回應：

```json
{
  "ok": true,
  "session": "svr-main",
  "output": "user@host:~$ pwd\n/var/www/html/server\n"
}
```

### `POST /send-text`

送出 literal text 到指定 session，可選擇是否附帶 Enter。

Request body：

```json
{
  "session": "svr-main",
  "text": "pwd",
  "append_enter": true
}
```

範例回應：

```json
{
  "ok": true,
  "session": "svr-main"
}
```

### `POST /send-key`

從狹窄 allowlist 中送出特殊按鍵。

Request body：

```json
{
  "session": "svr-main",
  "key": "Enter"
}
```

建議的 key allowlist：

- `Enter`
- `C-c`
- `Escape`
- `Tab`
- `Up`
- `Down`
- `Left`
- `Right`
- `PageUp`
- `PageDown`

範例回應：

```json
{
  "ok": true,
  "session": "svr-main",
  "key": "Enter"
}
```

## 錯誤格式

使用固定 JSON error body：

```json
{
  "ok": false,
  "error": "forbidden",
  "message": "Unknown or disallowed session."
}
```

建議的 error code：

- `unauthorized`
- `forbidden`
- `bad_request`
- `not_found`
- `bridge_error`

## 高風險指令

bridge 不應負責 OTP 或 approval workflow。

這層邏輯應留在 web app：

1. operator 送出指令
2. web app 判斷它是一般指令還是高風險指令
3. 若為高風險指令，web app 先完成 OTP 或 approval
4. web app 再把「已核准的那條精確指令」送到 bridge

這樣 bridge 會比較窄，也比較容易推理與稽核。

## 相容性備註

這個 repository 裡的公開 plugin、PHP demo 與 Node demo，主要用來展示 operator flow 與 UI 形狀。

而 [`python-bridge/`](../python-bridge/) 與 [`node-bridge/`](../node-bridge/) 則是 repository 內附的最小 runtime-facing bridge 範例。
