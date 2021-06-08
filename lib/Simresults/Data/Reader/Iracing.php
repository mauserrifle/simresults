<?php
namespace Simresults;

/**
 * The reader for iRacing
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_Iracing extends Data_Reader {

    /**
     * @var boolean  Whether we will set finish status none if 50% of laps is
     *               not completed
     */
    protected $finish_status_none_50percent_rule = false;


    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {
        if ($data = json_decode($data, TRUE)) {
            return isset($data['subsession_id']);
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

        // Init sessions array
        $sessions = array();

        // Initial object inits
        $track = new Track;
        $track->setVenue($this->helper->arrayGet($data['track'], 'track_name', 'Unknown'));

        $server = new Server;
        $server->setName($this->helper->arrayGet($data, 'session_name', 'Unknown'));

        $game = new Game;
        $game->setName('iRacing');

        $date_string = $data['start_time'];
        $date = new \DateTime($data['start_time']);


        // Car names
        $car_names = array();
        $class_names = array();

        if (isset($data['car_classes']))
        foreach ($data['car_classes'] as $class)
        {
            $class_names[$class['car_class_id']] = $class['name'];

            if (isset($class['cars_in_class']))
            foreach ($class['cars_in_class'] as $car) {
                $car_names[$car['car_id']] = $class['short_name'];
            }
        }


        // Reverse session order
        $session_results = array_reverse($data['session_results']);

        foreach ($session_results as $session_data)
        {
            // Init session
            $session = $this->helper->detectSession($session_data['simsession_name']);
            $session->setName($this->helper->arrayGet($session_data, 'simsession_type_name'));
            $session->setGame($game);
            $session->setServer($server);
            $session->setTrack($track);
            $session->setDateString($date_string);
            $session->setDate($date);

            if (isset($session_data['date'])) {
                $session->setDateString($session_data['date']);
            }

            $participants = array();
            if (isset($session_data['results']))
            foreach ($session_data['results'] as $part_data)
            {
                $driver = new Driver;
                $driver->setName(trim($part_data['display_name']));

                if (isset($part_data['cust_id'])) {
                    $driver->setDriverId((string)$part_data['cust_id']);
                }

                // Create participant and add driver
                $participant = Participant::createInstance();
                $participant->setDrivers(array($driver));

                $vehicle = new Vehicle;

                if (isset($car_names[$part_data['car_id']])) {
                    $vehicle->setName($car_names[$part_data['car_id']]);
                } else {
                    $vehicle->setName('Car '.$part_data['car_id']);
                }

                if (isset($part_data['car_class_id'])) {
                    if (isset($class_names[$part_data['car_class_id']])) {
                        $vehicle->setClass($class_names[$part_data['car_class_id']]);
                    } else {
                        $vehicle->setClass('Class '.$part_data['car_class_id']);
                    }
                }

                if (isset($part_data['livery']['car_number'])) {
                    $vehicle->setNumber((int)$part_data['livery']['car_number']);
                }

                $participant->setVehicle($vehicle);

                if (isset($part_data['club_name'])) {
                    $participant->setTeam($part_data['club_name']);
                }

                if (isset($part_data['starting_position'])) {
                    $participant->setGridPosition($part_data['starting_position']+1);
                }

                if (isset($part_data['finish_position'])) {
                    $participant->setPosition($part_data['finish_position']+1);
                    $participant->setFinishStatus(Participant::FINISH_NORMAL);
                }

                if (isset($part_data['finish_position_in_class'])) {
                    $participant->setClassPosition($part_data['finish_position_in_class']+1);
                }

                // iRacing log doesn not contain real laps, but does contain best lap
                // so we fake laps data based on best lap data
                for ($lap_num = 1; $lap_num <= $part_data['laps_complete']; $lap_num++)
                {
                    $lap = new Lap;
                    $lap->setParticipant($participant)
                        ->setVehicle($vehicle)
                        ->setDriver($driver)
                        ->setNumber($lap_num)
                        ->setPosition($participant->getGridPosition());

                    if ($lap_num == $part_data['best_lap_num']) {
                        $lap->setTime(round($part_data['best_lap_time'] / 10000, 4));
                    }

                    $participant->addLap($lap);
                }


                $participants[] = $participant;
            }

            // Set participants to session
            // Not sorting because log contains proper position data and sorting
            // wouldn't work anyway due missing laps data
            $session->setParticipants($participants);

            $sessions[] = $session;
        }

        return $sessions;
    }

}
