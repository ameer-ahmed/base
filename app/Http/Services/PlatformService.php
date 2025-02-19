<?php

namespace App\Http\Services;

use App\Enums\Platform;

abstract class PlatformService
{
    abstract public static function platform(): Platform;
}
