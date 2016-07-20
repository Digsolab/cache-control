<?php

namespace CacheControl\Lock;

use CacheControl\LockInterface;
use Predis\Client;

class RedisLock implements LockInterface
{

    /** @var Client */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
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
