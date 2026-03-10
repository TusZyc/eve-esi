#!/bin/bash

# EVE ESI - 测试服务器更新脚本
# 服务器：47.116.211.186

set -e

echo "🚀 开始更新 EVE ESI 测试服务器..."
echo "服务器：47.116.211.186"
echo ""

# 颜色定义
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# SSH 连接
SSH_USER="root"
SSH_HOST="47.116.211.186"
PROJECT_DIR="/opt/eve-esi"

echo -e "${YELLOW}[1/5] 连接到远程服务器...${NC}"

# 在远程服务器上执行命令
ssh ${SSH_USER}@${SSH_HOST} << 'ENDSSH'
set -e

echo -e "${YELLOW}[2/5] 进入项目目录...${NC}"
cd /opt/eve-esi

echo -e "${YELLOW}[3/5] 拉取最新代码...${NC}"
git pull origin main

echo -e "${YELLOW}[4/5] 安装依赖...${NC}"
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo -e "${YELLOW}[5/5] 清理缓存...${NC}"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan optimize:clear

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}✅ 更新完成！${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo "访问地址：http://47.116.211.186"
echo "资产页面：http://47.116.211.186/assets"
echo ""
echo -e "${YELLOW}更新内容:${NC}"
echo "  ✅ 资产管理模块（阶段 1）"
echo "  ✅ Dashboard 异步加载优化"
echo "  ✅ 认证和 Session 优化"
echo "  ✅ EVE 服务器状态优化"
echo ""

ENDSSH

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 服务器更新成功！${NC}"
else
    echo -e "${RED}❌ 服务器更新失败${NC}"
    exit 1
fi
