<?php

namespace App\Helpers;

use App\Services\EveDataService;

/**
 * EVE 辅助函数
 */
class EveHelper
{
    /**
     * Eve 数据服务实例
     */
    private static $service = null;
    
    /**
     * 获取数据服务实例
     */
    private static function getService()
    {
        if (self::$service === null) {
            self::$service = new EveDataService();
        }
        return self::$service;
    }
    
    /**
     * 通过 ID 获取物品名称
     */
    public static function getNameById($id, $type = 'item')
    {
        return self::getService()->getNameById($id, $type);
    }
    
    /**
     * 批量获取名称
     */
    public static function getNamesByIds($ids, $type = 'item')
    {
        return self::getService()->getNamesByIds($ids, $type);
    }
    
    /**
     * 获取数据更新时间
     */
    public static function getLastUpdateTime()
    {
        return self::getService()->getLastUpdateTime();
    }
    
    /**
     * 获取物品总数
     */
    public static function getItemCount()
    {
        return self::getService()->getItemCount();
    }
    
    /**
     * 检查是否需要更新数据
     */
    public static function needsUpdate()
    {
        return self::getService()->needsUpdate();
    }
    
    /**
     * 更新数据
     */
    public static function updateData()
    {
        return self::getService()->updateData();
    }
}
