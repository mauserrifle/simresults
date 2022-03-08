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
     * @inheritDoc
     */
    public static function canRead($data)
    {
        if ($data = json_decode($data, TRUE)) {
            return isset($data['players']);
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

        // No session data
        if ( ! $sessions_data = $this->helper->arrayGet($data, 'sessions'))
        {
            // Throw exception
            throw new Exception\Reader('Cannot read the session data');
        }

        // Init sessions array
        $sessions = array();


        // Get extra data for all sessions
        $extras = array();
        foreach ($this->helper->arrayGet($data, 'extras', array()) as $extras_data)
        {
            // Get name
            $name = $this->helper->arrayGet($extras_data, 'name');

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
            // Get participants (do for each session to prevent re-used objects
            // between sessions)
            $participants = array();
            $players_data = $this->helper->arrayGet($data, 'players', array());
            foreach ($players_data as $player_index => $player_data)
            {
                // Create driver
                $driver = new Driver;
                $driver->setName($this->helper->arrayGet($player_data, 'name'));

                // Create participant and add driver
                $participant = Participant::createInstance();
                $participant->setDrivers(array($driver))
                            ->setFinishStatus(Participant::FINISH_NORMAL);

                // Create vehicle and add to participant
                $vehicle = new Vehicle;
                $vehicle->setName($this->helper->arrayGet($player_data, 'car'));
                $participant->setVehicle($vehicle);

                // Add participant to collection
                $participants[] = $participant;
            }

            // Init session
            $name = $this->helper->arrayGet($session_data, 'name');
            $session = $this->helper->detectSession($name);
            $session->setMaxLaps(
                        (int) $this->helper->arrayGet($session_data, 'lapsCount'))
                    ->setMaxMinutes(
                        (int) $this->helper->arrayGet($session_data, 'duration'));

            // Set game
            $game = new Game; $game->setName('Assetto Corsa');
            $session->setGame($game);

            // Set server (we do not know...)
            $server = new Server;
            $server->setName($this->helper->arrayGet(
                $data, 'server', 'Unknown or offline'));
            $session->setServer($server);

            // Set track
            $track = new Track;
            $track->setVenue($this->helper->arrayGet($data, 'track'));
            $session->setTrack($track);


            // Get the laps
            $laps_data = $this->helper->arrayGet($session_data, 'laps', array());

            // No laps data
            if ( ! $laps_data)
            {
                // Use best laps if possible
                $laps_data = $this->helper->arrayGet($session_data, 'bestLaps', array());
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
                if ($lap_data['time'] > 0) {
                    $lap->setTime(round($lap_data['time'] / 1000, 4));
                }

                // Set sector times in seconds
                foreach ($this->helper->arrayGet($lap_data, 'sectors', array())
                             as $sector_time)
                {
                    if ($sector_time > 0) {
                        $lap->addSectorTime(round($sector_time / 1000, 4));
                    }
                }

                // Set compound info
                $lap->setFrontCompound(
                    $this->helper->arrayGet($lap_data, 'tyre'));
                $lap->setRearCompound(
                    $this->helper->arrayGet($lap_data, 'tyre'));

                // Has cuts
                if (is_numeric($cutsNum = $this->helper->arrayGet($lap_data, 'cuts')) AND
                    $cutsNum > 0)
                {
                    // Cuts with no time because we only know the number of cuts
                    for ($i=1; $i <= $cutsNum; $i++) {
                        $cut = new Cut;
                        $cut->setLap($lap);
                        $lap->addCut($cut);
                    }

                    // Invalid lap
                    if ($lap_data['time'] === -1 AND
                        $session->getType() !== Session::TYPE_RACE)
                    {
                        $lap->setTime(null);
                        $lap->setSectorTimes(array());
                    }
                }



                // Add lap to participant
                $lap_participant->addLap($lap);
            }

            // Session has predefined race result positions
            if ($race_result = $this->helper->arrayGet($session_data, 'raceResult'))
            {
                // Create new participants order
                $participants_sorted = array();
                foreach ($race_result as $race_position => $race_position_driver)
                {
                    if ( ! isset($participants[$race_position_driver])) {
                        continue;
                    }

                    $participants_sorted[] =
                        $participants[$race_position_driver];
                }

                $participants = $participants_sorted;
            }
            // No predefined result
            else
            {
                // Sort participants
                $this->sortParticipantsAndFixPositions($participants, $session);
            }

            // Add extras to session
            $session->setOtherSettings($extras);


            // Set participants (sorted)
            $session->setParticipants($participants);

            // Add session to collection
            $sessions[] = $session;
        }

        // Return all sessions
        return $sessions;
    }
}
