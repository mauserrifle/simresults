<?php
namespace Simresults;
use Simresults\Helper;

/**
 * The reader for Race07
 *
 * Supports the following games too:
 * * GT Legends
 * * GTR
 * * GTR 2
 * * F1 challenge 99-02
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_Race07 extends Data_Reader {

    /**
     * @var  array  The data as array
     */
    protected $array_data;


    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
        return (bool) self::parse_data($data);
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        $sessions = array();

        // Session data to re-use on next sessions. Must be collected in the
        // first raw session data (header sections)
        $initial_session = null;

        // Loop each session
        foreach ($this->array_data as $data)
        {
            // Has initial session state
            $session = null;
            if ($initial_session)
            {
                $session = clone $initial_session;
            }
            else
            {
                // Create new session instance
                $session = Session::createInstance();

                // Get date from human string when available
                if (isset($data['header']['timestring']))
                {
                    // WARNING: Default timezone used. Please note that this is not correct.
                    // The date is the date of the server, but we will never know the
                    // timezone because the data does not provide a timestamp or timezone
                    // information
                    if ($date = \DateTime::createFromFormat(
                        'Y/m/d H:i:s',
                        $data['header']['timestring'],
                        new \DateTimeZone(self::$default_timezone)
                    ))
                    {
                        $date->setTimezone(new \DateTimeZone(self::$default_timezone));
                        $session->setDate($date);
                    }
                }

                //--- Set game
                $game = new Game;
                $game->setName($data['header']['game'])
                     -> setVersion($data['header']['version']);
                $session->setGame($game);

                //--- Set server (we do not know...)
                $server = new Server; $server->setName('Unknown or offline');
                $session->setServer($server);

                //--- Set track

                $track = new Track;


                // Has race data
                if (isset($data['race']))
                {
                    // Matches track data with file based name
                    // (e.g. Scene=GameData\Locations\Monza_2007\2007_Monza.TRK)
                    if (preg_match('/^.*\\\\(.*)\\\\(.*)\..*$/i',
                        $data['race']['scene'], $track_matches))
                    {

                        // Set track values and set to session
                        $track->setVenue($track_matches[1])
                              ->setCourse($track_matches[2]);
                    }
                    // Track data not file based, probably just a string
                    else
                    {
                        $track->setVenue($data['race']['scene']);
                    }

                    $track->setLength( (float) $data['race']['track length']);
                    $session->setTrack($track);
                }

                // Remember this initial session state for all coming next
                // sessions
                $initial_session = clone $session;
            }

            // Get participants
            $this->setParticipantsAndSessionType($session, $data);

            // No participants, skip this session
            if ( ! $session->getParticipants())
            {
                continue;
            }

            $sessions[] = $session;
        }

        return $sessions;
    }


    /**
     * Set participants and session type on a session instance
     *
     * @param  Session  $session
     * @param  array    $data
     */
    protected function setParticipantsAndSessionType(Session $session, array $data)
    {
        // Init participants array
        $participants = array();

        // Collect drivers in array
        $driver_data_array = array();

        // No grid position by default
        $set_grid_position = false;
        foreach ($data as $key => $driver_data)
        {
            // Not a driver driver_data
            if ( ! preg_match('/slot([0-9]+)/i', $key, $matches))
            {
                continue;
            }

            // No qualtime
            if ( ! array_key_exists('qualtime', $driver_data))
            {
                // Create qualtime, we need it for easier closure usage
                $driver_data['qualtime'] = null;
            }
            // Has qualtime
            else
            {
                $set_grid_position = true;
            }

            $driver_data_array[] = $driver_data;
        }

        // Set grid positions
        if ($set_grid_position)
        {
            // Sort drivers by qualify time to figure out grid positions
            usort($driver_data_array, function($a, $b){
                // Same time
                if ($a['qualtime'] === $b['qualtime']) {
                    return 0;
                }

                // a has no time
                if ( ! $a['qualtime'])
                {
                    // $b is faster
                    return 1;
                }

                // b has no time
                if ( ! $b['qualtime'])
                {
                    // $a is faster
                    return -1;
                }

                return ($a['qualtime'] < $b['qualtime']) ? -1 : 1;
            });

            // Set grid positions
            foreach ($driver_data_array as
                $driver_key => &$driver_data_array_item)
            {
                $driver_data_array_item['grid_position'] = $driver_key+1;
            }
            unset($driver_data_array_item);
        }

        // All participants are dnf by default
        $all_dnf = true;

        // Loop each driver
        foreach ($driver_data_array as $driver_data)
        {
            // Create driver
            $driver = new Driver;
            $driver->setName($driver_data['driver']);

            // Create participant and add driver
            $participant = Participant::createInstance();
            $participant->setDrivers(array($driver))
                        ->setTeam(Helper::arrayGet($driver_data, 'team'));
                        // Finish position will be set later using an special
                        // sort

            // We have laps and must set grid positions
            if(Helper::arrayGet($driver_data, 'laps_collection') AND
                $set_grid_position)
            {
                $participant->setGridPosition($driver_data['grid_position']);
            }

            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName(Helper::arrayGet($driver_data, 'vehicle'));
            $participant->setVehicle($vehicle);

            // Has race time information
            if ($race_time = Helper::arrayGet($driver_data, 'racetime'))
            {
                // Not dnf by default if it's not 0:00:00.000 or dnf
                $set_dnf = ($race_time === '0:00:00.000' OR
                            $race_time === 'DNF');

                // Try setting seconds if not dnf
                if ( ! $set_dnf)
                try
                {
                    // Get seconds
                    $seconds = Helper::secondsFromFormattedTime($race_time);

                    // Set total time
                    $participant->setTotalTime($seconds);

                    // Is finished
                    $participant->setFinishStatus(Participant::FINISH_NORMAL);

                    $all_dnf = false;

                }
                // Catch invalid argument, probably a string status like DNF
                catch (\InvalidArgumentException $ex)
                {
                    $set_dnf = true;
                }

                // Should set this participant dnf
                if ($set_dnf)
                {
                    $participant->setFinishStatus(Participant::FINISH_DNF);

                    // Has reason
                    if (null !== $reason = Helper::arrayGet($driver_data, 'reason'))
                    {
                        $participant->setFinishComment("DNF (reason $reason)");
                    }
                }
            }

            // Laps count not found
            if (null === $laps_count = Helper::arrayGet($driver_data, 'laps'))
            {
                // Try racelaps key
                $laps_count = Helper::arrayGet($driver_data, 'racelaps');
            }

            // Has run laps
            if ($laps_count !== null AND $laps_count > 0)
            {
                // Get laps collection
                $laps_collection = Helper::arrayGet($driver_data, 'laps_collection');

                // Loop laps by lap count due to missing laps in results
                // so we can fill up the gaps
                for ($lap_i=1; $lap_i <= $laps_count; $lap_i++)
                {
                    // Init new lap
                    $lap = new Lap;

                    // Set participant
                    $lap->setParticipant($participant);

                    // Set first driver of participant as lap driver. Race07 does
                    // not support swapping
                    $lap->setDriver($participant->getDriver());

                    // Set lap number
                    $lap->setNumber($lap_i);

                    // Is first lap
                    if ($lap->getNumber() === 1)
                    {
                        // Set grid position as lap position
                        $lap->setPosition(Helper::arrayGet(
                            $driver_data, 'grid_position'));
                    }

                    // Lap data exists
                    if (isset($laps_collection[$lap_i]))
                    {
                        // Get lap data
                        $lap_data = $laps_collection[$lap_i];

                        // Set lap times
                        $lap->setTime($lap_data['time'])
                            ->setElapsedSeconds($lap_data['elapsed_time']);

                        $all_laps_missing = false;
                    }

                    // Add lap to participant
                    $participant->addLap($lap);
                }

                // All laps missing but has best lap
                if (sizeof($laps_collection) === 0 AND
                    ($racebestlap = Helper::arrayGet($driver_data, 'racebestlap') OR
                    $racebestlap = Helper::arrayGet($driver_data, 'bestlap')))
                {
                    // Get first lap and change time
                    $participant->getLap(1)->setTime(
                        Helper::secondsFromFormattedTime($racebestlap));
                }
            }

            // Add participant to collection
            $participants[] = $participant;
        }



        // All participants are dnf
        if ($all_dnf)
        {
            // Assume we're dealing with qualify session
            $session->setType(Session::TYPE_QUALIFY);
            $session->setName('Qualify or practice session');
        }
        // Not all participants are dnf
        else
        {
            // Race session
            $session->setType(Session::TYPE_RACE);
        }

        // Sort participants
        $this->sortParticipantsAndFixPositions($participants, $session);

        // Set participants on session
        $session->setParticipants($participants);
    }

    /**
     * @see Simresults\Data_Reader::init()
     */
    protected function init()
    {
        $this->array_data = $this->parse_data($this->data);
    }

    /**
     * Parses and converts the data to an array of multiple sessions. Keys will
     * be converted to lowercase names. Note that any second session will
     * miss header data!
     *
     * @return   array
     *
     */
    protected static function parse_data($full_data)
    {
        // Split data by sessions
        $data_sessions = explode('[END]', $full_data);

        // Prepare array data collection
        $array_data_collection = array();

        // Loop each part
        foreach ($data_sessions as $data)
        {
            // Empty data, ignore
            if ( ! trim($data)) continue;

            // Prepare array data
            $array_data = array();

            // Are laps zero based?
            $laps_zero_based = (bool) strpos($data, 'Lap=(0,');

            //----  Loop each line
            // Remember section
            $section = null;
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $line){
                // Empty line or META info, ignore this
                if ( ! $line OR strpos($line, '//') !== false ) continue;

                // Is header
                if (preg_match('/^\[(.*)\]$/i', $line, $matches))
                {
                    // Set section and continue to next line
                    $section = strtolower($matches[1]);
                    continue;
                }

                // No section, we failed
                if ( ! $section) return FALSE;

                // Get key and value
                $split = explode('=', $line, 2);

                // Get key value for array
                $key = strtolower($split[0]);

                // Is lap value
                if ($key === 'lap')
                {
                    // Laps array does not exist yet
                    if ( ! array_key_exists(
                        'laps_collection', $array_data[$section]))
                    {
                        // Init laps array
                        $array_data[$section]['laps_collection'] = array();
                    }

                    // Match lap information. e.g. (0, -1.000, 2:20.923)
                    preg_match('/^\((.*), ?(.*), ?(.*)\)$/i',
                        $split[1], $lap_matches);

                    // Zero based laps
                    if ($laps_zero_based)
                    {
                        // Increment lap number by 1
                        $lap_number = $lap_matches[1]+1;
                    }
                    // No zero based laps
                    else
                    {
                        // Use lap numbers defined in file
                        $lap_number = $lap_matches[1];
                    }

                    // Elapsed time negative, make sure it's positive
                    if ( 0 > ($elapsed_time = (float) $lap_matches[2]))
                    {
                        $elapsed_time = 0;
                    }

                    // Set lap data
                    $array_data[$section]['laps_collection'][$lap_number] = array (
                        'lap_number'     => (int) $lap_number,
                        'elapsed_time'   => $elapsed_time,
                        'time'           =>
                            Helper::secondsFromFormattedTime($lap_matches[3]),
                    );

                }
                // Is normal value
                else
                {
                    // Value does not exist yet
                    // WARNING: Quick fix for duplicate slots (which indicate
                    //          multiple sessions?)
                    if ( ! isset($array_data[$section][$key]))
                    {
                        // Set value
                        $array_data[$section][$key] = $split[1];
                    }
                }
            }

            $array_data_collection[] = $array_data;
        }

        return $array_data_collection;
    }
}
