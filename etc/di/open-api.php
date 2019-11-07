<?php declare(strict_types=1);

use OpenApi\Annotations\OpenApi;
use function OpenApi\scan;

return [
    'internal.http-server.open-api.yaml' => \DI\factory(function (
        OpenApi $openApi
    ) {
        return $openApi->toYaml();
    })
        ->parameter('openApi', \DI\get('internal.http-server.open-api.openapi')),
    'internal.http-server.open-api.json' => \DI\factory(function (
        OpenApi $openApi
    ) {
        return $openApi->toJson();
    })
        ->parameter('openApi', \DI\get('internal.http-server.open-api.openapi')),
    'internal.http-server.open-api.openapi' => \DI\factory(function (
        string $root
    ) {
        return scan(
            $root,
            [
                'exclude' => [
                    $root . 'vendor',
                    $root . 'tests',
                    $root . 'etc',
                ]
            ],
        );
    })
        ->parameter('root', \DI\get('config.root')),
];
