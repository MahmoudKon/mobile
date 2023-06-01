<?php

namespace App\Http\Middleware;

use App\Badrshop;
use Closure;

class Lines
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Badrshop::where('serial_id', auth()->guard('rep')->user()->shop_id)->first()->allow_lines) {
            return $next($request);
        } else {
            return response()->json(['status' => false, 'message' => 'خطوط السير غير مفعلة, برجاء التواصل مع الإدارة لتفعيلها'], 401);
        }
    }
}
