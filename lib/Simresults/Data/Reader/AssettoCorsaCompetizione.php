<?php
namespace Simresults;

/**
 * The reader for AssettoCorsa Competizione JSON files
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_AssettoCorsaCompetizione extends Data_Reader {

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {

        if ($dataParsed = json_decode($data, TRUE)) {
            return isset($dataParsed['sessionType']);
        }

        // Try UTF-16 encoding
        try {
            $dataParsed = iconv("UTF-16", "UTF-8", $data);
            if ($dataParsed = json_decode($dataParsed, TRUE)) {
                return isset($dataParsed['sessionType']);
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

        // Check session name to get type
        // TODO: Could we prevent duplicate code for this with other readers?
        switch(strtolower(preg_replace(
            '/\d/', '' ,$this->helper->arrayGet($data, 'sessionType'))))
        {
            case 'p':
            case 'fp':
                $type = Session::TYPE_PRACTICE;
                $name = 'Practice';
                break;
            case 'q':
                $type = Session::TYPE_QUALIFY;
                $name = 'Qualify';
                break;
            case 'r':
                $type = Session::TYPE_RACE;
                $name = 'Race';
                break;
            case 'w':
                $type = Session::TYPE_WARMUP;
                $name = 'Warmup';
                break;
        }


        // Set session values
        $session->setType($type)
                ->setName($name)
                ->setMaxLaps(
                    (int) $this->helper->arrayGet($data, 'RaceLaps'));


        // Set game
        $game = new Game; $game->setName('Assetto Corsa Competizione');
        $session->setGame($game);

        // Set server (we do not know...)
        $server = new Server;
        $server->setName($this->helper->arrayGet($data, 'server', 'Unknown'));
        $session->setServer($server);

        // Set track
        $track = new Track;
        $track->setVenue($this->helper->arrayGet($data, 'trackName'));
        $session->setTrack($track);


        // Other settings
        if ($is_wet=$this->helper->arrayGet($data['sessionResult'], 'isWetSession')) {
            $session->addOtherSetting('isWetSession', $is_wet);
        }

        /**
         * Participants
         */

        $participants_by_car_id = array();
        foreach ($data['sessionResult']['leaderBoardLines'] as $lead)
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

            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName('Car model '.$lead['car']['carModel']);

            $participant->setVehicle($vehicle);
            $participants_by_car_id[$lead['car']['carId']] = $participant;
        }





        /**
         * Laps
         */

        // Process laps
        foreach ($data['laps'] as $lap_data)
        {
            // Init new lap
            $lap = new Lap;

            $lap_participant = $participants_by_car_id[$lap_data['carId']];

            // Set participant
            $lap->setParticipant($lap_participant);

            // Set driver based on driver index (swapping support)
            $lap->setDriver($lap_participant->getDriver($lap_data['driverIndex']+1));

            // Always include race laps or valid laps for other sessions
            if ($session->getType() === Session::TYPE_RACE OR
                $lap_data['isValidForBest']) {

                // Set lap time in seconds
                if ($lap_data['laptime'] !== 99999) {
                    $lap->setTime(round($lap_data['laptime'] / 1000, 4));
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
        foreach ($data['penalties'] as $penalty_data) {

            // Create new penalty
            $penalty = new Penalty;

            $penalty_participant = $participants_by_car_id[$penalty_data['carId']];

            // Set message
            $penalty->setMessage(
                $penalty_participant->getDriver($penalty_data['driverIndex']+1)->getName().
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
