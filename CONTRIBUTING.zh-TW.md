# Contributing

[English](./CONTRIBUTING.md) | 繁體中文

感謝你對 Web CLI Guard 做出貢獻。

## 開 PR 前

1. 讓修改保持聚焦
2. 不要提交 secrets 或環境專屬值
3. 有行為變更時，記得同步更新 `README.md` 或 `docs/`
4. 任何 PHP 檔案都先用 `php -l` 驗證
5. 說明這次改動影響的是：
   - 只有 UI
   - bridge/runtime 設計
   - 安全模型

## 貢獻優先方向

這個專案歡迎的貢獻：

- 更安全的 bridge pattern
- 更好的文件與 threat modeling
- 更乾淨的 demo UX
- framework-agnostic 範例
- 不會削弱安全模型的 provider adapters

相對不鼓勵的方向：

- 把 demo 變成不受限制的 web terminal
- 加入預設偏向 root 的設計
- 把 vendor-specific secret 或基礎設施值寫死

## Coding Guidance

- 保持範例通用
- 優先採用低權限預設
- 讓安全假設寫得明確
- 把 demo mode 與 real execution mode 分開

## Issues 與 PR

提交 issue 或 PR 時，請至少說明：

- 你原本預期什麼
- 實際發生了什麼
- 這次改動是只影響 demo，還是打算給真實部署使用
- 你做了哪些安全取捨
