<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\EveHelper;

/**
 * 资产数据 API 控制器
 * 
 * 提供角色资产查询接口
 */
class AssetDataController extends Controller
{
    /**
     * 获取角色资产列表
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        Log::info('📦 [API] 请求资产数据', [
            'user_id' => $user->id ?? 'null',
            'character_id' => $user->eve_character_id ?? 'null',
        ]);
        
        // 检查用户是否登录
        if (!$user || !$user->eve_character_id) {
            Log::warning('📦 [API] 未授权或无角色', ['user_id' => $user->id ?? 'null']);
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => '未授权，请重新登录',
            ], 401);
        }
        
        // 检查是否有 Access Token
        if (empty($user->access_token)) {
            Log::warning('📦 [API] 缺少 Access Token', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'error' => 'no_token',
                'message' => '缺少访问令牌',
            ], 401);
        }
        
        try {
            Log::info('📦 [API] 请求 EVE API 资产数据');
            
            // 从 EVE API 获取资产列表
            $assetsResponse = Http::timeout(15)
                ->withToken($user->access_token)
                ->get(config('esi.base_url') . "characters/{$user->eve_character_id}/assets/");
            
            if ($assetsResponse->failed()) {
                Log::error('📦 [API] 资产数据获取失败', [
                    'status' => $assetsResponse->status(),
                    'body' => $assetsResponse->body(),
                ]);
                
                // Token 过期或权限不足
                if ($assetsResponse->status() === 401 || $assetsResponse->status() === 403) {
                    return response()->json([
                        'success' => false,
                        'error' => 'token_expired',
                        'message' => 'Token 已过期或权限不足',
                    ], 401);
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'eve_api_error',
                    'message' => 'EVE API 错误：HTTP ' . $assetsResponse->status(),
                ], $assetsResponse->status());
            }
            
            $assets = $assetsResponse->json();
            Log::info('📦 [API] 资产数据获取成功', ['count' => count($assets)]);
            
            // 如果没有资产
            if (empty($assets)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'assets' => [],
                        'summary' => [
                            'total_assets' => 0,
                            'total_value' => 0,
                            'total_volume' => 0,
                            'locations_count' => 0,
                        ],
                    ],
                ]);
            }
            
            // 批量查询物品名称
            $typeIds = array_unique(array_column($assets, 'type_id'));
            Log::info('📦 [API] 查询物品名称', ['count' => count($typeIds)]);
            $typeNames = EveHelper::getNamesByIds($typeIds, 'item');
            
            // 批量查询位置名称
            $locationIds = array_unique(array_column($assets, 'location_id'));
            Log::info('📦 [API] 查询位置名称', ['count' => count($locationIds)]);
            $locationNames = $this->getLocationNames($locationIds, $user->access_token);
            
            // 格式化资产数据
            $formattedAssets = $this->formatAssets($assets, $typeNames, $locationNames);
            
            // 计算统计信息
            $summary = $this->calculateSummary($formattedAssets);
            
            Log::info('📦 [API] 资产数据处理完成', [
                'total_assets' => $summary['total_assets'],
                'locations_count' => $summary['locations_count'],
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'assets' => $formattedAssets,
                    'summary' => $summary,
                ],
            ]);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('📦 [API] 资产数据连接失败：' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'connection_timeout',
                'message' => '连接超时，EVE API 可能不可用',
            ], 503);
        } catch (\Exception $e) {
            Log::error('📦 [API] 资产数据请求异常：' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'unknown_error',
                'message' => '未知错误：' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * 批量获取位置名称
     */
    private function getLocationNames(array $locationIds, string $accessToken): array
    {
        $names = [];
        
        try {
            // 使用批量查询接口
            $response = Http::timeout(10)
                ->withToken($accessToken)
                ->post(config('esi.base_url') . 'universe/names/', $locationIds);
            
            if ($response->ok()) {
                $result = $response->json();
                foreach ($result as $item) {
                    $names[$item['id']] = $item['name'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('📦 [API] 位置名称查询失败：' . $e->getMessage());
        }
        
        return $names;
    }
    
    /**
     * 格式化资产数据
     */
    private function formatAssets(array $assets, array $typeNames, array $locationNames): array
    {
        $formatted = [];
        
        foreach ($assets as $asset) {
            $typeId = $asset['type_id'];
            $locationId = $asset['location_id'];
            
            $formatted[] = [
                'type_id' => $typeId,
                'type_name' => $typeNames[$typeId] ?? '未知物品',
                'location_id' => $locationId,
                'location_name' => $locationNames[$locationId] ?? '未知位置',
                'location_flag' => $asset['location_flag'] ?? 'Unknown',
                'quantity' => $asset['quantity'] ?? 1,
                'is_singleton' => $asset['is_singleton'] ?? false,
                'volume' => 0, // 体积数据需要从物品数据库查询
                'price' => 0,  // 价格数据需要从市场 API 查询
                'total_value' => 0,
            ];
        }
        
        return $formatted;
    }
    
    /**
     * 计算统计信息
     */
    private function calculateSummary(array $assets): array
    {
        $totalAssets = count($assets);
        $totalQuantity = array_sum(array_column($assets, 'quantity'));
        $locations = array_unique(array_column($assets, 'location_id'));
        
        return [
            'total_assets' => $totalAssets,           // 物品种类数
            'total_quantity' => $totalQuantity,       // 物品总数量
            'total_value' => 0,                       // 总价值（待实现）
            'total_volume' => 0,                      // 总体积（待实现）
            'locations_count' => count($locations),   // 位置数量
        ];
    }
}
