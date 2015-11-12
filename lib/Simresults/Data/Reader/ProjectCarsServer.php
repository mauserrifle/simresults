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
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
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
        $attribute_names = $this->getAttributeNames();

        // Init sessions array
        $sessions = array();

        // Loop each history item
        foreach ($history_data as $history)
        {

            /**
             * Collect all participants
             */

            $initial_participants_by_ref = array();
            $initial_participants_by_id = array();

            // Loop all member entries and create participants
            foreach ($history['members'] as $part_ref => $part_data)
            {
                // Create driver
                $driver = new Driver;
                $driver->setName($part_data['name'])
                       ->setDriverId($part_data['steamid']);

                // Create participant and add driver
                $participant = Participant::createInstance();
                $participant->setDrivers(array($driver))
                            // No grid position yet. Can't figure out in log
                            // ->setGridPosition($player_index+1)
                            ->setFinishStatus(Participant::FINISH_NORMAL);

                // Create vehicle and add to participant
                $vehicle = new Vehicle;

                // TODO: Parse livery too?
                // $vehicle->setType( (string) $part_data['setup']['LiveryId']);

                // Have friendly vehicle name
                if (isset($attribute_names['vehicles'][$part_data['setup']
                    ['VehicleId']]))
                {
                    $vehicle->setName($attribute_names['vehicles']
                        [$part_data['setup']['VehicleId']]['name']);
                    $vehicle->setClass($attribute_names['vehicles']
                        [$part_data['setup']['VehicleId']]['class']);
                }
                else
                {
                    $vehicle->setName( (string) $part_data['setup']['VehicleId']);
                }

                $participant->setVehicle($vehicle);

                // Add participant to collection
                $initial_participants_by_ref[$part_ref] = $participant;
                $initial_participants_by_id[$part_data['participantid']] =
                    $participant;
            }


            // Get additional info from participants entries
            foreach ($history['participants'] as $part_data)
            {
                // Get previously parsed participant
                $participant = $initial_participants_by_ref[$part_data['RefId']];

                // Set whether participant is human
                $participant->getDriver()->setHuman((bool) $part_data['IsPlayer']);
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
                    $participants_by_ref[$part_key] = clone $part;
                }
                foreach ($initial_participants_by_id as $part_key => $part )
                {
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
                        ->setDate($date);


                // Set game
                $game = new Game; $game->setName('Project Cars');
                $session->setGame($game);

                // Set server
                // TODO: Set configurations
                $server = new Server; $server->setName($data['server']['name']);
                $session->setServer($server);

                // Set track
                $track = new Track;

                // Have friendly track name
                if (isset($attribute_names['tracks'][$history
                    ['setup']['TrackId']]))
                {
                    $track->setVenue($attribute_names['tracks'][$history
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

                // Parse event data such as laps
                $cut_data = array();
                foreach ($session_data['events'] as $event)
                {
                    // Get participant
                    $part = $participants_by_ref[$event['refid']];

                    // Remember this participant
                    $participants_with_events[] = $part;

                    // Is lap
                    // TODO: Lap cutting
                    if ($event['event_name'] === 'Lap')
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

                        // Add lap to participant
                        $part->addLap($lap);
                    }
                    elseif ($event['event_name'] === 'Impact')
                    {
                        $participant = $participants_by_id
                            [$event['participantid']];

                        // Other participant is unknown by default
                        $other_participant_name = 'unknown';

                        // Other participant known
                        if (-1 != $other_id =
                                $event['attributes']['OtherParticipantId'])
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

                        $incident = new Incident;
                        $incident->setMessage(sprintf(
                           '%s reported contact with another vehicle '.
                            '%s. CollisionMagnitude: %s' ,
                            $participant->getDriver()->getName(),
                            $other_participant_name,
                            $event['attributes']['CollisionMagnitude']
                        ));

                        // TODO: Add elapsed time
                        $date = new \DateTime;
                        $date->setTimestamp($event['time']);
                        $incident->setDate($date);

                        $session->addIncident($incident);
                    }
                    elseif ($event['event_name'] === 'CutTrackStart')
                    {
                        $cut_data[] = $event;
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
                        // Get participant
                        $part = $participants_by_ref[$result['refid']];

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

                foreach ($cut_data as $event)
                {
                    // Get participant
                    $part = $participants_by_ref[$event['refid']];

                    // Lap actually exists....
                    if ($lap = $part->getLap($event['attributes']['Lap']+1))
                    {
                        // Add cut
                        $lap->addCut();
                    }

                }


                /**
                 * Cleanup
                 */


                $participants = $participants_by_ref;

                // Remove any participant who did not participate
                foreach ($participants as $part_ref => $part)
                {
                    // No laps and not marked as participated
                    if ( ! in_array($part, $participants_with_events))
                    {
                        unset($participants[$part_ref]);
                    }
                }

                /**
                 * Data fixing
                 *
                 * TODO: Should not be duplicate code (other readers have this code
                 *       as well)
                 */

                // Get participant with normal array keys
                $participants = array_values($participants);


                // Session has predefined race result positions

                // WARNING: We only do this for race sessions because for
                // qualify and practice some drivers are missing from the
                // result
                if ($results = $session_data['results'] AND
                    $session->getType() === Session::TYPE_RACE)
                {
                    // Create new participants order
                    $participants_resultsorted = array();

                    foreach ($results as $result)
                    {
                        // Get participant
                        $participant = $participants_by_ref[$result['refid']];

                        // Set position
                        $participant->setPosition(
                            $result['attributes']['RacePosition']);

                        // Set total time
                        $participant->setTotalTime(round(
                            $result['attributes']['TotalTime'] / 1000, 4));

                        // Add to sorted array and remove from normal array
                        $participants_resultsorted[] = $participant;
                        unset($participants[
                            array_search($participant, $participants)]);

                    }

                    // Sort participants not sorted by result by total time
                    $participants =
                        Helper::sortParticipantsByTotalTime($participants);

                    // Merge the sorted participants result with normal sort
                    // array. Merge them and remove any duplicates
                    $participants = array_unique(array_merge(
                        $participants_resultsorted, $participants), SORT_REGULAR);

                }
                // Is race result but without results array
                elseif ($session->getType() === Session::TYPE_RACE)
                {
                    // Set all participants on unknown finish status
                    foreach ($participants_by_ref as $part)
                    {
                        $part->setFinishStatus(Participant::FINISH_NONE);
                    }

                    // Sort participants by last lap positions
                    $participants =
                        Helper::sortParticipantsByLastLapPosition($participants);
                }
                // Is practice or qualify
                else
                {
                    // Sort by best lap
                    $participants =
                        Helper::sortParticipantsByBestLap($participants);
                }



                // Fix all participant positions
                foreach ($participants as $key => $part)
                {
                    $part->setPosition($key+1);
                }

                // Set participants (sorted)
                $session->setParticipants($participants);



                // Is race result
                // WARNING: THIS CODE RELIES ON PARTICIPANTS BEING SET ON
                //          SESSION ABOVE
                if ($session->getType() === Session::TYPE_RACE)
                {
                    // Mark no finish status when participant has not completed atleast
                    // 50% of total laps
                    // TODO: Duplicate code!!!!
                    foreach ($participants as $participant)
                    {
                        // Finished normally and matches 50% rule
                        if ($participant->getFinishStatus()
                                === Participant::FINISH_NORMAL
                            AND
                            (! $participant->getNumberOfCompletedLaps() OR
                             50 > ($participant->getNumberOfCompletedLaps() /
                            ($session->getLastedLaps() / 100))))
                        {
                            $participant->setFinishStatus(Participant::FINISH_NONE);
                        }
                    }
                }


                // Fix elapsed seconds for all participant laps
                foreach ($participants as $participant)
                {
                   $elapsed_time = 0;
                   foreach ($participant->getLaps() as $lap_key => $lap)
                   {
                        // Set elapsed seconds and increment it
                        $lap->setElapsedSeconds($elapsed_time);
                        $elapsed_time += $lap->getTime();
                   }
                }


                $sessions[] = $session;
            }

        }



        // Return sessions
        return $sessions;
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
        return preg_replace('#//.*#', '', $json);
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
