<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\ClientIpResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 신뢰 프록시 규칙으로 해석한 클라이언트 IP 를 요청 속성(`client_ip`)에 싣는다.
 */
final class ClientIpMiddleware implements MiddlewareInterface
{
    public const string ATTRIBUTE = 'client_ip';

    public function __construct(private readonly ClientIpResolver $resolver)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(self::ATTRIBUTE, $this->resolver->resolve($request)));
    }
}
