<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Company;
use App\Models\User;
use Closure;
use Laravel\Passport\Passport;

class AuthUserSetup
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
        $user = $request->user();
        
        if ($user) {
            if ($user->role === User::$ROLE_AGENCY) {
                $user = Agency::find($user->id);
            } elseif ($user->role === User::$ROLE_COMPANY) {
                $user = Company::find($user->id);
            } elseif ($user->role === User::$ROLE_AGENT) {
                $user = Agent::find($user->id);
            }
    
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            $user->setupUserRolePermissions();
        }
    
    
        return $next($request);
    }
}
