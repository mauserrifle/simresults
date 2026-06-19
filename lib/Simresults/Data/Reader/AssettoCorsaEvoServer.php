<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Evo json files
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaEvoServer extends Data_Reader {

    protected $classes = ['Cup', 'GT2', 'GT3', 'GT4', 'GTC', 'ST', 'TCX'];

    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {
        $dataParsed = self::readLog($data);
        return ($dataParsed and isset($dataParsed['season_guid']));
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        // TODO
        // Flagged laps

        // Init session
        $session_data = self::readLog($this->data);
        $session = $this->helper->detectSession($session_data['session_type']);

        if ($base = $session_data['specialization']['base']??null) {
            if ($session_laps = $base['session_laps']??null) {
                $session->setMaxLaps($session_laps);
            }
            if ($session_duration_ms = $base['session_duration_ms']??null) {
                $session->setMaxMinutes($session_duration_ms / 60000);
            }
        }

        // Set game
        $game = new Game; $game->setName('Assetto Corsa Evo');
        $session->setGame($game);

        // Set server
        $server = new Server;
        $server->setName($session_data['server_name']??null);
        $session->setServer($server);

        // Set track
        $track = new Track;
        $track->setVenue($session_data['track_name']??null);
        $track->setCourse($session_data['track_layout_name']??null);
        $session->setTrack($track);

        // Collect cars/vehicles by id
        $cars_by_id = [];
        foreach ($session_data['cars'] as $car_data) {
            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName($vehicle_name = $car_data['model_displayname']??'Unknown')
                    ->setNumber($car_data['race_number']??null);

            foreach ($this->classes as $class) {
                if (stripos(strtolower($vehicle_name), strtolower($class)) !== false) {
                    $vehicle->setClass($class);
                    break;
                }
            }

            $car_id = $car_data['car_id']['a'].'-'.$car_data['car_id']['b'];
            $cars_by_id[$car_id] = $vehicle;
        }

        // Collect drivers
        $drivers_by_id = [];
        foreach ($session_data['drivers']??[] as $driver_data) {
            $driver = new Driver;

            $name = null;
            if ($first_name = $driver_data['first_name']??null) {
                $name .= $first_name;
            }
            if ($last_name = $driver_data['last_name']??null) {
                $name .= ' '.$last_name;
            }

            $driver->setName(trim($name))
                   ->setDriverId($driver_data['player_id']??null);

            $driver_id = $driver_data['guid']['a'].'-'.$driver_data['guid']['b'];
            $drivers_by_id[$driver_id] = $driver;
        }

        $laps_data = $session_data['laps']??[];

        // Collect participants in order of standings, but only when there are laps
        // because we cannot assign a car without laps...
        $participants_by_car_id = [];
        $position_per_class = [];
        if ($laps_data)
        foreach ($session_data['car_standings']??[] as $standing_data)  {

            $car_id = $standing_data['car_id']['a'].'-'.$standing_data['car_id']['b'];
            $vehicle = $cars_by_id[$car_id]??null;

            if (!$vehicle) {
                continue;
            }

            $participant = Participant::createInstance();
            $participant->setVehicle($vehicle)
                        ->setFinishStatus(Participant::FINISH_NORMAL)
                        ->setGridPosition($standing_data['start_position']??null);


            $vehicle_class = $vehicle->getClass()?:'Unknown';
            if (!isset($position_per_class[$vehicle_class])) {
                $position_per_class[$vehicle_class] = 0;
            }
            $participant->setClassPosition(++$position_per_class[$vehicle_class]);

            $participants_by_car_id[$car_id] = $participant;
        }

        /**
         * Laps
         */

        // Remember lap number per participant
        $lap_number_counter = array();

        // Collect laps
        foreach ($laps_data as $lap_data)  {

            $car_id = $lap_data['car_key']['a'].'-'.$lap_data['car_key']['b'];
            $vehicle = $cars_by_id[$car_id]??null;

            if (!$vehicle) {
                continue;
            }

            // Determine lap number of this participant
            $lap_number = null;
            if (!isset($lap_number_counter[$car_id])) {
                $lap_number = $lap_number_counter[$car_id] = 1;
            } else {
                $lap_number = ++$lap_number_counter[$car_id];
            }

            // Init new lap
            $lap = new Lap;

            if (!$lap_participant = $participants_by_car_id[$car_id]??null) {
                continue;
            }

            // Set participant
            $lap->setParticipant($lap_participant);

            $driver_id = $lap_data['driver_key']['a'].'-'.$lap_data['driver_key']['b'];

            if (!$lap_driver = $drivers_by_id[$driver_id] and !$lap_driver = $lap_participant->getDriver()) {
                continue;
            }

            $lap->setDriver($lap_driver);

            $lap_time = $lap_data['time']??null;
            if ($lap_time) {
                $lap->setTime(round($lap_time / 1000, 4));
            }


            $splits = $lap_data['split'] ?? [];
            if (count($splits) >= 1) {
                $lap->addSectorTime(round($splits[0] / 1000, 4)); // Sector 1
            }

            if (count($splits) >= 2) {
                $lap->addSectorTime(round($splits[1] / 1000, 4)); // Sector 2

                // Third sector known
                if (count($splits) >= 3) {
                    $lap->addSectorTime(round($splits[2] / 1000, 4)); // Sector 2
                }
                // Third sector not known, calculate Sector 3 if we have total time
                elseif ($lap_time) {
                    $sector3 = round(($lap_time - $splits[1] - $splits[0]) / 1000, 4);
                    $lap->addSectorTime($sector3);
                }
            }

            // Add lap to participant
            $lap_participant->addLap($lap);

            // Add driver to participant if it's not already added
            $participant_drivers = $lap_participant->getDrivers();
            if (!in_array($lap_driver, $participant_drivers, true)) {
                $participant_drivers[] = $lap_driver;
                $lap_participant->setDrivers($participant_drivers);
            }
        }

        /**
         * Penalties
         */

        // Penalties
        $penalties = [];
        $session_penalties_data = $session_data['penalty_collection']['session_penalties']??[];
        foreach ($session_penalties_data as $session_penalty_data) {

            $penalty_car_id = $session_penalty_data['car_id']['a'].'-'.$session_penalty_data['car_id']['b'];

            if (!$penalty_participant = $participants_by_car_id[$penalty_car_id]) {
                continue;
            }

            $cleared_penalties_data = $session_penalty_data['cleared_penalties']??[] ;
            foreach ($cleared_penalties_data as &$item) {
                $item['cleared'] = true;
            }
            unset($item);
            $pending_penalties_data = $session_penalty_data['pending_penalties']??[] ;
            foreach ($pending_penalties_data as &$item) {
                $item['cleared'] = false;
            }
            unset($item);

            foreach (array_merge($pending_penalties_data, $cleared_penalties_data) as $penalty_data) {
                // Create new penalty
                $penalty = new Penalty;
                $penalty->setParticipant($penalty_participant)
                        ->setServed($penalty_data['cleared'])
                        ->setElapsedSeconds($penalty_data['given_session_time_ms'] / 1000);


                $penalty_lap = $penalty_participant->getLap($penalty_data['given_lap_count']);
                $penalty_driver = $penalty_lap->getDriver();

                $penalty_type = ($penalty_data['penalty_data']['type']??'Unknown type');
                $penalty_reason = ($penalty_data['investigation']??'Unknown reason');

                if (stripos($penalty_reason, 'cut') !== false) {
                    $cut = new Cut;
                    $cut->setLap($penalty_lap)
                        ->setElapsedSeconds($penalty->getElapsedSeconds());
                    $penalty_lap->addCut($cut);
                }

                // Set message
                $penalty->setMessage(
                    $penalty_driver->getName().

                    ' - '.
                    ($penalty_reason).

                    ' - '.
                    $penalty_type.

                    ' - violation in lap '.
                    ($penalty_data['given_lap_count']??'?').

                    ' - cleared at (minutes) '.
                    (($penalty_data['cleared_session_time_ms'] / 1000)/60)
                );

                // Add penalty to penalties
                $penalties[] = $penalty;
            }
        }

        $session->setPenalties($penalties);


        /**
         * Incidents
         */

        $incidents = [];
        foreach ($session_data['collisions'] as $collision_data) {

            $collision_car_id = $collision_data['car_id']['a'].'-'.$collision_data['car_id']['b'];

            if (!$collision_participant = $participants_by_car_id[$collision_car_id]) {
                continue;
            }

            $type_collisions = array(
                'Car' => Incident::TYPE_CAR,
                'Object' => Incident::TYPE_ENV,
                'Wall' => Incident::TYPE_ENV,
            );

             // Type not known. continue to next
            if ( ! in_array($collision_data['collider'], array_keys($type_collisions))) {
                continue;
            }
            $collision_type = $type_collisions[$collision_data['collider']];


            $incident = new Incident;
            $incident->setType($collision_type)
                     ->setParticipant($collision_participant);


            $driver_names = [];
            foreach ($collision_participant->getDrivers() as $driver) {
                $driver_names[] = $driver->getName();
            }
            $driver_names_string = implode(', ', $driver_names);


            if ($collision_type === Incident::TYPE_CAR)
            {
                $incident->setMessage(sprintf(
                    '%s reported contact with another vehicle. '.
                    'Impact speed: %s' ,
                    $driver_names_string,
                    $collision_data['relative_impact_kmh']
                ));
            }
            elseif ($collision_type === Incident::TYPE_ENV)
            {
                $incident->setMessage(sprintf(
                    '%s reported contact with environment. '.
                    'Impact speed: %s' ,
                    $driver_names_string,
                    $collision_data['relative_impact_kmh']
                ));
            }

            $incidents[] = $incident;
        }

        $session->setIncidents($incidents);



        // Set participants with normal array keys
        $session->setParticipants(array_values($participants_by_car_id));

        return [$session];
    }

    protected static function readLog($data)
    {
        $dataParsed = json_decode($data, TRUE);

        if (!$dataParsed) {
            // Try UTF-16 encoding
            try {
                $dataParsed = iconv("UTF-16", "UTF-8", $data);
                $dataParsed = json_decode($dataParsed, TRUE);
            } catch(\Exception $ex) {}
        }

        if (!$dataParsed) {
            // Try windows fallback (untested and provided by community user from acc)
            try {
                $dataParsed = json_decode(
                    preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), TRUE);
            } catch(\Exception $ex) {}
        }

        if (!$dataParsed) {
            // Try windows fallback 2 (untested and provided by community user from acc)
            try {
                $dataParsed = mb_convert_encoding($data, 'UTF-16', 'UTF-16LE');
                $dataParsed = json_decode($dataParsed, TRUE);
            } catch(\Exception $ex) {}
        }

        return $dataParsed;
    }
}
