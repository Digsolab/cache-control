<?php

namespace CacheControl\Lock;

use Predis\Client;

class RedisLockTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject|Client */
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->getMock(Client::class, ['setnx', 'expire', 'del']);
    }

    public function testLockSuccess()
    {
        $lock = new RedisLock($this->client);

        $this->client->method('setnx')->willReturn(true);
        $this->client->method('expire')->willReturn(true);

        $this->client->expects($this->once())->method('setnx')->with('successTest', 1);
        $this->client->expects($this->once())->method('expire')->with('successTest', 500);

        $result = $lock->lock('successTest', 500);
        $this->assertTrue($result);
    }

    public function testLockFail()
    {
        $lock = new RedisLock($this->client);

        $this->client->method('setnx')->willReturn(false);

        $this->client->expects($this->once())->method('setnx')->with('failTest', 1);

        $result = $lock->lock('failTest', 100500);
        $this->assertFalse($result);
    }

    public function testUnlock()
    {
        $lock = new RedisLock($this->client);

        $this->client->expects($this->once())->method('del')->with(['unlockTest']);

        $lock->unlock('unlockTest');
    }

}
