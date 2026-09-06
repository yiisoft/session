<?php

declare(strict_types=1);

namespace Yiisoft\Session;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Exception;

use function implode;
use function urlencode;

/**
 * Session middleware handles storing session ID into a response cookie and
 * restoring the session associated with the ID from a request cookie.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestSessionId = $this->getSessionIdFromRequest($request);
        if ($requestSessionId !== null && $this->session->getId() === null) {
            $this->session->setId($requestSessionId);
        }

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->session->discard();
            throw $e;
        }

        return $this->commitSession($request, $response);
    }

    /**
     * Close session and add/modify response session cookie if necessary.
     *
     * @throws Exception
     */
    private function commitSession(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->session->isActive()) {
            $this->session->close();
        }

        $currentSessionId = $this->session->getId();
        if ($currentSessionId === null) {
            return $response;
        }

        if ($this->getSessionIdFromRequest($request) === $currentSessionId) {
            // SID not changed, no need to send new cookie.
            return $response;
        }

        return $response->withAddedHeader(
            'Set-Cookie',
            $this->buildSessionCookieHeader($request, $currentSessionId),
        );
    }

    /**
     * Build a `Set-Cookie` header value that stores the session ID.
     *
     * @throws Exception
     */
    private function buildSessionCookieHeader(ServerRequestInterface $request, string $sessionId): string
    {
        $cookieParameters = $this->session->getCookieParameters();

        $domain = $cookieParameters['domain'];
        if (empty($domain)) {
            $domain = $request->getUri()->getHost();
        }

        $useSecureCookie = $cookieParameters['secure'];
        if ($useSecureCookie && $request->getUri()->getScheme() !== 'https') {
            throw new SessionException(
                '"cookie_secure" is on but connection is not secure. Either set Session "cookie_secure" option to "0" or make connection secure.',
            );
        }

        $sameSite = $cookieParameters['samesite'] ?? 'Lax';

        $cookieParts = [$this->session->getName() . '=' . urlencode($sessionId)];

        if ($cookieParameters['lifetime'] > 0) {
            $expires = (new DateTimeImmutable())->add(
                new DateInterval('PT' . $cookieParameters['lifetime'] . 'S'),
            );
            $cookieParts[] = 'Expires=' . $expires->format(DateTimeInterface::RFC1123);
            $cookieParts[] = 'Max-Age=' . $cookieParameters['lifetime'];
        }

        $cookieParts[] = 'Domain=' . $domain;
        $cookieParts[] = 'Path=' . $cookieParameters['path'];

        // The "Secure" flag is required for cookies marked as "SameSite=None".
        if ($useSecureCookie || $sameSite === 'None') {
            $cookieParts[] = 'Secure';
        }

        if ($cookieParameters['httponly']) {
            $cookieParts[] = 'HttpOnly';
        }

        $cookieParts[] = 'SameSite=' . $sameSite;

        return implode('; ', $cookieParts);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        /** @psalm-var array<string, string> $cookies */
        $cookies = $request->getCookieParams();
        return $cookies[$this->session->getName()] ?? null;
    }
}
