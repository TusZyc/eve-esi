<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的资产 - EVE ESI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .eve-bg {
            background: linear-gradient(135deg, #0c1445 0%, #1a237e 50%, #283593 100%);
        }
        .eve-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        
        /* 骨架屏动画 */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.03) 100%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
            border-radius: 4px;
        }
    </style>
</head>
<body class="eve-bg min-h-screen text-white">
    <!-- 导航栏 -->
    <nav class="bg-white/10 backdrop-blur-lg border-b border-white/20">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold">🚀 EVE ESI</a>
                <div class="flex items-center space-x-4">
                    <span class="text-blue-200">欢迎，{{ $user->name }}</span>
                    <a href="{{ route('dashboard') }}" class="text-blue-300 hover:text-white">🏠 返回仪表板</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- 页面标题 -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">📦 我的资产</h1>
            <p class="text-blue-300">查看和管理你的角色资产</p>
        </div>

        <!-- 统计信息 -->
        <div id="asset-summary" class="grid md:grid-cols-4 gap-4 mb-8">
            <!-- 骨架屏 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow">
                <div class="skeleton h-8 w-20 mb-2"></div>
                <div class="skeleton h-4 w-32"></div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow">
                <div class="skeleton h-8 w-20 mb-2"></div>
                <div class="skeleton h-4 w-32"></div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow">
                <div class="skeleton h-8 w-20 mb-2"></div>
                <div class="skeleton h-4 w-32"></div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow">
                <div class="skeleton h-8 w-20 mb-2"></div>
                <div class="skeleton h-4 w-32"></div>
            </div>
        </div>

        <!-- 资产列表 -->
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">资产列表</h2>
                <div class="flex items-center space-x-4">
                    <input type="text" id="search-input" placeholder="搜索物品名称..." 
                           class="bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-blue-300 focus:outline-none focus:border-blue-400">
                </div>
            </div>
            
            <div id="asset-list" class="space-y-3">
                <!-- 骨架屏 -->
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="skeleton h-5 w-3/4 mb-2"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="skeleton h-5 w-3/4 mb-2"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="skeleton h-5 w-3/4 mb-2"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
            </div>
            
            <!-- 分页（暂不实现） -->
            <div id="pagination" class="mt-6 hidden">
                <!-- 分页控件 -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const API_ENDPOINT = '/api/dashboard/assets';
        let allAssets = [];
        let filteredAssets = [];

        // 格式化工具
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // 显示错误
        function showError(containerId, icon, title, message) {
            const container = document.getElementById(containerId);
            container.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">${icon}</div>
                    <p class="text-xl text-blue-300 mb-2">${title}</p>
                    <p class="text-blue-400">${message}</p>
                </div>
            `;
        }

        // 加载资产数据
        async function loadAssets() {
            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                // 检查 401 未授权
                if (response.status === 401) {
                    showError('asset-list', '🔐', '未授权', '会话已过期，请刷新页面重新登录');
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    allAssets = result.data.assets || [];
                    filteredAssets = allAssets;
                    const summary = result.data.summary;

                    // 渲染统计信息
                    renderSummary(summary);

                    // 渲染资产列表
                    renderAssetList(filteredAssets);
                } else {
                    let icon = '⚠️';
                    let title = '加载失败';
                    let message = result.message || '请稍后再试';

                    if (result.error === 'connection_timeout') {
                        icon = '🔄';
                        title = '连接超时';
                        message = 'EVE API 可能不可用，请稍后再试';
                    } else if (result.error === 'token_expired') {
                        icon = '🔐';
                        title = 'Token 过期';
                        message = '请刷新 Token 或重新授权';
                    }

                    showError('asset-list', icon, title, message);
                }
            } catch (error) {
                console.error('加载资产失败:', error);
                showError('asset-list', '⚠️', '加载失败', '网络错误，请刷新页面重试');
            }
        }

        // 渲染统计信息
        function renderSummary(summary) {
            const container = document.getElementById('asset-summary');
            container.innerHTML = `
                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow text-center">
                    <div class="text-3xl font-bold text-blue-400 mb-2">${formatNumber(summary.total_assets)}</div>
                    <div class="text-sm text-blue-200">物品种类</div>
                </div>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow text-center">
                    <div class="text-3xl font-bold text-green-400 mb-2">${formatNumber(summary.total_quantity || 0)}</div>
                    <div class="text-sm text-blue-200">物品总数</div>
                </div>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow text-center">
                    <div class="text-3xl font-bold text-yellow-400 mb-2">${formatNumber(summary.locations_count)}</div>
                    <div class="text-sm text-blue-200">存放位置</div>
                </div>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 eve-glow text-center">
                    <div class="text-3xl font-bold text-purple-400 mb-2">${summary.total_value > 0 ? formatNumber(summary.total_value) + ' ISK' : '计算中'}</div>
                    <div class="text-sm text-blue-200">总价值</div>
                </div>
            `;
        }

        // 渲染资产列表
        function renderAssetList(assets) {
            const container = document.getElementById('asset-list');

            if (assets.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📦</div>
                        <p class="text-blue-300">暂无资产数据</p>
                        <p class="text-blue-400 text-sm mt-2">你的角色目前没有资产</p>
                    </div>
                `;
                return;
            }

            let html = '';
            assets.forEach(asset => {
                html += `
                    <div class="bg-white/5 rounded-lg p-4 hover:bg-white/10 transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="font-medium text-lg">${asset.type_name}</div>
                                <div class="text-sm text-blue-300 mt-1">
                                    📍 ${asset.location_name}
                                    <span class="mx-2">•</span>
                                    📦 ${asset.location_flag}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold text-blue-400">x${formatNumber(asset.quantity)}</div>
                                ${asset.total_value > 0 ? `<div class="text-sm text-yellow-400">${formatNumber(asset.total_value)} ISK</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // 搜索功能
        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', (e) => {
                const keyword = e.target.value.toLowerCase().trim();
                
                if (!keyword) {
                    filteredAssets = allAssets;
                } else {
                    filteredAssets = allAssets.filter(asset => 
                        asset.type_name.toLowerCase().includes(keyword) ||
                        asset.location_name.toLowerCase().includes(keyword)
                    );
                }
                
                renderAssetList(filteredAssets);
            });
        }

        // 页面加载完成后开始加载数据
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📦 开始加载资产数据...');
            loadAssets();
            setupSearch();
        });
    </script>
</body>
</html>
