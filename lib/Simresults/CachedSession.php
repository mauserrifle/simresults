<?php
namespace Simresults;

/**
 * The cached session class. Overrides methods to implement cache.
 *
 * This is a more effective alternative for the decorator pattern where
 * the decorated class would not benefit from cache when calling itself.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class CachedSession extends Session {

    public function __construct(Helper $helper=null, Cache $cache=null)
    {
        if ( ! $cache) $cache = new Cache;
        $this->cache = $cache;

        parent::__construct($helper);
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedByTime()
    {
        return $this->cache(__FUNCTION__, func_get_args());

    }

    /**
     * {@inheritdoc}
     */
    public function getLapsByLapNumberSortedByTime($lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapByLapNumber($lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapsGroupedByParticipant()
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySector($sector)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapsBySectorGroupedByParticipant($sector)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySectorByLapNumber($sector, $lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapBySectorByLapNumber($sector, $lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBadLaps($above_percent = 107)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLedMostParticipant()
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadingParticipant($lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadingParticipantByElapsedTime($lap_number)
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }


    /**
     * {@inheritdoc}
     */
    public function getLastedLaps()
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPosition()
    {
        return $this->cache(__FUNCTION__, func_get_args());
    }


    /**
     * Invalidate the cache
     */
    public function invalidateCache()
    {
        $this->cache->flush();
    }


    /**
     * Invalidate cache on cloning
     */
    public function __clone()
    {
        $this->invalidateCache();
    }


    /**
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    protected function cache($method, $args)
    {
        $cache_key = __CLASS__.'::'.$method;
        if ($args) {
            $cache_key .= '-'.implode('-', $args);
        }

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  call_user_func_array(array('parent', $method), $args);
        $this->cache->put($cache_key, $result);

        return $result;
    }
}