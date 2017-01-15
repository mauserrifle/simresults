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

    /**
     * Helper to cache methods of an extended class that should cache the
     * parent
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function cacheParentCall($object, $method, $args)
    {
        // TODO: Find alternative due to spl_object_hash performance hit?
        $cache_key = spl_object_hash($object).
                    '-'.
                    get_class($object).
                    '::'.
                    $method;

        if ($args) {
            $cache_key .= '-'.implode('-', $args);
        }

        if (null !== $value = $this->get($cache_key))
        {
            return $this->get($cache_key);
        }

        $result =  call_user_func_array(array($object, 'parent::'.$method), $args);
        $this->put($cache_key, $result);

        return $result;
    }

}