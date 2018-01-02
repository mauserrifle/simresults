<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Server JSON files
 *
 * WARNING: These logs are bugged regarding CarIds. Same ID's, different
 * drivers. Also Result collection is not properly ordered. So we do not
 * rely on this information.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaServerJson extends Data_Reader {

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
        if ($data = json_decode($data, TRUE)) {
            return isset($data['TrackName']);
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

        // Init session
        $session = Session::createInstance();

        // Practice session by default
        $type = Session::TYPE_PRACTICE;

        // Check session name to get type
        // TODO: Could we prevent duplicate code for this with other readers?
        switch(strtolower($name = $this->helper->arrayGet($data, 'Type')))
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
                    (int) $this->helper->arrayGet($data, 'RaceLaps'));

        // Has Duration
        if ($seconds = (int) $this->helper->arrayGet($data, 'DurationSecs'))
        {
            $session->setMaxMinutes(round($seconds / 60));
        }
        // No duration
        else { $session->setMaxMinutes(0); }




        // Set game
        $game = new Game; $game->setName('Assetto Corsa');
        $session->setGame($game);

        // Set server (we do not know...)
        $server = new Server;
        $server->setName($this->helper->arrayGet($data, 'Server', 'Unknown'));
        $session->setServer($server);

        // Set track
        $track = new Track;
        $track->setVenue($this->helper->arrayGet($data, 'TrackName'));
        $track->setCourse($this->helper->arrayGet($data, 'TrackConfig'));
        $session->setTrack($track);




        // Get participants from Cars data
        $participants_by_name = array();
        $players_data = $this->helper->arrayGet($data, 'Cars', array());
        foreach ($players_data as $player_index => $player_data)
        {
            // Build participant
            $participant = $this->getParticipant(
                $name=$player_data['Driver']['Name'],
                $player_data['Driver']['Guid'],
                $player_data['Model'],
                $player_data['Driver']['Team'],
                $player_data['BallastKG'],
                $this->helper->arrayGet($player_data, 'Restrictor'),
                $player_data['Skin']
            );

            // Add participant to collection
            $participants_by_name[$name] = $participant;
        }



        // Get participants from result data.
        // WARNING: This should be orded by position but these logs are BUGGED.
        //          DO NOT TRUST!
        $players_data = $this->helper->arrayGet($data, 'Result', array());
        foreach ($players_data as $player_index => $player_data)
        {
            // No participant found
            $participant_created = FALSE;
            if ( ! isset($participants_by_name[$player_data['DriverName']]))
            {
                // Build participant
                $participant = $this->getParticipant(
                    $name=$player_data['DriverName'],
                    $player_data['DriverGuid'],
                    $player_data['CarModel'],
                    null,
                    $player_data['BallastKG'],
                    $this->helper->arrayGet($player_data, 'Restrictor')
                );

                // Add participant to collection
                $participants_by_name[$name] = $participant;
                $participant_created = TRUE;
            }

            $participant = $participants_by_name[$player_data['DriverName']];

            // Total time available
            if ($total_time=$player_data['TotalTime'])
            {
                $participant->setTotalTime(round($total_time / 1000, 4));
            }
            // No total time, only proceed if participant was newly created
            // (ignore duplicate entries)
            elseif ($participant_created)
            {
                $participant->setFinishStatus(Participant::FINISH_DNF);
            }

            // Set total time and position (but we can't trust, so we will
            // fix later again)
            $participant->setPosition($player_index+1);
        }




        // Process laps
        foreach ($data['Laps'] as $lap_data)
        {
            // Init new lap
            $lap = new Lap;

            // No participant found
            if ( ! isset($participants_by_name[$lap_data['DriverName']]))
            {
                // Build participant
                $participant = $this->getParticipant(
                    $name=$lap_data['DriverName'],
                    $lap_data['DriverGuid'],
                    $lap_data['CarModel'],
                    null,
                    $lap_data['BallastKG'],
                    $this->helper->arrayGet($lap_data, 'Restrictor')
                );

                // Add participant to collection
                $participants_by_name[$name] = $participant;
            }

            $lap_participant = $participants_by_name[$lap_data['DriverName']];

            // Set participant
            $lap->setParticipant($lap_participant);

            // Set first driver of participant as lap driver. AC does
            // not support swapping
            $lap->setDriver($lap_participant->getDriver());

            // Set lap time in seconds
            if ($lap_data['LapTime'] !== 99999) {
                $lap->setTime(round($lap_data['LapTime'] / 1000, 4));
            }

            // Set sector times in seconds
            foreach ($this->helper->arrayGet($lap_data, 'Sectors', array())
                         as $sector_time)
            {
                $lap->addSectorTime(round($sector_time / 1000, 4));
            }

                // Set compound info
                $lap->setFrontCompound(
                    $this->helper->arrayGet($lap_data, 'Tyre'));
                $lap->setRearCompound(
                    $this->helper->arrayGet($lap_data, 'Tyre'));

            // Add lap to participant
            $lap_participant->addLap($lap);
        }



        // Get car incidents from events
        if ($data['Events'])
        foreach ($data['Events'] as $event)
        {
            $type_events = array(
                'COLLISION_WITH_CAR' => Incident::TYPE_CAR,
                'COLLISION_WITH_ENV' => Incident::TYPE_ENV,
            );

            // Not collision. continue to next
            if ( ! in_array($event['Type'], array_keys($type_events))) {
                continue;
            }

            // No participant found
            if ( ! isset($participants_by_name[$event['Driver']['Name']]) OR
                 ! isset($participants_by_name[$event['OtherDriver']['Name']])) {
                continue;
            }

            $part = $participants_by_name[$event['Driver']['Name']];
            $other_part = $participants_by_name[$event['OtherDriver']['Name']];

            $incident = new Incident;

            $incident->setMessage(sprintf(
               '%s reported contact with another vehicle '.
                '%s. Impact speed: %s' ,
                $event['Driver']['Name'],
                $event['OtherDriver']['Name'],
                $event['ImpactSpeed']
            ));

            $incident->setType($type_events[$event['Type']]);
            $incident->setParticipant($part);
            $incident->setOtherParticipant($other_part);

            $session->addIncident($incident);
        }


        // Filter out participants without proper driver data
        $participants_by_name = array_values(
                array_filter($participants_by_name, function($part){

            $driver = $part->getDriver();
            return ($driver AND $driver->getName());
        }));


        /**
         * Data fixing
         */

        // Get participant with normal array keys
        $participants = array_values($participants_by_name);

        // Sort participants
        $this->sortParticipantsAndFixPositions($participants, $session);

        // Set participants (sorted)
        $session->setParticipants($participants);

        // Return session
        return array($session);
    }



    /**
     * Helper to get new participant instance
     *
     * @param  string        $name
     * @param  string        $guid
     * @param  string        $car
     * @param  string        $team
     * @param  int           $vehicle_ballast
     * @param  int           $vehicle_restrictor
     * @param  string        $vehicle_skin
     * @return Participant
     */
    protected function getParticipant($name, $guid, $car, $team=null,
                                      $vehicle_ballast=null,
                                      $vehicle_restrictor=null,
                                      $vehicle_skin=null)
    {
        // Create driver
        $driver = new Driver;
        $driver->setName($name)
               ->setDriverId($guid);

        // Create participant and add driver
        $participant = Participant::createInstance();
        $participant->setDrivers(array($driver))
                    // No grid position yet. Can't figure out in AC log
                    // files
                    // ->setGridPosition($player_index+1)
                    ->setFinishStatus(Participant::FINISH_NORMAL)
                    ->setTeam($team);

        // Create vehicle and add to participant
        $vehicle = new Vehicle;
        $vehicle->setName($car);

        // Has ballast
        if ($vehicle_ballast)
        {
            $vehicle->setBallast($vehicle_ballast);
        }

        // Has restrictor
        if ($vehicle_restrictor)
        {
            $vehicle->setRestrictor($vehicle_restrictor);
        }

        // Has skin
        if ($vehicle_skin)
        {
            $vehicle->setSkin($vehicle_skin);
        }

        $participant->setVehicle($vehicle);

        return $participant;
    }
}
