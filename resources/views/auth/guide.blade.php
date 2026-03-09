<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权引导 - EVE ESI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .eve-bg {
            background: linear-gradient(135deg, #0c1445 0%, #1a237e 50%, #283593 100%);
        }
        .eve-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body class="eve-bg min-h-screen text-white">
    <div class="container mx-auto px-4 py-8">
        <!-- 头部 -->
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-2 eve-glow">🔐 EVE ESI 授权</h1>
            <p class="text-blue-200">绑定你的 EVE 角色（Authorization Code 模式）</p>
        </header>

        <main class="max-w-3xl mx-auto">
            <!-- 步骤 1：生成授权链接 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 eve-glow">
                <h2 class="text-xl font-semibold mb-4">📋 第 1 步：生成授权链接</h2>
                
                <div class="space-y-4">
                    <p class="text-blue-100">点击下方按钮生成授权链接：</p>
                    
                    <button onclick="generateAuthUrl()" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition-all eve-glow">
                        🔗 生成授权链接
                    </button>
                    
                    <div id="authUrlContainer" class="hidden">
                        <p class="text-sm text-blue-300 mb-2">复制下面的链接到浏览器打开：</p>
                        <textarea id="authUrl" readonly 
                                  class="w-full bg-black/30 border border-blue-500/50 rounded-lg p-3 text-sm text-green-400 font-mono h-32"></textarea>
                        <button onclick="copyAuthUrl()" 
                                class="mt-2 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg">
                            📋 复制链接
                        </button>
                    </div>
                </div>
            </div>

            <!-- 步骤 2：授权并返回 Code -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 eve-glow">
                <h2 class="text-xl font-semibold mb-4">📋 第 2 步：授权并返回 Code</h2>
                
                <div class="space-y-4">
                    <ol class="list-decimal list-inside space-y-2 text-blue-100">
                        <li>打开上面生成的授权链接</li>
                        <li>登录网易通行证</li>
                        <li>选择 EVE 角色</li>
                        <li>确认授权</li>
                        <li>复制浏览器地址栏的<strong>完整 URL</strong></li>
                        <li>粘贴到下面的输入框</li>
                    </ol>
                    
                    <form action="{{ route('auth.callback') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-blue-300 mb-2">粘贴授权后的完整 URL：</label>
                            <textarea name="callback_url" id="callbackUrl" required 
                                      placeholder="https://esi.evepc.163.com/ui/oauth2-redirect.html?code=YOUR_CODE&state=YOUR_STATE"
                                      class="w-full bg-black/30 border border-blue-500/50 rounded-lg p-3 text-white font-mono h-32"></textarea>
                            <p class="text-xs text-blue-400 mt-2">
                                💡 URL 应该包含 <code class="text-yellow-400">?code=</code> 参数
                            </p>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition-all eve-glow">
                            ✅ 提交授权
                        </button>
                    </form>
                </div>
            </div>

            <!-- 权限说明 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 eve-glow">
                <h2 class="text-xl font-semibold mb-4">📊 申请权限（70+ 个完整权限）</h2>
                <div class="grid grid-cols-3 gap-2 text-xs text-blue-100 max-h-64 overflow-y-auto">
                    <div>📅 日历</div>
                    <div>📍 位置</div>
                    <div>📧 邮件</div>
                    <div>📚 技能</div>
                    <div>💰 钱包</div>
                    <div>🔍 搜索</div>
                    <div>🧬 克隆</div>
                    <div>👥 联系人</div>
                    <div>🌌 宇宙</div>
                    <div>💥 击杀</div>
                    <div>🏢 军团</div>
                    <div>📦 资产</div>
                    <div>🪐 行星</div>
                    <div>🚀 舰队</div>
                    <div>🖥️ UI</div>
                    <div>⚙️ 配船</div>
                    <div>📈 市场</div>
                    <div>🎖️ 忠诚点</div>
                    <div>🏅 勋章</div>
                    <div>📊 声望</div>
                    <div>🔬 研究</div>
                    <div>🏭 工业</div>
                    <div>📋 蓝图</div>
                    <div>📢 通知</div>
                    <div>📜 合同</div>
                    <div>😴 疲劳</div>
                    <div>⛏️ 采矿</div>
                    <div>👑 标题</div>
                </div>
                <div class="bg-blue-500/20 border border-blue-500/50 rounded-lg p-4 mt-4">
                    <p class="text-sm text-blue-200">
                        <strong>✅ 3V 模式完整权限：</strong><br>
                        使用官方 Client ID，70+ 个完整权限<br>
                        Authorization Code 模式，可获得 Refresh Token，永久有效
                    </p>
                </div>
            </div>
        </main>

        <!-- 页脚 -->
        <footer class="text-center mt-8 text-blue-300 text-sm">
            <a href="{{ route('home') }}" class="hover:text-white">← 返回首页</a>
        </footer>
    </div>

    <script>
        function generateAuthUrl() {
            // 配置 - 使用 3V 模式的官方 Client ID
            const clientId = 'bc90aa496a404724a93f41b4f4e97761';
            const redirectUri = 'https://ali-esi.evepc.163.com/ui/oauth2-redirect.html';
            const state = generateRandomState();
            const deviceId = 'tus';  // 设置为 tus
            
            // 完整的 70+ 个权限（3V 模式）
            const scopes = [
                // 日历
                'esi-calendar.respond_calendar_events.v1',
                'esi-calendar.read_calendar_events.v1',
                // 位置
                'esi-location.read_location.v1',
                'esi-location.read_ship_type.v1',
                'esi-location.read_online.v1',
                // 邮件
                'esi-mail.organize_mail.v1',
                'esi-mail.read_mail.v1',
                'esi-mail.send_mail.v1',
                // 技能
                'esi-skills.read_skills.v1',
                'esi-skills.read_skillqueue.v1',
                // 钱包
                'esi-wallet.read_character_wallet.v1',
                'esi-wallet.read_corporation_wallets.v1',
                // 搜索
                'esi-search.search_structures.v1',
                // 克隆
                'esi-clones.read_clones.v1',
                'esi-clones.read_implants.v1',
                // 联系人
                'esi-characters.read_contacts.v1',
                'esi-characters.write_contacts.v1',
                'esi-corporations.read_contacts.v1',
                'esi-alliances.read_contacts.v1',
                // 宇宙
                'esi-universe.read_structures.v1',
                // 击杀
                'esi-killmails.read_killmails.v1',
                'esi-killmails.read_corporation_killmails.v1',
                // 军团
                'esi-corporations.read_corporation_membership.v1',
                'esi-corporations.read_structures.v1',
                'esi-corporations.track_members.v1',
                'esi-corporations.read_divisions.v1',
                'esi-corporations.read_titles.v1',
                'esi-corporations.read_blueprints.v1',
                'esi-corporations.read_standings.v1',
                'esi-corporations.read_starbases.v1',
                'esi-corporations.read_facilities.v1',
                'esi-corporations.read_medals.v1',
                'esi-corporations.read_container_logs.v1',
                'esi-corporations.read_fw_stats.v1',
                // 资产
                'esi-assets.read_assets.v1',
                'esi-assets.read_corporation_assets.v1',
                // 行星
                'esi-planets.manage_planets.v1',
                'esi-planets.read_customs_offices.v1',
                // 舰队
                'esi-fleets.read_fleet.v1',
                'esi-fleets.write_fleet.v1',
                // UI
                'esi-ui.open_window.v1',
                'esi-ui.write_waypoint.v1',
                // 配船
                'esi-fittings.read_fittings.v1',
                'esi-fittings.write_fittings.v1',
                // 市场
                'esi-markets.structure_markets.v1',
                'esi-markets.read_character_orders.v1',
                'esi-markets.read_corporation_orders.v1',
                // 忠诚点
                'esi-characters.read_loyalty.v1',
                // 机会
                'esi-characters.read_opportunities.v1',
                // 勋章
                'esi-characters.read_medals.v1',
                // 声望
                'esi-characters.read_standings.v1',
                // 研究
                'esi-characters.read_agents_research.v1',
                // 工作
                'esi-industry.read_character_jobs.v1',
                'esi-industry.read_corporation_jobs.v1',
                // 蓝图
                'esi-characters.read_blueprints.v1',
                // 角色
                'esi-characters.read_corporation_roles.v1',
                'esi-characters.read_notifications.v1',
                'esi-characters.read_fw_stats.v1',
                'esi-characterstats.read.v1',
                // 合同
                'esi-contracts.read_character_contracts.v1',
                'esi-contracts.read_corporation_contracts.v1',
                // 疲劳
                'esi-characters.read_fatigue.v1',
                // 采矿
                'esi-industry.read_character_mining.v1',
                'esi-industry.read_corporation_mining.v1',
                // 标题
                'esi-characters.read_titles.v1',
            ];
            
            // 构建授权 URL（Authorization Code 模式：response_type=code）
            const authUrl = `https://login.evepc.163.com/v2/oauth/authorize?` +
                `response_type=code&` +
                `client_id=${clientId}&` +
                `redirect_uri=${encodeURIComponent(redirectUri)}&` +
                `state=${state}&` +
                `scope=${encodeURIComponent(scopes.join(' '))}&` +
                `device_id=${deviceId}`;
            
            // 显示结果
            document.getElementById('authUrl').value = authUrl;
            document.getElementById('authUrlContainer').classList.remove('hidden');
            
            // 保存 state 到 sessionStorage（用于验证回调）
            sessionStorage.setItem('esi_state', state);
        }
        
        function generateRandomState() {
            const array = new Uint8Array(16);
            crypto.getRandomValues(array);
            return Array.from(array, b => b.toString(16).padStart(2, '0')).join('');
        }
        
        function generateDeviceId() {
            // 生成一个固定的 Device ID（可以用项目名 + 随机数）
            return '12345678-1234-1234-1234-123456789012';
        }
        
        function copyAuthUrl() {
            const textarea = document.getElementById('authUrl');
            textarea.select();
            document.execCommand('copy');
            alert('✅ 已复制到剪贴板！');
        }
    </script>
</body>
</html>
