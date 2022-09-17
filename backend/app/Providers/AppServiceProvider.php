<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app('url')->forceRootUrl(env('APP_URL'));
        \Validator::extend('userEmail', function ($attribute, $value, $parameters, $validator) {
            if (isset($parameters[0])) {
                return !User::where('email', $value)->withTrashed()->whereNotIn('id', [$parameters[0]])->count();
            }
            return !User::where('email', $value)->withTrashed()->count();
        });
        \Validator::replacer('user_email', function ($message, $attribute, $rule, $parameters) {
            return 'Email already in use!';
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
