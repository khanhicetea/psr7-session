## Installation

```
composer require "khanhicetea/psr7-session"
```

## Get Started

It's built on top Symfony HTTP Foundation, so you can use **Symfony SessionHandler** excepts **NativeSessionHandler**

This demo below is use **RedisHandler**

```php
$redis = new \Redis();
$redis->connect('redis_server', 6379, 3);
$redis_handler = new \Psr7Session\Handler\RedisHandler($redis, 3600);

$middleware = SessionMiddleware::create()
            ->name('I_am_cookie_plz_dont_eat_me')
            ->httpOnly()
            ->maxAge(3600)
            ->handler($redis_handler);

$slim_app->add($middleware); 
```

Use **Session** object
```php
use Psr7Session\Middleware as SessionMiddleware;

$session = SessionMiddleware::getSession($request);
$default = 123;
$value = $session->get('key', $default);
$value++;
$session->set('key', $value);
```