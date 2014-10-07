<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Server
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaServer extends Data_Reader {

    /**
     * @var  array  The data as array
     */
    protected $array_data;

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
        return (strpos($data, 'Server CFG Path') !== false);
    }

    /**
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
    {
        // Get array data
        $data = $this->array_data;

        // Init sessions array
        $sessions = array();

        // Remember last qualify session to make up grid positions
        $last_qualify_session = null;

        // Loop each session from data
        foreach ($data as $session_data)
        {
            // loop sessions
            $session = new Session;

            // Set session type
            $type = null;

            switch($session_data['type'])
            {
                case 'qualify':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'practice':
                    $type = Session::TYPE_PRACTICE;
                    break;
                case 'warmup':
                    $type = Session::TYPE_PRACTICE;
                    break;
                case 'race':
                    $type = Session::TYPE_RACE;
                    break;
            }
            $session->setType($type);

            // Set session name
            $session->setName($session_data['name']);

            // Set max time and laps
            $session->setMaxMinutes($session_data['time']);
            $session->setMaxLaps($session_data['laps']);

            // Set game
            $game = new Game; $game->setName('Assetto Corsa');
            $session->setGame($game);

            // Set track
            $track = new Track;
            $track->setVenue($session_data['track']);
            $session->setTrack($track);

            // Has date
            if (isset($session_data['date']))
            {
                // Set it
                $session->setDateString($session_data['date']);
            }

            // Set server
            if (isset($session_data['server']))
            {
                $server = new Server;
                $server->setName($session_data['server'])
                       ->setDedicated(true);
                $session->setServer($server);
            }

            // Add allowed vehicles
            foreach ($session_data['car_list'] as $vehicle_name)
            {
                $vehicle = new Vehicle;
                $vehicle->setName($vehicle_name);
                $session->addAllowedVehicle($vehicle);
            }

            // Set chats
            foreach ($session_data['chats'] as $chat_message)
            {
                $chat = new Chat;
                $chat->setMessage($chat_message);
                $session->addChat($chat);
            }

            // Set participants
            $participants = array();
            foreach ($session_data['participants'] as $part_data)
            {
                // No name
                if ( ! $this->get($part_data, 'name'))
                {
                    continue;
                }

                // Create driver
                $driver = new Driver;
                $driver->setName($part_data['name']);

                // Total time not greater than 0
                if (0 >= $total_time=$this->get($part_data, 'total_time'))
                {
                    // Total time is null
                    $total_time = null;
                }

                // Create participant and add driver
                $participant = new Participant;
                $participant->setDrivers(array($driver))
                            ->setTotalTime($total_time);

                // Has total time parsed data and should not be a forced DNF
                if ($total_time AND ! $this->get($part_data, 'force_dnf'))
                {
                    $participant->setFinishStatus(Participant::FINISH_NORMAL);
                }
                // No total time in parsed data
                else
                {
                    $participant->setFinishStatus(Participant::FINISH_DNF);
                }

                // Create vehicle and add to participant
                if (isset($part_data['vehicle']))
                {
                    $vehicle = new Vehicle;
                    $vehicle->setName($part_data['vehicle']);
                    $participant->setVehicle($vehicle);
                }

                // Has team
                if (isset($part_data['team']))
                {
                    $participant->setTeam($part_data['team']);
                }


                // Has guid
                if (isset($part_data['guid']))
                {
                    $driver->setDriverId($part_data['guid']);
                }

                // Collect laps
                foreach ($this->get($part_data, 'laps', array()) as
                    $lap_i => $lap_data)
                {
                    // Init new lap
                    $lap = new Lap;

                    // Set participant
                    $lap->setParticipant($participant);

                    // Set first driver of participant as lap driver. AC does
                    // not support swapping
                    $lap->setDriver($participant->getDriver());

                    // Set lap number
                    $lap->setNumber($lap_i+1);

                    // Set lap times
                    $lap->setTime($lap_data['time']);

                    // Add lap to participant
                    $participant->addLap($lap);
                }

                // No laps and race result
                if ( ! $participant->getLaps() AND
                    $session->getType() === Session::TYPE_RACE)
                {
                    // Did not finish
                    $participant->setFinishStatus(Participant::FINISH_DNF);
                }

                // Add participant to collection
                $participants[] = $participant;
            }

            // Is race result
            if ($session->getType() === Session::TYPE_RACE)
            {
                // Sort participants by total time
                $participants = Helper::sortParticipantsByTotalTime($participants);
            }
            // Is practice of qualify
            else
            {
                // Sort by best lap
                $participants = Helper::sortParticipantsByBestLap($participants);
            }

            // Fix participant positions
            foreach ($participants as $key => $part)
            {
                $part->setPosition($key+1);
            }

            // Set participants to session
            $session->setParticipants($participants);


            // Fix elapsed seconds for all participant laps
            foreach ($session->getParticipants() as $participant)
            {
               $elapsed_time = 0;
               foreach ($participant->getLaps() as $lap)
               {
                    // Set elapsed seconds and increment it
                    $lap->setElapsedSeconds($elapsed_time);
                    $elapsed_time += $lap->getTime();
               }
            }

            // Is qualify
            if ($session->getType() === Session::TYPE_QUALIFY)
            {
                // Remember last qualify session
                $last_qualify_session = $session;
            }
            // Is race and has last qualify session
            else if ($session->getType() === Session::TYPE_RACE AND
                     $last_qualify_session)
            {
                // Get pairticpants of last qualify session and store names
                $last_qualify_session_participants = array();
                foreach ($last_qualify_session->getParticipants() as $part)
                {
                    $last_qualify_session_participants[] =
                        $part->getDriver()->getName();
                }

                // Loop this session participants
                foreach ($participants as $part)
                {
                    // Found participant in qualify array
                    if (false !== $key =
                        array_search($part->getDriver()->getName(),
                            $last_qualify_session_participants))
                    {
                        $part->setGridPosition($key+1);
                    }
                }

            }

            // Fix driver positions for laps
            $session_lasted_laps = $session->getLastedLaps();

            // Loop each lap number, beginning from 2, because we can't
            // figure out positions for lap 1 in AC
            // TODO: Duplicate code with RACE07 and AC normal reader
            for($i=2; $i <= $session_lasted_laps; $i++)
            {
                // Get laps sorted by elapsed time
                $laps_sorted = $session->getLapsByLapNumberSortedByTime($i);

                // Sort laps by elapsed time
                $laps_sorted = Helper::sortLapsByElapsedTime($laps_sorted);

                // Loop each lap and fix position data
                foreach ($laps_sorted as $lap_key => $lap)
                {
                    // Only fix position if lap has a time, this way users of this
                    // library can easier detect whether it's a dummy lap and
                    // decide how to show them
                    if ($lap->getTime() OR $lap->getElapsedSeconds())
                    {
                        $lap->setPosition($lap_key+1);
                    }
                }
            }


            // Add session to collection
            $sessions[] = $session;
        }


        // Return all sessions
        return $sessions;
    }

    /**
     * @see Simresults\Data_Reader::init()
     */
    protected function init()
    {
        $this->array_data = $this->parse_data($this->data);
    }

    /**
     * Parses and converts the data to an array. Keys will be converted to
     * lowercase names
     *
     * @return   array
     *
     */
    protected static function parse_data($data)
    {
        // No server log
        if (strpos($data, 'Server CFG Path') === false) return false;

        // Make utf8
        $data = utf8_encode($data);

        // Split data by sessions
        $data_sessions = explode('NextSession', $data);

       // No sessions
        if ( ! $data_sessions) return false;

        // Init return array
        $return_array = array();

        // Remember participants of connect info of all sessions, to remember
        // which car they have been running. Not every session has connect
        // info from the driver so we would miss that information
        $all_participants_by_connect = array();

        // Remember last car list of server
        $last_car_list = array();

        // Rmember previous session
        $prev_session_meta = array();

        // Loop each session data
        foreach ($data_sessions as $data_session)
        {
            // Init session data
            $session = $prev_session_meta;

            // Get session type
            preg_match('/TYPE=(.*)/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['type'] = strtolower(trim($matches[1]));
            }

            // Get session name
            preg_match('/Session: (.*)/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['name'] = $matches[1];
            }

            // Get track (from server register info)
            preg_match('/TRACK=(.*)&/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['track'] = $matches[1];
            }

            // Get max time
            preg_match('/TIME=(.*)/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['time'] = (int) $matches[1];
            }

            // Get max laps
            preg_match('/LAPS=(.*)/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['laps'] = (int) $matches[1];
            }

            // Get server name (from server register info)
            preg_match('/register\?name=(.*?)&/i', $data_session, $matches);
            if (isset($matches[1]))
            {
                $session['server'] = urldecode($matches[1]);
            }

            // Get date
            preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}.*/i', $data_session, $matches);
            if (isset($matches[0]))
            {
                $session['date'] = $matches[0];
            }

            // Remember this basic info
            $prev_session_meta = $session;

            // Get allowed cars
            preg_match('/Car list:.*?(\_(.*)\_).*?Client interval/si',
                $data_session, $car_matches);

            // Has car matches
            if (isset($car_matches[1]))
            {
                // Explode on new line and add to car list
                $session['car_list'] = explode("\n", $car_matches[1]);
                array_walk($session['car_list'], function(&$value) {
                     $value = trim($value, '_');
                });
            }
            // No car matches
            else
            {
                // Use last known list
                $session['car_list'] = $last_car_list;
            }

            // Set last car list
            $last_car_list = $session['car_list'];

            // Collect participants by connect information so we know which
            // car they run
            preg_match_all(
                '/REQUESTED CAR: (.*?)PASSWORD.*?DRIVER: (.*?) '
                .'(?:\[\]).*?OK/si', $data_session, $part_matches);

            // Loop each match and collect participants
            $participants = array();
            foreach ($part_matches[0] as $part_key => $part_data)
            {
                $participants[trim($part_matches[2][$part_key])] = array(
                    'name'    => trim($part_matches[2][$part_key]),
                    'vehicle' => trim($part_matches[1][$part_key]),
                    'laps'    => array(),
                );
            }

            // No participants found, try different method
            if ( ! $participants)
            {
                preg_match_all(
                    '/Adding car: (.*?) name=(.*?) model=(.*?) skin=(.*?)/si',
                    $data_session, $part_matches);

                    // Loop each match and collect participants
                    foreach ($part_matches[0] as $part_key => $part_data)
                    {
                        $participants[trim($part_matches[2][$part_key])] = array(
                            'name'    => trim($part_matches[2][$part_key]),
                            'vehicle' => trim($part_matches[3][$part_key]),
                            'laps'    => array(),
                        );
                    }
            }

            // No participants found, try another different method....
            if ( ! $participants)
            {
                // MODEL: fc2_2014_season (0) [JC [Ma team]]
                // DRIVERNAME: JC
                // GUID:76561198023156518
                preg_match_all(
                    '/MODEL: (.*?) .*? \[.*? \[(.*?)\]\].*?DRIVERNAME: (.*?)'
                    .'GUID:([0-9]+)/si', $data_session, $part_matches);

                // Loop each match and collect participants
                $participants = array();
                foreach ($part_matches[0] as $part_key => $part_data)
                {
                    $participants[trim($part_matches[3][$part_key])] = array(
                        'name'    => trim($part_matches[3][$part_key]),
                        'vehicle' => trim($part_matches[1][$part_key]),
                        'team'    => trim($part_matches[2][$part_key]),
                        'guid'    => trim($part_matches[4][$part_key]),
                        'laps'    => array(),
                    );
                }
            }

            // Store participants to all participants array
            $all_participants_by_connect = array_merge(
                $all_participants_by_connect, $participants);

            // Split session on any possible restarting
            $data_sessions2 = explode('RESTARTING SESSION', $data_session);

            // Process session or multiple sessions from restart
            foreach ($data_sessions2 as $data_session2)
            {
                // Make copy of initial session data
                $session2 = $session;

                // Make copy of initial participants that were collected so we
                // can  re-use this data for any other restart
                $participants_copy = $participants;

                // Find all laps, ignoring any discarded. If none found,
                // continue to next session
                // MATCH: LAP Zimtpatrone :] 8:51:564
                if ( ! preg_match_all(
                           '/LAP (.*?) ([0-9:]+[0-9]+)'
                           .'(\n|\r)(?!WARNING: LAPTIME DISCARDED|LAP REFUSED)/i',
                           $data_session2, $lap_matches))
                {
                    continue;
                }

                // Loop each lap and add lap to belonging participant
                foreach ($lap_matches[0] as $lap_key => $lap_data)
                {
                    // Add name just to be sure
                    $participants_copy[$lap_matches[1][$lap_key]]['name'] =
                        trim($lap_matches[1][$lap_key]);

                    // Add lap
                    $participants_copy[$lap_matches[1][$lap_key]]['laps'][] = array(
                        'time' => Helper::secondsFromFormattedTime(
                                      $lap_matches[2][$lap_key], true),
                    );
                    $no_laps = false;
                }

                // Explode data on race end detection
                $race_end = explode('RACE OVER', $data_session2);

                // 3 or more parts. Last is probably from  "RACE OVER PACKET,
                // FINAL RANK". We should ignore that as it included alot of
                // 0:00:000 times..
                if (count($race_end) >= 3)
                {
                    // Get second part after RACE OVER
                    $race_end = $race_end[1];
                }
                else
                {
                    // Use last part by default
                    $race_end = array_pop($race_end);
                }


                // Get total times
                // MATCH: 0) Rodrigo  Sanchez Paz BEST: 16666:39:999 TOTAL:
                //        0:00:000 Laps:0 SesID:4"
                preg_match_all('/[0-9]+\).*? (.*?) BEST:.*?TOTAL: ([0-9]+.*?) Laps.*?/i',
                    $race_end, $time_matches);
                foreach ($time_matches[0] as $time_key => $time_data)
                {
                    // Add name and laps just to be sure
                    $participants_copy[$time_matches[1][$time_key]]['name'] =
                        trim($time_matches[1][$time_key]);

                    // Not 0
                    if ($time_matches[2][$time_key] !== '0:00:000')
                    {
                        $participants_copy[$time_matches[1][$time_key]]['total_time'] =
                            Helper::secondsFromFormattedTime(
                                  $time_matches[2][$time_key], true);
                    }
                }

                // Is race session
                if ($session2['type'] === 'race')
                {
                    // Loop each participant and find the max laps ran for this
                    // session
                    $max_laps = 0;
                    foreach ($participants_copy as &$part)
                    {
                        // Participant has more laps than current max laps
                        if (isset($part['laps']) AND count($part['laps']) > $max_laps)
                        {
                            $max_laps = count($part['laps']);
                        }
                    }

                    // Loop each participant to check for lapped driver that
                    // might have not finished but has a total time from logs
                    foreach ($participants_copy as &$part)
                    {
                        // Participant has no total time. No need to process
                        // to speed up parsing
                        if ( ! isset($part['total_time']) OR
                             ! $part['total_time'])
                        {
                            continue;
                        }

                        // Total laps is 3+ less of the max laps (what leader
                        // ran)
                        if (isset($part['laps']) AND
                            (count($part['laps'])+3) <= $max_laps)
                        {
                            // Find all BEST TOTAL lines of this driver.
                            // But only those with actual lap data (1+)
                            if ( ! preg_match_all(
                                '/[0-9]+\).*? '.$part['name'].' BEST:.*?'
                                .'TOTAL: [0-9]+.*? Laps:([1-9]+).*?/i',
                                $data_session2,
                                $time_matches))
                            {
                                // No best times, continue to next
                                continue;
                            }

                            // Get last 3 best lines data
                            $last_3_best = array_slice($time_matches[1], -3, 3);

                            // Not 3 best lines found
                            if ( ! $last_3_best OR count($last_3_best) < 3)
                            {
                                continue;
                            }

                            // All laps found are the same This should be DNF
                            if ($last_3_best[0] === $last_3_best[1] AND
                                $last_3_best[1] === $last_3_best[2])
                            {
                                // Force participant dnf
                                $part['force_dnf'] = true;
                            }

                        }
                    }
                }

                // // Set participants_copy to session, preserving name key values
                // for later usage to fix missing data
                $session2['participants'] = $participants_copy;

                // Get chats
                preg_match_all('/CHAT (.*)?/i', $data_session2, $chat_matches);
                $session2['chats'] = $chat_matches[1];

                // Add session
                $return_array[] = $session2;
            }

        }


        /// Loop each session from return array
        foreach ($return_array as &$session_data)
        {
            // Loop each participant
            foreach ($session_data['participants'] as $part_name => &$part_data)
            {
                // Participant was known by connect info, fix name and vehicle
                // info. Some participants miss them because were collected by
                // laps and were not known by current session connect info
                if (isset($all_participants_by_connect[$part_name]))
                {
                    // Get participant from all connect info
                    $part_connect = $all_participants_by_connect[$part_name];

                    // Merge data to fix missing data
                    $part_data = array_merge($part_connect, $part_data);
                }
            }
            unset($part_data);

            $session_data['participants'] = array_values(
                $session_data['participants']);
        }
        unset($session_data);

        return $return_array;
    }

    /**
     * Retrieve a single key from an array. If the key does not exist in the
     * array, the default value will be returned instead.
     *
     *     // Get the value "username" from $_POST, if it exists
     *     $username = Arr::get($_POST, 'username');
     *
     *     // Get the value "sorting" from $_GET, if it exists
     *     $sorting = Arr::get($_GET, 'sorting');
     *
     * This function is from the Kohana project (http://kohanaframework.org/).
     *
     * @param   array   $array      array to extract from
     * @param   string  $key        key name
     * @param   mixed   $default    default value
     * @return  mixed
     */
    protected function get($array, $key, $default = NULL)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
