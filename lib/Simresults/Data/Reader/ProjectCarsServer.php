<?php
namespace Simresults;

/**
 * The reader for Project Cars sms_stats data files.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_ProjectCarsServer extends Data_Reader {


    /**
     * @var  array  The attribute names of project cars 1
     */
    protected $attribute_names;

    /**
     * @var  array  The attribute names of project cars 2
     */
    protected $attribute_names2;

    /**
     * @var  array  The attribute names of Automobilista 2
     */
    protected $attribute_names_automobilista2;

    /**
     * @var array Some Automobilista 2 vehicle ids so we can detect the game
     *            Warning: This is an old detection method. Just here for
     *            legacy reasons.
     */
    public static $automobilista2_vehicle_ids = array(
        1932261404,
        306371028,
        -494068343,
        306785397,
        -739789710,
        -532210519,
        553963368,
        619110280,
        1836524676,
        95104745,
        -1870819346,
        703591920,
        65202613,
        1437730287,
        575788923,
        -93205368,
        374810616,
        -487937394,
        253111186,
        851522805,
        -2053858829,
        -1660644383,
        -1404228714,
        523915852,
        1323381033,
        -1834081784,
        802736208,
    );

    /**
     * Game object of the current session we are looping
     */
    protected $current_game;


    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {
        if (FALSE === strpos($data, '"stages"')) {
            return false;
        }
        // Clean json so we can parse it without errors
        $data = self::cleanJSON($data);

        if ($data = json_decode($data, TRUE)) {
            return isset($data['stats']);
        }

        return false;
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        // Get data
        $data = json_decode(self::cleanJSON($this->data), TRUE);
        $data = $data['stats'];

        // No session data
        if ( ! $history_data = $data['history'])
        {
            // Throw exception
            throw new Exception\Reader('Cannot read the session data');
        }

        // Remove sessions without stages
        $history_data = array_filter($history_data, function($data) {
            return (bool) $data['stages'];
        });


        // Get attribute info of project cars to figure out vehicle names etc
        $this->attribute_names = $this->getAttributeNames();
        $this->attribute_names2 = $this->getAttributeNames2();
        $this->attribute_names_automobilista2 = $this->getAttributeNamesAutomobilista2();


        // Initial game. But might be changed later based on data
        $this->setCurrentGameName('Project Cars');

        // Init sessions array
        $sessions = array();

        // Loop each history item
        foreach ($history_data as $history)
        {

            /**
             * Collect all participants
             */

            $initial_participants_by_ref = array(); // Depricated! TODO: Remove
            $initial_participants_by_id = array();
            $initial_participants_by_name = array();

            // Loop all member entries and create participants
            // Also detect game already so if we detected Automobilista 2, we
            // will never again detect the other games
            foreach ($history['members'] as $part_ref => $part_data)
            {
                if (!isset($part_data['participantid'])) {
                    continue;
                }

                // Get participant
                $participant = $this->getParticipant($part_data);

                // Old refid code
                // $initial_participants_by_ref[$part_ref] = $participant;

                // Only store participant by id if it has not yet been
                // processed. IF it is already present, then the log is
                // bugged. The first registration has priority
                if (!isset($initial_participants_by_id[$part_data['participantid']])) {
                    // Add participant to collection
                    $initial_participants_by_id[$part_data['participantid']] =
                        $participant;
                }

                // Always store by name backup
                $initial_participants_by_name[$part_data['name']] =
                    $participant;

                // Dummy vehicle to detect game
                if (isset($part_data['setup']['VehicleId'])) {
                    $vehicle = new Vehicle;
                    $this->setVehicleName($part_data['setup']['VehicleId'], $vehicle);
                }
            }


            // Get server configuration
            $read_settings = array(
                'DamageType', 'FuelUsageType', 'PenaltiesType',
                'ServerControlsSetup', 'ServerControlsTrack',
                'ServerControlsVehicle', 'ServerControlsVehicleClass',
                'TireWearType',
             );
            $session_settings = array();
            foreach ($history['setup'] as $setup_key => $setup_value) {
                if (in_array($setup_key, $read_settings)) {
                    $session_settings[$setup_key] = $setup_value;
                }
            }

            if (array_key_exists('AllowedCutsBeforePenalty', $history['setup'])) {
                $this->setCurrentGameName('Automobilista 2');
            }

            // Loop all stages data
            foreach ($history['stages'] as $type_key => $session_data)
            {
                // Make new unique array of participants to prevent reference
                // issues across multiple sessions
                $participants_by_ref = array();
                $participants_by_id = array();
                $participants_by_name = array();
                foreach ($initial_participants_by_ref as $part_key => $part ) {
                    $participants_by_ref[$part_key] = clone $part;
                }
                foreach ($initial_participants_by_id as $part_key => $part ) {
                    $participants_by_id[$part_key] = clone $part;
                }
                foreach ($initial_participants_by_name as $part_key => $part ) {
                    $participants_by_name[$part_key] = clone $part;
                }


                // Setup name for session type
                $type_setup_name = ucfirst($type_key);
                $type_setup_name2 = preg_replace('/[0-9]+/', '', $type_setup_name);

                // Init session
                $session = $this->helper->detectSession($type_key);

                // Different setup name for quality
                if ($session->getType() === Session::TYPE_QUALIFY) {
                    $type_setup_name = 'Qualify';
                }

                $max_laps = NULL;
                // Setting key found
                if (isset($history['setup'][$type_setup_name.'Length']))
                {
                    // Get max laps using the usual setting keys
                   $max_laps = $history['setup'][$type_setup_name.'Length'];
                }
                // Setting key not found, probably Project Cars 2
                elseif (isset($history['setup'][$type_setup_name2.'Length']))
                {
                    // Get max laps using alternative
                   $max_laps = $history['setup'][$type_setup_name2.'Length'];
                }




                // Date of this session
                $date = new \DateTime;
                if (isset($session_data['start_time'])) {
                    $date->setTimestamp($session_data['start_time']);
                }
                $date->setTimezone(new \DateTimeZone(self::$default_timezone));

                // Set session values
                $session->setDate($date)
                        ->setOtherSettings($session_settings);

                if ($max_laps) {
                    $session->setMaxLaps($max_laps);
                }

                // Set current game object
                $session->setGame($this->current_game);

                // Set server
                // TODO: Set configurations
                $server = new Server; $server->setName($data['server']['name']);
                $session->setServer($server);

                // Set track
                $track = new Track;

                // Have friendly track name
                if (isset($this->attribute_names['tracks'][$history
                    ['setup']['TrackId']]))
                {
                    $track->setVenue($this->attribute_names['tracks'][$history
                        ['setup']['TrackId']]['name']);
                }
                elseif (isset($this->attribute_names2['tracks'][$history
                    ['setup']['TrackId']]))
                {
                    $track->setVenue($this->attribute_names2['tracks'][$history
                        ['setup']['TrackId']]['name']);
                }
                elseif (isset($this->attribute_names_automobilista2['tracks'][$history
                    ['setup']['TrackId']]))
                {
                    $track->setVenue($this->attribute_names_automobilista2['tracks'][$history
                        ['setup']['TrackId']]['name']);
                }
                else
                {
                    // TODO: We should test this works too? Same for vehicles
                    // when our json attribute config is missing items
                    $track->setVenue( (string) $history['setup']['TrackId']);
                }

                $session->setTrack($track);


                // Remember participants with actual events
                $participants_with_events = array();

                // Parse events first only to collect missing participants
                if (isset($session_data['events']) AND is_array($session_data['events']))
                foreach ($session_data['events'] as $event)
                {
                    // Participant unknown
                    if ( ! isset($participants_by_id[
                        $event['participantid']])) {
                        // Build it and fallback to less info
                        $part = $this->getParticipant($event);
                        $participants_by_id[$event['participantid']] = $part;
                        $participants_by_name[$event['name']] = $part;
                    }
                }

                // Parse event data such as laps
                $cut_data = array();
                $driver_has_entered_pit = array();
                $finished_participants_by_id = array();
                if (isset($session_data['events']) AND is_array($session_data['events']))
                foreach ($session_data['events'] as $event)
                {
                    // Get participant
                    $part = $participants_by_id[$event['participantid']];

                    // Remember this participant
                    $participants_with_events[] = $part;

                    // Is lap and the lap is valid (only checked for non-race)
                    if ($event['event_name'] === 'Lap' AND
                        ($session->getType() === Session::TYPE_RACE
                         OR
                         $event['attributes']['CountThisLapTimes']))
                    {
                        // Init new lap
                        $lap = new Lap;

                        // Set participant
                        $lap->setParticipant($part);

                        // Set first driver of participant as lap driver. PJ
                        // does not support swapping
                        $lap->setDriver($part->getDriver());

                        // Set total time
                        $lap->setTime(round($event['attributes']['LapTime']
                            / 1000, 4));

                        // Add sectors
                        for ($sector_i=1; $sector_i<=3; $sector_i++) {
                            $lap->addSectorTime(round($event['attributes']
                                ['Sector'.$sector_i.'Time'] / 1000, 4));
                        }

                        // Set lap position
                        $lap->setPosition($event['attributes']['RacePosition']);

                        // Set number
                        $lap->setNumber($event['attributes']['Lap']+1);

                        // Has entered pit
                        if (isset($driver_has_entered_pit[$event['name']]))
                        {
                            $lap->setPitLap(TRUE);
                            unset($driver_has_entered_pit[$event['name']]);
                        }

                        // Add lap to participant
                        $part->addLap($lap);
                    }
                    elseif ($event['event_name'] === 'Impact')
                    {
                        $type = Incident::TYPE_ENV;
                        $other_part = NULL;

                        // Other participant known
                        if ((-1 != $other_id =
                                $event['attributes']['OtherParticipantId']
                             ) AND isset($participants_by_id[$other_id]))
                        {
                            $other_part = $participants_by_id[$other_id];
                            $type = Incident::TYPE_CAR;

                        }
                        // Participant not known
                        else
                        {
                            // Skip for now until we know what -1 means
                            continue;
                        }

                        $incident = new Incident;
                        $incident->setMessage(sprintf(
                           '%s reported contact with another vehicle '.
                            '%s. CollisionMagnitude: %s' ,
                            $part->getDriver()->getName(),
                            $other_part ? $other_part->getDriver()->getName() : 'unknown',
                            $event['attributes']['CollisionMagnitude']
                        ));

                        // TODO: Add elapsed time
                        $date = new \DateTime;
                        $date->setTimestamp($event['time']);
                        $incident->setDate($date);
                        $incident->setElapsedSeconds(
                            $date->getTimestamp()
                            -
                            $session->getDate()->getTimestamp()
                        );
                        $incident->setParticipant($part);
                        $incident->setOtherParticipant($other_part);
                        $incident->setType($type);

                        $session->addIncident($incident);
                    }
                    elseif (in_array($event['event_name'],
                                array('CutTrackStart', 'CutTrackEnd')))
                    {
                        $cut_data[] = $event;
                    }
                    elseif ($event['event_name'] === 'State' AND
                            $event['attributes']['NewState'] === 'Retired')
                    {
                        $part->setFinishStatus(Participant::FINISH_DNF);
                    }
                    elseif ($event['event_name'] === 'State' AND
                            $event['attributes']['NewState'] === 'Finished')
                    {
                        $part->setFinishStatus(Participant::FINISH_NORMAL);
                        $finished_participants_by_id[$event['participantid']] = $part;
                    }
                    elseif ($event['event_name'] === 'State' AND
                            $event['attributes']['NewState'] === 'EnteringPits' AND
                            $event['attributes']['PreviousState'] === 'Racing' )
                    {
                       $driver_has_entered_pit[$event['name']] = TRUE;
                    }

                }


                /**
                 * TODO: So many duplicate code below regarding results array
                 *       reading! Fix this
                 */

                // Has results array we can read finish statusses from
                if ($results = $session_data['results'] AND is_array($results))
                {
                    // Loop each result and process the lap
                    foreach ($results as $result)
                    {
                        if (!isset($result['participantid'])) {
                            continue;
                        }

                        // Participant not found, continue to next
                        if ( ! isset($participants_by_id[
                                         $result['participantid']]))
                        {
                            continue;
                        }

                        // Has DNF state
                        if (in_array(strtolower($result['attributes']['State']),
                                array('dnf', 'retired')))
                        {
                            // Get participant
                            $part = $participants_by_id[$result['participantid']];

                            // Set DNF
                            $part->setFinishStatus(Participant::FINISH_DNF);
                        }
                    }
                }


                // We did not have any events data to process but we have
                // final results. Let's use this data to atleast get 1 best
                // lap of these participants
                if ( ! $session_data['events'] AND
                     $results = $session_data['results'])
                {
                    // Loop each result and process the lap
                    foreach ($results as $result)
                    {
                        // Participant not found, build it
                        if ( ! isset($participants_by_id[
                                         $result['participantid']]))
                        {
                            $part = $this->getParticipant($result);
                            $participants_by_id[$result['participantid']] = $part;
                            $participants_by_name[$result['name']] = $part;
                        }

                        // Get participant
                        $part = $participants_by_id[$result['participantid']];

                        // Remember this participant (fake it had events)
                        $participants_with_events[] = $part;

                        // Has best lap
                        if ($result['attributes']['FastestLapTime'])
                        {
                            // Init new lap
                            $lap = new Lap;

                            // Set participant
                            $lap->setParticipant($part);

                            // Set first driver of participant as lap driver. PJ
                            // does not support swapping
                            $lap->setDriver($part->getDriver());

                            // Set total time
                            $lap->setTime(round($result['attributes']
                                ['FastestLapTime'] / 1000, 4));

                            // Set number
                            $lap->setNumber(1);

                            // Add lap to participant
                            $part->addLap($lap);
                        }

                    }
                }


                /**
                 * Process cut info
                 */

                foreach ($cut_data as $key => $event)
                {
                    // Get participant
                    $part = $participants_by_id[$event['participantid']];

                    // Start of cut and lap actually exists
                    if ($event['event_name'] === 'CutTrackStart' AND
                        $lap = $part->getLap($event['attributes']['Lap']+1))
                    {
                        // Find the end of cutting by looping following events
                        for ($end_key=$key+1; $end_key < count($cut_data);
                                 $end_key++)
                        {
                            $next_event = $cut_data[$end_key];

                            // Next event is another cut start. Ignore current
                            // cut as theres no proper end data
                            if ($next_event['event_name'] === 'CutTrackStart' AND
                                $next_event['participantid'] == $event['participantid'])
                            {
                                // Theres no end
                                break;
                            }
                            // Next event is end of current cut
                            elseif ($next_event['event_name'] === 'CutTrackEnd' AND
                                $next_event['participantid'] == $event['participantid'])
                            {

                                $cut = new Cut;
                                $cut->setCutTime(round(
                                    $next_event['attributes']['ElapsedTime']
                                    / 1000, 4));
                                $cut->setTimeSkipped(round(
                                    $next_event['attributes']['SkippedTime']
                                    / 1000, 4));


                                $date = new \DateTime;
                                $date->setTimestamp($next_event['time']);
                                $date->setTimezone(new \DateTimeZone(
                                    self::$default_timezone));
                                $cut->setDate($date);
                                $cut->setLap($lap);
                                $cut->setElapsedSeconds(
                                    $date->getTimestamp()
                                    -
                                    $session->getDate()->getTimestamp()
                                );
                                $cut->setElapsedSecondsInLap(round(
                                    $event['attributes']['LapTime']
                                    / 1000, 4));

                                $lap->addCut($cut);

                                // Stop searching
                                break;
                            }
                        }
                    }

                }



                /**
                 * Last resort missing data fixing
                 */

                // Get additional info from participants entries
                if (isset($history['participants']) AND is_array($history['participants']))
                foreach ($history['participants'] as $part_data)
                {
                    // Driver not known
                    if ( ! isset($participants_by_name[
                        $part_data['Name']])) {
                        continue;
                    }

                    // Get previously parsed participant
                    $participant = $participants_by_name[
                        $part_data['Name']];

                    // Vehicle unknown, fix it
                    if ( ! $participant->getVehicle()->getName() AND
                         isset($part_data['VehicleId']))
                    {
                        $this->setVehicleName($part_data['VehicleId'],
                                              $participant->getVehicle());
                    }
                }



                /**
                 * Cleanup
                 */


                $participants = $participants_by_id;

                // Remove any participant who did not participate
                foreach ($participants as $part_id => $part)
                {
                    // No laps and not marked as participated
                    // TODO: Make test for strict comparison (true arg), log
                    // is on Project Cars forum for test
                    if ( ! in_array($part, $participants_with_events, true))
                    {
                        unset($participants[$part_id]);
                    }
                }



                // Get participant with normal array keys
                $participants = array_values($participants);

                // Session has predefined race result positions and it is a
                // race session
                if ($results = $session_data['results'] AND
                    is_array($results) AND
                    $session->getType() === Session::TYPE_RACE)
                {
                    // Init sorted result array
                    $participants_resultsorted = array();

                    // Get sorted participts from result array, add to sorted
                    // array and remove participant from normal array
                    foreach ($results as $result)
                    {
                        if (!isset($result['participantid'])) {
                            continue;
                        }

                        // Participant not found, continue to next
                        if ( ! isset($participants_by_id[
                                         $result['participantid']]))
                        {
                            continue;
                        }

                        // Get participant
                        $participant = $participants_by_id[
                            $result['participantid']];

                        // Set total time
                        $participant->setTotalTime(round(
                            $result['attributes']['TotalTime'] / 1000, 4));

                        // Add to sorted array and remove from normal array
                        $participants_resultsorted[] = $participant;
                        unset($participants[
                            array_search($participant, $participants, true)]);
                    }

                    // Sort leftover participants not sorted by result by
                    // total time
                    $participants =
                        $this->helper->sortParticipantsByTotalTime($participants);


                    // Merge the sorted participants result with normal sort
                    // array. Merge them and remove any duplicates
                    // NOTE: We are not using array_unique as it's causing
                    // recursive depedency
                    $merged = array_merge(
                        $participants_resultsorted, $participants);
                    $final  = array();

                    foreach ($merged as $current) {
                        if ( ! in_array($current, $final, true)) {
                            $final[] = $current;
                        }
                    }

                    $participants = $final;

                    // We cannot trust the sorted results only, so we also
                    // fallback to laps sorting
                    if (!$this->finalResultsContainAllFinishedDrivers(
                        $results, $finished_participants_by_id))
                    {
                        // Sort participants
                        $this->sortParticipantsAndFixPositions(
                            $participants, $session);
                    }

                }
                // No predefined result
                else
                {
                    // Is race
                    if ($session->getType() === Session::TYPE_RACE)
                    {
                        // Set all participants on unknown finish status
                        // We should of had a result for proper statusses
                        foreach ($participants as $part)
                        {
                            $part->setFinishStatus(Participant::FINISH_NONE);
                        }
                    }

                    // Sort participants
                    $this->sortParticipantsAndFixPositions(
                        $participants, $session, TRUE);
                }


                // Filter out participants without proper driver data
                $participants = array_values(array_filter($participants, function($part){
                    $driver = $part->getDriver();
                    return ($driver AND $driver->getName());
                }));

                // Set participants (sorted)
                $session->setParticipants($participants);

                $sessions[] = $session;
            } // End stages loop
        } // End history loop


        // Swap warmup and race positions if wrong
        $prevous_session = null;
        foreach ($sessions as $key => $session)
        {
            // Found warmup after race session
            if ($prevous_session AND
                $prevous_session->getType() === Session::TYPE_RACE AND
                $session->getType() === Session::TYPE_WARMUP)
            {
                // Swap them
                $sessions[$key] = $prevous_session;
                $sessions[$key-1] = $session;
            }

            // Remember previous session
            $prevous_session = $session;
        }




        /**
         * Collect all known steam ids by driver name to fix missing ids across sessions
         */

        // Collect steam ids from regular session parsing
        $participants_steamid_by_name = array();
        foreach ($sessions as $session)
        foreach ($session->getParticipants() as $part)
        foreach ($part->getDrivers() as $driver)
        {
            if (!$driver_id = $driver->getDriverId()) {
                continue;
            }
            $participants_steamid_by_name[$driver->getName()] = $driver->getDriverId();
        }
        // Collect steamids from players array
        if ($players = $this->helper->arrayGet($data, 'players'))
        {
            foreach ($players as $steamid => $player)
            {
                // Not a proper steam id or id already found, ignore this data
                if (!is_numeric($steamid) OR strlen($steamid) < 17 OR
                    isset($participants_steamid_by_name[$player['name']])) {
                    continue;
                }

                $participants_steamid_by_name[$player['name']] = $steamid;
            }
        }
        // // Fix all missing steam ids
        foreach ($sessions as $session)
        foreach ($session->getParticipants() as $part)
        foreach ($part->getDrivers() as $driver)
        {
            if (!$driver->getDriverId() AND
                isset($participants_steamid_by_name[$driver->getName()]))
            {
                $driver->setDriverId((string)$participants_steamid_by_name
                    [$part->getDriver()->getName()]);
            }
        }

        // Return sessions
        return $sessions;
    }


    /**
     * Helper to get new participant instance
     *
     * @param  array        $part_data
     * @return Participant
     */
    protected function getParticipant($part_data)
    {
        // Create driver
        $driver = new Driver;
        $driver->setName($part_data['name'])
               ->setHuman(false);

        // Has steam id
        if (isset($part_data['steamid'])) {
            $driver->setDriverId($part_data['steamid']);
            $driver->setHuman(true);
        }

        // Human check
        if (isset($part_data['IsPlayer'])) {
            $driver->setHuman($part_data['IsPlayer']);
        }
        if (isset($part_data['is_player'])) {
            $driver->setHuman($part_data['is_player']);
        }

        // Create participant and add driver
        $participant = Participant::createInstance();
        $participant->setDrivers(array($driver))
                    ->setFinishStatus(Participant::FINISH_NORMAL);

        // Create vehicle and add to participant
        $vehicle = new Vehicle;

        // TODO: Parse livery too?
        // $vehicle->setType( (string) $part_data['setup']['LiveryId']);

        // Has vehicle in root
        $vehicle_id = null;
        if (isset($part_data['VehicleId']))
        {
            $vehicle_id = $part_data['VehicleId'];
        }
        // Has vehicle in setup data
        elseif (isset($part_data['setup']) AND
            isset($part_data['setup']['VehicleId'])) {
            $vehicle_id = $part_data['setup']['VehicleId'];
        }
        // Has vehicle in attributes data
        elseif (isset($part_data['attributes']) AND
            isset($part_data['attributes']['VehicleId'])) {
            $vehicle_id = $part_data['attributes']['VehicleId'];
        }


        $this->setVehicleName($vehicle_id, $vehicle);

        $participant->setVehicle($vehicle);

        return $participant;
    }



    /**
     * Clean JSON data. Removes unwanted comments that break parsing
     *
     * @param   string  $json
     * @return  string
     */
    protected static function cleanJSON($json)
    {
        /**
         * Remove the following lines
         *
         *     // Persistent data for addon 'sms_stats', addon version 2.0
         *
         *     // Automatically maintained by the addon, do not edit!
         *
         *     // EOF //
         *
         * Make sure the following lines are not removed partly:
         *
         *     "name" : "LEAGUE-NAME // GT3 MASTERS #02",
         */

        // Filter out comments above json
        $json_parts = explode('{', $json, 2);
        $new_json = '{ '.$json_parts[1];

        // Filter oout last comment
        $new_json = str_replace('// EOF //', '', $new_json);

        return $new_json;
    }


    /**
     * Set vehicle name by vehicle id and vehicle object
     *
     * @param   int  $vehicle_id
     * @param   Vehicle  $vehicle
     */
    protected function setVehicleName($vehicle_id, Vehicle $vehicle)
    {
        // Automobilista 2 already detected, we will favor Automobilista
        if ($this->current_game->getName() === 'Automobilista 2') {
            $this->setVehicleNameAutomobilista2($vehicle_id, $vehicle);
        }
        // Is Automobilista2 based on hardcoded unique ids
        elseif (in_array($vehicle_id, self::$automobilista2_vehicle_ids)) {
            $this->setVehicleNameAutomobilista2($vehicle_id, $vehicle);
        }
        // Detect using Project Cars with Automobilista 2 fallback
        else {
            $this->setVehicleNameProjectCars($vehicle_id, $vehicle);
            if (!$vehicle->getName()) {
                $this->setVehicleNameAutomobilista2($vehicle_id, $vehicle);
            }
        }

        // Still no vehicle name, use vehicle id as name
        if (!$vehicle->getName()) {
            $vehicle->setName( (string) $vehicle_id);
        }
    }


    /**
     * Set vehicle name by vehicle id and vehicle object using only the
     * automobilista 2 data
     *
     * @param   int  $vehicle_id
     * @param   Vehicle  $vehicle
     */
    protected function setVehicleNameAutomobilista2($vehicle_id, Vehicle $vehicle)
    {
        // Vehicle name in hardcoded unique ids
        if (in_array($vehicle_id, self::$automobilista2_vehicle_ids))
        {
            $this->setCurrentGameName('Automobilista 2');
            $vehicle->setName( (string) $vehicle_id);
        }

        // Vehicle name in data, overwrite id name from above code (that's why
        // no elseif)
        if (isset($this->attribute_names_automobilista2['vehicles'][$vehicle_id]))
        {
            $this->setCurrentGameName('Automobilista 2');
            $vehicle->setName($this->attribute_names_automobilista2['vehicles']
                [$vehicle_id]['name']);
            $vehicle->setClass($this->attribute_names_automobilista2['vehicles']
                [$vehicle_id]['class']);
        }
    }

    /**
     * Set vehicle name by vehicle id and vehicle object using only the
     * project cars data
     *
     * @param   int  $vehicle_id
     * @param   Vehicle  $vehicle
     */
    protected function setVehicleNameProjectCars($vehicle_id, Vehicle $vehicle)
    {
        // Have friendly vehicle name from Project Cars
        if (isset($this->attribute_names['vehicles'][$vehicle_id]))
        {
            $this->setCurrentGameName('Project Cars');
            $vehicle->setName($this->attribute_names['vehicles']
                [$vehicle_id]['name']);
            $vehicle->setClass($this->attribute_names['vehicles']
                [$vehicle_id]['class']);
        }
        // Have friendly vehicle name from Project Cars 2
        elseif (isset($this->attribute_names2['vehicles'][$vehicle_id]))
        {
            $this->setCurrentGameName('Project Cars 2');
            $vehicle->setName($this->attribute_names2['vehicles']
                [$vehicle_id]['name']);
            $vehicle->setClass($this->attribute_names2['vehicles']
                [$vehicle_id]['class']);
        }
    }



    /**
     * Get the attribute names of the project cars attributes json
     *
     * @param string $file
     * @return array
     */
    public function getAttributeNames($file='ProjectCarsAttributes.json')
    {
        // Get attribute info of project cars to figure out vehicle names etc
        $attributes = json_decode(file_get_contents(
            realpath(__DIR__.'/'.$file)), true);

        // Attributes we would like to have from config
        $attribute_names = array(
            'vehicles'        => array(),
            'tracks'          => array(),
        );

        // Make easy readable array
        foreach ($attribute_names as $cat => &$values)
        {
            if (isset($attributes['response'][$cat]) AND
                isset($attributes['response'][$cat]['list']))
            {
                foreach($attributes['response'][$cat]['list'] as $item)
                {
                    if (isset($item['id'])) {
                        $id = $item['id'];
                    } else {
                        $id = $item['value'];
                    }

                    $values[$id]['name'] = $item['name'];

                    if (isset($item['class']))
                    {
                        $values[$id]['class'] = $item['class'];
                    }
                }
            }
        }

        return $attribute_names;
    }

    /**
     * Get the attribute names of the project cars 2 attributes json
     *
     * @return array
     */
    public function getAttributeNames2()
    {
        return $this->getAttributeNames('ProjectCars2Attributes.json');
    }

    /**
     * Get the attribute names of the Automobilista 2 attributes json
     *
     * @return array
     */
    public function getAttributeNamesAutomobilista2()
    {
        return $this->getAttributeNames('ProjectCarsAutomobilista2Attributes.json');
    }

    /**
     * Helper to set the current game name
     *
     * @param string $name
     */
    protected function setCurrentGameName($name)
    {
        if ($this->current_game === NULL) {
            $this->current_game = new Game;
        }

        // Automobilista 2 already detected, do not proceed with the setting
        // the current given name
        if ($this->current_game->getName() === 'Automobilista 2') {
            return;
        }

        $this->current_game->setName($name);
    }

    protected function finalResultsContainAllFinishedDrivers($results, $finished_participants_by_id)
    {
        $result_ids = array();
        foreach ($results as $result) {
            $result_ids[] = $result['participantid'];
        }

        foreach ($finished_participants_by_id as $id => $part)
        {
            if ( ! array_key_exists($id, $result_ids)) {
                return false;
            }
        }

        return true;
    }

}
