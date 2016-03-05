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

    /**
     * @var  array  The cache for getting a lap by number
     */
    protected $cache_lap = array();

    /**
     * @var  array|null  The cache for laps sorted by time
     */
    protected $cache_laps_sorted_by_time;

    /**
     * @var  int|null  The cache for the number of completed laps
     */
    protected $cache_number_of_completed_laps;

    /**
     * @var int|null  The cache for number of led laps
     */
    protected $cache_number_of_laps_led;

    /**
     * @var  array  The cache for laps sorted by sector
     */
    protected $cache_laps_sorted_by_sector = array();

    /**
     * @var  array  The cache for best lap by sector
     */
    protected $cache_best_lap_by_sector = array();

    /**
     * @var  array  The cache for average lap, with or without pit sectors
     */
    protected $cache_average_lap = array();

    /**
     * @var  Lap|null  The cache for best possible lap
     */
    protected $cache_best_possible_lap;

    /**
     * @var  Lap|null  The cache for best lap
     */
    protected $cache_best_lap;

    /**
     * @var  array|null  The cache for vehicles
     */
    protected $cache_vehicles;

    /**
     * @var  array  The cache for consistency ignore first lap or not
     */
    protected $cache_consistency = array(
        true   => null,
        false  => null,
    );


    /**
     * {@inheritdoc}
     */
    public function getLap($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number, $this->cache_lap))
        {
            return $this->cache_lap[$lap_number];
        }

        // Return lap and cache it
        return $this->cache_lap[$lap_number] = parent::getLap($lap_number);
    }

    /**
     * {@inheritdoc}
     */
    public function getVehicles()
    {
        // There is cache
        if ($this->cache_vehicles !== null)
        {
            return $this->cache_vehicles;
        }

        return $this->cache_vehicles = parent::getVehicles();
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedByTime()
    {
        // There is cache
        if ($this->cache_laps_sorted_by_time !== null)
        {
            return $this->cache_laps_sorted_by_time;
        }

        // Return laps sorted by time and cache it
        return $this->cache_laps_sorted_by_time =
            parent::getLapsSortedByTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLap()
    {
        // There is cache
        if ($this->cache_best_lap !== null)
        {
            return $this->cache_best_lap;
        }

        return $this->cache_best_lap = parent::getBestLap();
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfCompletedLaps()
    {
        // There is cache
        if ($this->cache_number_of_completed_laps !== null)
        {
            return $this->cache_number_of_completed_laps;
        }

        // Return number of completed laps and cache it
        return $this->cache_number_of_completed_laps =
           parent::getNumberOfCompletedLaps();
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberOfLapsLed()
    {
        // There is cache
        if ($this->cache_number_of_laps_led !== null)
        {
            return $this->cache_number_of_laps_led;
        }

        // Return number of led laps and cache it
        return $this->cache_number_of_laps_led = parent::getNumberOfLapsLed();
    }

    /**
     * {@inheritdoc}
     */
    public function getLapsSortedBySector($sector)
    {
        // There is cache
        if (array_key_exists($sector, $this->cache_laps_sorted_by_sector))
        {
            return $this->cache_laps_sorted_by_sector[$sector];
        }

        // Return laps sorted by sector and cache it
        return $this->cache_laps_sorted_by_sector[$sector] =
            parent::getLapsSortedBySector($sector);
    }

    /**
     * {@inheritdoc}
     */
    public function getBestLapBySector($sector)
    {
        // There is cache
        if (array_key_exists($sector, $this->cache_best_lap_by_sector))
        {
            return $this->cache_best_lap_by_sector[$sector];
        }

        return $this->cache_best_lap_by_sector[$sector] =
            parent::getBestLapBySector($sector);
    }

    /**
     * {@inheritdoc}
     */
    public function getAverageLap($exclude_pitstop_sectors=false)
    {
        // There is cache
        if (array_key_exists( (int) $exclude_pitstop_sectors,
            $this->cache_average_lap))
        {
            return $this->cache_average_lap[ (int) $exclude_pitstop_sectors];
        }

        // Return average lap and cache it
        return $this->cache_average_lap[ (int) $exclude_pitstop_sectors] =
            parent::getAverageLap($exclude_pitstop_sectors);
    }

    /**
     * {@inheritdoc}
     */
    public function getBestPossibleLap()
    {
        // There is cache
        if ($this->cache_best_possible_lap !== null)
        {
            return $this->cache_best_possible_lap;
        }

        // Return best possible lap and cache it
        return $this->cache_best_possible_lap = parent::getBestPossibleLap();
    }


    /**
     * {@inheritdoc}
     */
    public function getConsistency($ignore_first_lap = true)
    {
        // There is cache
        if ($this->cache_consistency[$ignore_first_lap] !== null)
        {
            return $this->cache_consistency[$ignore_first_lap];
        }

        // Return consistency
        return $this->cache_consistency[$ignore_first_lap] =
            parent::getConsistency($ignore_first_lap);
    }


    /**
     * Invalidate the cache
     */
    public function invalidateCache()
    {
        $this->cache_laps_sorted_by_time = null;
        $this->cache_number_of_completed_laps = null;
        $this->cache_number_of_laps_led = null;
        $this->cache_laps_sorted_by_sector = array();
        $this->cache_best_lap_by_sector = array();
        $this->cache_average_lap = array();
        $this->cache_best_possible_lap = null;
        $this->cache_best_lap = null;
        $this->cache_vehicles = null;
        $this->cache_consistency = array(
            true   => null,
            false  => null,
        );
    }

    /**
     * Invalidate cache on cloning
     */
    public function __clone()
    {
        $this->invalidateCache();
    }

}
