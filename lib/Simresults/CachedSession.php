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
        $cache_key = __METHOD__;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLapsSortedByTime();
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsByLapNumberSortedByTime($lap_number)
    {
        $cache_key = __METHOD__.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLapsByLapNumberSortedByTime($lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapByLapNumber($lap_number)
    {
        $cache_key = __METHOD__.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getBestLapByLapNumber($lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapsGroupedByParticipant()
    {
        $cache_key = __METHOD__;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getBestLapsGroupedByParticipant();
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySector($sector)
    {
        $cache_key = __METHOD__.'-'.$sector;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLapsSortedBySector($sector);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapsBySectorGroupedByParticipant($sector)
    {
        $cache_key = __METHOD__.'-'.$sector;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getBestLapsBySectorGroupedByParticipant($sector);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySectorByLapNumber($sector, $lap_number)
    {
        $cache_key = __METHOD__.'-'.$sector.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result = parent::getLapsSortedBySectorByLapNumber($sector, $lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapBySectorByLapNumber($sector, $lap_number)
    {
        $cache_key = __METHOD__.'-'.$sector.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result = parent::getBestLapBySectorByLapNumber($sector, $lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBadLaps($above_percent = 107)
    {
        $cache_key = __METHOD__.'-'.$above_percent;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getBadLaps($above_percent);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLedMostParticipant()
    {
        $cache_key = __METHOD__;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLedMostParticipant();
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadingParticipant($lap_number)
    {
        $cache_key = __METHOD__.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLeadingParticipant($lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getLeadingParticipantByElapsedTime($lap_number)
    {
        $cache_key = __METHOD__.'-'.$lap_number;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLeadingParticipantByElapsedTime($lap_number);
        $this->cache->put($cache_key, $result);

        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function getLastedLaps()
    {
        $cache_key = __METHOD__;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getLastedLaps();
        $this->cache->put($cache_key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPosition()
    {
        $cache_key = __METHOD__;

        if (null !== $value = $this->cache->get($cache_key))
        {
            return $this->cache->get($cache_key);
        }

        $result =  parent::getMaxPosition();
        $this->cache->put($cache_key, $result);

        return $result;
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