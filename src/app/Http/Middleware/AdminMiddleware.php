<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * **追加機能**
     * スタッフが管理者用のページを閲覧しようとした際、メッセージと共に最初の打刻画面に戻す
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) : Response
    {
        if(!auth()->user()->is_admin){
            return redirect('/')->with('message','管理者権限がありません');
        }
        return $next($request);
    }
}
