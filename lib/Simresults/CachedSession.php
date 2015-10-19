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

    /**
     * @var  array|null  The cache for laps sorted by time
     */
    protected $cache_laps_sorted_by_time;

    /**
     * @var  array  The cache for laps by lap number sorted by time
     */
    protected $cache_laps_by_lap_number_sorted_by_time = array();

    /**
     * @var  array  The cache for best lap by lap number
     */
    protected $cache_best_lap_by_lap_number = array();

    /**
     * @var  array|null  The cache for best laps grouped by participant
     */
    protected $cache_best_laps_grouped_by_participant;

    /**
     * @var  array  The cache for laps sorted by sector
     */
    protected $cache_laps_sorted_by_sector = array();

    /**
     * @var  array  The cache for best laps by sector grouped by participant
     */
    protected $cache_best_laps_by_sector_grouped_by_participant = array();

    /**
     * @var  array  The cache for laps sorted by sector by lap number
     */
    protected $cache_laps_sorted_by_sector_by_lap_number = array();

    /**
     * @var  array  The cache for best lap by sector by lap number
     */
    protected $cache_best_lap_by_sector_by_lap_number = array();

    /**
     * @var  array|null  The cache for bad laps
     */
    protected $cache_bad_laps;

    /**
     * @var  Participant|null  The cache for led most participant
     */
    protected $cache_led_most_participant;

    /**
     * @var  array  The cache for the leading participant per lap
     */
    protected $cache_leading_participant = array();

    /**
     * @var  array  The cache for the leading participant by elapsed time per
     *              lap
     */
    protected $cache_leading_participant_by_elapsed_time = array();

    /**
     * @var  int|null  The cache for the lasted laps
     */
    protected $cache_lasted_laps;

    /**
     * @var  int|null  The cache for the max position
     */
    protected $cache_max_position;


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

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_time =
        	parent::getLapsSortedByTime();
    }

	/**
	 * {@inheritdoc}
	 */
    public function getLapsByLapNumberSortedByTime($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_laps_by_lap_number_sorted_by_time))
        {
            return $this->cache_laps_by_lap_number_sorted_by_time[$lap_number];
        }

        // Return sorted laps and cache it
        return $this->cache_laps_by_lap_number_sorted_by_time[$lap_number] =
            parent::getLapsByLapNumberSortedByTime($lap_number);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBestLapByLapNumber($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_best_lap_by_lap_number))
        {
            return $this->cache_best_lap_by_lap_number[$lap_number];
        }

        return $this->cache_best_lap_by_lap_number[$lap_number] =
        	parent::getBestLapByLapNumber($lap_number);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBestLapsGroupedByParticipant()
    {
        // There is cache
        if ($this->cache_best_laps_grouped_by_participant !== null)
        {
            return $this->cache_best_laps_grouped_by_participant;
        }

        // Return sorted laps and cache it
        return $this->cache_best_laps_grouped_by_participant =
            parent::getBestLapsGroupedByParticipant();
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

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_sector[$sector] =
            parent::getLapsSortedBySector($sector);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBestLapsBySectorGroupedByParticipant($sector)
    {
        // There is cache
        if (array_key_exists($sector,
                $this->cache_best_laps_by_sector_grouped_by_participant))
        {
            return $this->cache_best_laps_by_sector_grouped_by_participant[
                       $sector];
        }

        // Return sorted laps and cache it
        return $this->cache_best_laps_by_sector_grouped_by_participant[$sector]
            = parent::getBestLapsBySectorGroupedByParticipant($sector);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getLapsSortedBySectorByLapNumber($sector, $lap_number)
    {
        // There is cache
        if (array_key_exists("$sector-$lap_number",
                $this->cache_laps_sorted_by_sector_by_lap_number))
        {
            return $this->cache_laps_sorted_by_sector_by_lap_number[
                       "$sector-$lap_number"];
        }

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_sector_by_lap_number[
            "$sector-$lap_number"] = parent::getLapsSortedBySectorByLapNumber(
            	$sector, $lap_number);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBestLapBySectorByLapNumber($sector, $lap_number)
    {
        // There is cache
        if (array_key_exists("$sector-$lap_number",
                $this->cache_best_lap_by_sector_by_lap_number))
        {
            return $this->cache_best_lap_by_sector_by_lap_number[
                       "$sector-$lap_number"];
        }

        return $this->cache_best_lap_by_sector_by_lap_number[
           "$sector-$lap_number"] = parent::getBestLapBySectorByLapNumber(
           		$sector, $lap_number);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBadLaps($above_percent = 107)
    {
        // There is cache
        if ($this->cache_bad_laps !== null)
        {
            return $this->cache_bad_laps;
        }

        // Return the laps with proper keys and cache it
        return $this->cache_bad_laps = parent::getBadLaps($above_percent);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getLedMostParticipant()
    {
        // There is cache
        if ($this->cache_led_most_participant !== null)
        {
            return $this->cache_led_most_participant;
        }

        // Return and cache
        return $this->cache_led_most_participant =
        	parent::getLedMostParticipant();
    }

	/**
	 * {@inheritdoc}
	 */
    public function getLeadingParticipant($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number, $this->cache_leading_participant))
        {
            return $this->cache_leading_participant[$lap_number];
        }

        return parent::getLeadingParticipant($lap_number);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getLeadingParticipantByElapsedTime($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_leading_participant_by_elapsed_time))
        {
            return $this->cache_leading_participant_by_elapsed_time[
                $lap_number];
        }

        // Return and cache it
        return $this->cache_leading_participant_by_elapsed_time[$lap_number] =
            parent::getLeadingParticipantByElapsedTime($lap_number);
    }


	/**
	 * {@inheritdoc}
	 */
    public function getLastedLaps()
    {
        // There is cache
        if ($this->cache_lasted_laps !== null)
        {
            return $this->cache_lasted_laps;
        }

        // Return number of laps lasted and cache it
        return $this->cache_lasted_laps = parent::getLastedLaps();
    }

	/**
	 * {@inheritdoc}
	 */
    public function getMaxPosition()
    {
        // There is cache
        if ($this->cache_max_position !== null)
        {
            return $this->cache_max_position;
        }

        // Return max position and cache it
        return $this->cache_max_position = parent::getMaxPosition();
    }


	/**
	 * {@inheritdoc}
	 */
    public function __clone()
    {
        $this->cache_laps_sorted_by_time = NULL;
        $this->cache_laps_by_lap_number_sorted_by_time = array();
        $this->cache_best_laps_grouped_by_participant = NULL;
        $this->cache_laps_sorted_by_sector = array();
        $this->cache_best_laps_by_sector_grouped_by_participant = array();
        $this->cache_laps_sorted_by_sector_by_lap_number = array();
        $this->cache_bad_laps = NULL;
        $this->cache_led_most_participant = NULL;
        $this->cache_leading_participant = array();
        $this->cache_leading_participant_by_elapsed_time = array();
        $this->cache_lasted_laps = NULL;
        $this->cache_max_position = NULL;
    }

}