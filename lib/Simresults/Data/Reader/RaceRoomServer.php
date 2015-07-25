<?php
namespace Simresults;

/**
 * The reader for RaceRoom server logs
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
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
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
            if ($setting_value = $this->get($data, $setting)) {
                $other_settings[$setting] = $setting_value;
            }
        }

        // Init sessions array
        $sessions = array();

        // Gather all sessions
        foreach ($data['Sessions'] as $session_data)
        {
            // Init session
            $session = new Session;

            // Practice session by default
            $type = Session::TYPE_PRACTICE;

            // Check session type
            switch(strtolower($name = $session_data['Type']))
            {
                case 'qualify':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'warmup':
                    $type = Session::TYPE_WARMUP;
                    break;
                case 'race':
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
            $server = new Server; $server->setName($this->get($data, 'Server'));
            $session->setServer($server);

            // Set track
            $track = new Track;
            $track->setVenue($this->get($data, 'Track'));
            $session->setTrack($track);

            // Get participants and their best lap (only lap)
            $participants = array();
            $players_data = $this->get($session_data, 'Players', array());
            foreach ($players_data as $player_index => $player_data)
            {
                // Create driver
                $driver = new Driver;
                $driver->setName($this->get($player_data, 'Username',
                                            'unknown'));

                // Create participant and add driver
                $participant = new Participant;
                $participant->setDrivers(array($driver))
                            ->setPosition($this->get($player_data, 'Position',
                                                     null))
                            ->setFinishStatus(Participant::FINISH_NORMAL);

                // Create vehicle and add to participant
                $vehicle = new Vehicle;
                $vehicle->setName($this->get($player_data, 'Car'));
                $participant->setVehicle($vehicle);

                // Has best lap
                if (0 < $best_lap = $this->get($player_data, 'BestLapTime'))
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