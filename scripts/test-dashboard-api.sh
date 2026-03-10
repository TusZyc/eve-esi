#!/bin/bash
# 测试 Dashboard API 端点
# 用法：./test-dashboard-api.sh

set -e

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧪 Dashboard API 端点测试"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 配置
BASE_URL="http://localhost:8000"  # 根据实际情况修改

echo "📍 基础 URL: $BASE_URL"
echo ""

# 检查服务器是否运行
echo "🔍 检查服务器是否运行..."
if curl -s --connect-timeout 2 "$BASE_URL" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 服务器正在运行${NC}"
else
    echo -e "${RED}❌ 服务器未运行${NC}"
    echo "请先启动 Laravel 开发服务器："
    echo "  cd /home/tus/.openclaw/workspace/projects/eve-esi"
    echo "  php artisan serve"
    exit 1
fi

echo ""

# 测试服务器状态 API
echo "📡 测试 /api/dashboard/server-status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
STATUS_RESPONSE=$(curl -s --connect-timeout 5 --max-time 10 \
    "$BASE_URL/api/dashboard/server-status" 2>&1)

if [ $? -eq 0 ]; then
    echo "响应内容："
    echo "$STATUS_RESPONSE" | jq . 2>/dev/null || echo "$STATUS_RESPONSE"
    
    # 检查是否成功
    if echo "$STATUS_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
        echo -e "${GREEN}✅ 服务器状态 API 正常${NC}"
        PLAYERS=$(echo "$STATUS_RESPONSE" | jq -r '.data.players')
        echo "   在线人数：$PLAYERS"
    else
        ERROR=$(echo "$STATUS_RESPONSE" | jq -r '.error // "unknown"')
        echo -e "${YELLOW}⚠️  服务器状态异常：$ERROR${NC}"
    fi
else
    echo -e "${RED}❌ 请求失败${NC}"
fi

echo ""

# 测试技能数据 API（需要登录）
echo "📚 测试 /api/dashboard/skills"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 提示：此接口需要登录会话"
echo "   请在浏览器中登录后，从 Cookie 中获取 session_id"
echo ""

# 测试技能队列 API（需要登录）
echo "⏳ 测试 /api/dashboard/skill-queue"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💡 提示：此接口需要登录会话"
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ API 端点测试完成"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 显示路由列表
echo "📋 可用的 Dashboard API 路由："
echo "  GET /api/dashboard/server-status  - 服务器状态"
echo "  GET /api/dashboard/skills         - 技能数据（需登录）"
echo "  GET /api/dashboard/skill-queue    - 技能队列（需登录）"
echo ""
