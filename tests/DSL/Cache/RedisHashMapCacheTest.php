<?php

namespace DSL\Cache;

use Doctrine\Common\Cache\Cache;
use Predis\Client;
use Predis\Response\Status;

class RedisHashMapCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject|Client */
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->getMock(Client::class, ['hmset', 'del', 'exists', 'expire', 'hgetall', 'info']);
    }

    public function fetchDataProvider()
    {
        return [
            ['a', ['a' => 1, 'b' => 2]],
            ['b', false],
            ['c', null],
        ];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @dataProvider fetchDataProvider
     */
    public function testFetch($key, $value)
    {
        $this->client->method('hgetall')->willReturn($value);

        $this->client->expects($this->once())->method('hgetall')->with($key);

        $cache = new RedisHashMapCache($this->client);

        $result = $cache->fetch($key);

        $this->assertEquals($value, $result);
    }

    public function saveExceptionDataProvider()
    {
        return [
            [null],
            [''],
            [1],
            [[]],
            [[1 => []]],
        ];
    }

    /**
     * @param mixed $value
     * @expectedException \InvalidArgumentException
     * @dataProvider saveExceptionDataProvider
     */
    public function testSaveException($value)
    {
        $cache = new RedisHashMapCache($this->client);
        $cache->save('test1', $value);
    }

    public function saveDataProvider()
    {
        return [
            ['a', [1], 0, 'OK', true],
            ['b', [1, 2], 500, 'ERR', false]
        ];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @param string $payload
     * @param bool $expects
     * @dataProvider saveDataProvider
     */
    public function testSave($key, $value, $expire, $payload, $expects)
    {
        $this->client->method('hmset')->willReturn(new Status($payload));

        $this->client->expects($this->once())->method('hmset')->with($key, $value);
        if ($expire) {
            $this->client->expects($this->once())->method('expire')->with($key, $expire);
        }

        $cache = new RedisHashMapCache($this->client);

        $result = $cache->save($key, $value, $expire);

        $this->assertTrue($result === $expects);
    }

    public function testDelete()
    {
        $this->client->expects($this->once())->method('del')->with(['del-key']);

        $cache = new RedisHashMapCache($this->client);

        $cache->delete('del-key');
    }

    public function containsDataProvider()
    {
        return [
            ['good-key', true],
            ['lost-key', false],
        ];
    }

    /**
     * @param string $key
     * @param bool $exists
     * @dataProvider containsDataProvider
     */
    public function testContains($key, $exists)
    {
        $this->client->method('exists')->willReturn($exists);

        $this->client->expects($this->once())->method('exists')->with($key);

        $cache = new RedisHashMapCache($this->client);

        $result = $cache->contains($key);
        $this->assertTrue($exists === $result);
    }

    public function testGetStats()
    {
        $this->client->method('info')->willReturn(
            [
                'Stats' => ['keyspace_hits' => 1, 'keyspace_misses' => 2,],
                'Server' => ['uptime_in_seconds' => 3,],
                'Memory' => ['used_memory' => 444,],
            ]
        );

        $this->client->expects($this->once())->method('info')->with();

        $cache = new RedisHashMapCache($this->client);

        $result = $cache->getStats();

        $this->assertEquals(
            [
                Cache::STATS_HITS             => 1,
                Cache::STATS_MISSES           => 2,
                Cache::STATS_UPTIME           => 3,
                Cache::STATS_MEMORY_USAGE     => 444,
                Cache::STATS_MEMORY_AVAILABLE => false
            ],
            $result
        );
    }

}
