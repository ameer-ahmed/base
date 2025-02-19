<?php

namespace App\Http\Services\Api\V1\Auth;

use App\Enums\Platform;

class AuthWebService extends AuthService
{
    public static function platform(): Platform
    {
        return Platform::WEBSITE;
    }

    public function whatIsMyPlatform() : string // will be invoked if the request came from website endpoints
    {
        return 'platform: website!';
    }
}
