<?php
namespace Simresults;

/**
 * The penalty class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Penalty {

    // The penalty types
    const TYPE_UNKNOWN = NULL;
    const TYPE_STOPGO = 'stopgo';
    const TYPE_DRIVETHROUGH = 'drivethrough';

    /**
     * @var  string  The type based on constants. Defaults to unknown
     */
    protected $type = self::TYPE_UNKNOWN;

    /**
     * @var  string  The penalty message
     */
    protected $message;

    /**
     * @var  \DateTime  The date. Mind that this does not support miliseconds.
     *
     */
    protected $date;

    /**
     * @var  float  The elapsed time in seconds. This could be used to get
     *              a precise time including miliseconds.
     */
    protected $elapsed_seconds;

    /**
     * The participant causing the penalty
     *
     * @var  Participant|null
     */
    protected $participant;


    /**
     * Set the penalty message
     *
     * @param   string   $message
     * @return  Penalty
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the penalty message
     *
     * @return  string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the date and time this penalty was. Mind that this does not support
     * miliseconds
     *
     * @param   \DateTime  $date
     * @return  Penalty
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get the date and time this penalty was. Mind that this does not support
     * miliseconds
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
     * @return  Penalty
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
     * Set the penalty type based on the constants
     *
     * @param   string      $type
     * @return  Penalty
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the penalty type based on the constants
     *
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the participant
     *
     * @param   Participant  $participant
     * @return  Penalty
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

}
