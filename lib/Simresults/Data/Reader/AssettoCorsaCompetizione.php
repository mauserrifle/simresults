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
        0 => 'Porsche 991 GT3',
        1 => 'Mercedes AMG GT3',
        2 => 'Ferrari 488 GT3',
        3 => 'Audi R8 LMS 2015',
        4 => 'Lamborghini Huracan GT3 2015',
        5 => 'Mclaren 650s GT3',
        6 => 'Nissan GT R Nismo GT3 2018',
        7 => 'BMW M6 GT3',
        8 => 'Bentley Continental GT3 2018',
        9 => 'Porsche 991 II GT3 Cup ',
        10 => 'Nissan GT-R Nismo GT3 2015',
        11 => 'Bentley Continental GT3 2016',
        12 => 'Aston Martin Vantage V12 GT3',
        13 => 'Lamborghini Gallardo R-EX',
        14 => 'Jaguar G3',
        15 => 'Lexus RC F GT3',
        16 => 'Lamborghini Huracan Evo 2019',
        17 => 'Honda NSX GT3 2016',
        18 => 'Lamborghini Huracan SuperTrofeo (Gen1)',
        19 => 'Audi R8 LMS Evo 2019',
        20 => 'AMR V8 Vantage 2019',
        21 => 'Honda NSX Evo 2019 ',
        22 => 'McLaren 720S GT3 2019',
        23 => 'Porsche 911 II GT3 R 2019',
    );

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {

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

        return false;
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {

        if ( ! $data = json_decode($this->data, TRUE)) {
            $data = iconv("UTF-16", "UTF-8", $this->data);
            $data = json_decode($data, TRUE);
        }


        // Init session
        $session = Session::createInstance();

        // Practice session by default
        $type = Session::TYPE_PRACTICE;

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

        // Check session name to get type
        // TODO: Could we prevent duplicate code for this with other readers?
        switch($session_type_value)
        {
            case 'p':
            case 'fp':
            case 'practice':
                $type = Session::TYPE_PRACTICE;
                $name = 'Practice';
                break;
            case 'q':
            case 'qualify':
                $type = Session::TYPE_QUALIFY;
                $name = 'Qualify';
                break;
            case 'r':
            case 'race':
            case 10:
                $type = Session::TYPE_RACE;
                $name = 'Race';
                break;
            case 'w':
            case 'warmup':
                $type = Session::TYPE_WARMUP;
                $name = 'Warmup';
                break;
            default:
                $type = Session::TYPE_PRACTICE;
                $name = 'Unknown';
                break;
        }


        // Set session values
        $session->setType($type)
                ->setName($name);

        if ($max_laps = (int) $this->helper->arrayGet($session_data, 'RaceLaps')) {
            $session->setMaxLaps($max_laps);
        }

        // Set game
        $game = new Game; $game->setName('Assetto Corsa Competizione');
        $session->setGame($game);

        // Set server (we do not know...)
        $server = new Server;
        $server->setName($this->helper->arrayGet($session_data, 'server', 'Unknown'));
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
         * Participants
         */

        $participants_by_car_id = array();
        foreach ($session_result['leaderBoardLines'] as $lead)
        {
            // Create drivers
            $drivers = array();

            foreach ($lead['car']['drivers'] as $driver_data)
            {
                $driver = new Driver;
                $driver->setName(trim($driver_data['firstName']. ' '.$driver_data['lastName']));
                $drivers[] = $driver;
            }

            // Create participant and add driver
            $participant = Participant::createInstance();
            $participant->setDrivers($drivers)
                        ->setFinishStatus(Participant::FINISH_NORMAL)
                        ->setTeam($lead['car']['teamName']);

            // Total time available
            if ($total_time=$lead['timing']['totalTime'])
            {
                $participant->setTotalTime(round($total_time / 1000, 4));
            }

            // Find vehicle name
            $vehicle_name = 'Car model '.$lead['car']['carModel'];
            if (isset($this->cars[(int)$lead['car']['carModel']])) {
                $vehicle_name = $this->cars[(int)$lead['car']['carModel']];
            }
            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName($vehicle_name)
                    ->setNumber((int)$lead['car']['raceNumber']);

            $participant->setVehicle($vehicle);
            $participants_by_car_id[$lead['car']['carId']] = $participant;
        }





        /**
         * Laps
         */

        // Process laps
        foreach ($data['laps'] as $lap_data)
        {
            if (!isset($participants_by_car_id[$lap_data['carId']])) {
                continue;
            }

            // Init new lap
            $lap = new Lap;

            $lap_participant = $participants_by_car_id[$lap_data['carId']];

            // Set participant
            $lap->setParticipant($lap_participant);

            $driverIndex = 0;
            if (isset($penalty_data['driverIndex'])) {
                $driverIndex = $penalty_data['driverIndex'];
            } elseif (isset($lap_data['driverId'])) {
                $driverIndex = $lap_data['driverId'];;
            }

            // Set driver based on driver index (swapping support)
            $lap->setDriver($lap_participant->getDriver($driverIndex+1));

            // Always include race laps or valid laps for other sessions
            if ($session->getType() === Session::TYPE_RACE OR
                $this->helper->arrayGet($lap_data, 'isValidForBest')) {

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
                             as $sector_time)
                {
                    $lap->addSectorTime(round($sector_time / 1000, 4));
                }
            }

            // Add lap to participant
            $lap_participant->addLap($lap);
        }



        /**
         * Penalties
         */

        // Penalties
        $penalties = array();
        $penalties_data = $this->helper->arrayGet($data, 'penalties', array());
        foreach ($penalties_data as $penalty_data) {

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
                $penalty_data['reason'].
                ' - '.
                $penalty_data['penalty'].
                ' - violation in lap '.$penalty_data['violationInLap'].
                ' - cleared in lap '.$penalty_data['violationInLap']

            );

            $penalty->setParticipant($penalty_participant)
                    ->setServed((bool)$penalty_data['clearedInLap']);

            // Add penalty to penalties
            $penalties[] = $penalty;

            // Set invalid laps on non-race sessions
            if ($session->getType() !== Session::TYPE_RACE AND
                $penalty_data['penalty'] === 'RemoveBestLaptime') {

                $penalty_lap = $penalty_participant->getLap($penalty_data['violationInLap']);
                if ($penalty_lap) {
                    $penalty_lap->setTime(null);
                    $penalty_lap->setSectorTimes(array());
                }
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
