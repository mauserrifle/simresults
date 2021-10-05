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

    /**
     * @var  array  The sort order of finish statusses
     */
    public static $finish_sort_order = array(
        Participant::FINISH_NORMAL,
        Participant::FINISH_DNF,
        Participant::FINISH_DQ,
        Participant::FINISH_NONE,
    );


    /**
     * @var  array  The drivers
     */
    protected $drivers = array();

    /**
     * @var  string  The team
     */
    protected $team;

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
     * @var  Helper  The helper for sorting
     */
    protected $helper;


    public function __construct(Helper $helper=null)
    {
        if ( ! $helper) $helper = new Helper;

        $this->helper = $helper;
    }


    /**
     * Create a participant instance. Returns cached version.
     *
     * @return Participant
     */
    public static function createInstance()
    {
        return new CachedParticipant;
    }

    /**
     * Set the drivers
     *
     * @param   array  $drivers
     * @return  Participant
     */
    public function setDrivers(array $drivers)
    {
        $this->drivers = $drivers;
        return $this;
    }

    /**
     * Get the drivers
     *
     * @return  array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Return one driver. Defaults to the first driver
     *
     * @param  int  $driver_number
     * @return Driver|null
     */
    public function getDriver($driver_number = 1)
    {
        if ( ! isset($this->drivers[$driver_number-1])) {
            return null;
        }

        return $this->drivers[$driver_number-1];
    }

    /**
     * Set the team
     *
     * @param   string      $team
     * @return  Participant
     */
    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * Get the team
     *
     * @return  string
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set the vehicle. Use this when a participant has one main vehicle he
     * drives for all laps or the reader just supports one vehicle parsing.
     *
     * For multiple please sonsider setting a vehicle on laps. `getVehicles()`
     * will parse the laps to return vehicles.
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
     * Get the vehicle.
     *
     * When the argument `$ignore_lap_vehicles` is set to false (default) it
     * returns a vehicle in this order:
     *
     *     * The best lap vehicle (if any)
     *     * The main vehicle set on participant (if any)
     *     * The first found vehicle on laps (if any)
     *
     * Considering using `getVehicles()` especially for non-race sessions!
     * A participant might ran  multiple cars on different laps due to
     * reconnecting
     *
     * @param  boolean  $ignore_lap_vehicles   Used to prevent infinite loops
     *                                         when calling `Lap::getVehicle()`
     *                                         which fallbacks to this class.
     *
     * @return Vehicle
     */
    public function getVehicle($ignore_lap_vehicles=false)
    {
        // Has multiple vehicles from laps
        if ( ! $ignore_lap_vehicles AND $vehicles = $this->getVehicles())
        {
            // Return best lap vehicle if any
            if ($best_lap = $this->getBestLap() AND
                $vehicle = $best_lap->getVehicle())
            {
                return $vehicle;
            }

            // No best lap vehicle, just return the first found if our main
            // vehicle has not been set
            if ( ! $this->vehicle)
            {
                return $vehicles[0];
            }
        }

        // Has main vehicle, return it
        if($this->vehicle)
        {
            return $this->vehicle;
        }

        return NULL;
    }

    /**
     * Get the vehicles. This gets all the vehicles from the participant
     * laps. If the laps do not have vehicles set, the main vehicle will be
     * read
     *
     * @return  array
     */
    public function getVehicles()
    {
        // Get vehicles from laps
        $vehicles = array();
        foreach ($this->laps as $lap)
        {
            if ( ! in_array($vehicle=$lap->getVehicle(), $vehicles, true))
            {
                $vehicles[] = $vehicle;
            }
        }

        // No vehicles found by laps, but this participant has a main vehicle
        if ( ! $vehicles AND $this->vehicle)
        {
            $vehicles[] = $this->vehicle;
        }

        return $vehicles;
    }

    /**
     * Set the final position for this participant
     *
     * @param   int          $position
     * @return  Participant
     */
    public function setPosition($position=null)
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
     * Add lap to this participant. When the lap number is not set on Lap,
     * this method adds the number.
     *
     * @param   Lap  $lap
     * @return  Participant
     */
    public function addLap(Lap $lap)
    {
        // No lap number set. Set lap number because we can't function without
        if ( ! $lap->getNumber())
        {
            // No laps yet, so is the first
            if ( ! $this->laps)
            {
                $lap->setNumber(1);
            }
            // Has laps, get last added lap and use as number
            else
            {
                $lap->setNumber(
                    $this->laps[count($this->laps)-1]->getNumber()
                    +
                    1
                );
            }
        }

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
        // Find lap by number. We are not selecting on array key because
        // in some cases not all laps are in the array!
        foreach ($this->laps as $lap)
        {
            if ($lap->getNumber() === $lap_number) return $lap;
        }

        return null;
    }

    /**
     * Get the last lap
     *
     * @return  Lap|null
     */
    public function getLastLap()
    {
        if ($laps = $this->laps)
        {
            return array_pop($laps);
        }

        return null;
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
        if ( ! $this->pitstops)
        {
            // Return number of pit laps
            return count(array_filter($this->getLaps(), function($lap) {
                return $lap->isPitLap();
            }));
        }

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
                // Lap does not exist on leader
                if ( ! $leading_lap = $leading_participant->getLap($lap_i))
                {
                    // Go to next
                    continue;
                }

                // Negative lap difference
                if ($lap_difference < 0)
                {
                    // Subtract lap time of leading participant to total gap
                    $gap = round(
                        $gap - $leading_lap->getTime(),
                        4
                    );
                }
                // Positive lap difference
                else
                {
                    // Add lap time of leading participant to total gap
                    $gap = round(
                        $gap + $leading_lap->getTime(),
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
        // Return laps sorted by time
        return $this->helper->sortLapsByTime($this->getLaps());
    }

    /**
     * Returns the (completed) best lap for this participant
     *
     * @return  Lap|null
     */
    public function getBestLap()
    {
        // No laps
        if ( ! $this->laps)
        {
            return NULL;
        }

        // Get laps sorted by time
        $laps = $this->getLapsSortedByTime();

        // Only return a completed lap
        foreach ($laps as $lap)
        {
            if ($lap->isValidForBest())
            {
                return $lap;
            }
        }

        return NULL;
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
        // Return number of completed laps
        return count(array_filter($this->getLaps(), function($lap) {
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
        // Return number of led laps
        return count(array_filter($this->getLaps(), function($lap) {
                return ($lap->getPosition() === 1);
            }));
    }

    /**
     * Returns the laps sorted by the given sector time (ASC)
     *
     * @param int $sector
     * @return array
     */
    public function getLapsSortedBySector($sector)
    {
        // Return laps sorted by sector
        return $this->helper->sortLapsBySector($this->getLaps(), $sector);
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
     * Returns the difference in starting and ending position. Returns null
     * when unknown
     *
     * @return  int|null
     */
    public function getPositionDifference()
    {
        // No grid position
        if ( ! $this->getGridPosition()) {
            return null;
        }

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
     * @param   boolean   $exclude_pitstop_sectors  Set to true to exclude any
     *                                              sectors that were part of
     *                                              pitting (e.g. L1S3->L2S1)
     *
     * @return  Lap|null
     */
    public function getAverageLap($exclude_pitstop_sectors=false)
    {
        // No completed laps
        if ( $this->getNumberOfCompletedLaps() === 0)
        {
            return null;
        }

        // Init sum of sectors
        $sectors_sum = array(0, 0, 0);

        // Init count per sector
        $sectors_count = array(0, 0, 0);

        // No previous lap by default in laps loop
        $prev_lap = null;

        // Loop each lap
        foreach (($laps=$this->getLaps()) as $lap_key => $lap)
        {
            // Loop 3 sectors
            for ($i=0; $i<3; $i++)
            {
                // Has sector
                if ($sector = $lap->getSectorTime($i+1) AND
                    // Is not a pit lap and not looping sector 3
                    ! ($exclude_pitstop_sectors AND $lap->isPitLap() AND
                        $i ==2 )
                    AND
                    // Previous lap is not a pit lap and not looping sector 1
                    // of the current lap
                    ! ($exclude_pitstop_sectors AND $prev_lap
                        AND $prev_lap->isPitLap() AND $i === 0))
                {
                    // Sum sector time
                    $sectors_sum[$i] =
                        round($sectors_sum[$i] + $sector, 4);

                    // Increment sector count
                    $sectors_count[$i]++;
                }
            }

            // Remember previous lap
            $prev_lap = $lap;
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

        // Return average lap
        return $average_lap;

    }



    /**
     * Get the best possible lap. Based on sectors. Also includes non-completed
     * laps.
     *
     * @return  Lap|null
     */
    public function getBestPossibleLap()
    {
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

        // Return best possible lap
        return $best_possible_lap;
    }

    /**
     * Get the consistency of the driver. Based on:
     * best lap MINUS average non-best.
     *
     * Pit or laps slower than 21s are ignored. First lap is also ignored by
     * default but may be changed by arguments.
     *
     * @param   boolean  $ignore_first_lap
     * @return  float
     */
    public function getConsistency($ignore_first_lap = true)
    {
        // Not enough laps
        if ( $this->getNumberOfCompletedLaps() <= 1)
        {
            return null;
        }

        // Get best lap
        if (!$best_lap = $this->getBestLap()) {
            return null;
        }

        // Get total time of all non-best
        $total_time = 0;
        $total_time_laps_num = 0;
        foreach ($this->getLaps() as $key => $lap)
        {
            // Is best lap, not valid (cuts), pit lap, just too slow compared
            // to the best lap (+21s) or first lap that should be ignored
            if ($lap === $best_lap OR ! $lap->isValidForBest() OR
                $lap->isPitLap() OR
                $lap->getTime() >= ($best_lap->getTime()+21) OR
                ($ignore_first_lap AND $key === 0))
            {
                continue;
            }

            // Add lap time to total time
            $total_time += $lap->getTime();

            $total_time_laps_num++;
        }

        // No total time
        if ( ! $total_time)
        {
            return null;
        }

        // Get average of total time
        $average = $total_time / $total_time_laps_num;

        // Return consistency
        return round($average - $best_lap->getTime(), 4);
    }

    /**
     * Get the consistency as percentage
     *
     * @param boolean $ignore_first_lap
     * @return  float|null
     */
    public function getConsistencyPercentage($ignore_first_lap = true)
    {
        // No consistency
        if ( ! $consistency = $this->getConsistency($ignore_first_lap))
        {
            return null;
        }

        return round(
            100 - ($consistency / ($this->getBestLap()->getTime() / 100)),
            2);
    }

    /**
     * Get the percentage of laps a given driver has driven within this
     * participant
     *
     * @param   Driver  $driver
     * @return  float
     */
    public function getDriverPercentage(Driver $driver)
    {
        $lap_count = 0 ;
        foreach ($this->getLaps() as $lap)
        {
            if ($lap->getDriver() === $driver)
            {
                $lap_count++;
            }
        }

        if (!$lap_count) {
            return 0.00;
        }

        return round((100 / $this->getNumberOfLaps()) * $lap_count, 2);
    }
}
