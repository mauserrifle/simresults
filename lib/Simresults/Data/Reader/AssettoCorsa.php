<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa
 *
 * TODO: Check session types when more game modes are being released
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsa extends Data_Reader {

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
       return (bool) json_decode($data, TRUE);
    }

    /**
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
    {
        // Get data
        $data = json_decode($this->data, TRUE);

        // No session data
        if ( ! $sessions_data = $this->get($data, 'sessions'))
        {
            // Throw exception
            throw new Exception\Reader('Cannot read the session data');
        }

        // Init sessions array
        $sessions = array();

        // Init participants array
        $participants = array();

        // Get participants
        $players_data = $this->get($data, 'players', array());
        foreach ($players_data as $player_data)
        {
            // Create driver
            $driver = new Driver;
            $driver->setName($this->get($player_data, 'name'));

            // Create participant and add driver
            $participant = new Participant;
            $participant->setDrivers(array($driver));

            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName($this->get($player_data, 'car'));
            $participant->setVehicle($vehicle);

            // Add participant to collection
            $participants[] = $participant;
        }

        // Gather all sessions
        foreach ($sessions_data as $session_data)
        {
            // Init session
            $session = new Session;

            // Practice session by default
            $type = Session::TYPE_PRACTICE;

            // Check session name to get type
            // TODO: Should be checked when full game is released. Also create
            //       tests for it!
            switch(strtolower($name = $this->get($session_data, 'name')))
            {
                case 'qualify session':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'warmup session':
                    $type = Session::TYPE_WARMUP;
                    break;
                case 'race session':
                    $type = Session::TYPE_RACE;
                    break;
            }

            // Set session values
            $session->setType($type)
                    ->setName($name)
                    ->setMaxLaps(
                        (int) $this->get($session_data, 'lapsCount'))
                    ->setMaxMinutes(
                        (int) $this->get($session_data, 'duration'));

            // Set game
            $game = new Game; $game->setName('Assetto Corsa');
            $session->setGame($game);

            // Set track
            $track = new Track;
            $track->setVenue($this->get($data, 'track'));
            $session->setTrack($track);

            // Set participants
            $session->setParticipants($participants);

            // Get the laps
            foreach ($this->get($session_data, 'laps', array()) as $lap_data)
            {
                // Init new lap
                $lap = new Lap;

                // Set participant
                $lap->setParticipant(
                    $lap_participant = $participants[$lap_data['car']]);

                // Set first driver of participant as lap driver. AC does
                // not support swapping
                $lap->setDriver($lap_participant->getDriver());

                // Set lap number (+1 because AC is zero based)
                $lap->setNumber($lap_data['lap']+1);

                // Set lap time in seconds
                $lap->setTime($lap_data['time'] / 1000);

                // Set sector times in seconds
                foreach ($this->get($lap_data, 'sectors', array())
                             as $sector_time)
                {
                    $lap->addSectorTime($sector_time / 1000);
                }

                // Add lap to participant
                $lap_participant->addLap($lap);
            }

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
