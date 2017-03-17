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
            $session = Session::createInstance();

            // Practice session by default
            $type = Session::TYPE_PRACTICE;

            // Check session type
            switch(strtolower($name = $session_data['Type']))
            {
                case 'qualify':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'qualify2':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'qualify3':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'warmup':
                    $type = Session::TYPE_WARMUP;
                    break;
                case 'race':
                    $type = Session::TYPE_RACE;
                    break;
                case 'race2':
                    $type = Session::TYPE_RACE;
                    break;
                case 'race3':
                    $type = Session::TYPE_RACE;
                    break;
            }

            // Set session values
            $session->setType($type)
                    ->setDate($date)
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

                // Has laps
                if ($laps = $this->helper->arrayGet($player_data, 'RaceSessionLaps'))
                {
                    foreach ($laps as $lap_key => $lap_data)
                    {
                        // Negative lap time, skip
                        if ($lap_data['Time'] < 0) continue;

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
                        $lap->setTime(round($lap_data['Time'] / 1000, 4));

                        // Add lap to participant
                        $participant->addLap($lap);
                    }

                }
                // Has best lap (fallback)
                elseif (0 < $best_lap = $this->helper->arrayGet($player_data, 'BestLapTime'))
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
