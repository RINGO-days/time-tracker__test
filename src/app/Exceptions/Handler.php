<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];
    protected $internalDontReport = [];
    protected $exceptionMap = [];
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */

    // 本人以外のアクセスによるエラーである、ModelNotFoundExceptionがNotFoundHttpEcveptionに、
    // AuthorizationExceptionが、AccessDeniedExcptionに自動で内包？されてしまうため、その自動機能の解除アクション
    protected function prepareException(Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return $e;
        }
        if ($e instanceof AuthorizationException) {
            return $e;
        }

        return parent::prepareException($e);
    }

    public function register() : void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ModelNotFoundException $e, $request){
            if($request->is('api/*')){
                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。'
                ],404);
            }
        });
        // $this->renderable(function (AccessDeniedHttpException $e, $request){
        //     if($request->is('api/*')){
        //         return response()->json([
        //             'error' => 'この操作を実行する権限がありません。'
        //         ],403);
        //     }
        // });
        $this->renderable(function (AuthorizationException $e, $request){
            if($request->is('api/*')){
                return response()->json([
                    'error' => 'この操作を実行する権限がありません。'
                ],403);
            }
        });
        // $this->renderable(function (NotFoundHttpException $e, $request){
        //     if($request->is('api/*')){
        //         return response()->json([
        //             'error' => 'エンドポイントが違います。'
        //         ],404);
        //     }
        // });
    }
}
