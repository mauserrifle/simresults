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
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
    {
        // Get data
        $data = json_decode($this->data, TRUE);

        // Init session
        $session = new Session;

        // Practice session by default
        $type = Session::TYPE_PRACTICE;

        // Check session name to get type
        // TODO: Could we prevent duplicate code for this with other readers?
        switch(strtolower($name = Helper::arrayGet($data, 'Type')))
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
                    (int) Helper::arrayGet($data, 'RaceLaps'));

        // Has Duration
        if ($seconds = (int) Helper::arrayGet($data, 'DurationSecs'))
        {
            $session->setMaxMinutes(round($seconds / 60));
        }
        // No duration
        else { $session->setMaxMinutes(0); }




        // Set game
        $game = new Game; $game->setName('Assetto Corsa');
        $session->setGame($game);

        // Set server (we do not know...)
        $server = new Server; $server->setName('Unknown');
        $session->setServer($server);

        // Set track
        $track = new Track;
        $track->setVenue(Helper::arrayGet($data, 'TrackName'));
        $session->setTrack($track);




        // Get participants from Cars data
        $participants_by_name = array();
        $players_data = Helper::arrayGet($data, 'Cars', array());
        foreach ($players_data as $player_index => $player_data)
        {
            // Build participant
            $participant = $this->getParticipant(
                $name=$player_data['Driver']['Name'],
                $player_data['Driver']['Guid'],
                $player_data['Model'],
                $player_data['Driver']['Team']
            );

            // Add participant to collection
            $participants_by_name[$name] = $participant;
        }



        // Get participants from result data.
        // WARNING: This should be orded by position but these logs are BUGGED.
        //          DO NOT TRUST!
        $players_data = Helper::arrayGet($data, 'Result', array());
        foreach ($players_data as $player_index => $player_data)
        {
            // No participant found
            if ( ! isset($participants_by_name[$player_data['DriverName']]))
            {
                // Build participant
                $participant = $this->getParticipant(
                    $name=$player_data['DriverName'],
                    $player_data['DriverGuid'],
                    $player_data['CarModel']
                );

                // Add participant to collection
                $participants_by_name[$name] = $participant;
            }

            $participant = $participants_by_name[$player_data['DriverName']];

            // Total time available
            if ($total_time=$player_data['TotalTime'])
            {
                $participant->setTotalTime(round($total_time / 1000, 4));
            }
            // No total time
            else
            {
                // DNF
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
                    $lap_data['CarModel']
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
            $lap->setTime(round($lap_data['LapTime'] / 1000, 4));

            // Set sector times in seconds
            foreach (Helper::arrayGet($lap_data, 'Sectors', array())
                         as $sector_time)
            {
                $lap->addSectorTime(round($sector_time / 1000, 4));
            }

            // Add lap to participant
            $lap_participant->addLap($lap);
        }



        // Get car incidents from events
        foreach ($data['Events'] as $event)
        {
            // Not car collision. continue to next
            if ($event['Type'] !== 'COLLISION_WITH_CAR') continue;

            $incident = new Incident;
            $incident->setMessage(sprintf(
               '%s reported contact with another vehicle '.
                '%s. Impact speed: %s' ,
                $event['Driver']['Name'],
                $event['OtherDriver']['Name'],
                $event['ImpactSpeed']
            ));
            $session->addIncident($incident);
        }





        /**
         * Data fixing
         *
         * TODO: Should not be duplicate code (other readers have this code
         *       as well)
         */

        // Get participant with normal array keys
        $participants = array_values($participants_by_name);


        // Is race result
        if ($session->getType() === Session::TYPE_RACE)
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



        // Is race result
        if ($session->getType() === Session::TYPE_RACE)
        {
            // Mark no finish status when participant has not completed atleast
            // 50% of total laps
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

                // Set lap number
                $lap->setNumber($lap_key+1);
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
     * @return Participant
     */
    protected function getParticipant($name, $guid, $car, $team=null)
    {
        // Create driver
        $driver = new Driver;
        $driver->setName($name)
               ->setDriverId($guid);

        // Create participant and add driver
        $participant = new Participant;
        $participant->setDrivers(array($driver))
                    // No grid position yet. Can't figure out in AC log
                    // files
                    // ->setGridPosition($player_index+1)
                    ->setFinishStatus(Participant::FINISH_NORMAL)
                    ->setTeam($team);

        // Create vehicle and add to participant
        $vehicle = new Vehicle;
        $vehicle->setName($car);
        $participant->setVehicle($vehicle);

        return $participant;
    }
}
