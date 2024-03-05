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
     * @param  mixed  $object
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function cacheParentCall($object, $method, $args)
    {
        $cache_key = null;

        if (function_exists('spl_object_id')) {
            $cache_key  = spl_object_id($object);
        } else {
            $cache_key  = spl_object_hash($object);
        }

        $cache_key .= '-'.
                    get_class($object).
                    '::'.
                    $method;

        if ($args) {
            // Fix object arguments
            $cacheArgs = array_map(function($el) {
                if (is_object($el)) {
                    if (function_exists('spl_object_id')) {
                        return spl_object_id($el);
                    } else {
                        return spl_object_hash($el);
                    }
                }
                return $el;
            }, $args);

            $cache_key .= '-'.implode('-', $cacheArgs);
        }

        if (null !== $value = $this->get($cache_key))
        {
            return $this->get($cache_key);
        }

        $result = $object->parentCall($method, $args);
        $this->put($cache_key, $result);

        return $result;
    }

}