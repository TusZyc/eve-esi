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
            <p class="text-blue-200">绑定你的 EVE 角色</p>
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

            <!-- 步骤 2：授权并返回 Token -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 eve-glow">
                <h2 class="text-xl font-semibold mb-4">📋 第 2 步：授权并返回 Token</h2>
                
                <div class="space-y-4">
                    <ol class="list-decimal list-inside space-y-2 text-blue-100">
                        <li>打开上面生成的授权链接</li>
                        <li>登录网易通行证</li>
                        <li>选择 EVE 角色</li>
                        <li>确认授权</li>
                        <li>复制浏览器地址栏的完整 URL</li>
                        <li>粘贴到下面的输入框</li>
                    </ol>
                    
                    <form action="{{ route('auth.callback') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm text-blue-300 mb-2">粘贴授权后的完整 URL：</label>
                            <textarea name="callback_url" id="callbackUrl" required 
                                      placeholder="https://esi.evepc.163.com/ui/oauth2-redirect.html#access_token=..."
                                      class="w-full bg-black/30 border border-blue-500/50 rounded-lg p-3 text-white font-mono h-32"></textarea>
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
                <h2 class="text-xl font-semibold mb-4">📊 申请权限</h2>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center">
                        <span class="text-green-400 mr-2">✓</span>
                        <span>技能队列</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-400 mr-2">✓</span>
                        <span>技能信息</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-400 mr-2">✓</span>
                        <span>角色资产</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-green-400 mr-2">✓</span>
                        <span>钱包余额</span>
                    </div>
                </div>
                <p class="text-xs text-blue-300 mt-4">
                    ⚠️ Token 有效期约 20 分钟，过期后需重新授权
                </p>
            </div>
        </main>

        <!-- 页脚 -->
        <footer class="text-center mt-8 text-blue-300 text-sm">
            <a href="{{ route('home') }}" class="hover:text-white">← 返回首页</a>
        </footer>
    </div>

    <script>
        function generateAuthUrl() {
            // 配置
            const clientId = 'bc90aa496a404724a93f41b4f4e97761';
            const redirectUri = 'https://esi.evepc.163.com/ui/oauth2-redirect.html';
            const state = generateRandomState();
            const scopes = [
                'esi-skills.read_skills.v1',
                'esi-skills.read_skillqueue.v1',
                'esi-assets.read_assets.v1',
                'esi-wallet.read_character_wallet.v1'
            ];
            
            // 构建 URL
            const authUrl = `https://login.evepc.163.com/v2/oauth/authorize?` +
                `response_type=token&` +
                `client_id=${clientId}&` +
                `redirect_uri=${encodeURIComponent(redirectUri)}&` +
                `state=${state}&` +
                `scope=${encodeURIComponent(scopes.join(' '))}&` +
                `realm=eve&` +
                `device_id=${generateDeviceId()}`;
            
            // 显示结果
            document.getElementById('authUrl').value = authUrl;
            document.getElementById('authUrlContainer').classList.remove('hidden');
            
            // 保存 state 到 sessionStorage
            sessionStorage.setItem('esi_state', state);
        }
        
        function generateRandomState() {
            const array = new Uint8Array(16);
            crypto.getRandomValues(array);
            return Array.from(array, b => b.toString(16).padStart(2, '0')).join('');
        }
        
        function generateDeviceId() {
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
