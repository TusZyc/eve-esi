<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * 资产控制器
 */
class AssetController extends Controller
{
    /**
     * 显示资产列表页面
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        return view('assets.index', [
            'user' => $user,
            'pageTitle' => '我的资产',
        ]);
    }
}
