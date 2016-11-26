<?php
namespace Simresults;

/**
 * The cache class
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Cache {

    /**
     * @var array
     */
    protected $data;

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed  $value
     */
    public function put($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @return string
     */
    public function get($key)
    {
        if (isset($this->data[$key]))
        {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Remove an item from the cache
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key)
    {
        unset($this->data[$key]);
        return true;
    }

    /**
     * Remove all items from the cache
     *
     * @param  string $key
     * @return bool
     */
    public function flush()
    {
        $this->data = array();
        return true;
    }

}