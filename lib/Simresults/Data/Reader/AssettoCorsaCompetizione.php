<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Competizione client & server JSON files
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaCompetizione extends Data_Reader {

    protected $cars = array(
        0 => array('name' => 'Porsche 991 GT3', 'class' => 'GT3'),
        1 => array('name' => 'Mercedes AMG GT3', 'class' => 'GT3'),
        2 => array('name' => 'Ferrari 488 GT3', 'class' => 'GT3'),
        3 => array('name' => 'Audi R8 GT3 2015', 'class' => 'GT3'),
        4 => array('name' => 'Lamborghini Huracan GT3', 'class' => 'GT3'),
        5 => array('name' => 'McLaren 650s GT3', 'class' => 'GT3'),
        6 => array('name' => 'Nissan GT-R Nismo GT3 2018', 'class' => 'GT3'),
        7 => array('name' => 'BMW M6 GT3', 'class' => 'GT3'),
        8 => array('name' => 'Bentley Continental GT3 2018', 'class' => 'GT3'),
        9 => array('name' => 'Porsche 991 II GT3 Cup', 'class' => 'Cup'),
        10 => array('name' => 'Nissan GT-R Nismo GT3 2015', 'class' => 'GT3'),
        11 => array('name' => 'Bentley Continental GT3 2015', 'class' => 'GT3'),
        12 => array('name' => 'Aston Martin Vantage V12 GT3', 'class' => 'GT3'),
        13 => array('name' => 'Lamborghini Gallardo R-EX', 'class' => 'GT3'),
        14 => array('name' => 'Emil Frey Jaguar G3', 'class' => 'GT3'),
        15 => array('name' => 'Lexus RC F GT3', 'class' => 'GT3'),
        16 => array('name' => 'Lamborghini Huracan Evo 2019', 'class' => 'GT3'),
        17 => array('name' => 'Honda NSX GT3', 'class' => 'GT3'),
        18 => array('name' => 'Lamborghini Huracan SuperTrofeo', 'class' => 'ST'),
        19 => array('name' => 'Audi R8 LMS Evo 2019', 'class' => 'GT3'),
        20 => array('name' => 'Aston Martin Vantage V8 2019', 'class' => 'GT3'),
        21 => array('name' => 'Honda NSX Evo 2019', 'class' => 'GT3'),
        22 => array('name' => 'McLaren 720S GT3 Special', 'class' => 'GT3'),
        23 => array('name' => 'Porsche 991 II GT3 R 2019', 'class' => 'GT3'),
        24 => array('name' => 'Ferrari 488 GT3 Evo', 'class' => 'GT3'),
        25 => array('name' => 'Mercedes AMG GT3 2020', 'class' => 'GT3'),
        26 => array('name' => 'Ferrari 488 Challenger Evo', 'class' => 'GTC'),

        27 => array('name' => 'BMW M2 Club Sport Racing', 'class' => 'TCX'),

        28 => array('name' => 'Porsche 992 GT3 Cup', 'class' => 'Cup'),
        29 => array('name' => 'Lamborghini Huracan SuperTrofeo Evo2', 'class' => 'ST'),
        30 => array('name' => 'BMW M4 GT3', 'class' => 'GT3'),
        31 => array('name' => 'Audi R8 LMS Evo2 2022', 'class' => 'GT3'),

        32 => array('name' => 'Ferrari 296 GT3', 'class' => 'GT3'),
        33 => array('name' => 'Lamborghini Huracan Evo2', 'class' => 'GT3'),
        34 => array('name' => 'Porsche 992 GT3 R', 'class' => 'GT3'),
        35 => array('name' => 'McLaren 720S GT3 Evo 2023', 'class' => 'GT3'),

        // GT4 DLC
        50 => array('name' => 'Alpine A110 GT4', 'class' => 'GT4'),
        51 => array('name' => 'Aston Martin Vantage GT4', 'class' => 'GT4'),
        52 => array('name' => 'Audi R8 LMS GT4', 'class' => 'GT4'),
        53 => array('name' => 'BMW M4 GT4', 'class' => 'GT4'),
        55 => array('name' => 'Chevrolet Camaro GT4', 'class' => 'GT4'),
        56 => array('name' => 'Ginetta G55 GT4', 'class' => 'GT4'),
        57 => array('name' => 'KTM X-Bow GT4', 'class' => 'GT4'),
        58 => array('name' => 'Maserati MC GT4', 'class' => 'GT4'),
        59 => array('name' => 'McLaren 570S GT4', 'class' => 'GT4'),
        60 => array('name' => 'Mercedes AMG GT4', 'class' => 'GT4'),
        61 => array('name' => 'Porsche 718 Cayman GT4', 'class' => 'GT4'),

        80 => array('name' => 'Audi R8 LMS GT2', 'class' => 'GT2'),
        82 => array('name' => 'KTM XBOW GT2', 'class' => 'GT2'),
        83 => array('name' => 'Maserati MC20 GT2', 'class' => 'GT2'),
        84 => array('name' => 'Mercedes AMG GT2', 'class' => 'GT2'),
        85 => array('name' => 'Porsche 911 GT2 RS CS Evo', 'class' => 'GT2'),
        86 => array('name' => 'Porsche 935', 'class' => 'GT2'),
    );

    protected $carsPs5 = array(
        0 => array('name' => 'Porsche 991 GT3 R', 'class' => 'GT3'),
        1 => array('name' => 'Mercedes AMG GT3', 'class' => 'GT3'),
        2 => array('name' => 'Ferrari 488 GT3', 'class' => 'GT3'),
        3 => array('name' => 'Audi R8 LMS', 'class' => 'GT3'),
        4 => array('name' => 'Lamborghini Huracan GT3', 'class' => 'GT3'),
        5 => array('name' => 'McLaren 650s GT3', 'class' => 'GT3'),
        6 => array('name' => 'Nissan GT-R Nismo GT3 Evo', 'class' => 'GT3'),
        7 => array('name' => 'BMW M6 GT3', 'class' => 'GT3'),
        8 => array('name' => 'Bentley Continental GT3 Evo', 'class' => 'GT3'),
        9 => array('name' => 'Porsche 991 II GT3 Cup', 'class' => 'GT3'),
        10 => array('name' => 'Nissan GT-R Nismo GT3', 'class' => 'GT3'),
        11 => array('name' => 'Bentley Continental GT3', 'class' => 'GT3'),
        12 => array('name' => 'Aston Martin V12 Vantage GT3', 'class' => 'GT3'),
        13 => array('name' => 'Reiter Engineering R-EX GT3', 'class' => 'GT3'),
        14 => array('name' => 'Emil Frey Jaguar G3', 'class' => 'GT3'),
        15 => array('name' => 'Lexus RC F GT3', 'class' => 'GT3'),
        16 => array('name' => 'Lamborghini Huracan GT3 Evo', 'class' => 'GT3'),
        17 => array('name' => 'Honda NSX GT3', 'class' => 'GT3'),
        18 => array('name' => 'Lamborghini Huracan ST', 'class' => 'GT3'),
        19 => array('name' => 'Audi R8 LMS Evo', 'class' => 'GT3'),
        20 => array('name' => 'Aston Martin V8 Vantage GT3', 'class' => 'GT3'),
        21 => array('name' => 'Honda NSX GT3 Evo', 'class' => 'GT3'),
        22 => array('name' => 'McLaren 720s GT3', 'class' => 'GT3'),
        23 => array('name' => 'Porsche 991 II GT3 R Evo', 'class' => 'GT3'),
        24 => array('name' => 'Ferrari 488 GT3 Evo', 'class' => 'GT3'),
        25 => array('name' => 'Mercedes AMG GT3 Evo', 'class' => 'GT3'),
        26 => array('name' => 'BMW M4 GT3', 'class' => 'GT3'),
        27 => array('name' => 'Ferrari 488 Challenge Evo', 'class' => 'GTC'),
        28 => array('name' => 'BMW M2 CS Racing', 'class' => 'TCX'),
        29 => array('name' => 'Porsche 992 GT3 Cup', 'class' => 'GT3'),
        30 => array('name' => 'Lamborghini Huracan ST Evo 2', 'class' => 'GT3'),
        31 => array('name' => 'Audi R8 LMS Evo II', 'class' => 'GT3'),
        32 => array('name' => 'Ferrari 296 GT3', 'class' => 'GT3'),
        33 => array('name' => 'Lamborghini Huracan GT3 Evo 2', 'class' => 'GT3'),
        34 => array('name' => 'Porsche 992 GT3 R', 'class' => 'GT3'),
        35 => array('name' => 'McLaren 720s GT3 Evo', 'class' => 'GT3'),
        50 => array('name' => 'Alpine A110 GT4', 'class' => 'GT4'),
        51 => array('name' => 'Aston Martin V8 Vantage GT4', 'class' => 'GT4'),
        52 => array('name' => 'Audi R8 LMS GT4', 'class' => 'GT4'),
        53 => array('name' => 'BMW M4 GT4', 'class' => 'GT4'),
        55 => array('name' => 'Chevrolet Camaro GT4.R', 'class' => 'GT4'),
        56 => array('name' => 'Ginetta G55 GT4', 'class' => 'GT4'),
        57 => array('name' => 'KTM X-Bow GT4', 'class' => 'GT4'),
        58 => array('name' => 'Maserati GranTurismo MC GT4', 'class' => 'GT4'),
        59 => array('name' => 'McLaren 570s GT4', 'class' => 'GT4'),
        60 => array('name' => 'Mercedes AMG GT4', 'class' => 'GT4'),
        61 => array('name' => 'Porsche 718 Cayman GT4 Clubsport', 'class' => 'GT4'),

        80 => array('name' => 'Audi R8 LMS GT2', 'class' => 'GT2'),
        82 => array('name' => 'KTM XBOW GT2', 'class' => 'GT2'),
        83 => array('name' => 'Maserati MC20 GT2', 'class' => 'GT2'),
        84 => array('name' => 'Mercedes AMG GT2', 'class' => 'GT2'),
        85 => array('name' => 'Porsche 911 GT2 RS CS Evo', 'class' => 'GT2'),
        86 => array('name' => 'Porsche 935', 'class' => 'GT2'),
    );

    protected $cup_categories = array(
        0 => 'Overall',
        1 => 'Pro-Am',
        2 => 'Am',
        3 => 'Silver',
        4 => 'National',
    );

    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {

        // TODO: Fix duplicate code with readSessions
        if ($dataParsed = json_decode($data, TRUE)) {
            return (isset($dataParsed['sessionType']) OR
                    isset($dataParsed['sessionDef']));
        }

        // Try UTF-16 encoding
        try {
            $dataParsed = iconv("UTF-16", "UTF-8", $data);
            if ($dataParsed = json_decode($dataParsed, TRUE)) {
                return (isset($dataParsed['sessionType']) OR
                        isset($dataParsed['sessionDef']));
            }
        } catch(\Exception $ex) {}

        // Try windows fallback (untested and provided by community user)
        try {
           if ($dataParsed = json_decode(
                preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), TRUE)) {

                return (isset($dataParsed['sessionType']) OR
                        isset($dataParsed['sessionDef']));
            }
        } catch(\Exception $ex) {}

        // Try windows fallback 2 (untested and provided by community user)
        try {
            $dataParsed = mb_convert_encoding($data, 'UTF-16', 'UTF-16LE');

            if ($dataParsed = json_decode($dataParsed, TRUE)) {
                return (isset($dataParsed['sessionType']) OR
                        isset($dataParsed['sessionDef']));
            }
        } catch(\Exception $ex) {}

        return false;
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        // TODO: Fix duplicate code with canRead
        if ( ! $data = json_decode($this->data, TRUE)) {

            try {
                $data = iconv("UTF-16", "UTF-8", $this->data);
                $data = json_decode($data, TRUE);
            } catch(\Exception $ex) {}

            if ( ! $data) {
                $data = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->data), TRUE);

                if ( ! $data) {
                    $data = mb_convert_encoding($this->data, 'UTF-16', 'UTF-16LE');
                    $data = json_decode($data, TRUE);
                }
            }
        }


        $session_data = $data;
        $parse_settings = false;

        // Client has different array
        if (isset($data['sessionDef'])) {
            $session_data = $data['sessionDef'];
            $parse_settings = true;
        }


        $session_type_value = (string)$this->helper->arrayGet(
            $session_data, 'sessionType');

        if (is_numeric($session_type_value)) {
            // Keep numeric
        } else {
            // Clean session type numbering
            $session_type_value = strtolower(preg_replace(
                '/\d/', '' ,$session_type_value));
        }


        // Init session
        $session = $this->helper->detectSession($session_type_value, array(
            '0' => Session::TYPE_PRACTICE,
            '4' => Session::TYPE_QUALIFY,
            '10' => Session::TYPE_RACE,
        ));


        if ($max_laps = (int) $this->helper->arrayGet($session_data, 'RaceLaps')) {
            $session->setMaxLaps($max_laps);
        }

        // Set game
        $game = new Game; $game->setName('Assetto Corsa Competizione');
        $session->setGame($game);

        // Set server (we do not know...)
        $server = new Server;
        $server->setName($serverName=$this->helper->arrayGet($session_data, 'serverName', 'Unknown'));
        $session->setServer($server);

        // Set track
        $track = new Track;
        $track->setVenue($this->helper->arrayGet($session_data, 'trackName', 'Unknown'));
        $session->setTrack($track);

        $session_result = array();
        if (isset($data['sessionResult'])) {
            $session_result = $data['sessionResult'];
        } elseif(isset($data['snapShot'])) {
            $session_result = $data['snapShot'];
        }

        // Other settings
        if (NULL !== $is_wet=$this->helper->arrayGet($session_result, 'isWetSession')) {
            $session->addOtherSetting('isWetSession', $is_wet);
        }

        if ($parse_settings) {
            foreach ($session_data as $session_key => $session_value) {
                if (!is_array($session_value)) {
                    $session->addOtherSetting($session_key,
                        (string)$session_value);
                } else {
                    foreach ($session_value as $session_subkey => $session_subvalue) {
                        if (!is_array($session_subvalue)) {
                            $session->addOtherSetting($session_subkey,
                                (string)$session_subvalue);
                        }
                    }
                }
            }
        }


        /**
         * Console fixes
         */

        $carsWithConsoleFixes = $this->cars;
        if (preg_match('/xbox/i', $serverName)) {
            // Swap car ids
            // TODO: Create tests

            // Ferrari 488 Challenger Evo (26) becomes BMW M4 GT3 (30)
            $carsWithConsoleFixes[26] = $this->cars[30];

            // BMW M2 Club Sport Racing (27) becomes Ferrari 488 Challenger Evo (26)
            $carsWithConsoleFixes[27] = $this->cars[26];

            // Porsche 992 GT3 Cup (28) becomes BMW M2 Club Sport Racing (27)
            $carsWithConsoleFixes[28] = $this->cars[27];

            // Lamborghini Huracan Supertrofeo Evo2 (29) becomes Porsche 992 GT3 Cup (28)
            $carsWithConsoleFixes[29] = $this->cars[28];

            // BMW M4 GT3 (30) becomes Lamborghini Huracan Supertrofeo Evo2 (29)
            $carsWithConsoleFixes[30] = $this->cars[29];
        }
        if (preg_match('/(ps5|crossplay)/i', $serverName)) {
            $carsWithConsoleFixes = $this->carsPs5;
        }




        /**
         * Participants
         */

        $participants_by_car_id = array();

        $position_per_class = array(
            'GT3' => 0,
            'GT4' => 0,
        );

        if (isset($session_result['leaderBoardLines']))
        foreach ($session_result['leaderBoardLines'] as $lead_key => $lead)
        {
            if (!isset($lead['car']['carId'])) {
                continue;
            }

            $team_name_driver = null;

            // Create drivers
            $drivers = array();

            if (isset($lead['car']['drivers']))
            foreach ($lead['car']['drivers'] as $driver_data)
            {
                $driver = new Driver;

                $name = null;
                if ($first_name = $this->helper->arrayGet($driver_data, 'firstName')) {
                    $name .= $first_name;
                }
                if ($last_name = $this->helper->arrayGet($driver_data, 'lastName')) {

                    // Team name parsing where some leagues add it to the last name after newline
                    if (preg_match("/^(.*)\n(.*)$/i", $last_name, $last_name_matches)) {
                        $last_name = $last_name_matches[1];

                        if (!$team_name_driver) { // Only first do occurence
                            $team_name_driver = $last_name_matches[2];
                        }
                    }

                    $name .= ' '.$last_name;
                }

                $driver->setName(trim($name));

                $driver->setDriverId($this->helper->arrayGet(
                            $driver_data, 'playerId'));

                $drivers[] = $driver;
            }

            // Create participant and add driver
            $participant = Participant::createInstance();
            $participant->setDrivers($drivers)
                        ->setFinishStatus(Participant::FINISH_NORMAL)
                        ->setTeam($this->helper->arrayGet(
                            $lead['car'], 'teamName')?:$team_name_driver);

            // Doesn't seem to be correct. Order seems in finish order
            // if ($session->getType() === Session::TYPE_RACE) {
            //     $participant->setGridPosition($lead_key+1);
            // }

            // Total time available
            if (is_numeric($lead['timing']['totalTime']) AND
                $total_time=$lead['timing']['totalTime'])
            {
                $participant->setTotalTime(round($total_time / 1000, 4));
            }


            // Find vehicle name
            $vehicle_name = 'Unknown';
            $vehicle_class = null;
            $car_model = $this->helper->arrayGet($lead['car'], 'carModel');
            if (is_numeric($car_model))
            {
                $vehicle_name = 'Car model '.$car_model;
                if (isset($carsWithConsoleFixes[(int)$car_model])) {
                    $model = $carsWithConsoleFixes[(int)$car_model];
                    if (isset($model['name'])) {
                        $vehicle_name = $model['name'];
                    }
                    if (isset($model['class'])) {
                        $vehicle_class = $model['class'];
                    }
                }
            }

            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName($vehicle_name);
            if ($vehicle_class) {
                $vehicle->setClass($vehicle_class);
            }

            if (!isset($position_per_class[$vehicle_class?:'Unknown'])) {
                $position_per_class[$vehicle_class?:'Unknown'] = 0;
            }
            $participant->setClassPosition(++$position_per_class[$vehicle_class?:'Unknown']);

            // Has vehicle number
            if (NULL !==
                $race_number = $this->helper->arrayGet($lead['car'], 'raceNumber'))
            {
                $vehicle->setNumber((int)$lead['car']['raceNumber']);
            }

            // Has cup category
            $cup_category = $this->helper->arrayGet($lead['car'], 'cupCategory');
            if (is_numeric($cup_category) AND isset($this->cup_categories[$cup_category]))
            {
                $vehicle->setCup($this->cup_categories[$cup_category]);
            }

            $participant->setVehicle($vehicle);
            $participants_by_car_id[$lead['car']['carId']] = $participant;
        }



        /**
         * Laps
         */

        // Remember lap number per participant
        $lap_number_counter = array();

        // Remember positions per lap number
        $lap_position_counter = array();

        // Remember all first sectors excluding the first lap (it is bugged)
        // We will use this later to calculate averages.
        $all_first_sectors_excl_first_lap = array();

        // Process laps
        if (isset($data['laps']) AND is_array($data['laps']))
        foreach ($data['laps'] as $lap_data)
        {
            if (!isset($lap_data['carId']) OR
                !isset($participants_by_car_id[$lap_data['carId']])) {
                continue;
            }

            // Determine lap number of this participant
            $lap_number = null;
            if (!isset($lap_number_counter[$lap_data['carId']])) {
               $lap_number = $lap_number_counter[$lap_data['carId']] = 1;
            } else {
                $lap_number = ++$lap_number_counter[$lap_data['carId']];
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

            $lap_participant = $participants_by_car_id[$lap_data['carId']];

            // Set participant
            $lap->setParticipant($lap_participant)
                 ->setPosition($lap_position);

            $driverIndex = 0;
            if (isset($lap_data['driverIndex'])) {
                $driverIndex = $lap_data['driverIndex'];
            } elseif (isset($lap_data['driverId'])) {
                $driverIndex = $lap_data['driverId'];;
            }

            // Set driver based on driver index (swapping support)
            $lap->setDriver($lap_participant->getDriver($driverIndex+1));

            // Is valid for best?
            $valid_for_best = $this->helper->arrayGet($lap_data, 'isValidForBest');
            if (is_bool($valid_for_best)) {
                $lap->setValidForBest($valid_for_best);
            }

            // Always include race laps or valid laps for other sessions
            // TODO: Should we just include them in other sessions since
            // we check for valid laps?
            if ($session->getType() === Session::TYPE_RACE OR $valid_for_best)
            {

                $lap_time = $this->helper->arrayGet($lap_data, 'laptime');
                if ( ! $lap_time) {
                    $lap_time = $this->helper->arrayGet($lap_data, 'lapTime');
                }

                // Set lap time in seconds
                if ($lap_time !== 99999) {
                    $lap->setTime(round($lap_time / 1000, 4));
                }

                // Set sector times in seconds
                foreach ($this->helper->arrayGet($lap_data, 'splits', array())
                             as $sector_key => $sector_time)
                {
                    // Collect all first sector times excluding lap 1
                    if ($lap_number > 1 AND $sector_key === 0) {
                        $all_first_sectors_excl_first_lap[] = $sector_time;
                    }

                    $lap->addSectorTime(round($sector_time / 1000, 4));
                }
            }

            // Add lap to participant
            $lap_participant->addLap($lap);
        }


        /**
         * Data fixing of laps for race sessions
         *
         * The  timer starts when the player enters the session or presses drive.
         * So we cannot trust sector 1 times or total times.
         */
        if ($session->getType() === Session::TYPE_RACE AND
            $all_first_sectors_excl_first_lap)
        {
            // Calculate sector 1 average excluding lap 1
            $all_first_sectors_excl_first_lap_average = (
                array_sum($all_first_sectors_excl_first_lap)
                /
                count($all_first_sectors_excl_first_lap)
            );

            // Base new sector 1 time on average + 5 seconds (due grid start)
            $new_sector1_time = round(
                ($all_first_sectors_excl_first_lap_average + 5000) / 1000, 4);

            // Set all lap 1 first sectors to the new sector time.
            foreach ($participants_by_car_id as $part)
            foreach ($part->getLaps() as $lap)
            {
                // Is first lap and has sectors
                if ($lap->getNumber() === 1 AND $sectors = $lap->getSectorTimes())
                {
                    // Set new average + lap position*0.001 and set sector times
                    if ($lap->getPosition()) {
                        $new_sector1_time += round(($lap->getPosition() * 0.001), 4);
                    }

                    $sectors[0] = $new_sector1_time;
                    $lap->setSectorTimes($sectors);

                    // Set new total time based on the sum
                    $lap->setTime(round(array_sum($sectors), 4));
                }
            }
        }


        /**
         * Penalties
         */

        // Penalties
        $penalties = array();
        $penalties_data = $this->helper->arrayGet($data, 'penalties', array());
        foreach ($penalties_data as $penalty_data) {

            if (!isset($penalty_data['carId']) OR
                !isset($participants_by_car_id[$penalty_data['carId']])) {
                continue;
            }

            // Create new penalty
            $penalty = new Penalty;

            $penalty_participant = $participants_by_car_id[$penalty_data['carId']];

            $driverIndex = 0;
            if (isset($penalty_data['driverIndex'])) {
                $driverIndex = $penalty_data['driverIndex'];
            } elseif (isset($penalty_data['driverId'])) {
                $driverIndex = $penalty_data['driverId'];;
            }

            // Set message
            $penalty->setMessage(
                $penalty_participant->getDriver($driverIndex+1)->getName().

                ' - '.
                $this->helper->arrayGet($penalty_data, 'reason', 'Unknown reason').

                ' - '.
                ($penalty_data['penalty'] === 'None' ? 'No penalty' : $penalty_data['penalty']).

                ' - violation in lap '.
                ($penalty_data['violationInLap']?:$penalty_data['clearedInLap']).

                ' - cleared in lap '.
                $penalty_data['clearedInLap']
            );

            $penalty->setParticipant($penalty_participant)
                    ->setServed((bool)$penalty_data['clearedInLap']);

            // Add penalty to penalties
            $penalties[] = $penalty;

            $penalty_lap = $penalty_participant->getLap($penalty_data['violationInLap']?:$penalty_data['clearedInLap']);

            // Set invalid laps on non-race sessions
            if ($session->getType() !== Session::TYPE_RACE AND
                $penalty_data['penalty'] === 'RemoveBestLaptime') {

                if ($penalty_lap) {
                    $penalty_lap->setTime(null);
                    $penalty_lap->setSectorTimes(array());
                }
            }

            // Reason cutting, add Cut to lap
            if (strtolower($this->helper->arrayGet($penalty_data, 'reason')) === 'cutting' AND
                $penalty_lap)
            {
                $cut = new Cut;
                $cut->setLap($penalty_lap);
                $penalty_lap->addCut($cut);
            }
        }

        $session->setPenalties($penalties);



        /**
         * Data fixing
         */

        // Get participant with normal array keys
        $participants = array_values($participants_by_car_id);

        // Set participants (sorted)
        $session->setParticipants($participants);

        // Return session
        return array($session);
    }

}
