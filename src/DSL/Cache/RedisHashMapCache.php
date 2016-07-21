<?php

namespace DSL\Cache;

use Doctrine\Common\Cache\Cache;
use Predis\Client;
use Predis\Response\Status;

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
        $data = $this->client->hgetall($id);

        return $data ?: false;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf('data should be an array, "%s" was given', gettype($data)));
        }

        if (!$data) {
            throw new \InvalidArgumentException('data array should contain at least one key');
        }

        foreach ($data as $value) {
            if (!is_scalar($value) && !is_null($value)) {
                throw new \InvalidArgumentException('data array should have only scalar or null values');
            }
        }

        /** @var Status $result */
        $result = $this->client->hmset($id, $data);
        if ($lifeTime) {
            $this->client->expire($id, $lifeTime);
        }

        return 'OK' == $result->getPayload();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return (bool) $this->client->del([$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return (bool) $this->client->exists($id);
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
