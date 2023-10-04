<?php
namespace Simresults;

/**
 * The reader for Second Monitor json files
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_SecondMonitor extends Data_Reader {


    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {
        if ($data = json_decode($data, TRUE)) {
            return isset($data['SessionRunTime']);
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
        $session = $this->helper->detectSession($data['SessionType']);

        // Set game
        $game = new Game; $game->setName($data['Simulator']);
        if (strtoupper($game->getName()) === 'AMS 2') {
            $game->setName('Automobilista 2');
        }
        $session->setGame($game);

        // Set track
        $track = new Track;
        $track->setVenue('Unknown');
        if ($trackInfo = $this->arrayGet($data, 'TrackInfo')) {
            $track->setVenue($this->arrayGet($trackInfo, 'TrackName', 'Unknown'));
            $track->setCourse($this->arrayGet($trackInfo, 'TrackLayoutName'));

            if ($layoutLength = $this->arrayGet($trackInfo, 'LayoutLength') and
                $inMeters = $this->arrayGet($layoutLength, 'InMeters')
            ){
                $track->setLength($inMeters);
            }
        }
        $session->setTrack($track);


        if (($totalLaps=$this->arrayGet($data, 'TotalNumberOfLaps')) > 0) {
            $session->setMaxLaps($totalLaps);
        }

        $participants_by_pos = array();
        foreach ($data['Drivers'] as $driver_data) {

            // Find participant
            $driver = new Driver;

            $name = $driver_data['DriverId'];
            if ($driverLongName = $this->arrayGet($driver_data, 'DriverLongName')) {
                $name = $driverLongName;
            }

            $driver->setName(trim($name))
                   ->setHuman($this->arrayGet($driver_data, 'IsPlayer', true));

            $driver->setDriverId($driver_data['DriverId']);

            $participant = Participant::createInstance();
            $participant->setDrivers(array($driver))
                        ->setPosition($driver_data['FinishingPosition'])
                        ->setGridPosition($driver_data['InitialPosition']);


            if ($posClass = $this->arrayGet($driver_data, 'FinishingPositionInClass')) {
                $participant->setClassPosition($posClass);
            }
            if ($initialPosClass = $this->arrayGet($driver_data, 'InitialPositionInClass')) {
                $participant->setClassGridPosition($initialPosClass);
            }

            $participant->setFinishStatus(Participant::FINISH_NORMAL);
            // Finish statusses seem bugged?
            // $finishStatusLower = $this->arrayGet($driver_data, 'FinishStatus');
            // if ($finishStatusLower === 'finished') {
            //     $participant->setFinishStatus(Participant::FINISH_NORMAL);
            // } else {
            //     $participant->setFinishStatus(Participant::FINISH_NONE);
            // }

            // Find vehicle
            $vehicle_name = $this->arrayGet($driver_data, 'CarName', 'Unknown');
            $vehicle = new Vehicle;
            $vehicle->setName($vehicle_name);
            if ($class = $this->arrayGet($driver_data, 'ClassName')) {
                $vehicle->setClass($class);
            }

            $participant->setVehicle($vehicle);



            // Find laps
            $laps_data = $this->arrayGet($driver_data, 'Laps', array());
            foreach ($laps_data as $lap_data) {
                $lap = new Lap;
                $lap->setParticipant($participant);
                $lap->setDriver($participant->getDriver());
                $lap->setTime($this->helper->secondsFromFormattedTime($lap_data['LapTime']));

                // Add sectors
                for ($sector_i=1; $sector_i<=3; $sector_i++) {
                    $lap->addSectorTime($this->helper->secondsFromFormattedTime(
                        $lap_data['Sector'.$sector_i]
                    ));
                }

                // Set lap position
                $lap->setPosition($lap_data['LapStartPosition']);

                // TODO: Support class position?
                // $lap->setClassPosition($lap_data['LapStartPositionClass']);

                // Set number
                $lap->setNumber($lap_data['LapNumber']);

                // Has entered pit
                $lap->setPitLap($lap_data['IsPitLap']);

                // Is valid?
                $lap->setValidForBest($lap_data['IsValid']);

                // Add lap to participant
                $participant->addLap($lap);
            }





            // Add participant to collection
            $participants_by_pos[$participant->getPosition()] = $participant;

        }

        // Sort participants
        ksort($participants_by_pos);
        $participants = array_values($participants_by_pos);
        // $this->sortParticipantsAndFixPositions($participants, $session);

        // Set participants to session
        $session->setParticipants($participants);



        return array($session);
    }


}
