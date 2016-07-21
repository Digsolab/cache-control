<?php

namespace DSL;

interface LockInterface
{
    /**
     * @param string  $lockName Lock name
     * @param integer $ttl      Lock time in seconds
     *
     * @return bool
     */
    public function lock($lockName, $ttl);

    /**
     * @param string $lockName
     */
    public function unlock($lockName);

}
