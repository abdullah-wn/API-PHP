<?php
namespace App\Utils;

interface CacheFieldInterface
{
    /**
     * 
     * @param string $key
     * @return CacheFieldInterface $this
     */
    public function get($key);
    
    /**
     * 
     * @param string $key
     * @param string $value
     * 
     * @return CacheFieldInterface $this
     */
    public function set($key, $value);
    
    /**
     * 
     * @param string $key
     */
    public function remove($key);
}

