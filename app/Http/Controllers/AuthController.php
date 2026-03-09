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
        
        // 从 URL 中提取 code 和 state
        $code = $this->extractCodeFromUrl($callbackUrl);
        $state = $this->extractStateFromUrl($callbackUrl);
        
        if (empty($code)) {
            Log::error('无法从 URL 中提取 code', ['url' => $callbackUrl]);
            return redirect()->route('auth.guide')
                ->with('error', '无法从 URL 中提取授权码（code），请检查 URL 是否正确');
        }
        
        // 验证 state（防止 CSRF）
        $savedState = session('esi_state');
        if ($state && $savedState && $state !== $savedState) {
            Log::error('State 验证失败', [
                'expected' => $savedState,
                'received' => $state,
            ]);
            return redirect()->route('auth.guide')
                ->with('error', '授权验证失败（state 不匹配），请重新授权');
        }
        
        Log::info('Code 提取成功', ['code' => substr($code, 0, 10) . '...']);
        
        // 用 code 换取 Token
        $tokenData = $this->getAccessToken($code);
        
        if (empty($tokenData['access_token'])) {
            Log::error('Token 换取失败', ['response' => $tokenData]);
            return redirect()->route('auth.guide')
                ->with('error', 'Token 换取失败：' . ($tokenData['error_description'] ?? $tokenData['error'] ?? '未知错误'));
        }
        
        Log::info('Token 换取成功', [
            'expires_in' => $tokenData['expires_in'] ?? 'N/A',
            'has_refresh' => !empty($tokenData['refresh_token']),
        ]);
        
        // 使用 Access Token 获取角色信息（使用官方验证端点）
        $characterResponse = Http::withToken($tokenData['access_token'])
            ->get('https://login.evepc.163.com/oauth/verify');
        
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
        
        // 更新 Token 信息（包括 Refresh Token）
        $user->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
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
     * 从回调 URL 中提取 code 参数
     */
    private function extractCodeFromUrl($url)
    {
        $queryParams = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        return $queryParams['code'] ?? null;
    }
    
    /**
     * 从回调 URL 中提取 state 参数
     */
    private function extractStateFromUrl($url)
    {
        $queryParams = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        return $queryParams['state'] ?? null;
    }
    
    /**
     * 用 authorization code 换取 Access Token
     */
    private function getAccessToken($code)
    {
        $tokenUrl = config('esi.oauth_url') . 'token';
        $clientId = config('esi.client_id');
        
        Log::info('请求 Token', [
            'url' => $tokenUrl,
            'client_id' => $clientId,
        ]);
        
        // 发送 POST 请求（不需要 Client Secret，因为是公开客户端）
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->post($tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
        ]);
        
        Log::info('Token 响应', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        
        if ($response->failed()) {
            return [
                'error' => 'Token request failed',
                'error_description' => $response->body(),
            ];
        }
        
        return $response->json();
    }
    
    /**
     * 用 Refresh Token 刷新 Access Token
     */
    public function refreshToken(User $user)
    {
        if (empty($user->refresh_token)) {
            Log::warning('没有 Refresh Token', ['user_id' => $user->id]);
            return false;
        }
        
        $tokenUrl = config('esi.oauth_url') . 'token';
        $clientId = config('esi.client_id');
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ])->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->refresh_token,
            'client_id' => $clientId,
        ]);
        
        if ($response->ok()) {
            $tokenData = $response->json();
            $user->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $user->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 1200),
            ]);
            Log::info('Token 刷新成功', ['user_id' => $user->id]);
            return true;
        }
        
        Log::error('Token 刷新失败', [
            'user_id' => $user->id,
            'error' => $response->body(),
        ]);
        return false;
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
