<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\EveHelper;

/**
 * 角色位置 API 控制器
 */
class CharacterLocationController extends Controller
{
    /**
     * 获取角色当前位置
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        Log::info('📍 [API] 请求角色位置', [
            'user_id' => $user->id ?? 'null',
            'character_id' => $user->eve_character_id ?? 'null',
        ]);
        
        if (!$user || !$user->eve_character_id) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => '未授权，请重新登录',
            ], 401);
        }
        
        if (empty($user->access_token)) {
            return response()->json([
                'success' => false,
                'error' => 'no_token',
                'message' => '缺少访问令牌',
            ], 401);
        }
        
        try {
            // 获取角色位置
            $locationResponse = Http::timeout(10)
                ->withToken($user->access_token)
                ->get(config('esi.base_url') . "characters/{$user->eve_character_id}/location/");
            
            if ($locationResponse->failed()) {
                Log::error('📍 [API] 位置信息获取失败', [
                    'status' => $locationResponse->status(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'eve_api_error',
                    'message' => 'EVE API 错误',
                ], $locationResponse->status());
            }
            
            $location = $locationResponse->json();
            
            // 位置信息
            $solarSystemId = $location['solar_system_id'] ?? null;
            $stationId = $location['station_id'] ?? null;
            $structureId = $location['structure_id'] ?? null;
            
            Log::info('📍 [API] 位置信息', [
                'solar_system_id' => $solarSystemId,
                'station_id' => $stationId,
                'structure_id' => $structureId,
            ]);
            
            // 查询名称（先查询星系名称）
            $names = [];
            $idsToQuery = [];
            if ($solarSystemId) $idsToQuery[] = $solarSystemId;
            if ($stationId) $idsToQuery[] = $stationId;
            // structure_id 可能超过 int32 范围，跳过查询
            
            if (!empty($idsToQuery)) {
                $namesResponse = Http::timeout(10)
                    ->withToken($user->access_token)
                    ->post(config('esi.base_url') . 'universe/names/', $idsToQuery);
                
                if ($namesResponse->ok()) {
                    $namesResult = $namesResponse->json();
                    foreach ($namesResult as $item) {
                        $names[$item['id']] = $item['name'];
                    }
                }
            }
            
            // 构建位置显示文本
            $solarSystemName = $names[$solarSystemId] ?? '未知星系';
            
            if ($stationId && isset($names[$stationId])) {
                // 在 NPC 空间站
                $stationName = $names[$stationId];
                // 简化空间站名称（去掉冗长部分）
                $shortStationName = $this->shortenStationName($stationName);
                $locationDisplay = "{$solarSystemName} - {$shortStationName}";
            } elseif ($structureId) {
                // 在玩家建筑（structure_id 太大无法查询名称）
                $locationDisplay = "{$solarSystemName} - 玩家建筑";
            } else {
                // 在太空中（未停靠）
                $locationDisplay = "{$solarSystemName} - 未停靠";
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'solar_system_id' => $solarSystemId,
                    'solar_system_name' => $solarSystemName,
                    'station_id' => $stationId,
                    'station_name' => $names[$stationId] ?? null,
                    'structure_id' => $structureId,
                    'structure_name' => $names[$structureId] ?? null,
                    'location_display' => $locationDisplay,
                    'is_docked' => $stationId !== null || $structureId !== null,
                ],
            ]);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('📍 [API] 位置信息连接失败：' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'connection_timeout',
                'message' => '连接超时',
            ], 503);
        } catch (\Exception $e) {
            Log::error('📍 [API] 位置信息请求异常：' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'unknown_error',
                'message' => '未知错误',
            ], 500);
        }
    }
    
    /**
     * 简化空间站名称
     */
    private function shortenStationName(string $name): string
    {
        // 去掉常见的冗长部分
        $patterns = [
            ' - Moon \d+' => '',
            ' - Planet \d+' => '',
            ' - Asteroid Belt' => '',
            ' Republic Parliament Bureau' => ' 共和国议会局',
            ' Navy Assembly Plant' => ' 海军组装厂',
            ' Trading Post' => ' 贸易站',
        ];
        
        $shortName = $name;
        foreach ($patterns as $pattern => $replacement) {
            $shortName = preg_replace('/' . $pattern . '/', $replacement, $shortName);
        }
        
        return $shortName;
    }
}
