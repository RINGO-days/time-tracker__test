<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function () {
            return view('auth.register',['nav' => false]);
        });

        Fortify::loginView(function () {
            return view('auth.login',['nav' => false]);
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email',['nav' => false]);
        });

        $this->app->instance(Registerresponse::class, new class implements RegisterResponse{
            public function toResponse($request)
            {
                return redirect('/email/verify',);
            }
        });

        $this->app->instance(Loginresponse::class, new class implements LoginResponse{
            public function toResponse($request)
            {
                if(! $request->user()->hasVerifiedEmail()){
                    return redirect('/email/verify');
                }
                return redirect('/attendance');
            }
        });

        $this->app->instance(Logoutresponse::class, new class implements LogoutResponse{
            public function toResponse($request)
            {
                return redirect('/login')->with('message','ログアウトしました。');
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
