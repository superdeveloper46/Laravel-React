<?php

namespace App\Services\Passport;

use App\Models\User;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class BearerTokenResponse extends \League\OAuth2\Server\ResponseTypes\BearerTokenResponse
{
    /**
     * Add custom fields to your Bearer Token response here, then override
     * AuthorizationServer::getResponseType() to pull in your version of
     * this class rather than the default.
     *
     * @param AccessTokenEntityInterface $accessToken
     *
     * @return array
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken)
    {
        $user = User::find($this->accessToken->getUserIdentifier());
        
        if ($user->role === User::$ROLE_COMPANY) {
            $user->agencies = $user->getAgencies();
        }

        return [
            'user' => $user->only([
                'id', 'name', 'email', 'avatar_path', 'permissions', 'phone', 'role', 'agencies'
            ]),
        ];
    }
}
