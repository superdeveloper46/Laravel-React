<?php
/**
 * Created by PhpStorm.
 * User: dmitri
 * Date: 1/27/19
 * Time: 9:53 PM
 */

namespace App\Repositories;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Company;
use App\Models\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class UserRepository extends \Laravel\Passport\Bridge\UserRepository {
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $user = User::where('email', $username)->first();
        if (! $user) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }
    
        return new \Laravel\Passport\Bridge\User($user->getAuthIdentifier());
    }
}