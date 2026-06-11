<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Evo json files
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaEvo extends Data_Reader {

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
        // Penalties
        // Flagged laps
        // Collisions

        // Init session
        $session_data = self::readLog($this->data);
        $session = $this->helper->detectSession($session_data['session_type']);

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
            $vehicle->setName($car_data['model_displayname']??'Unknown')
                    ->setNumber($car_data['race_number']??null);

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

            $participants_by_car_id[$car_id] = $participant;
        }

        /**
         * Laps
         */

        // Remember lap number per participant
        $lap_number_counter = array();

        // Remember positions per lap number
        $lap_position_counter = array();

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

            // Determine lap position
            $lap_position = null;
            if (!isset($lap_position_counter[$lap_number])) {
                $lap_position = $lap_position_counter[$lap_number] = 1;
            } else {
                $lap_position = ++$lap_position_counter[$lap_number];
            }


            // Init new lap
            $lap = new Lap;

            $lap_participant = $participants_by_car_id[$car_id];

            // Set participant
            $lap->setParticipant($lap_participant)
                ->setPosition($lap_position);

            $driver_id = $lap_data['driver_key']['a'].'-'.$lap_data['driver_key']['b'];

            // Set driver based on driver index (swapping support)
            $lap_driver = $drivers_by_id[$driver_id];
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

                // Calculate Sector 3 if we have total time
                if ($lap_time) {
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
