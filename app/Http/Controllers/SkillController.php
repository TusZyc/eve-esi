<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SkillController extends Controller
{
    /**
     * 显示技能队列页面
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // 检查 Token 是否过期
        if ($user->isTokenExpired()) {
            $this->refreshToken($user);
        }
        
        // 获取技能信息
        $skillsData = $this->getSkillsData($user);
        
        // 获取技能队列
        $skillQueue = $this->getSkillQueue($user);
        
        // 计算技能点
        $totalSP = $skillsData['total_sp'] ?? 0;
        $unallocatedSP = $skillsData['unallocated_sp'] ?? 0;
        
        // 计算训练总时间
        $trainingTimeRemaining = 0;
        if (!empty($skillQueue)) {
            foreach ($skillQueue as $queueItem) {
                $trainingTimeRemaining += ($queueItem['finish_date_total'] ?? 0) - time();
            }
        }
        
        return view('skills.index', [
            'user' => $user,
            'skillsData' => $skillsData,
            'skillQueue' => $skillQueue,
            'totalSP' => $totalSP,
            'unallocatedSP' => $unallocatedSP,
            'trainingTimeRemaining' => $trainingTimeRemaining,
        ]);
    }
    
    /**
     * 获取技能数据
     */
    private function getSkillsData($user)
    {
        $cacheKey = 'skills_' . $user->eve_character_id;
        
        return Cache::remember($cacheKey, 300, function() use ($user) {
            $response = Http::withToken($user->access_token)
                ->get(config('esi.base_url') . 'characters/' . $user->eve_character_id . '/skills/');
            
            return $response->ok() ? $response->json() : null;
        });
    }
    
    /**
     * 获取技能队列
     */
    private function getSkillQueue($user)
    {
        $cacheKey = 'skillqueue_' . $user->eve_character_id;
        
        return Cache::remember($cacheKey, 60, function() use ($user) {
            $response = Http::withToken($user->access_token)
                ->get(config('esi.base_url') . 'characters/' . $user->eve_character_id . '/skillqueue/');
            
            return $response->ok() ? $response->json() : [];
        });
    }
    
    /**
     * 刷新 Token
     */
    private function refreshToken($user)
    {
        if (empty($user->refresh_token)) {
            return;
        }
        
        $response = Http::withBasicAuth(
            config('esi.client_id'),
            config('esi.client_secret')
        )->post(config('esi.oauth_url') . 'token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->refresh_token,
        ]);
        
        if ($response->ok()) {
            $tokenData = $response->json();
            $user->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $user->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);
        }
    }
    
    /**
     * 格式化时间
     */
    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . '秒';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . '分' . ($seconds % 60) . '秒';
        } elseif ($seconds < 86400) {
            return floor($seconds / 3600) . '小时' . floor(($seconds % 3600) / 60) . '分';
        } else {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            return $days . '天' . $hours . '小时';
        }
    }
}
