<?php

namespace Psr7Session\Handler;

class RedisHandler implements \SessionHandlerInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * List of available options:
     *  * key_prefix: The key prefix [default: '']
     *
     * @param \Redis $redis    The redis instance
     * @param int    $lifetime Max lifetime in seconds to keep sessions stored
     * @param array  $options  Options for the session handler
     *
     * @throws \InvalidArgumentException When Redis instance not provided
     */
    public function __construct($redis, $lifetime, array $options = array())
    {
        if (!$redis instanceof \Redis) {
            throw new \InvalidArgumentException('Redis instance required');
        }

        $this->redis = $redis;
        $this->lifetime = $lifetime;

        if (!is_array($options)) {
            $options = array();
        }
        $this->options = array_merge(array(
            'key_prefix' => '',
        ), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $key = $this->getKey($sessionId);

        return (string) $this->redis->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $key = $this->getKey($sessionId);

        return $this->redis->setex($key, $this->lifetime, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $key = $this->getKey($sessionId);

        return 1 === $this->redis->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        /* Note: Redis will handle the expiration of keys with SETEX command
         * See: http://redis.io/commands/setex
         */
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * Get the redis key.
     *
     * @param string $sessionId session id
     */
    protected function getKey($sessionId)
    {
        if (is_string($this->options['key_prefix'])) {
            return $this->options['key_prefix'].$sessionId;
        }

        return $sessionId;
    }
}
