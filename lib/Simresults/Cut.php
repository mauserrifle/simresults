<?php
namespace Simresults;

/**
 * The cut class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Cut {

    /**
     * @var  float  The cut time in seconds
     */
    protected $cut_time;

    /**
     * @var  float  The time skipped
     */
    protected $time_skipped;

    /**
     * @var  Lap  The lap this cut happend
     */
    protected $lap;

    /**
     * @var  \DateTime  The date. Mind that this does not support miliseconds.
     */
    protected $date;

    /**
     * @var  float  The elapsed time in seconds. This could be used to get
     *              a precise time including miliseconds.
     */
    protected $elapsed_seconds;

    /**
     * @var  float  The elapsed time in lap in seconds. This could be used to
     *              get a precise time including miliseconds.
     */
    protected $elapsed_seconds_in_lap;



    /**
     * Set the cut cut time
     *
     * @param   float    $cut_time
     * @return  Cut
     */
    public function setCutTime($cut_time)
    {
        $this->cut_time = $cut_time;
        return $this;
    }

    /**
     * Get the cut time
     *
     * @return  float
     */
    public function getCutTime()
    {
        return $this->cut_time;
    }

    /**
     * Set the time skipped
     *
     * @param   float    $seconds
     * @return  Cut
     */
    public function setTimeSkipped($seconds)
    {
        $this->time_skipped = $seconds;
        return $this;
    }

    /**
     * Get the time skipped
     *
     * @return  float
     */
    public function getTimeSkipped()
    {
        return $this->time_skipped;
    }

    /**
     * Set the lap this cut happend
     *
     * @param   Lap  $lap
     * @return  Cut
     */
    public function setLap(Lap $lap)
    {
        $this->lap = $lap;
        return $this;
    }

    /**
     * Get the lap this cut happend
     *
     * @return  Lap
     */
    public function getLap()
    {
        return $this->lap;
    }

    /**
     * Set the date and time this cut happend. Mind that this does not
     * support miliseconds.
     *
     * @param   \DateTime  $date
     * @return  Cut
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get the date and time this cut happend. Mind that this does not
     * support miliseconds.
     *
     * @return  \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the elapsed time in seconds. This could be used to get a precise
     * time including miliseconds.
     *
     * @param   float  $seconds
     * @return  Cut
     */
    public function setElapsedSeconds($seconds)
    {
        $this->elapsed_seconds = $seconds;
        return $this;
    }

    /**
     * Get the elapsed time in seconds. This could be used to get a precise
     * time including miliseconds.
     *
     * @return  float
     */
    public function getElapsedSeconds()
    {
        return $this->elapsed_seconds;
    }

    /**
     * Set the elapsed time in lap in seconds. This could be used to get a
     * precise time including miliseconds.
     *
     * @param   float  $seconds
     * @return  Cut
     */
    public function setElapsedSecondsInLap($seconds)
    {
        $this->elapsed_seconds_in_lap = $seconds;
        return $this;
    }

    /**
     * Get the elapsed time in lap in seconds. This could be used to get a
     * precise time including miliseconds.
     *
     * FUTURE: Calculate using cut date if this data is missing
     *
     * @return  float
     */
    public function getElapsedSecondsInLap()
    {
        return $this->elapsed_seconds_in_lap;
    }

}