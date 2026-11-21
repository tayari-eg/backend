<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Public\AppConfigResource;

class AppConfigController extends Controller
{
    /**
     * Public app configuration: minimum supported and latest app versions, and the server time.
     *
     * @unauthenticated
     */
    public function __invoke(): AppConfigResource
    {
        return new AppConfigResource([
            'min_supported_versions' => config('tayari.min_supported_versions'),
            'latest_versions' => config('tayari.latest_versions'),
        ]);
    }
}
