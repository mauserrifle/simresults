<?php
namespace Simresults;

/**
 * The lap class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Lap {

    /**
     * @var  int  The lap number
     */
    protected $number;

    /**
     * @var  Participant  The participant running this lap
     */
    protected $participant;

    /**
     * @var  Driver  The driver that drove this lap. A driver is part of the
     *               participant's team
     */
    protected $driver;

    /**
     * @var  int  The position the participant was in
     */
    protected $position;

    /**
     * @var  float  The time in seconds
     */
    protected $time;

    /**
     * @var  array  The aids this participant has used in this lap
     */
    protected $aids = array();

    /**
     * @var  array  Array containing all the sector times
     */
    protected $sector_times = array();

    /**
     * @var  float  The elapsed time in seconds before this lap started
     */
    protected $elapsed_seconds;

    /**
     * @var  string  The front compound used within this lap
     */
    protected $front_compound;

    /**
     * @var  string  The rear compound used within this lap
     */
    protected $rear_compound;

    /**
     * @var  float  Fuel percentage left in tank
     */
    protected $fuel;

    /**
     * @var  boolean  Whether there was a pitstop on this lap
     */
    protected $pit_lap;


    /**
     * Set the lap number
     *
     * @param   int  $number
     * @return  Lap
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Get the lap number
     *
     * @return  int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set the participant
     *
     * @param   Participant  $participant
     * @return  Lap
     */
    public function setParticipant(Participant $participant)
    {
        $this->participant = $participant;
        return $this;
    }

    /**
     * Get the participant
     *
     * @return  Participant
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * Set the driver that drove this lap. This driver should be part of the
     * participant team.
     *
     * @param   Driver  $driver
     * @return  Lap
     */
    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get the driver that drove this lap. This driver is part of the
     * participant team.
     *
     * @return  Driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set the position the participant was in
     *
     * @param   int  $position
     * @return  Lap
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Get the position the participant was in
     *
     * @return  int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set the time in seconds
     *
     * @param   float  $time
     * @return  Lap
     */
    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * Get the time in seconds. When not set this tries to calculate from the
     * sector times. When the lap is not completed, this will return null.
     *
     * @return  float|null
     */
    public function getTime()
    {
        // No time and we have 3 sector times
        if ($this->time === null AND count($this->getSectorTimes()) === 3)
        {
            // Try to get time from sector times
            $time = (float) 0;
            foreach ($this->getSectorTimes() as $sector)
            {
                $time  = round($time + $sector, 4);
            }

            // Time is zero or lower
            if ($time <= 0)
            {
                // Just return null
                return null;
            }

            // Return calculated time
            return $time;
        }

        return $this->time;
    }

    /**
     * Set the sector times
     *
     * @param   array  $sector_times
     * @return  Lap
     */
    public function setSectorTimes(array $sector_times)
    {
        $this->sector_times = $sector_times;
        return $this;
    }

    /**
     * Get the sector times
     *
     * @return  array
     */
    public function getSectorTimes()
    {
        return $this->sector_times;
    }

    /**
     * Get sector time by sector number
     *
     * @param   int    $sector_number
     * @return  float|null
     */
    public function getSectorTime($sector_number)
    {
        // Sector does not exist
        if ( ! isset($this->sector_times[$sector_number-1]))
        {
            return null;
        }

        // Return sector time
        return $this->sector_times[$sector_number-1];
    }

    /**
     * Add a new sector time
     *
     * @param   float  $sector_time
     * @return  Lap
     */
    public function addSectorTime($sector_time)
    {
        $this->sector_times[] = $sector_time;
        return $this;
    }

    /**
     * Set the aids this participant used
     *
     * @param   array  $aids
     * @return  Lap
     */
    public function setAids(array $aids)
    {
        $this->aids = $aids;
        return $this;
    }

    /**
     * Get the aids this participant used
     *
     * @return  array
     */
    public function getAids()
    {
        return $this->aids;
    }

    /**
     * Add a new aid this participant used
     *
     * @param   string  $aid
     * @return  Lap
     */
    public function addAid($aid)
    {
        $this->aids[] = $aid;
        return $this;
    }

    /**
     * Set the elapsed time in seconds before this lap started
     *
     * @param   float  $seconds
     * @return  Lap
     */
    public function setElapsedSeconds($seconds)
    {
        $this->elapsed_seconds = $seconds;
        return $this;
    }

    /**
     * Get the elapsed time in seconds before this lap started
     *
     * @return  float
     */
    public function getElapsedSeconds()
    {
        return $this->elapsed_seconds;
    }

    /**
     * Set the front compound used within this lap
     *
     * @param   string  $front_compound
     * @return  Lap
     */
    public function setFrontCompound($front_compound)
    {
        $this->front_compound = $front_compound;
        return $this;
    }

    /**
     * Get the front compound used within this lap
     *
     * @return  string
     */
    public function getFrontCompound()
    {
        return $this->front_compound;
    }

    /**
     * Set the rear compound used within this lap
     *
     * @param   string  $rear_compound
     * @return  Lap
     */
    public function setRearCompound($rear_compound)
    {
        $this->rear_compound = $rear_compound;
        return $this;
    }

    /**
     * Get the rear compound used within this lap
     *
     * @return  string
     */
    public function getRearCompound()
    {
        return $this->rear_compound;
    }

    /**
     * Set the fuel percentage left in tank
     *
     * @param   float  $fuel
     * @return  Lap
     */
    public function setFuel($fuel)
    {
        $this->fuel = $fuel;
        return $this;
    }

    /**
     * Get the fuel percentage left in tank
     *
     * @return  float
     */
    public function getFuel()
    {
        return $this->fuel;
    }

    /**
     * Set whether there was a pitstop on this lap
     *
     * @param   boolean  $pit_lap
     * @return  Lap
     */
    public function setPitLap($pit_lap)
    {
        $this->pit_lap = $pit_lap;
        return $this;
    }

    /**
     * Get whether there was a pitstop on this lap
     *
     * @return  boolean
     */
    public function isPitLap()
    {
        return $this->pit_lap;
    }


    /**
     * Returns whether this lap is completed or not
     *
     * @return  boolean
     */
    public function isCompleted()
    {
        return (
            $this->getTime() !== null
        );
    }

    /**
     * Returns the gap between this lap and the given lap
     *
     * @return  float|null
     */
    public function getGap(Lap $lap)
    {
        // Given lap or this lap has no time
        if ($lap->getTime() === null OR $this->getTime() === null)
        {
            return null;
        }

        // Return difference and round to 4 to fix floats
        return round($lap->getTime() - $this->getTime(), 4);
    }

    /**
     * Returns the gap between this lap sector and given lap sector
     *
     * @param   Lap  $lap
     * @param   int  $sector
     * @return  float|null
     */
    public function getSectorGap(Lap $lap, $sector)
    {
        // Get sectors
        $this_sectors = $this->getSectorTimes();
        $lap_sectors = $lap->getSectorTimes();

        // Define array sector index
        $sector_index = $sector -1;

        // Given lap or thos lap has no sector time
        if ( ! isset($lap_sectors[$sector_index]) OR
             ! isset($this_sectors[$sector_index]))
        {
            return null;
        }

        // Return difference and round to 4 to fix floats
        return round(
            $lap_sectors[$sector_index] - $this_sectors[$sector_index],
            4
        );
    }
}
