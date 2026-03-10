# 部署到测试服务器指南

**服务器信息：**
- **IP:** 47.116.211.186
- **用户:** root
- **项目目录:** /opt/eve-esi
- **访问地址:** http://47.116.211.186

---

## 🚀 快速部署（推荐）

### Step 1: 提交并推送代码

```bash
cd /home/tus/.openclaw/workspace/projects/eve-esi
git add -A
git commit -m "feat: 资产管理模块开发完成"
git push origin main
```

✅ 已完成推送！

---

### Step 2: SSH 登录服务器更新

**方式 A：使用 SSH 密钥（如果已配置）**

```bash
# 执行更新脚本
bash update-remote.sh
```

**方式 B：手动登录更新（当前可用）**

```bash
# 1. SSH 登录服务器
ssh root@47.116.211.186

# 2. 进入项目目录
cd /opt/eve-esi

# 3. 拉取最新代码
git pull origin main

# 4. 安装依赖
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

# 5. 清理缓存
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan optimize:clear

# 6. 重启容器（可选）
docker compose restart
```

**方式 C：一条命令更新**

```bash
ssh root@47.116.211.186 "cd /opt/eve-esi && git pull && docker compose exec -T app composer install --no-dev -o && docker compose exec -T app php artisan optimize:clear"
```

---

## 📋 本次更新内容

### 新增功能

1. **资产管理模块（阶段 1）**
   - 📦 资产列表页面 (`/assets`)
   - 📊 统计信息（物品种类、总数、位置数）
   - 🔍 实时搜索功能
   - 📱 响应式设计

2. **Dashboard 异步加载优化**
   - ⚡ 首屏时间 <0.5 秒
   - 🔄 并行 API 请求
   - 💀 骨架屏加载动画

3. **API 端点**
   - `GET /api/dashboard/assets` - 资产数据
   - `GET /api/dashboard/server-status` - 服务器状态
   - `GET /api/dashboard/skills` - 技能数据
   - `GET /api/dashboard/skill-queue` - 技能队列

4. **错误处理优化**
   - 🔐 401 未授权处理
   - ⚠️ 友好的错误提示
   - 📝 详细的日志记录

---

## 🧪 测试清单

### 访问测试

- [ ] 首页能正常访问
- [ ] OAuth2 授权正常
- [ ] Dashboard 数据加载正常
- [ ] 资产页面能访问（`/assets`）
- [ ] 资产列表显示正常
- [ ] 搜索功能正常

### 功能测试

- [ ] 服务器状态显示
- [ ] 技能数据显示
- [ ] 技能队列显示
- [ ] 资产列表显示
- [ ] 资产搜索功能

### 错误处理

- [ ] Session 过期提示
- [ ] Token 过期处理
- [ ] EVE API 超时提示
- [ ] 网络错误提示

---

## 🔧 常用命令

### 查看日志

```bash
# 应用日志
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose logs -f app"

# Nginx 日志
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose logs -f nginx"

# Laravel 日志
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose exec -T app tail -f storage/logs/laravel.log"
```

### 进入容器

```bash
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose exec app bash"
```

### 重启服务

```bash
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose restart"
```

### 查看容器状态

```bash
ssh root@47.116.211.186 "cd /opt/eve-esi && docker compose ps"
```

---

## 🐛 故障排查

### 问题 1: 页面空白

**可能原因：**
- 代码未更新
- 缓存未清理
- 权限问题

**解决方法：**
```bash
ssh root@47.116.211.186 << 'EOF'
cd /opt/eve-esi
git pull
docker compose exec app php artisan optimize:clear
docker compose restart
EOF
```

---

### 问题 2: 资产页面 404

**可能原因：**
- 路由未缓存
- 配置未更新

**解决方法：**
```bash
ssh root@47.116.211.186 << 'EOF'
cd /opt/eve-esi
docker compose exec app php artisan route:cache
docker compose exec app php artisan config:cache
EOF
```

---

### 问题 3: API 返回 401

**可能原因：**
- Session 过期
- Cookie 未携带

**解决方法：**
- 清除浏览器 Cookie
- 重新登录授权

---

### 问题 4: EVE API 连接超时

**可能原因：**
- EVE 服务器维护
- 网络问题

**解决方法：**
- 检查 EVE 服务器状态
- 等待维护结束

---

## 📊 部署检查清单

### 部署前

- [x] 代码已提交到 Git
- [x] 代码已推送到 GitHub
- [ ] 服务器已更新
- [ ] 依赖已安装
- [ ] 缓存已清理

### 部署后

- [ ] 首页访问正常
- [ ] Dashboard 显示正常
- [ ] 资产页面可访问
- [ ] 所有功能正常
- [ ] 日志无错误

---

## 📞 快速部署命令

**复制粘贴这条命令即可完成更新：**

```bash
ssh root@47.116.211.186 "cd /opt/eve-esi && git pull && docker compose exec -T app composer install --no-dev -o && docker compose exec -T app php artisan optimize:clear && echo '✅ 更新完成！'"
```

---

*更新时间：2026-03-10 17:45*  
*服务器：47.116.211.186*  
*项目：EVE ESI 管理平台*
