<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ViewGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(!auth()->user()->is_admin){
            $request->mergeIfMissing([
                'user_id' => auth()->id()
            ]);

            if((int)$request->user_id !== auth()->id()){
                return back()->with('message','他ユーザーの閲覧権限がありません。');
            }
        }

        return $next($request);
    }
}
