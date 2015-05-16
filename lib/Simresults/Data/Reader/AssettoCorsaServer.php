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
            // Remember which vehicles are parsed
            $vehicle_names = array();

            // Init session
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
            if (isset($session_data['name']))
            {
                $session->setName($session_data['name']);
            }

            // Set max time
            if (isset($session_data['time']))
            {
                $session->setMaxMinutes($session_data['time']);
            }

            // Set max laps
            if (isset($session_data['laps']))
            {
                $session->setMaxLaps($session_data['laps']);
            }

            // Set game
            $game = new Game; $game->setName('Assetto Corsa');
            $session->setGame($game);

            // Has track
            if (isset($session_data['track']))
            {
                $track = new Track;
                $track->setVenue($session_data['track']);
                $session->setTrack($track);
            }

            // Has date
            if (isset($session_data['date']))
            {
                // Set it
                $session->setDateString($session_data['date']);
            }

            // Set server
            $server = new Server;
            $server->setDedicated(true);
            if (isset($session_data['server']))
            {
                $server->setName($session_data['server']);
            }
            else
            {
                $server->setName('Unknown');
            }
            $session->setServer($server);

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

                // Remember vehicle instances by vehicle name
                $vehicles = array();

                // Create vehicle and add to participant
                $vehicle = null;
                if (isset($part_data['vehicle']))
                {
                    // Init vehicle
                    $vehicle = new Vehicle;
                    $vehicle->setName($part_data['vehicle']);
                    $participant->setVehicle($vehicle);

                    // Remember vehicle instance
                    $vehicles[$part_data['vehicle']] = $vehicle;

                    // Remember vehicle names for this entire session
                    $vehicle_names[$part_data['vehicle']] = 1;
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

                    // No lap vehicle
                    if ( ! $lap_data['vehicle'])
                    {
                        // Just use participant vehicle if it is available
                        if ($vehicle)
                        {
                            $lap->setVehicle($vehicle);
                        }
                    }
                    // Has lap vehicle and vehicle instance of lap already known
                    elseif (isset($vehicles[$v=$lap_data['vehicle']]))
                    {
                        // Set vehicle instance
                        $lap->setVehicle($vehicles[$v]);
                    }
                    // Vehicle instance not known. Set new
                    else
                    {
                        // Init vehicle
                        $vehicle = new Vehicle;
                        $vehicle->setName($lap_data['vehicle']);
                        $lap->setVehicle($vehicle);

                        // Remember vehicle
                        $vehicles[$lap_data['vehicle']] = $vehicle;
                    }

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


            // Only one vehicle type in this session
            if (count($vehicle_names) === 1)
            {
                // Find any participant without vehicle and fix missing.
                // This is an easy last resort fix when parsing was bugged
                // We assume everybody has this vehicle
                foreach ($session->getParticipants() as $participant)
                if ( ! $participant->getVehicle())
                {
                    // Init vehicle
                    $vehicle = new Vehicle;
                    $vehicle->setName(key($vehicle_names));
                    $participant->setVehicle($vehicle);
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
    protected function parse_data($data)
    {
        // No server log
        if (strpos($data, 'Server CFG Path') === false) return false;

        // Make utf8
        $data = utf8_encode($data);

        // Contains windows new lines as text (so not real ones). User might
        // edited the file in a very wrong way
        if (strpos($data, '\r'))
        {
            // Replace them with real unix new lines
            $data = str_replace('\r', "\n", $data);
        }

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
            $session['type'] = 'practice'; // defaults to practice
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

            // Remember which participant regex was used so we can re-use
            // these regex at lap matching
            $participant_regex = null;
            $participant_regex_vehicle_match_key = null;

            // Collect participants by connect information so we know which
            // car they run
            preg_match_all(
                $participant_regex =
                '/REQUESTED CAR: (.*?)\R.*?DRIVER ACCEPTED.*?DRIVER: (.*?) \['
                .'/si', $data_session, $part_matches);

            // First value match is for vehicle
            $participant_regex_vehicle_match_key = 1;

            // Loop each match and collect participants
            $participants = array();
            foreach ($part_matches[0] as $part_key => $part_data)
            {
                // Explode data by REQUESTED CAR again
                // WARNING: We test this because sometimes the regular
                //          expression fails due to a missing `DRIVER` part.
                //          On fail the match includes a invalid connect and
                //          valid one in one. This mixes up car selection etc
                //          due to matches to the invalid connect (first
                //          instance)
                $part_data_exploded = preg_split(
                    '/REQUESTED CAR: .*?/', $part_data);

                // Filter any empty value
                $part_data_exploded = array_filter($part_data_exploded);

                // Init participant values
                $vehicle = null;
                $name = null;

                // Has multiple parts
                if (count($part_data_exploded) > 1)
                {
                    // Get last match as its the proper one
                    $part_data_tmp = array_pop($part_data_exploded);

                    // Do another match similar to above
                    preg_match(
                        '/(.*?)\R.*?DRIVER ACCEPTED.*?DRIVER: (.*?) \['
                        .'/si', $part_data_tmp, $part_tmp_matches);

                    // Name contains new lines, something went wrong in
                    // matching. Probably a last failed connect without the
                    // "DRIVER" part. We ignore this match!
                    $name = trim($part_tmp_matches[2]);
                    if (strstr($name, PHP_EOL)) continue;

                    $vehicle = trim($part_tmp_matches[1]);
                }
                // No multiple parts, proper match
                else
                {
                    $name = trim($part_matches[2][$part_key]);
                    $vehicle =  trim($part_matches
                        [$participant_regex_vehicle_match_key]
                        [$part_key]);
                }

                // Participant already exists
                if (isset($participants[$this->getDriverKey($name)]))
                {
                    // Vehicle is different
                    if ($participants[$this->getDriverKey($name)]['vehicle'] !== $vehicle)
                    {
                        // Mark participant to have multiple cars
                        $participants[$this->getDriverKey($name)]['has_multiple_cars'] = true;
                    }
                    // Vehcle not different, just ignore
                }
                // Participant is new
                else
                {
                    // Add participant
                    $participants[$this->getDriverKey($name)] = array(
                        'name'               => $name,
                        'vehicle'            => $vehicle,
                        'laps'               => array(),
                        'has_multiple_cars'  => false,
                    );
                }
            }

            // No participants found, try different method
            if ( ! $participants)
            {
                preg_match_all(
                    $participant_regex =
                    '/SUB\|(.*?)\|(.*?)\|(.*?)\|\|(.*?)\|(.*?)/i',
                    $data_session, $part_matches);

                    // First value match is for vehicle
                    $participant_regex_vehicle_match_key = 1;

                    // Loop each match and collect participants
                    foreach ($part_matches[0] as $part_key => $part_data)
                    {
                        $name = trim($part_matches[3][$part_key]);
                        $vehicle = trim($part_matches
                                       [$participant_regex_vehicle_match_key]
                                       [$part_key]);
                        $guid = trim($part_matches[4][$part_key]);

                        // Participant already exists
                        if (isset($participants[$this->getDriverKey($name)]))
                        {
                            // Vehicle is different
                            if ($participants[$this->getDriverKey($name)]['vehicle'] !== $vehicle)
                            {
                                // Mark participant to have multiple cars
                                $participants[$this->getDriverKey($name)]['has_multiple_cars'] = true;
                            }
                            // Vehcle not different, just ignore
                        }
                        // Participant is new
                        else
                        {
                            $participants[$this->getDriverKey($name)] = array(
                                'name'               => $name,
                                'vehicle'            => $vehicle,
                                'guid'               => $guid,
                                'laps'               => array(),
                                'has_multiple_cars'  => false,
                            );
                        }
                    }
            }

            // No participants found, try different method
            if ( ! $participants)
            {
                preg_match_all(
                    $participant_regex =
                    '/Adding car: (.*?) name=(.*?) model=(.*?) skin=(.*?) guid=(.*)/i',
                    $data_session, $part_matches);

                    // Third value match is for vehicle
                    $participant_regex_vehicle_match_key = 3;

                    // Loop each match and collect participants
                    foreach ($part_matches[0] as $part_key => $part_data)
                    {
                        $name = trim($part_matches[2][$part_key]);
                        $vehicle = trim($part_matches
                                       [$participant_regex_vehicle_match_key]
                                       [$part_key]);
                        $guid = trim($part_matches[5][$part_key]);

                        // Participant already exists
                        if (isset($participants[$this->getDriverKey($name)]))
                        {
                            // Vehicle is different
                            if ($participants[$this->getDriverKey($name)]['vehicle'] !== $vehicle)
                            {
                                // Mark participant to have multiple cars
                                $participants[$this->getDriverKey($name)]['has_multiple_cars'] = true;
                            }
                            // Vehcle not different, just ignore
                        }
                        // Participant is new
                        else
                        {
                            $participants[$this->getDriverKey($name)] = array(
                                'name'               => $name,
                                'vehicle'            => $vehicle,
                                'guid'               => $guid,
                                'laps'               => array(),
                                'has_multiple_cars'  => false,
                            );
                        }
                    }
            }

            // No participants found, try another different method....
            if ( ! $participants)
            {
                // MODEL: fc2_2014_season (0) [JC [Ma team]]
                // DRIVERNAME: JC
                // GUID:76561198023156518
                //
                // WARNING: Not using new line modifier (/s) because this causes
                // bad matching when DRIVERNAME has an empty value
                preg_match_all(
                    $participant_regex =
                    '/MODEL: (.*?) .*? \[.*? \[(.*?)\]\].*?\RDRIVERNAME: (.{1,}?)\R'
                    .'GUID:([0-9]+)/i', $data_session, $part_matches);

                // First value match is for vehicle
                $participant_regex_vehicle_match_key = 1;

                // Loop each match and collect participants
                $participants = array();
                foreach ($part_matches[0] as $part_key => $part_data)
                {
                    $name = trim($part_matches[3][$part_key]);
                    $vehicle = trim($part_matches
                                [$participant_regex_vehicle_match_key]
                                [$part_key]);

                    // Participant already exists
                    if (isset($participants[$this->getDriverKey($name)]))
                    {
                        // Vehicle is different
                        if ($participants[$this->getDriverKey($name)]['vehicle'] !== $vehicle)
                        {
                            // Mark participant to have multiple cars
                            $participants[$this->getDriverKey($name)]['has_multiple_cars'] = true;
                        }
                        // Vehcle not different, just ignore
                    }
                    // Participant is new
                    else
                    {
                        $participants[$this->getDriverKey($name)] = array(
                            'name'    => $name,
                            'vehicle' => $vehicle,
                            'team'    => trim($part_matches[2][$part_key]),
                            'guid'    => trim($part_matches[4][$part_key]),
                            'laps'    => array(),
                            'has_multiple_cars'
                                      => false,
                        );
                    }
                }
            }



            // Store participants to all participants array. Using union method
            // to prevent any data losing that happens using array_merge
            $all_participants_by_connect =
                $all_participants_by_connect + $participants;

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

                // Is race session
                if ($session2['type'] === 'race')
                {
                    // Explode data on race over line
                    $race_end = explode('RACE OVER DETECTED!', $data_session2);

                    // Just one result. Probably a race session that is not
                    // finished
                    if (count($race_end) === 1)
                    {
                        $before_race_over = $race_end[0];
                        $after_race_over = null;
                    }
                    // More results. Ignore anything above 2 parts by just
                    // reading the first and second part. For example:
                    // when we would had 3 or more parts. Last is probably from
                    // "RACE OVER PACKET, FINAL RANK". We should ignore that
                    // as it included alot of 0:00:000 times..
                    else
                    {
                        $before_race_over = $race_end[0];
                        $after_race_over = $race_end[1];
                    }


                    // Parse lap data before race over, continue to next
                    // session data if failed
                    if ( ! $this->parseLapData(
                            $before_race_over, $data_session2,
                            $participants_copy, $participant_regex,
                            $participant_regex_vehicle_match_key))
                    {
                        continue;
                    }

                    // Has data after race over
                    if ($after_race_over)
                    {
                        // Parse lap data from after race over. Make sure we
                        // only parse one lap per driver to prevent any extra
                        // laps by drivers running victory laps after finish
                        $this->parseLapData(
                            $after_race_over, $data_session2,
                            $participants_copy, $participant_regex,
                            $participant_regex_vehicle_match_key, true);
                    }


                    // Get total times
                    // MATCH: 0) Rodrigo  Sanchez Paz BEST: 16666:39:999 TOTAL:
                    //        0:00:000 Laps:0 SesID:4"
                    preg_match_all(
                        '/[0-9]+\).*? (.*?) BEST:.*?TOTAL: ([0-9]+.*?) '
                        .'Laps:(.*?) SesID.*?/',
                        $after_race_over, $time_matches);
                    foreach ($time_matches[0] as $time_key => $time_data)
                    {
                        // Add name and laps just to be sure
                        $name = trim($time_matches[1][$time_key]);
                        $name_key = $this->getDriverKey($name);
                        $participants_copy[$name_key]['name'] = $name;

                        // Has laps
                        if (isset($participants_copy[$name_key]['laps']))
                        {
                            // Laps count of BEST is higher than the laps we
                            // actually found to parse
                            if ($time_matches[3][$time_key] > count(
                                    $participants_copy[$name_key]['laps']) )
                            {
                                // Ignore this total time. It's not right.
                                // Probably includes extra victory laps after
                                // finishing
                                continue;
                            }
                        }

                        // Not 0
                        if ($time_matches[2][$time_key] !== '0:00:000')
                        {
                            $participants_copy[$name_key]['total_time'] =
                                Helper::secondsFromFormattedTime(
                                      $time_matches[2][$time_key], true);
                        }
                    }

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
                                '/[0-9]+\).*? '.preg_quote($part['name'], '/')
                                .' BEST:.*?'
                                .'TOTAL: [0-9]+.*? Laps:([1-9]+).*?/',
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
                } // End race session
                // Not a race session
                else
                {
                    // Parse lap data, continue to next session data if failed
                    if ( ! $this->parseLapData(
                        $data_session2, $data_session2, $participants_copy,
                        $participant_regex, $participant_regex_vehicle_match_key))
                    {
                        continue;
                    }
                } // End not race session

                // // Set participants_copy to session, preserving name key values
                // for later usage to fix missing data
                $session2['participants'] = $participants_copy;

                // Get chats
                preg_match_all('/CHAT (.*)?/', $data_session2, $chat_matches);
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
     * Parses the lap data and manipulates the `participants_copy` array
     *
     * @param   array     $data                  Data that may be splitted
     * @param   array     $all_data              All data (not splitted)
     * @param   array     $participants_copy     Participants (by reference!)
     * @param   array     $participants_regex    Regex to match participants
     * @param   array     $participant_regex_vehicle_match_key
     * @param   boolean   $only_one_lap_per_driver
     *
     * @return  boolean  success or not
     */
    protected function parseLapData($data, $all_data, &$participants_copy,
        $participant_regex, $participant_regex_vehicle_match_key,
        $only_one_lap_per_driver=false)
    {
        // Find all laps. Include lines below the lap in the matching
        // too, so we can later find whether it was discarded.
        // The use of (?!WORD) negative word expressions in this regex
        // produced too many difficulties and bugs, that's the reason
        // we match discarded too at this point and filter them later
        if ( ! preg_match_all(
                   '/LAP (.*?) ([0-9]+:[0-9:]+).*?'
                   .'(1\)|SendLapCompletedMessage|'
                   .'WARNING: LAPTIME DISCARDED| LAP REFUSED|$)/s',
                   $data, $lap_matches))
        {
            return false;
        }

        // Remember drivers that had a lap
        $parsed_driver = array();

        // Loop each lap and add lap to belonging participant
        foreach ($lap_matches[0] as $lap_key => $lap_data)
        {
            // Lap is refused or discarded? Ignore this lap!
            if (preg_match(
                '/(WARNING: LAPTIME DISCARDED|LAP REFUSED)/',
                $lap_data))
            {
               continue;
            }

            // Driver name
            $name = trim($lap_matches[1][$lap_key]);
            $name_key = $this->getDriverKey($name);

            // Should only parse one lap per driver and this driver has been
            // parsed already
            if ($only_one_lap_per_driver AND isset($parsed_driver[$name_key]))
            {
                // Continue to next
                continue;
            }

            // Remember we parsed this driver
            $parsed_driver[$name_key] = true;

            // Add name just to be sure
            $participants_copy[$name_key]['name'] = $name;

            // Lap vehicle not known by default (assume participant
            // has one vehicle)
            $lap_vehicle = null;

            // Participant has multiple cars in use
            if (isset($participants_copy[$name_key]['has_multiple_cars']) AND
                $participants_copy[$name_key]['has_multiple_cars'])
            {
                // Split data with lap data as delimiter
                $data_session2_split = explode($lap_data, $all_data);

                // Get first part
                $data_session2_split = $data_session2_split[0];

                // Car found above lap data
                if (preg_match_all($participant_regex,
                           $data_session2_split,
                           $lap_car_matches))
                {
                    // Get last vehicle matched
                    $lap_vehicle = trim(array_pop($lap_car_matches[
                        $participant_regex_vehicle_match_key]));
                }
                // Else no car found in lap data. This session probably
                // has no multiple connect info. This may happen because
                // we check the connect info for `has_multiple_cars`
                // on the entire log (containing all sessions).
            }


            // Add lap
            $participants_copy[$name_key]['laps'][] = array(
                'time'    => Helper::secondsFromFormattedTime(
                                 $lap_matches[2][$lap_key], true),
                'vehicle' => $lap_vehicle
                                 ? $lap_vehicle
                                 : (isset($participants_copy[$name_key]['vehicle'])
                                     ? $participants_copy[$name_key]['vehicle']
                                     : null)
            );
        }

        return true;
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

    /**
     * Get the driver names key for storing in the participants array
     *
     * @param  string $name
     * @return string
     */
    protected function getDriverKey($name)
    {
        return strtolower(str_replace(' ', '', $name));
    }
}
