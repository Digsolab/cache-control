<?php

namespace CacheControl\Cache;

use Doctrine\Common\Cache\Cache;
use Predis\Client;

class RedisHashMapCache implements Cache
{

    /** @var Client  */
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
    public function fetch($id)
    {
        return $this->client->hgetall($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $result = $this->client->hmset($id, $data);
        if ($lifeTime) {
            $this->client->expire($id, $lifeTime);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->client->del([$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->client->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        $info = $this->client->info();

        return array(
            Cache::STATS_HITS              => $info['Stats']['keyspace_hits'],
            Cache::STATS_MISSES            => $info['Stats']['keyspace_misses'],
            Cache::STATS_UPTIME            => $info['Server']['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $info['Memory']['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE  => false
        );
    }

}
