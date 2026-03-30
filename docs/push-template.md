# Push Template

When your GitHub repository is ready:

```bash
cd /var/www/html/server/oss/web-cli-guard
git remote add origin <your-github-repo-url>
git push -u origin main
```

## Example

```bash
git remote add origin git@github.com:your-name/web-cli-guard.git
git push -u origin main
```

## If `origin` Already Exists

```bash
git remote set-url origin <your-github-repo-url>
git push -u origin main
```
