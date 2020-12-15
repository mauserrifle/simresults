<?php
namespace Simresults;

/**
 * The reader for RaceRoom server logs
 *
 * TODO: finish status
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_RaceRoomServer extends Data_Reader {

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
        if ($data = json_decode($data, TRUE)) {
            return isset($data['Sessions']);
        }

        return false;
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        // Get data
        $data = json_decode($this->data, TRUE);

        // Get date
        preg_match('/\d{10}/i', $data['Time'], $time_matches);
        $date = new \DateTime; $date->setTimestamp($time_matches[0]);
        $date->setTimezone(new \DateTimeZone(self::$default_timezone));

        // Get other settings
        $other_settings = array();
        $known_setting_keys = array(
                'Experience',
                'Difficulty',
                'FuelUsage',
                'MechanicalDamage',
                'FlagRules',
                'CutRules',
                'RaceSeriesFormat',
                'WreckerPrevention',
                'MandatoryPitstop',
                'MandatoryPitstop'
        );
        foreach ($known_setting_keys as $setting)
        {
            if ($setting_value = $this->helper->arrayGet($data, $setting)) {
                $other_settings[$setting] = $setting_value;
            }
        }

        // Init sessions array
        $sessions = array();

        // Gather all sessions
        foreach ($data['Sessions'] as $session_data)
        {

            // Init session
            $session = $this->helper->detectSession(strtolower($name = $session_data['Type']));

            // Set session values
            $session->setDate($date)
                    ->setOtherSettings($other_settings);

            // Set game
            $game = new Game; $game->setName('RaceRoom Racing Experience');
            $session->setGame($game);

            // Set server
            $server = new Server; $server->setName($this->helper->arrayGet($data, 'Server'));
            $session->setServer($server);

            // Set track
            $track = new Track;
            $track->setVenue($this->helper->arrayGet($data, 'Track'));
            $track->setCourse($this->helper->arrayGet($data, 'TrackLayout'));
            $session->setTrack($track);

            // Get participants and their best lap (only lap)
            $participants = array();
            $players_data = $this->helper->arrayGet($session_data, 'Players', array());
            foreach ($players_data as $player_index => $player_data)
            {
                // Create driver
                $driver = new Driver;

                // Has name
                if ($name = $this->helper->arrayGet($player_data, 'FullName') OR
                    $name = $this->helper->arrayGet($player_data, 'Username'))
                {
                    $driver->setName($name);
                }
                // No name
                else
                {
                    $driver->setName('unknown');
                }

                // Create participant and add driver
                $participant = Participant::createInstance();
                $participant->setDrivers(array($driver))
                            ->setPosition($this->helper->arrayGet(
                                $player_data, 'Position', null));

                // Has finish status
                if ($status = $this->helper->arrayGet($player_data, 'FinishStatus'))
                {
                    // Figure out status
                    switch(strtolower($status))
                    {
                        case 'finished':
                        case 'none':
                            $participant->setFinishStatus(
                                Participant::FINISH_NORMAL);
                            break;
                        case 'disqualified':
                            $participant->setFinishStatus(
                                Participant::FINISH_DQ);
                            break;
                        default:
                            $participant->setFinishStatus(
                                Participant::FINISH_DNF);
                            break;
                    }
                }
                // No finish status, so always finished
                else
                {
                    $participant->setFinishStatus(Participant::FINISH_NORMAL);
                }

                // Has total time
                if ($total_time = $this->helper->arrayGet($player_data, 'TotalTime'))
                {
                    $participant->setTotalTime(
                        round($player_data['TotalTime'] / 1000, 4));
                }

                // Create vehicle and add to participant
                $vehicle = new Vehicle;
                $vehicle->setName($this->helper->arrayGet($player_data, 'Car'));
                $participant->setVehicle($vehicle);

                // Laps
	            $laps = $this->helper->arrayGet($player_data, 'RaceSessionLaps');
	            $best_lap = $this->helper->arrayGet($player_data, 'BestLapTime');

	            if ($best_lap > 0 && $laps) {
	            	// Validate: Remove laps, if all laps has no time but BestLapTime is set
		            $hasLapWithTime = false;
		            foreach ($laps as $lap_key => $lap_data)
		            {
			            if ($lap_data['Time'] > 0) {
				            $hasLapWithTime = true;
				            break;
			            }
		            }
		            if (!$hasLapWithTime) {
			            $laps = array();
		            }
	            }

	            // Has Laps
                if ($laps)
                {
                    foreach ($laps as $lap_key => $lap_data)
                    {
                        // Negative lap time, skip
                        if ($lap_data['Time'] < 0) continue;

                        // Last lap, is race session, driver is dnf and
                        // lap has incidents. We should exclude this lap
                        // since it is registered fully with sectors and total
                        // time as-if it were completed
                        if ($lap_key === (count($laps)-1) AND
                            $session->getType() === Session::TYPE_RACE AND
                            $participant->getFinishStatus() === Participant::FINISH_DNF AND
                            $this->helper->arrayGet($lap_data, 'Incidents')
                        )
                        {
                            continue;
                        }

                        // Init new lap
                        $lap = new Lap;

                        // Set participant
                        $lap->setParticipant($participant);

                        // Set first driver of participant as lap driver. RR does
                        // not support swapping
                        $lap->setDriver($participant->getDriver());

                        // Set lap data
                        $lap->setNumber($lap_key+1);
                        $lap->setPosition($lap_data['Position']);
                        $lap->setPitLap($lap_data['PitStopOccured']);
                        $lap->setTime($time=(round($lap_data['Time'] / 1000, 4)));

                        // Set sector times in seconds
                        $sectors_total = 0;
                        foreach ($this->helper->arrayGet($lap_data, 'SectorTimes', array())
                                     as $sector_time)
                        {
                            if ($sector_time > 0) {
                                $sector_time_calc = $sector_time - $sectors_total;
                                $lap->addSectorTime(round($sector_time_calc / 1000, 4));
                                $sectors_total = $sector_time;
                            }
                        }

                        // Invalid lap
                        if ($session->getType() !== Session::TYPE_RACE AND
                            array_key_exists('Valid', $lap_data) AND
                            ! $lap_data['Valid'])
                        {
                            $lap->setTime(null);
                            $lap->setSectorTimes(array());
                        }

                        // Add lap to participant
                        $participant->addLap($lap);


                        // Has incidents
                        if ($incidents = $this->helper->arrayGet($lap_data, 'Incidents'))
                        {
                            // Type 0 = Car to car collision
                            // Type 1 = Collision with a track object
                            // Type 2 = Going the wrong way
                            // Type 3 = Going off track
                            // Type 4 = Staying stationary on the track
                            // Type 5 = Losing control of the vehicle
                            // Type 6 = Not serving a penalty
                            // Type 7 = Disconnecting / Giving up before the end of a race
                            // Type 8 = Missing the race start

                            $types = array(
                                0 => Incident::TYPE_CAR,
                                1 => Incident::TYPE_ENV,
                                // Defaults to other
                            );

                            // Game update date with incident index changes
                            $game_update_date = new \DateTime('2020-05-06');
                            $game_update_date->setTimezone(new \DateTimeZone(self::$default_timezone));

                            // This log uses the new game update
                            if ($date > $game_update_date) {
                                // Use newer indexes
                                $type_messages = array(
                                    0 => 'Car to car collision',
                                    1 => 'Collision with a track object',
                                    2 => 'Going the wrong way',
                                    3 => 'Going off track',
                                    4 => 'Staying stationary on the track',
                                    5 => 'Losing control of the vehicle',
                                    6 => 'Invalid Lap',
                                    7 => 'Not serving a penalty',
                                    8 => 'Disconnecting / Giving up before the end of a race',

                                    // Not confirmed key! But I guessed it should be included
                                    9 => 'Missing the race start',
                                );
                            } else {
                                $type_messages = array(
                                    0 => 'Car to car collision',
                                    1 => 'Collision with a track object',
                                    2 => 'Going the wrong way',
                                    3 => 'Going off track',
                                    4 => 'Staying stationary on the track',
                                    5 => 'Losing control of the vehicle',
                                    6 => 'Not serving a penalty',
                                    7 => 'Disconnecting / Giving up before the end of a race',
                                    8 => 'Missing the race start',
                                );
                            }



                            foreach ($incidents as $incident_data)
                            {
                                $type = $this->helper->arrayGet(
                                    $types, $incident_data['Type'], Incident::TYPE_OTHER);

                                $type_message = $this->helper->arrayGet(
                                    $type_messages, $incident_data['Type'], 'Unknown');

                                $incident = new Incident;
                                $incident->setMessage(sprintf(
                                   'LAP %s, %s, %s, Points: %s',
                                    $lap->getNumber(),
                                    $participant->getDriver()->getName(),
                                    $type_message,
                                    $incident_data['Points']
                                ));


                                $incident->setParticipant($participant);
                                $incident->setType($type);

                                $session->addIncident($incident);
                            }

                        }

                    }

                }
                // Has best lap (fallback)
                elseif (0 < $best_lap)
                {
                    // Init new lap
                    $lap = new Lap;

                    // Set participant
                    $lap->setParticipant($participant);

                    // Set first driver of participant as lap driver. RR does
                    // not support swapping
                    $lap->setDriver($participant->getDriver());

                    // Set lap number
                    $lap->setNumber(1);

                    // Set lap time in seconds
                    $lap->setTime(round($best_lap / 1000, 4));

                    // Add lap to participant
                    $participant->addLap($lap);
                }

                // Add participant to collection
                $participants[] = $participant;
            }






            // Add participants to session
            $session->setParticipants($participants);

            // Add session to collection
            $sessions[] = $session;
        }

        // Return all sessions
        return $sessions;
    }
}
