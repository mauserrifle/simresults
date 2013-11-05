<?php
namespace Simresults;

/**
 * The track class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Track {

    /**
     * @var  string  The venue of the track (e.g. Sebring)
     */
    protected $venue;

    /**
     * @var  string  What course of the track (e.g. Sebring 12h Course)
     */
    protected $course;

    /**
     * @var  string  The event of the track (e.g. 12h Course)
     */
    protected $event;

    /**
     * @var  float  The length of the track in metres
     */
    protected $length;


    /**
     * Set the venue of the track
     *
     * @param   string  $venue
     * @return  Track
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
        return $this;
    }

    /**
     * Get the venue of the track (e.g. Sebring)
     *
     * @return  string
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * Set the course of the track (e.g. Sebring 12h Course)
     *
     * @param   string  $course
     * @return  Track
     */
    public function setCourse($course)
    {
        $this->course = $course;
        return $this;
    }

    /**
     * Get the course of the track
     *
     * @return  string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set the event of the track (e.g. 12h Course)
     *
     * @param   string  $event
     * @return  Track
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get the event of the track
     *
     * @return  string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set the length of the track in metres
     *
     * @param   float  $length
     * @return  Track
     */
    public function setLength($length)
    {
        $this->length = (float) $length;
        return $this;
    }

    /**
     * Get the length of the track in metres
     *
     * @return  string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get a friendly name version of the venue, course and event name. Tries
     * to remove any duplicates.
     *
     * @return  string
     */
    public function getFriendlyName()
    {
        //--- Build initial track name
        $track_name = $this->getVenue();

        // Has course name
        if ($track_course = $this->getCourse())
        {
            // Course name is not already part of the name
            if ( ! preg_match(
                       sprintf('/\b(%s)\b/i', preg_quote($track_course)),
                       $track_name))
            {
                // Add course name to track name
                $track_name .= ', '.$track_course;
            }
        }

        // Has event name
        if ($track_event = $this->getEvent())
        {
            // Event name is not already part of the name
            if ( ! preg_match(
                       sprintf('/\b(%s)\b/i', preg_quote($track_event)),
                       $track_name))
            {
                // Add event to track name
                $track_name .= sprintf(' (%s)',$track_event);
            }
        }

        return $track_name;
    }

}
