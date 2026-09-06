<?php

declare(strict_types=1);

namespace Yiisoft\Session\Tests;

use Exception;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionException;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\SessionMiddleware;

final class SessionMiddlewareTest extends TestCase
{
    private const COOKIE_PARAMETERS = [
        'path' => 'examplePath',
        'domain' => 'exampleDomain',
        'httponly' => true,
        'samesite' => 'Strict',
        'lifetime' => 3600,
        'secure' => true,
    ];

    private const CURRENT_SID = 'exampleCurrentSidValue';
    private const REQUEST_SID = 'exampleRequestSidValue';
    private const SESSION_NAME = 'exampleSessionName';

    private MockObject|RequestHandlerInterface $requestHandlerMock;

    private MockObject|SessionInterface $sessionMock;

    private MockObject|ServerRequestInterface $requestMock;

    private MockObject|UriInterface $uriMock;

    private SessionMiddleware $sessionMiddleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->sessionMiddleware = new SessionMiddleware($this->sessionMock);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->uriMock = $this->createMock(UriInterface::class);
    }

    public function testProcessDiscardsSessionWhenRequestHandlerThrowsException(): void
    {
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willThrowException(new Exception());

        $this->sessionMock
            ->expects($this->once())
            ->method('discard');

        $this->expectException(Exception::class);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessThrowsSessionExceptionWhenConnectionIsNotUsingHttps(): void
    {
        $this->setUpSessionMock();
        $this->setUpRequestMock(false);
        $this->expectException(SessionException::class);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessGetsDomainFromRequestWhenDomainCookieParameterNotProvided(): void
    {
        $this->setUpSessionMock(false);
        $this->setUpRequestMock();

        $this->uriMock
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('domain');

        $response = new Response();
        $this->setUpRequestHandlerMock($response);
        $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testProcessDoesNotAlterResponseIfSessionIsNotActive(): void
    {
        $this->setUpSessionMock(true, false, null);
        $this->setUpRequestMock();

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
        $this->assertEquals($response, $result);
    }

    public function testProcessDoesNotAlterResponseWhenSessionIdIsTheSame(): void
    {
        $this->setUpSessionMock(true, true, 'session_id');
        $this->setUpRequestMock(true, 'session_id');

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);
        $this->assertEquals($response, $result);
    }

    public function testManualCloseSession(): void
    {
        $this->setUpSessionMock(true, false, 'session_id');
        $this->setUpRequestMock(true, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $this->assertNotSame($response, $result);
    }

    public function testProcessSetsSessionCookieWithAllParameters(): void
    {
        $this->setUpSessionMock(true, false, 'new_session_id');
        $this->setUpRequestMock(true, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $this->assertMatchesRegularExpression(
            '~^exampleSessionName=new_session_id; Expires=[A-Za-z0-9,:+ ]+; Max-Age=3600; Domain=exampleDomain; Path=examplePath; Secure; HttpOnly; SameSite=Strict$~',
            $result->getHeaderLine('Set-Cookie'),
        );
    }

    public function testProcessEncodesSessionCookieValue(): void
    {
        $this->setUpSessionMock(true, false, 'value with spaces');
        $this->setUpRequestMock(true, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $this->assertStringStartsWith('exampleSessionName=value+with+spaces;', $result->getHeaderLine('Set-Cookie'));
    }

    public function testProcessOmitsExpiresAndMaxAgeWhenLifetimeIsZero(): void
    {
        $this->setUpSessionMock(true, false, 'new_session_id', ['lifetime' => 0]);
        $this->setUpRequestMock(true, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $this->assertSame(
            'exampleSessionName=new_session_id; Domain=exampleDomain; Path=examplePath; Secure; HttpOnly; SameSite=Strict',
            $result->getHeaderLine('Set-Cookie'),
        );
    }

    public function testProcessOmitsHttpOnlyWhenDisabled(): void
    {
        $this->setUpSessionMock(true, false, 'new_session_id', ['httponly' => false]);
        $this->setUpRequestMock(true, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $cookieHeader = $result->getHeaderLine('Set-Cookie');
        $this->assertStringNotContainsString('HttpOnly', $cookieHeader);
        $this->assertStringEndsWith('; Secure; SameSite=Strict', $cookieHeader);
    }

    public function testProcessForcesSecureFlagWhenSameSiteIsNone(): void
    {
        $this->setUpSessionMock(true, false, 'new_session_id', ['samesite' => 'None', 'secure' => false]);
        $this->setUpRequestMock(false, null);

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $cookieHeader = $result->getHeaderLine('Set-Cookie');
        $this->assertStringContainsString('; Secure; ', $cookieHeader);
        $this->assertStringEndsWith('SameSite=None', $cookieHeader);
    }

    public function testProcessUsesRequestHostAsCookieDomainWhenDomainNotProvided(): void
    {
        $this->setUpSessionMock(false, false, 'new_session_id');
        $this->setUpRequestMock(true, null);

        $this->uriMock
            ->method('getHost')
            ->willReturn('example.com');

        $response = new Response();
        $this->setUpRequestHandlerMock($response);

        $result = $this->sessionMiddleware->process($this->requestMock, $this->requestHandlerMock);

        $this->assertStringContainsString('; Domain=example.com; ', $result->getHeaderLine('Set-Cookie'));
    }

    private function setUpRequestHandlerMock(ResponseInterface $response): void
    {
        $this->requestHandlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response);
    }

    private function setUpSessionMock(
        bool $cookieDomainProvided = true,
        bool $isActive = true,
        ?string $sessionId = self::CURRENT_SID,
        array $cookieParametersOverride = [],
    ): void {
        $this->sessionMock
            ->expects($this->any())
            ->method('isActive')
            ->willReturn($isActive);

        $this->sessionMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        $this->sessionMock
            ->expects($this->any())
            ->method('getID')
            ->willReturn($sessionId);

        $cookieParams = array_merge(self::COOKIE_PARAMETERS, $cookieParametersOverride);
        if (!$cookieDomainProvided) {
            $cookieParams['domain'] = '';
        }

        $this->sessionMock
            ->expects($this->any())
            ->method('getCookieParameters')
            ->willReturn($cookieParams);
    }

    private function setUpRequestMock(bool $isConnectionSecure = true, ?string $sessionId = self::REQUEST_SID): void
    {
        $uriScheme = $isConnectionSecure ? 'https' : 'http';
        $this->setUpUriMock($uriScheme);

        $this->requestMock
            ->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);

        $requestCookieParams = $sessionId === null ? [] : [self::SESSION_NAME => $sessionId];

        $this->requestMock
            ->expects($this->any())
            ->method('getCookieParams')
            ->willReturn($requestCookieParams);
    }

    private function setUpUriMock(string $uriScheme): void
    {
        $this->uriMock
            ->expects($this->any())
            ->method('getScheme')
            ->willReturn($uriScheme);
    }
}
