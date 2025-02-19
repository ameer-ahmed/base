<?php

namespace App\Http\Services\Api\V1\Auth;

use App\Enums\Platform;

class AuthMobileService extends AuthService
{
    public static function platform(): Platform
    {
        return Platform::MOBILE;
    }

    public function whatIsMyPlatform() : string // will be invoked if the request came from mobile endpoints
    {
        return 'platform: mobile!';
    }
}
