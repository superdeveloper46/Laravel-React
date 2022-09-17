<?php
/**
 * Created by PhpStorm.
 * User: dmitri
 * Date: 1/27/19
 * Time: 9:55 PM
 */

namespace App\Providers;


use App\Repositories\UserRepository;
use App\Services\Passport\PasswordGrant;
use App\Services\Passport\BearerTokenResponse;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge;
use League\OAuth2\Server\AuthorizationServer;

class PassportServiceProvider extends \Laravel\Passport\PassportServiceProvider {
    
    /**
     * Make the authorization service instance.
     *
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function makeAuthorizationServer()
    {
        return new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            $this->makeCryptKey('private'),
            app('encrypter')->getKey(),
            new BearerTokenResponse() // <-- The class you created above
        );
    }
    
    /**
     * Create and configure a Password grant instance.
     *
     * @return \League\OAuth2\Server\Grant\PasswordGrant
     */
    protected function makePasswordGrant()
    {
        $grant = new PasswordGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );
        
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        
        return $grant;
    }
}