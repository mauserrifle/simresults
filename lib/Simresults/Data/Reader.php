<?php
namespace Simresults;

/**
 * The abstract data reader. It's the base for all readers. It's able to
 * find the proper reader using the factory method.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
abstract class Data_Reader {

    /**
     * @var  string  The default timezone to use
     */
    public static $default_timezone = 'UTC';

    /**
     * @var  string  The data to read
     */
    protected $data;

    /**
     * @var  Helper  The helper for sorting
     */
    protected $helper;

    /**
     * @var boolean  Whether we will set finish status none if 50% of laps is
     *               not completed
     */
    protected $finish_status_none_50percent_rule = true;


    /**
     * Create a new data reader for the given file or string.
     *
     * @param   string   $data   Data as file path or string
     *
     * @throws  Exception\CannotFindReader  when no reader can be found
     * @throws  Exception\NoData            when no data is given or found
     * @return  Data_Reader
     */
    public static function factory($data)
    {
        // Known readers
        $known_readers = array(
            'Simresults\Data_Reader_Rfactor2',
            'Simresults\Data_Reader_AssettoCorsa',
            'Simresults\Data_Reader_AssettoCorsaServerJson',
            'Simresults\Data_Reader_AssettoCorsaServer',
            'Simresults\Data_Reader_AssettoCorsaCompetizione',
            'Simresults\Data_Reader_ProjectCarsServer',
            'Simresults\Data_Reader_RaceRoomServer',
            'Simresults\Data_Reader_Race07',
            'Simresults\Data_Reader_Iracing',
        );

        // File checking
        try
        {
            // Data is a file
            if (is_file($data))
            {
                // Read contents of file
                $data = file_get_contents($data);
            }
        }
        // Ignore any errors (TODO: Unitest this somehow?)
        catch (\Exception $ex) {}

        // No data
        if ( ! $data)
        {
            throw new Exception\NoData(
                'Cannot find a reader for the given data');
        }

        // Loop each known reader and return the one which can handle the data
        foreach ($known_readers as $reader_class)
        {
            // Reader can read this data
            if ($reader_class::canRead($data))
            {
                // Create new reader instance and return it
                return new $reader_class($data);
            }
        }

        // Throw exception because we couldn't find any reader
        throw new Exception\CannotFindReader(
            'Cannot find a reader for the given data');
    }

    /**
     * Construct new reader with given string data
     *
     * @param   string  $data
     * @param   Helper  $helper
     * @throws  Exception\CannotReadData
     */
    public function __construct($data, Helper $helper=null)
    {
        // Cannot read the data
        if ( ! static::canRead($data))
        {
            // Throw exception
            throw new Exception\CannotReadData('Cannot read the given data');
        }

        // Set data to instance
        $this->data = $data;

        if ( ! $helper) $helper = new Helper;
        $this->helper = $helper;

        // Run init method so the object can init properly
        $this->init();
    }

    /**
     * Returns whether a data reader can read the data given
     *
     * @param   string   $data   Data as string
     * @throws  Exception\CannotReadData
     * @return  boolean  true for possible reading
     */
    public static function canRead($data)
    {
        // Throw exception
        throw new Exception\CannotReadData('canRead not implemented in Reader');
    }

    /**
     * Returns one session
     *
     * @throws  Exception\NoSession    when session is not found
     * @param int $session_number
     * @return  Session
     */
    public function getSession($session_number=1)
    {
        // Get sessions
        $sessions = $this->getSessions();

        // Session not found
        if ( ! isset($sessions[$session_number-1]) OR
             ! $session = $sessions[$session_number-1])
        {
            throw new Exception\NoSession(
                'Cannot find a session for session number '.$session_number);
        }

        // Return
        return $session;
    }

    /**
     * Returns all sessions
     *
     * @return  array
     */
    public function getSessions()
    {
        // Return the sessions with fixed data when required
        return $this->fixSessions($this->readSessions());
    }

    /**
     * Reads all sessions from the logs. Implement this in each reader
     *
     * @return array
     */
    abstract protected function readSessions();

    /**
     * Optional init method
     */
    protected function init() { }



    /**
     * Reads all sessions and fixes any data that has not been set by the
     * reader
     *
     * @param  array  $sessions
     * @return array
     */
    protected function fixSessions(array $sessions)
    {
        // Fix grid positions
        $this->fixGridPositions($sessions);

        // Fix finish statusses based on number of laps because we
        // are missing finish statusses alot
        $this->fixFinishStatusBasedOnLaps($sessions);

        // Fix laps data
        $this->fixLapsData($sessions);

        // Fix position numbers of participants
        foreach ($sessions as $session)
        {
            $this->fixParticipantPositions($session->getParticipants());
        }

        return $sessions;
    }



    /**
     * Below are data fixes that can be used by all readers. All methods are
     * designed to be safe and will ignore setting any data when specific
     * reader has already set the data
     */

    /**
     * Fix grid positions on race session by using previous qualify data.
     * Only sets the grid position when its not already set on the driver.
     *
     *
     * @param  array  $sessions
     */
    protected function fixGridPositions(array $sessions)
    {
        $last_qualify_parts = array();

        foreach ($sessions as $session)
        {
            // Is qualify
            if ($session->getType() === Session::TYPE_QUALIFY)
            {
                // Reset last qualify participants
                $last_qualify_parts = array();

                // Loop each participant and remember its position
                foreach ($session->getParticipants() as $part)
                {
                    $last_qualify_parts[$part->getDriver()->getName()]
                        = $part->getPosition();
                }
            }
            // Is race
            elseif ($session->getType() === Session::TYPE_RACE)
            {
                // Fix positions
                foreach ($session->getParticipants() as $part)
                {
                    // Qualify position known and no grid has been set yet
                    if (isset($last_qualify_parts[
                        $part->getDriver()->getName()]) AND
                        ! $part->getGridPosition())
                    {
                        // Set grid position
                        $part->setGridPosition($last_qualify_parts[
                            $part->getDriver()->getName()]);
                    }
                }
            }

        }
    }


    /**
     * Fix participants finish statusses based on the number of laps rule. Use
     * this when log files do not always provide proper finish statusses.
     *
     * @param  array   $sessions
     */
    protected function fixFinishStatusBasedOnLaps(array $sessions)
    {
        foreach ($sessions as $session)
        {
            // Is race result
            if ($session->getType() === Session::TYPE_RACE)
            {
                // Mark no finish status when participant has not completed atleast
                // 50% of total laps
                foreach ($session->getParticipants() as $participant)
                {
                    // Has no laps
                    if ($participant->getNumberOfCompletedLaps() === 0)
                    {
                        // Always set DNF
                        $participant->setFinishStatus(Participant::FINISH_DNF);
                    }
                    // Finished normally and matches 50% rule
                    elseif ($this->finish_status_none_50percent_rule AND
                        $participant->getFinishStatus() === Participant::FINISH_NORMAL AND
                        (! $participant->getNumberOfCompletedLaps() OR
                         50 > ($participant->getNumberOfCompletedLaps() /
                        ($session->getLastedLaps() / 100))))
                    {
                        $participant->setFinishStatus(Participant::FINISH_NONE);
                    }
                }
            }
        }
    }


    /**
     * Fixes laps data regarding lap numbers and elapsed time. It does not
     * change this data when it's already set using the specific result files!
     *
     * @param  array   $sessions
     */
    protected function fixLapsData(array $sessions)
    {
        foreach ($sessions as $session)
        {
            // Fix elapsed seconds and positions for all participant laps if
            // it's missing
            foreach ($session->getParticipants() as $participant)
            {
               $elapsed_time = 0;
               foreach ($participant->getLaps() as $lap_key => $lap)
               {
                    // No elapsed seconds
                    if ($lap->getElapsedSeconds() === NULL)
                    {
                        // Set elapsed seconds if we actualy have a time on this
                        // lap. We keep it NULL to be consistant in knowing when
                        // data is really missing
                        if ($lap->getTime() !== NULL)
                        {
                            $lap->setElapsedSeconds($elapsed_time);
                        }

                        // Increase elapsed time
                        $elapsed_time += $lap->getTime();
                    }

                    // Set lap number if not available
                    if ( ! $lap->getNumber())
                    {
                        $lap->setNumber($lap_key+1);
                    }
               }
            }


            // Find whether we should fix lap positions by checking whether the
            // second lap of the first participant is missing position data. Not
            // checking the first lap because the it might have grid position
            if ($parts = $session->getParticipants() AND
                $lap = $parts[0]->getLap(2) AND ! $lap->getPosition())
            {
                $session_lasted_laps = $session->getLastedLaps();

                // Loop each lap number
                for($i=1; $i <= $session_lasted_laps; $i++)
                {
                    // Get laps by lap number from session
                    $laps_sorted = $session->getLapsByLapNumberSortedByTime($i);

                    // Sort the laps by elapsed time
                    $laps_sorted = $this->helper->sortLapsByElapsedTime($laps_sorted);

                    // Loop each lap and fix position data
                    foreach ($laps_sorted as $lap_key => $lap)
                    {
                        // Only fix position if lap has a time, this way users of this
                        // library can easier detect whether it's a dummy lap and
                        // decide how to show them
                        if ( ! $lap->getPosition() AND
                             ($lap->getTime() OR $lap->getElapsedSeconds()))
                        {
                            $lap->setPosition($lap_key+1);
                        }
                    }
                }
            }
        }
    }





    /**
     * Fix the positions of participants. Participants should be sorted
     * before calling this method
     *
     * @param  array  $participants
     */
    protected function fixParticipantPositions(array $participants)
    {
        // Fix participant positions
        foreach ($participants as $key => $part)
        {
            $part->setPosition($key+1);
        }
    }



    /**
     * Sort the participants in the proper way according to the session info.
     * Also fixes the position numbers
     *
     * @param  array   $participants
     * @param  Session $session
     * @param  boolean $sort_by_last_lap_position_on_missing_finish_statusses
     *
     * @return array
     */
    protected function sortParticipantsAndFixPositions(
        array &$participants,
        Session $session,
        $sort_by_last_lap_position_on_missing_finish_statusses=false)
    {
        // Is race result
        if ($session->getType() === Session::TYPE_RACE)
        {
            // Never sort by last lap by default
            $sort_by_last_lap = false;

            // We should sort by last lap if all finish statusses are missing
            if ($sort_by_last_lap_position_on_missing_finish_statusses)
            {
                $sort_by_last_lap = true;
                foreach ($session->getParticipants() as $part)
                {
                    if ($part->getFinishStatus() !== Participant::FINISH_NONE)
                    {
                        $sort_by_last_lap = false;
                    }
                }
            }

            // Sort by last lap
            if ($sort_by_last_lap)
            {
                // Sort participants by last lap positions
                $participants =
                    $this->helper->sortParticipantsByLastLapPosition($participants);
            }
            // We have a normal race result
            else
            {
                // Sort participants by total time
                $participants =
                    $this->helper->sortParticipantsByTotalTime($participants);
            }

        }
        // Is practice or qualify
        else
        {
            // Sort by best lap
            $participants =
                $this->helper->sortParticipantsByBestLap($participants);
        }


        $this->fixParticipantPositions($participants);

        return $participants;
    }



}
