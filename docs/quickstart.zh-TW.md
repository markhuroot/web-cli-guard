# Quickstart

[English](./quickstart.md) | 繁體中文

這是從 clone repository 到跑起真實 tmux bridge 的最短路徑。

## 目標

完成後，你應該會有：

- 一個真實的 tmux session
- 一個綁在 `127.0.0.1` 的本地 Python bridge
- 一到兩個 session 名稱的 allowlist
- 以及由純 PHP demo、Node.js demo 或 WordPress plugin 其中之一連到該 bridge

## 1. 建立低權限 Runtime User

例如：

```bash
sudo useradd --create-home --shell /bin/bash tmuxsvc
```

不要給它 `sudo`。

## 2. 準備工作目錄

例如：

```bash
sudo mkdir -p /srv/web-cli-guard/workdir
sudo chown -R tmuxsvc:tmuxsvc /srv/web-cli-guard
```

## 3. 啟動共享 tmux Session

使用這個 repository 內附的 bootstrap script：

```bash
sudo cp examples/scripts/tmuxsvc-bootstrap.example.sh /usr/local/bin/web-cli-guard-bootstrap
sudo chmod +x /usr/local/bin/web-cli-guard-bootstrap
sudo -u tmuxsvc TMUX_SESSION_NAME=agent-main TMUX_SESSION_WORKDIR=/srv/web-cli-guard/workdir /usr/local/bin/web-cli-guard-bootstrap
```

這會建立 tmux session，並在其中啟動互動式 shell。

## 4. 啟動 Python Bridge

在 repository 根目錄執行：

```bash
python3 python-bridge/server.py \
  --host 127.0.0.1 \
  --port 8765 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main
```

如果你想開兩個 session：

```bash
python3 python-bridge/server.py \
  --host 127.0.0.1 \
  --port 8765 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main \
  --allow-session repo-main
```

如果你比較想用 Node.js，repository 也有：

```bash
node node-bridge/server.js \
  --host 127.0.0.1 \
  --port 8766 \
  --token change-me \
  --socket /var/lib/web-cli-guard-tmux/default.sock \
  --allow-session agent-main
```

## 5. 驗證 Bridge

檢查 health：

```bash
curl -H 'Authorization: Bearer change-me' http://127.0.0.1:8765/health
```

列出 sessions：

```bash
curl -H 'Authorization: Bearer change-me' http://127.0.0.1:8765/sessions
```

抓取 pane 輸出：

```bash
curl -H 'Authorization: Bearer change-me' 'http://127.0.0.1:8765/capture?session=agent-main'
```

## 6. 接上純 PHP Demo

先 export：

```bash
export WCG_BRIDGE_URL=http://127.0.0.1:8765
export WCG_BRIDGE_TOKEN=change-me
```

再執行：

```bash
cd php-demo
php -S 127.0.0.1:8080
```

開啟：

`http://127.0.0.1:8080/index.php`

UI 上的 badge 應該會從 demo mode 變成 bridge mode。

## 7. 接上 WordPress Plugin

在 plugin 設定頁中：

- 把 `Bridge URL` 設成 `http://127.0.0.1:8765`
- 把 `Bridge Token` 設成共享 token
- 把 `Allowed Sessions` 設成你要暴露的 tmux allowlist

之後可用：

- `Test Bridge Connection`
- console shortcode UI

如果 bridge 暫時不可用，公開版 plugin 會退回 demo mode。

## 8. 接上 Node.js Demo

先建立本地設定檔：

```bash
cd node-demo
cp .env.example .env
```

編輯 `.env`，例如：

```dotenv
WCG_BRIDGE_URL=http://127.0.0.1:8765
WCG_BRIDGE_TOKEN=change-me
```

再執行：

```bash
cd node-demo
node server.js
```

開啟：

`http://127.0.0.1:8090`

這樣就能用小型 Node.js runtime 取得同樣的 operator flow。

如果你比較習慣 shell export，也可以直接 export；它會覆蓋 `.env` 裡的設定。

## 選配：使用 systemd

repo 已內附 service example：

- `examples/systemd/dreamj-tmuxsvc.service.example`
- `examples/systemd/web-cli-guard-node-bridge.service.example`

當你希望 tmux sessions 在重開機後仍能自動回來時，這會很有用。

## 操作備註

- 除非有很強的理由，否則 bridge 盡量只綁 localhost。
- bridge 不是主要 sandbox 邊界。
- runtime OS user 應維持低權限。
- 高風險指令的 OTP 或核准流程，仍應放在 web layer 再送到 bridge。
