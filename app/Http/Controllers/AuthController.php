<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * 显示授权引导页面
     */
    public function guide()
    {
        return view('auth.guide');
    }
    
    /**
     * 处理 OAuth2 回调（用户粘贴的 URL）
     */
    public function callback(Request $request)
    {
        Log::info('OAuth2 回调', ['input' => $request->all()]);
        
        // 获取用户粘贴的 URL
        $callbackUrl = $request->input('callback_url');
        
        if (empty($callbackUrl)) {
            Log::error('缺少回调 URL');
            return redirect()->route('auth.guide')
                ->with('error', '请提供授权后的完整 URL');
        }
        
        // 从 URL 中提取 Token
        $tokenData = $this->extractTokenFromUrl($callbackUrl);
        
        if (empty($tokenData['access_token'])) {
            Log::error('无法从 URL 中提取 Token', ['url' => $callbackUrl]);
            return redirect()->route('auth.guide')
                ->with('error', '无法从 URL 中提取 Access Token，请检查 URL 是否正确');
        }
        
        Log::info('Token 提取成功', ['expires_in' => $tokenData['expires_in'] ?? 'N/A']);
        
        // 使用 Access Token 获取角色信息
        $characterResponse = Http::withToken($tokenData['access_token'])
            ->get(config('esi.base_url') . 'verify/');
        
        if ($characterResponse->failed()) {
            Log::error('获取角色信息失败', [
                'status' => $characterResponse->status(),
                'body' => $characterResponse->body(),
            ]);
            return redirect()->route('auth.guide')
                ->with('error', '获取角色信息失败：' . $characterResponse->body());
        }
        
        $characterData = $characterResponse->json();
        Log::info('角色信息', ['character' => $characterData]);
        
        // 查找或创建用户
        $user = User::firstOrCreate(
            ['eve_character_id' => $characterData['CharacterID']],
            [
                'name' => $characterData['CharacterName'],
                'email' => $characterData['CharacterName'] . '@eve.local',
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ]
        );
        
        // 更新 Token 信息
        $user->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => null, // Implicit 模式没有 Refresh Token
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 1200),
            'corporation_id' => $characterData['CorporationID'] ?? null,
            'alliance_id' => $characterData['AllianceID'] ?? null,
        ]);
        
        Log::info('用户登录成功', ['user_id' => $user->id, 'character_id' => $user->eve_character_id]);
        
        // 登录用户
        Auth::login($user);
        
        return redirect()->route('dashboard')
            ->with('success', '欢迎，' . $characterData['CharacterName'] . '! 授权成功！');
    }
    
    /**
     * 从回调 URL 中提取 Token
     */
    private function extractTokenFromUrl($url)
    {
        $tokenData = [];
        
        // Implicit 模式：URL fragment (#access_token=xxx)
        if (strpos($url, '#access_token=') !== false) {
            $fragment = parse_url($url, PHP_URL_FRAGMENT);
            if ($fragment) {
                parse_str($fragment, $params);
                $tokenData = [
                    'access_token' => $params['access_token'] ?? null,
                    'token_type' => $params['token_type'] ?? 'Bearer',
                    'expires_in' => isset($params['expires_in']) ? (int) $params['expires_in'] : 1200,
                    'scope' => $params['scope'] ?? null,
                ];
            }
        }
        
        // Authorization Code 模式：?code=xxx
        if (strpos($url, '?code=') !== false || strpos($url, '&code=') !== false) {
            $queryParams = [];
            parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
            $tokenData = [
                'code' => $queryParams['code'] ?? null,
                'state' => $queryParams['state'] ?? null,
            ];
        }
        
        return $tokenData;
    }
    
    /**
     * 登出
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('用户登出', ['user_id' => Auth::id()]);
        
        return redirect()->route('home')
            ->with('success', '已安全登出');
    }
}
