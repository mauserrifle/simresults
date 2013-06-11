<?php
namespace Simresults;

/**
 * The participant class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Participant {

    // The finish statusses
    const FINISH_NORMAL = 'finished'; // finished
    const FINISH_DNF    = 'dnf';      // did not finish
    const FINISH_DQ     = 'dq';       // disqualified
    const FINISH_NONE   = 'none';     // no finish status


    //------ Cache values

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
     * @var  Lap|null  The cache for average lap
     */
    protected $cache_average_lap;

    /**
     * @var  Lap|null  The cache for best possible lap
     */
    protected $cache_best_possible_lap;



    //------ Participant values

    /**
     * @var  Driver  The driver
     */
    protected $driver;

    /**
     * @var  Vehicle  The vehicle
     */
    protected $vehicle;

    /**
     * @var  int  The final position for this participant
     */
    protected $position;

    /**
     * @var  int  The grid position for this participant
     */
    protected $grid_position;

    /**
     * @var  int  The final class position for this participant
     */
    protected $class_position;

    /**
     * @var  int  The class grid position for this participant
     */
    protected $class_grid_position;

    /**
     * @var  array  The laps of this participant
     */
    protected $laps = array();

    /**
     * @var  float  The total time this participant has driven. Used to
     *              overwrite the total time of a participant, which is
     *              normally calculated through the lap times. Useful when a
     *              driver has a penalty for extra time or the laps are in-
     *              complete. It is known that you can't rely on lap times for
     *              rfactor 2 for example. Sometimes a lap time is just
     *              'missing'. When it's possible, always set this.
     */
    protected $total_time;

    /**
     * @var  int  The number of pitstops for this participant
     */
    protected $pitstops = 0;

    /**
     * @var  string  The finish status based on constants. Defaults to status
     *               none
     */
    protected $finish_status = self::FINISH_NONE;

    /**
     * @var  string  Comment on finish status. Mainly used when not finished.
     */
    protected $finish_status_comment;

    /**
     * Set the driver
     *
     * @param   Driver  $driver
     * @return  Participant
     */
    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get the driver
     *
     * @return  Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set the vehicle
     *
     * @param   Vehicle      $vehicle
     * @return  Participant
     */
    public function setVehicle(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    /**
     * Get the vehicle
     *
     * @return  Vehicle
     */
    public function getVehicle()
    {
        return $this->vehicle;
    }

    /**
     * Set the final position for this participant
     *
     * @param   int          $position
     * @return  Participant
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Get the final position for this participant
     *
     * @return  int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set the grid position for this participant
     *
     * @param   int          $grid_position
     * @return  Participant
     */
    public function setGridPosition($grid_position)
    {
        $this->grid_position = $grid_position;
        return $this;
    }

    /**
     * Get the grid position for this participant
     *
     * @return  int
     */
    public function getGridPosition()
    {
        return $this->grid_position;
    }

    /**
     * Set the class position for this participant
     *
     * @param   int          $class_position
     * @return  Participant
     */
    public function setClassPosition($class_position)
    {
        $this->class_position = $class_position;
        return $this;
    }

    /**
     * Get the class position for this participant
     *
     * @return  int
     */
    public function getClassPosition()
    {
        return $this->class_position;
    }

    /**
     * Set the class grid position for this participant
     *
     * @param   int          $class_grid_position
     * @return  Participant
     */
    public function setClassGridPosition($class_grid_position)
    {
        $this->class_grid_position = $class_grid_position;
        return $this;
    }

    /**
     * Get the class grid position for this participant
     *
     * @return  int
     */
    public function getClassGridPosition()
    {
        return $this->class_grid_position;
    }

    /**
     * Set the laps of this participant
     *
     * @param   array          $laps
     * @return  Participant
     */
    public function setLaps(array $laps)
    {
        $this->laps = $laps;
        return $this;
    }

    /**
     * Get the laps of this participant
     *
     * @return  array
     */
    public function getLaps()
    {
        return $this->laps;
    }

    /**
     * Add lap to this participant
     *
     * @param   Lap  $lap
     * @return  Participant
     */
    public function addLap(Lap $lap)
    {
        $this->laps[] = $lap;
        return $this;
    }

    /**
     * Get lap by lap number
     *
     * @param   int  $lap_number
     * @return  Lap|null
     */
    public function getLap($lap_number)
    {
        // Lap does not exist
        if ( ! isset($this->laps[$lap_number-1]))
        {
            return null;
        }

        // Return lap
        return $this->laps[$lap_number-1];
    }

    /**
     * Set the number of pitstops this participant had
     *
     * @param   int          $pitstops
     * @return  Participant
     */
    public function setPitstops($pitstops)
    {
        $this->pitstops = $pitstops;
        return $this;
    }

    /**
     * Get the number of pitstops this participant had
     *
     * @return  int
     */
    public function getPitstops()
    {
        return $this->pitstops;
    }

    /**
     * Set the finish status based on the constants
     *
     * @param   string       $finish_status
     * @return  Participant
     */
    public function setFinishStatus($finish_status)
    {
        $this->finish_status = $finish_status;
        return $this;
    }

    /**
     * Get the finish status based on the constants
     *
     * @return  string
     */
    public function getFinishStatus()
    {
        return $this->finish_status;
    }

    /**
     * Set the finish status comment
     *
     * @param   string       $comment
     * @return  Participant
     */
    public function setFinishComment($comment)
    {
        $this->finish_status_comment = $comment;
        return $this;
    }

    /**
     * Get the finish status comment
     *
     * @return  string
     */
    public function getFinishStatusComment()
    {
        return $this->finish_status_comment;
    }

    /**
     * Set the total time. Used to overwrite the total time of a participant,
     * which is  normally calculated through the lap times. Useful when a
     * driver has a penalty for extra time or the laps are in-complete.
     * It is known that you can't rely on lap times for rfactor 2 for example.
     * Sometimes a lap time is just 'missing'. When it's possible, always set
     * this.
     *
     * @param   float          $total_time
     * @return  Participant
     */
    public function setTotalTime($total_time)
    {
        $this->total_time = $total_time;
        return $this;
    }

    /**
     * Returns the total time of all (completed) laps
     *
     * return  float
     */
    public function getTotalTime()
    {
        // Total time overwrite
        if ($this->total_time !== null)
        {
            // Return the hard total time
            return $this->total_time;
        }

        // Total timegetTotalTime
        $total = 0;

        // Loop each lap
        foreach ($this->getLaps() as $lap)
        {
            // Is completed lap
            if ($lap->isCompleted())
            {
                $total = round($total + $lap->getTime(), 4);
            }
        }

        // Return total and set it as hard value
        return $this->total_time = $total;
    }

    /**
     * Returns the gap of the total time between participant and given
     * participant. When a participant is lapped, the lapped time will be
     * added too.
     *
     * @param   Participant  $participant
     * @return  float
     */
    public function getTotalTimeGap(Participant $participant)
    {
        // Calculate the gap by total time
        $gap = round($participant->getTotalTime() - $this->getTotalTime(), 4);

        // Lap difference is not 0, so one of the participants is lapped
        if ( 0 !== ($lap_difference =
                ($this->getNumberOfLaps() -
                    $participant->getNumberOfLaps())))
        {
            //--- Add remaining laps of this participant to the total gap

            // By default this participant is leading
            $leading_participant = $this;

            // Negative lap difference
            if ($lap_difference < 0)
            {
                // Comparing participant is leading
                $leading_participant = $participant;
            }

            // Loop lap difference
            for ($lap_i=$leading_participant->getNumberOfLaps();
                 $lap_i > ($leading_participant->getNumberOfLaps() -
                                abs($lap_difference));
                 $lap_i--)
            {
                // Negative lap difference
                if ($lap_difference < 0)
                {
                    // Subtract lap time of leading participant to total gap
                    $gap = round(
                        $gap - $leading_participant->getLap($lap_i)->getTime(),
                        4
                    );
                }
                // Positive lap difference
                else
                {
                    // Add lap time of leading participant to total gap
                    $gap = round(
                        $gap + $leading_participant->getLap($lap_i)->getTime(),
                        4
                    );
                }
            }
        }

        // Return gap
        return $gap;
    }

    /**
     * Returns the laps sorted by time (ASC)
     *
     * @return  array
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
        	Helper::sortLapsByTime($this->getLaps());
    }

    /**
     * Returns the best lap for this participant
     *
     * @return  Lap
     */
    public function getBestLap()
    {
        $laps = $this->getLapsSortedByTime();
        return array_shift($laps);
    }

    /**
     * Returns the number of laps this participant raced
     *
     * @return  int
     */
    public function getNumberOfLaps()
    {
        return count($this->getLaps());
    }

    /**
     * Returns the number of completed laps this participant raced
     *
     * @return  int
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
        	count(array_filter($this->getLaps(), function($lap) {
            	return $lap->isCompleted();
        	}));
    }

    /**
     * Returns the number of laps this participant led
     *
     * @return  int
     */
    public function getNumberOfLapsLed()
    {
        // There is cache
    	if ($this->cache_number_of_laps_led !== null)
    	{
    		return $this->cache_number_of_laps_led;
    	}

        // Return number of led laps and cache it
        return $this->cache_number_of_laps_led =
        	count(array_filter($this->getLaps(), function($lap) {
            	return ($lap->getPosition() === 1);
        	}));
    }

    /**
     * Returns the laps sorted by the given sector time (ASC)
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
        	Helper::sortLapsBySector($this->getLaps(), $sector);
    }

    /**
     * Returns the best lap by sector
     *
     * @param   int  $sector
     * @return  Lap
     */
    public function getBestLapBySector($sector)
    {
        $laps = $this->getLapsSortedBySector($sector);
        return array_shift($laps);
    }

    /**
     * Returns the difference in starting and ending position
     *
     * @return  int
     */
    public function getPositionDifference()
    {
        return (int) ($this->getGridPosition() - $this->getPosition());
    }

    /**
     * Returns the aids used by this participant by looking into all lap aids.
     * This could be used as a summary of all aids on laps. Mind that when
     * a user switched between values of the aid, some values might get lost
     * due to the merging of the aids. For proper aid overviews, please parse
     * the lap aids yourself.
     *
     * @return  array
     */
    public function getAids()
    {
        // Loop each lap and get aids summary
        $aids = array();
        foreach ($this->getLaps() as $lap)
        {
            // Merge lap aids with other aids
            $aids = array_merge($aids, $lap->getAids());
        }

        // Return aids
        return $aids;
    }

    /**
     * Get the average lap. Based on sectors. Also includes non-completed laps.
     *
     * @return  Lap|null
     */
    public function getAverageLap()
    {
        // There is cache
    	if ($this->cache_average_lap !== null)
    	{
    		return $this->cache_average_lap;
    	}

        // No completed laps
        if ( $this->getNumberOfCompletedLaps() === 0)
        {
            return null;
        }

        // Init sum of sectors
        $sectors_sum = array(0, 0, 0);

        // Init count per sector
        $sectors_count = array(0, 0, 0);

        // Loop each lap
        foreach ($this->getLaps() as $lap)
        {
            for ($i=0; $i<3; $i++)
            {
                // Has sector
                if ($sector = $lap->getSectorTime($i+1))
                {
                    // Sum sector time
                    $sectors_sum[$i] =
                        round($sectors_sum[$i] + $sector, 4);

                    // Increment sector count
                    $sectors_count[$i]++;
                }
            }
        }

        // Not all sectors have been driven
        if ( ! $sectors_count[0] OR ! $sectors_count[1] OR ! $sectors_count[2])
        {
            return null;
        }

        // Make averages
        $sector_average = array(
            round($sectors_sum[0]/$sectors_count[0], 4),
            round($sectors_sum[1]/$sectors_count[1], 4),
            round($sectors_sum[2]/$sectors_count[2], 4),
        );

        // Make total time
        $total_time = array_reduce($sector_average, function($a, $b) {
            return round($a + $b, 4);
        });

        // Create average lap
        $average_lap = new Lap;
        $average_lap
        	->setSectorTimes($sector_average)
            ->setTime($total_time)
            ->setParticipant($this);

        // Return average lap and cache it
        return $this->cache_average_lap = $average_lap;

    }

    /**
     * Get the best possible lap. Based on sectors. Also includes non-completed
     * laps.
     *
     * @return  Lap|null
     */
    public function getBestPossibleLap()
    {
        // There is cache
    	if ($this->cache_best_possible_lap !== null)
    	{
    		return $this->cache_best_possible_lap;
    	}

        // No best lap of one of the sectors
        if ( ! $sector1_lap = $this->getBestLapBySector(1) OR
             ! $sector2_lap = $this->getBestLapBySector(2) OR
             ! $sector3_lap = $this->getBestLapBySector(3))
        {
            return null;
        }

        // Get sector times
        $sector_1 = $sector1_lap->getSectorTime(1);
        $sector_2 = $sector2_lap->getSectorTime(2);
        $sector_3 = $sector3_lap->getSectorTime(3);

        // One of the sectors is missing
        if ( ! $sector_1 OR ! $sector_2 OR ! $sector_3)
        {
            return null;
        }

        // Create best possible lap
        $best_possible_lap = new Lap;
        $best_possible_lap
            ->setSectorTimes(array(
                $sector_1,
                $sector_2,
                $sector_3))
            ->setTime(round(round($sector_1+$sector_2,4)+$sector_3,4))
            ->setParticipant($this);

        // Return best possible lap and cache it
        return $this->cache_best_possible_lap = $best_possible_lap;
    }
}

?>