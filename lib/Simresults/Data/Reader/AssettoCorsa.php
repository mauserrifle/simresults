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
        if ($data = json_decode($data, TRUE)) {
            return isset($data['players']);
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

        // No session data
        if ( ! $sessions_data = Helper::arrayGet($data, 'sessions'))
        {
            // Throw exception
            throw new Exception\Reader('Cannot read the session data');
        }

        // Init sessions array
        $sessions = array();


        // Get extra data for all sessions
        $extras = array();
        foreach (Helper::arrayGet($data, 'extras', array()) as $extras_data)
        {
            // Get name
            $name = Helper::arrayGet($extras_data, 'name');

            // Loop all values and add as extra settings
            foreach ($extras_data as $extra_data_key => $extra_data_value)
            {
                // Is name
                if ($extra_data_key === 'name')
                {
                    // Skip this
                    continue;
                }

                // Add to extras collection
                $extras[ucfirst($name).' '.$extra_data_key]
                    = $extra_data_value;
            }
        }

        // Gather all sessions
        foreach ($sessions_data as $session_data)
        {
            // Init session
            $session = Session::createInstance();

            // Get participants (do for each session to prevent re-used objects
            // between sessions)
            $participants = array();
            $players_data = Helper::arrayGet($data, 'players', array());
            foreach ($players_data as $player_index => $player_data)
            {
                // Create driver
                $driver = new Driver;
                $driver->setName(Helper::arrayGet($player_data, 'name'));

                // Create participant and add driver
                $participant = new Participant;
                $participant->setDrivers(array($driver))
                            // No grid position yet. Can't figure out in AC log
                            // files
                            // ->setGridPosition($player_index+1)
                            ->setFinishStatus(Participant::FINISH_NORMAL);

                // Create vehicle and add to participant
                $vehicle = new Vehicle;
                $vehicle->setName(Helper::arrayGet($player_data, 'car'));
                $participant->setVehicle($vehicle);

                // Add participant to collection
                $participants[] = $participant;
            }

            // Practice session by default
            $type = Session::TYPE_PRACTICE;

            // Check session name to get type
            // TODO: Should be checked when full game is released. Also create
            //       tests for it!
            switch(strtolower($name = Helper::arrayGet($session_data, 'name')))
            {
                case 'qualify session':
                case 'qualify':
                    $type = Session::TYPE_QUALIFY;
                    break;
                case 'warmup session':
                    $type = Session::TYPE_WARMUP;
                    break;
                case 'race session':
                case 'quick race':
                case 'race':
                    $type = Session::TYPE_RACE;
                    break;
            }

            // Set session values
            $session->setType($type)
                    ->setName($name)
                    ->setMaxLaps(
                        (int) Helper::arrayGet($session_data, 'lapsCount'))
                    ->setMaxMinutes(
                        (int) Helper::arrayGet($session_data, 'duration'));

            // Set game
            $game = new Game; $game->setName('Assetto Corsa');
            $session->setGame($game);

            // Set server (we do not know...)
            $server = new Server; $server->setName('Unknown or offline');
            $session->setServer($server);

            // Set track
            $track = new Track;
            $track->setVenue(Helper::arrayGet($data, 'track'));
            $session->setTrack($track);


            // Get the laps
            $laps_data = Helper::arrayGet($session_data, 'laps', array());

            // No laps data
            if ( ! $laps_data)
            {
                // Use best laps if possible
                $laps_data = Helper::arrayGet($session_data, 'bestLaps', array());
            }

            // Process laps
            foreach ($laps_data as $lap_data)
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
                $lap->setTime(round($lap_data['time'] / 1000, 4));

                // Set sector times in seconds
                foreach (Helper::arrayGet($lap_data, 'sectors', array())
                             as $sector_time)
                {
                    $lap->addSectorTime(round($sector_time / 1000, 4));
                }

                // Add lap to participant
                $lap_participant->addLap($lap);
            }

            // Session has predefined race result positions
            if ($race_result = Helper::arrayGet($session_data, 'raceResult'))
            {
                // Create new participants order
                $participants_sorted = array();
                foreach ($race_result as $race_position => $race_position_driver)
                {
                    $participants_sorted[] =
                        $participants[$race_position_driver];
                }

                $participants = $participants_sorted;
            }
            // Is race result
            elseif ($session->getType() === Session::TYPE_RACE)
            {
                // Sort participants by total time
                $participants =
                    Helper::sortParticipantsByTotalTime($participants);
            }
            // Is practice or qualify
            else
            {
                // Sort by best lap
                $participants =
                    Helper::sortParticipantsByBestLap($participants);
            }

            // Fix participant positions
            foreach ($participants as $key => $part)
            {
                $part->setPosition($key+1);
            }

            // Set participants (sorted)
            $session->setParticipants($participants);


            // Fix elapsed seconds for all participant laps
            foreach ($participants as $participant)
            {
               $elapsed_time = 0;
               foreach ($participant->getLaps() as $lap)
               {
                    // Set elapsed seconds and increment it
                    $lap->setElapsedSeconds($elapsed_time);
                    $elapsed_time += $lap->getTime();
               }
            }


            // Fix driver positions for laps
            $session_lasted_laps = $session->getLastedLaps();

            // Loop each lap number, beginning from 2, because we can't
            // figure out positions for lap 1 in AC
            for($i=2; $i <= $session_lasted_laps; $i++)
            {
                // Get laps by lap number from session
                $laps_sorted = $session->getLapsByLapNumberSortedByTime($i);

                // Sort the laps by elapsed time
                $laps_sorted = Helper::sortLapsByElapsedTime($laps_sorted);

                // Loop each lap and fix position data
                foreach ($laps_sorted as $lap_key => $lap)
                {
                    // Fix lap position
                    $lap->setPosition($lap_key+1);
                }
            }

            // Add extras to session
            $session->setOtherSettings($extras);

            // Add session to collection
            $sessions[] = $session;
        }

        // Return all sessions
        return $sessions;
    }
}
