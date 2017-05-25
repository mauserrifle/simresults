<?php
namespace Simresults;
use Simresults\Result\Helper;
use Simresults\Result\Session;
use Simresults\Result\Participant;

/**
 * The reader for Project Cars sms_stats data files.
 *
 * WARNING: This file is highly experimental and has been made in a hurry.
 *          Expect alot of duplicate code that has to be refactored soon
 *          (TODO).
 *
 * GOOD TO KNOW: participant is cloned, when this happens laps are resetted
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_ProjectCarsServer extends Data_Reader {


    /**
     * @var  array  The attribute names
     */
    protected $attribute_names;


    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
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

            // Loop all member entries and create participants
            foreach ($history['members'] as $part_ref => $part_data)
            {
                // Get participant
                $participant = $this->getParticipant($part_data);

                // Add participant to collection
                // $initial_participants_by_ref[$part_ref] = $participant;
                $initial_participants_by_id[$part_data['participantid']] =
                    $participant;
            }


            // Get additional info from participants entries
            // Disabled due to duplicate refids bugs 2015-12-14
            // foreach ($history['participants'] as $part_data)
            // {
            //     // Get previously parsed participant
            //     $participant = $initial_participants_by_ref[
            //         $part_data['RefId']];

            //     // Set whether participant is human
            //     $participant->getDriver()->setHuman(
            //         (bool) $part_data['IsPlayer']);
            // }


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


            // Loop all stages data
            foreach ($history['stages'] as $type_key => $session_data)
            {
                // Make new unique array of participants to prevent reference
                // issues across multiple sessions
                $participants_by_ref = array();
                $participants_by_id = array();
                foreach ($initial_participants_by_ref as $part_key => $part )
                {
                    $part->setLaps(array()); // Fix laps collection
                    $participants_by_ref[$part_key] = clone $part;
                }
                foreach ($initial_participants_by_id as $part_key => $part )
                {
                    $part->setLaps(array()); // Fix laps collection
                    $participants_by_id[$part_key] = clone $part;
                }

                // Init session
                $session = Session::createInstance();

                // Practice session by default
                $type = Session::TYPE_PRACTICE;

                // Setup name for session type
                $type_setup_name = ucfirst($type_key);

                // Check session name to get type
                // TODO: Could we prevent duplicate code for this with other readers?
                switch(strtolower(preg_replace('#\d#', '', $type_key)))
                {
                    case 'qualifying':
                        $type = Session::TYPE_QUALIFY;
                        $type_setup_name = 'Qualify';
                        break;
                    case 'warmup':
                        $type = Session::TYPE_WARMUP;
                        break;
                    case 'race':
                        $type = Session::TYPE_RACE;
                        break;
                }


                // Date of this session
                $date = new \DateTime;
                $date->setTimestamp($session_data['start_time']);
                $date->setTimezone(new \DateTimeZone(self::$default_timezone));

                // Set session values
                $session->setType($type)
                        ->setName($type_key)
                        ->setMaxLaps($history['setup'][$type_setup_name.'Length'])
                        ->setDate($date)
                        ->setOtherSettings($session_settings);


                // Set game
                $game = new Result\Game; $game->setName('Project Cars');
                $session->setGame($game);

                // Set server
                // TODO: Set configurations
                $server = new Result\Server; $server->setName($data['server']['name']);
                $session->setServer($server);

                // Set track
                $track = new Result\Track;

                // Have friendly track name
                if (isset($this->attribute_names['tracks'][$history
                    ['setup']['TrackId']]))
                {
                    $track->setVenue($this->attribute_names['tracks'][$history
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
                foreach ($session_data['events'] as $event)
                {
                    // Participant unknown
                    if ( ! isset($participants_by_id[
                        $event['participantid']])) {
                        // Build it and fallback to less info
                        $part = $this->getParticipant($event);
                        $participants_by_id[$event['participantid']] = $part;
                    }
                }

                // Parse event data such as laps
                $cut_data = array();
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
                        $lap = new Result\Lap;

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

                        // Add lap to participant
                        $part->addLap($lap);
                    }
                    elseif ($event['event_name'] === 'Impact')
                    {
                        // Other participant is unknown by default
                        $other_participant_name = 'unknown';

                        // Other participant known
                        if ((-1 != $other_id =
                                $event['attributes']['OtherParticipantId']
                             ) AND isset($participants_by_id[$other_id]))
                        {
                            // Set other name
                            $other_participant_name =
                                $participants_by_id[$other_id]
                                    ->getDriver()->getName();

                        }
                        // Participant not known
                        else
                        {
                            // Skip for now until we know what -1 means
                            continue;
                        }

                        $incident = new Result\Incident;
                        $incident->setMessage(sprintf(
                           '%s reported contact with another vehicle '.
                            '%s. CollisionMagnitude: %s' ,
                            $part->getDriver()->getName(),
                            $other_participant_name,
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

                }


                /**
                 * TODO: So many duplicate code below regarding results array
                 *       reading! Fix this
                 */

                // Has results array we can read finish statusses from
                if ($results = $session_data['results'])
                {
                    // Loop each result and process the lap
                    foreach ($results as $result)
                    {
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
                        // Participant not found, continue to next
                        if ( ! isset($participants_by_id[
                                         $result['participantid']]))
                        {
                            continue;
                        }

                        // Get participant
                        $part = $participants_by_id[$result['participantid']];

                        // Remember this participant (fake it had events)
                        $participants_with_events[] = $part;

                        // Has best lap
                        if ($result['attributes']['FastestLapTime'])
                        {
                            // Init new lap
                            $lap = new Result\Lap;

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

                                $cut = new Result\Cut;
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


                // Session has predefined race result positions
                // WARNING: We only do this for race sessions because for
                // qualify and practice some drivers are missing from the
                // result
                if ($results = $session_data['results'] AND
                    $session->getType() === Session::TYPE_RACE)
                {
                    // Sort participants using our own sort
                    $tmp_sort =
                        $this->helper->sortParticipantsByTotalTime($participants);

                    // Find whether our leading participant using our own sort
                    // is in the result
                    $leading_is_in_result = false;
                    foreach ($results as $result)
                    {
                        // Participant not found, continue to next
                        if ( ! isset($participants_by_id[
                                         $result['participantid']]))
                        {
                            continue;
                        }

                        // Get participant
                        $participant = $participants_by_id[
                            $result['participantid']];

                        // Leading found
                        if ($participant === $tmp_sort[0])
                        {
                            $leading_is_in_result = true;
                        }
                    }

                    // Leading participant is in the results array
                    if ($leading_is_in_result)
                    {
                        // Init sorted result array
                        $participants_resultsorted = array();

                        foreach ($results as $result)
                        {
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

                        // Sort participants not sorted by result by total time
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
                    }
                    // Cannot trust the results, just fallback to laps sorting
                    else
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
        $driver = new Result\Driver;
        $driver->setName($part_data['name'])
               ->setHuman(false);

        // Has steam id
        if (isset($part_data['steamid'])) {
            $driver->setDriverId($part_data['steamid']);
            $driver->setHuman(true);
        }

        // Create participant and add driver
        $participant = Participant::createInstance();
        $participant->setDrivers(array($driver))
                    ->setFinishStatus(Participant::FINISH_NORMAL);

        // Create vehicle and add to participant
        $vehicle = new Result\Vehicle;

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


        // Have friendly vehicle name
        if (isset($this->attribute_names['vehicles'][$vehicle_id]))
        {
            $vehicle->setName($this->attribute_names['vehicles']
                [$part_data['setup']['VehicleId']]['name']);
            $vehicle->setClass($this->attribute_names['vehicles']
                [$part_data['setup']['VehicleId']]['class']);
        }
        else
        {
            $vehicle->setName( (string) $vehicle_id);
        }

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
        // Remove comments which are not supported by json syntax
        return preg_replace('#// .*#', '', $json);
    }


    /**
     * Get the attribute names of the project cars attributes json
     *
     * @return array
     */
    protected function getAttributeNames()
    {
        // Get attribute info of project cars to figure out vehicle names etc
        $attributes = json_decode(file_get_contents(
            realpath(__DIR__.'/ProjectCarsAttributes.json')), true);

        // Attributes we would like to have from config
        $attribute_names = array(
            'vehicles'        => array(),
            'liveries'        => array(),
            'tracks'          => array(),
        );

        // Make easy readable array
        foreach ($attribute_names as $cat => &$values)
        {
            foreach($attributes['response'][$cat]['list'] as $item)
            {
                $values[$item['id']]['name'] = $item['name'];

                if (isset($item['class']))
                {
                    $values[$item['id']]['class'] = $item['class'];
                }
            }
        }

        return $attribute_names;
    }

}
