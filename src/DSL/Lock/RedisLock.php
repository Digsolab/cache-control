<?php

namespace DSL\Lock;

use DSL\LockInterface;
use Predis\ClientInterface as RedisClient;

class RedisLock implements LockInterface
{

    /** @var RedisClient */
    private $client;

    /**
     * @param RedisClient $client
     */
    public function __construct(RedisClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function lock($lockName, $ttl)
    {
        if ($this->client->setnx($lockName, 1)) {
            return $this->client->expire($lockName, $ttl);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock($lockName)
    {
        $this->client->del([$lockName]);
    }

}
