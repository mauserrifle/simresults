<?php
namespace Simresults\Result;

/**
 * The incident class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Incident {

    /**
     * @var  int
     */
    protected $id;

    /**
     * @var  string  The incident message
     */
    protected $message;

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
     * @var  boolean  Whether this incident is worth reviewing
     */
    protected $for_review = false;

    /**
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param   int      $id
     * @return  Incident
     */

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set the incident message
     *
     * @param   string    $message
     * @return  Incident
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the message
     *
     * @return  string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the date and time this incident happend. Mind that this does not
     * support miliseconds.
     *
     * @param   \DateTime  $date
     * @return  Incident
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get the date and time this incident happend. Mind that this does not
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
     * @return  Incident
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
     * Set whether this incident is worth reviewing
     *
     * @param   boolean  $for_review
     * @return  Incident
     */
    public function setForReview($for_review)
    {
        $this->for_review = $for_review;
        return $this;
    }

    /**
     * Get whether this incident is worth reviewing
     *
     * @return  boolean
     */
    public function isForReview()
    {
        return $this->for_review;
    }
}
