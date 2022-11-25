<?php
namespace Simresults;

/**
 * The cached participant class. Overrides methods to implement cache.
 *
 * This is a more effective alternative for the decorator pattern where
 * the decorated class would not benefit from cache when calling itself.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class CachedParticipant extends Participant {

    use CacheParentCallTrait;

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Helper $helper=null, Cache $cache=null)
    {
        if ( ! $cache) $cache = new Cache;
        $this->cache = $cache;

        parent::__construct($helper);
    }


    /**
     * {@inheritdoc}
     */
    public function getLap($lap_number)
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getVehicles()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedByTime()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLap()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfCompletedLaps()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfLapsLed()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySector($sector)
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapBySector($sector)
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getAverageLap($exclude_pitstop_sectors=false)
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getBestPossibleLap()
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
    }


    /**
     * {@inheritdoc}
     */
    public function getConsistency($ignore_first_lap = true)
    {
        return $this->cache->cacheParentCall(
            $this, __FUNCTION__, func_get_args());
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
}
