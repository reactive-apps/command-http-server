<?php declare(strict_types=1);

namespace ReactiveApps\Command\HttpServer\Controller;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReactiveApps\Command\HttpServer\Annotations\Method;
use ReactiveApps\Command\HttpServer\Annotations\Routes;
use ReactiveApps\Command\HttpServer\Annotations\Template;
use ReactiveApps\Command\HttpServer\Exception\UnknownRealmException;
use ReactiveApps\Command\HttpServer\TemplateResponse;
use RingCentral\Psr7\Response;
use function WyriHaximus\getIn;
use WyriHaximus\React\Http\Middleware\Session;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;

final class OpenAPI
{
    /** @var string */
    private $yaml;

    /**
     * OpenAPI constructor.
     * @Inject({"internal.http-server.open-api.yaml"}
     */
    public function __construct(string $yaml)
    {
        $this->yaml = $yaml;
    }

    /**
     * @Method("GET")
     * @Routes({
     *     "/openapi.yml"
     * })
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function spec(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(
            200,
            [
                'Content-Type' => 'application/x-yaml',
            ],
            $this->yaml,
        );
    }
}
