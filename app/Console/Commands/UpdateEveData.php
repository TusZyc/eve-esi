<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\EveHelper;

/**
 * 更新 EVE 物品数据
 */
class UpdateEveData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eve:update-data {--force : 强制更新，忽略时间检查}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从 ceve-market.org 更新 EVE 物品数据';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('开始更新 EVE 物品数据...');
        
        // 检查是否需要更新
        if (!$this->option('force') && !EveHelper::needsUpdate()) {
            $lastUpdate = EveHelper::getLastUpdateTime();
            $this->info("数据上次更新时间：{$lastUpdate}");
            $this->info("数据未超过 7 天，跳过更新。使用 --force 强制更新。");
            return 0;
        }
        
        $this->info('正在下载最新数据...');
        
        $success = EveHelper::updateData();
        
        if ($success) {
            $this->info('✅ 数据更新成功！');
            $this->info('物品总数：' . EveHelper::getItemCount());
            $this->info('更新时间：' . EveHelper::getLastUpdateTime());
            return 1;
        } else {
            $this->error('❌ 数据更新失败，请检查日志');
            return 0;
        }
    }
}
