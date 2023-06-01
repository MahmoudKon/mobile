<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
// use Illuminate\Foundation\Auth\Access\AuthorizesResources;

class Controller extends BaseController
{
    use AuthorizesRequests, 
    // AuthorizesResources, 
    DispatchesJobs, ValidatesRequests;
    
    public function __construct()
    {
        if (auth()->guard('user')->check()) {
            \Log::info("URI => ". request()->getPathInfo());
            \Log::info("Controller => User : " . auth()->guard('user')->user()->user_name . "  |  shop_id : " . auth()->guard('user')->user()->shop_id);
            \Log::info("________________________________________________________________");
        }
    }
}
