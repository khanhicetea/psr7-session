<?php

namespace Psr7Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Psr7Middlewares\Utils\AttributeTrait;
use DateTime;
use SessionHandlerInterface;
use InvalidArgumentException;


class Middleware
{
    use AttributeTrait;

    const KEY = 'SESSION';

    private $name = 'I_AM_COOKIE';
    private $domain = null;
    private $httpOnly = false;
    private $secure = false;
    private $path = '/';
    private $maxAge = 0;
    private $expires = null;
    private $sessionHandler = null;

    public static function create() {
        return new static();
    }

    public static function getSession($request) {
        return self::getAttribute($request, self::KEY);
    }

    public function name($name) {
        $this->name = $name;
        return $this;
    }

    public function domain(string $domain = null) {
        $this->domain = $domain;
        return $this;
    }

    public function httpOnly(bool $httpOnly = true) {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    public function secure(bool $secure = true) {
        $this->secure = $secure;
        return $this;
    }

    public function path(string $path = '/') {
        $this->path = $path;
        return $this;
    }

    public function maxAge(int $maxAge = 3600) {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function expires(DateTime $expires) {
        $this->expires = $expires;
        return $this;
    }

    public function handler(SessionHandlerInterface $handler) {
        $this->sessionHandler = $handler;
        return $this;
    }

    private function getHeaderValue(string $id) {
        $values = [
            sprintf('%s=%s', $this->name, $id)
        ];

        if ($this->domain) {
            $values[] = sprintf('Domain=%s', $this->domain);
        }

        if ($this->path) {
            $values[] = sprintf('Path=%s', $this->path);
        }

        if ($this->maxAge) {
            $values[] = sprintf('Max-Age=%d', $this->maxAge);
        } elseif ($this->expires) {
            $values[] = sprintf('Expires=%s GMT', $this->expires->format('D, d-M-Y H:i:s'));
        }

        if ($this->httpOnly) {
            $values[] = 'HttpOnly';
        }

        if ($this->secure) {
            $values[] = 'Secure';
        }

        return implode('; ', $values);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$this->sessionHandler instanceof SessionHandlerInterface) {
            throw new InvalidArgumentException('Session handler required');
        }

        $session_cookie_name = $this->name;
        $cookies = $request->getCookieParams();
        $session_id = $cookies[$session_cookie_name] ?? null;

        $sessionStorage = new SessionStorage(
            $this->sessionHandler,
            $session_id,
            $session_cookie_name
        );
        $session = new Session($sessionStorage);
        $request = self::setAttribute($request, self::KEY, $session);
        
        $response = $next($request, $response);

        $session->save();

        if (is_null($session_id)|| $sessionStorage->isRegenerated()) {
            return $response->withAddedHeader('Set-Cookie', $this->getHeaderValue($sessionStorage->getId()));
        }

        return $response;
    }
}
