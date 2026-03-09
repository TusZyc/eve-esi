<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\EveHelper;

class EveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 注册 Blade 指令
        Blade::directive('eveName', function ($expression) {
            return "<?php echo App\Helpers\EveHelper::getNameById({$expression}); ?>";
        });
        
        Blade::directive('eveNames', function ($expression) {
            return "<?php echo json_encode(App\Helpers\EveHelper::getNamesByIds({$expression})); ?>";
        });
        
        Blade::directive('eveDataUpdateTime', function () {
            return "<?php echo App\Helpers\EveHelper::getLastUpdateTime(); ?>";
        });
        
        Blade::directive('eveItemCount', function () {
            return "<?php echo App\Helpers\EveHelper::getItemCount(); ?>";
        });
    }
}
