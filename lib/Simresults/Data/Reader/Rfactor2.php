<?php
namespace Simresults;

/**
 * The reader for rfactor 2. Supports rfactor 1 too.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_Rfactor2 extends Data_Reader {

    /**
     * @var  \DOMDocument  The domdocument to read the rfactor XML results
     */
    protected $dom;

    /**
     * @var  array  Cached participants
     */
    protected $participants;

    /**
     * @var  array  Cached laps
     */
    protected $laps;

    /**
     * @inheritDoc
     */
    public static function canRead($data)
    {
        // Try to parse file with DomDocument if it begins with a XML open tag
        if (strpos($data, '<') === 0)
        try
        {
            // Create new dom document
            $dom = new \DOMDocument('1.0', 'utf-8');

            // Clean xml data
            $data = self::cleanXML($data);

            // Cannot read data into dom document
            if ( ! $dom->loadXML($data))
            {
                return false;
            }

            // Is rFactorXMLs
            if ($test = $dom->getElementsByTagName('rFactorXML'))
            {
                // We can read this
                return true;
            }
        }
        // Ignore all errors
        catch (\Exception $ex) { }

        // Cannot read by default
        return false;
    }

    /**
     * @see \Simresults\Data_Reader::readSessions()
     */
    protected function readSessions()
    {
        // Create new session instance
        $session = Session::createInstance();

        // Is race session
        if (
            $xml_session = $this->dom->getElementsByTagName('Race')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Race2')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Race3')->item(0)
        	)
        {
            // Set type to race
            $session->setType(Session::TYPE_RACE);
        }
        // Is qualify session
        elseif (
            $xml_session = $this->dom->getElementsByTagName('Qualify')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Qualify1')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Qualify2')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Qualify3')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Qualify4')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Qualify5')->item(0)
            )
        {
            // Set type to qualify
            $session->setType(Session::TYPE_QUALIFY);
        }
        // Is warmup session
        elseif ($xml_session = $this->dom->getElementsByTagName('Warmup')->item(0))
        {
            // Set type to warmup
            $session->setType(Session::TYPE_WARMUP);
        }
        // Is practice session
        elseif (
            $xml_session = $this->dom->getElementsByTagName('Practice')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Practice1')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Practice2')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('Practice3')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('TestDay')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('TestDay1')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('TestDay2')->item(0) OR
            $xml_session = $this->dom->getElementsByTagName('TestDay3')->item(0)
            )
        {
            // Set type to practice
            $session->setType(Session::TYPE_PRACTICE);
        }
        // No session data found
        else
        {
            // Throw exception
            throw new Exception\Reader('Cannot read the session data');
        }

        // Using fixed setups?
        $session->setSetupFixed( (bool) $this->tagValue(
            'FixedSetups', $xml_session));


        // Get other settings
        $session
            ->addOtherSetting('MechFailRate', (int) $this->tagValue(
                'MechFailRate'))
              ->addOtherSetting('DamageMult', (int) $this->tagValue(
                'DamageMult'))
            ->addOtherSetting('FuelMult', (int) $this->tagValue(
                'FuelMult'))
            ->addOtherSetting('TireMult', (int) $this->tagValue(
                'TireMult'));

        // Create session date
        $date = new \DateTime(
            date('c', (int) $this->tagValue('DateTime', $xml_session)));

        // Set UTC timezone by default
        $date->setTimezone(new \DateTimeZone(self::$default_timezone));

        // Set date to session
        $session->setDate($date);

        // Max laps is max value
        if (($max_laps = (int) $this->tagValue('Laps', $xml_session)) === 2147483647)
        {
            // Just set it to 0
            $max_laps = 0;
        }

        // Set max laps and minutes
        $session
            ->setMaxLaps($max_laps)
            ->setMaxMinutes( (int) $this->tagValue('Minutes', $xml_session));

        // Set which mod was used
        $session->setMod($this->tagValue('Mod'));

        // Set server
        $session->setServer($this->getServer());

        // Set game
        $session->setGame($this->getGame());

        // Set track
        $session->setTrack($this->getTrack());


        // Get participants
        $participants = $this->getParticipants();


        // Find whether we are dealing with position corruption
        $position_corruption = false;

           // DNF statusses
        $dnf_statusses = array(
            Participant::FINISH_DNF,
            Participant::FINISH_DQ,
            Participant::FINISH_NONE,
        );

        // This is a race session
        if ($session->getType() === Session::TYPE_RACE)
        {
            // Loop each participant to find position corruption
            foreach ($participants as $part_key => $part)
            {
                // There is a previous participant
                if (isset($participants[$part_key-1]) AND
                    $prev_part = $participants[$part_key-1])
                {
                    // This participant did not finish
                    if (in_array($part->getFinishStatus(), $dnf_statusses))
                    {
                        // No need to compare to previous, continue to next
                        continue;
                    }

                    // Total time is lower than previous participant and participant
                    // is not lapped, that is not right...
                    if ($part->getTotalTime() < $prev_part->getTotalTime() AND
                        $part->getNumberOfLaps() === $prev_part->getNumberOfLaps())
                    {
                        // We are dealing with corrupted positions
                        $position_corruption = true;
                    }
                }
            }

            // We have position corruption
            if ($position_corruption)
            {
                // Sort participants by total time
                $participants = $this->helper->sortParticipantsByTotalTime($participants);
            }
        }
        // Other session
        else
        {
            // Loop each participant to find position corruption
            foreach ($participants as $part_key => $part)
            {
                // There is a previous participant
                if (isset($participants[$part_key-1]) AND
                    $prev_part = $participants[$part_key-1])
                {
                    // This participant has no best lap
                    if ( ! $part->getBestLap())
                    {
                        // Just continue to next
                        continue;
                    }

                    // Previous participant has no best lap and this participant
                    // does OR this participant best lap is faster than previous
                    // best lap
                    if ( ( ! $prev_part->getBestLap() AND $part->getBestLap())
                          OR
                          $part->getBestLap()->getTime() <
                              $prev_part->getBestLap()->getTime())
                    {
                        // We are dealing with corrupted positions
                        $position_corruption = true;
                    }
                }
            }

            // We have position corruption
            if ($position_corruption)
            {
                // Sort by best lap instead of position
                $participants =
                    $this->helper->sortParticipantsByBestLap($participants);
            }
        }

        // There is position corruption
        if ($position_corruption)
        {
            // Fix the positions of the participants
            foreach ($participants as $key => $part)
            {
                // Set position
                $part->setPosition($key+1);
            }
        }

        // Set participants
        $session->setParticipants($participants);

        // Set vehicles allowed
        $session->setAllowedVehicles($this->getAllowedVehicles());

        // Set chats
        $this->setChats($session);

        // Set incidents
        $this->setIncidents($session);

        // Set penalties
        $this->setPenalties($session);

        // Return the session
        return array($session);

    }

    /**
     * Init the DOM
     *
     * @see \Simresults\Data_Reader::init()
     */
    protected function init()
    {
        // Create the new DOMDocument
        $dom = $this->dom = new \DOMDocument('1.0', 'utf-8');

        // Clean xml data
        $data = self::cleanXML($this->data);

        // Load the data as XML
        $dom->loadXML($data);
    }



    /**
     * Get the track
     *
     * @return  Track
     */
    protected function getTrack()
    {
        // Create new track
        $track = new Track;

        // Set track values
        $track
            ->setVenue($this->tagValue('TrackVenue'))
            ->setCourse($this->tagValue('TrackCourse'))
            ->setEvent($this->tagValue('TrackEvent'))
            ->setLength( (float) $this->tagValue('TrackLength'));

        // Return track
        return $track;
    }


    /**
     * Get the game
     *
     * @return  Game
     */
    protected function getGame()
    {
        // Create new game
        $game = new Game;

        // Get game version
        $game_version = $this->tagValue('GameVersion');

        // Default game name
        $game_name = 'rFactor 2';

        // Mod is from gamestockcar
        if (preg_match('/reiza[0-9]+\.rfm/i', $this->tagValue('Mod')))
        {
            $game_name = 'Game Stock Car Extreme';
        }
        // Mod is from automobilista
        elseif (preg_match('/.*?\.srs/i', $this->tagValue('Mod')))
        {
            $game_name = 'Automobilista';
        }
        // Game version matches rfactor 1 version. Let's hope rfactor 2 will
        // never use this version :)
        elseif ($game_version === '1.255')
        {
            // It's rfactor 1
            $game_name = 'rFactor';
        }

        // Set game values
        $game
            ->setName($game_name)
            ->setVersion($game_version);

        // Return game
        return $game;
    }


    /**
     * Get the server
     *
     * @return  Server|null
     */
    protected function getServer()
    {
        // Create new server
        $server = new Server;

        // Not multiplayer. No server data available
        if ($this->tagValue('Setting') !== 'Multiplayer')
        {
            return null;
        }

        // Set game values
        $server
            ->setName($this->tagValue('ServerName'))
            ->setMotd($this->tagValue('MOTD'))
            ->setDedicated( (bool) $this->tagValue('Dedicated'));

        // Return game
        return $server;
    }

    /**
     * Get participants sorted by ending position
     *
     * @return  array
     */
    protected function getParticipants()
    {
        // Drivers already read
        if ($this->participants !== null)
        {
            // Return already read participants
            return $this->participants;
        }

        // Init drivers array
        $participants = array();


        // Remember all lap positions to detect corruption later
        $lap_positions = array();

        // Remeber all lap instances per lap number so we fix position
        // corruption on them
        $all_laps_by_lap_number = array();

        // Loop each driver (if any)
        /* @var $driver_xml \DOMNode */
        foreach ($this->dom->getElementsByTagName('Driver') as $driver_xml)
        {
            // Create new driver
            $main_driver = new Driver;

            // Get position
            $position = (int) $this->tagValue('Position', $driver_xml);

            // Set driver values
            $main_driver
                ->setName($this->tagValue('Name', $driver_xml))
                ->setHuman( (bool) $this->tagValue('isPlayer', $driver_xml));

            // Create new vehicle
            $vehicle = new Vehicle;

            // Set vehicle values
            $vehicle
                ->setName($this->tagValue('VehName', $driver_xml))
                ->setType($this->tagValue('CarType', $driver_xml))
                ->setClass($this->tagValue('CarClass', $driver_xml))
                ->setNumber( (int) $this->tagValue('CarNumber', $driver_xml));

            // Create participant
            $participant = Participant::createInstance();

            // Set participant values
            $participant
                ->setTeam(
                    $this->tagValue('TeamName', $driver_xml))
                ->setPosition(
                    (int) $this->tagValue('Position', $driver_xml))
                ->setClassPosition(
                    (int) $this->tagValue('ClassPosition', $driver_xml))
                ->setGridPosition(
                    (int) $this->tagValue('GridPos', $driver_xml))
                ->setClassGridPosition(
                    (int) $this->tagValue('ClassGridPos', $driver_xml))
                ->setPitstops(
                    (int) $this->tagValue('Pitstops', $driver_xml));

            // Has finish time
            if ($finish_time = (float)
                    $this->tagValue('FinishTime', $driver_xml))
            {
                // Overwrite total time, because rfactor results tend to be
                // corrupted at times
                $participant->setTotalTime($finish_time);
            }

            // Get finish status value
            $finish_status = strtolower($this->tagValue(
                'FinishStatus', $driver_xml));

            // Has finished
            if ($finish_status === 'finished normally')
            {
                // Set finish status
                $participant->setFinishStatus(Participant::FINISH_NORMAL);
            }
            // Is disqualified
            elseif ($finish_status === 'dq')
            {
                // Set disqualified status
                $participant->setFinishStatus(Participant::FINISH_DQ);
            }
            // Not finished
            elseif ($finish_status === 'dnf')
            {
                // Set did not finish status
                $participant->setFinishStatus(Participant::FINISH_DNF);

                // Set finish comment (if any)
                if ($finish_status = $this->tagValue('DNFReason', $driver_xml))
                {
                    $participant->setFinishComment($finish_status);
                }
            }
            // No finish status
            else
            {
                // Set no finish status
                $participant->setFinishStatus(Participant::FINISH_NONE);
            }


            // Get the driver swaps
            $swaps_xml = $driver_xml->getElementsByTagName('Swap');

            // Init drivers array, a participant can have multiple
            $drivers = array();

            // Remember the drivers per laps
            $drivers_per_laps = array();

            // Remember drivers by name so we can re-use them
            $drivers_by_name = array();

            // Remember the number of swaps (always -1 of number of swap
            // elements in XML, because first driver starts on grid, which is
            // actually not really a swap)
            $number_of_swaps = 0;

            // Loop each swap
            $first_swap = true; // First swap reminder, can't use $swap_xml_key
                                // to detect because it is bugged in hhvm!
            foreach ($swaps_xml as $swap_xml_key => $swap_xml)
            {
                // Empty driver name
                if ( ! $driver_name = $this->nodeValue($swap_xml))
                {
                    // Skip this swap
                    continue;
                }

                // Driver already processed
                if (array_key_exists($driver_name, $drivers_by_name))
                {
                    // Use existing found driver instance
                    $swap_driver = $drivers_by_name[$driver_name];
                }
                // New driver
                else
                {
                    // Create new driver
                    $swap_driver = new Driver;

                    // Set name
                    $swap_driver->setName($driver_name);

                    // Use human state the same of main driver within XML
                    $swap_driver->setHuman($main_driver->isHuman());

                    // Add swap driver to drivers array
                    $drivers[] = $swap_driver;

                    // Remember swap driver by name
                    $drivers_by_name[$driver_name] = $swap_driver;
                }

                // Add swap driver to drivers per lap
                $drivers_per_laps[] = array(
                    'start_lap'  =>  (int) $swap_xml->getAttribute('startLap'),
                    'end_lap'    =>  (int) $swap_xml->getAttribute('endLap'),
                    'driver'     =>  $swap_driver,
                );

                // Not first swap element, so this is a real swap that happend
                // within pits
                if ( ! $first_swap)
                {
                    // Increment the number of swaps
                    $number_of_swaps++;
                }

                // Not first swap anymore
                $first_swap = false;
            }

            // No drivers yet, so no drivers through swap info
            if ( ! $drivers)
            {
                // Add main driver to drivers array because we could not get
                // it from the swap info
                $drivers[] = $main_driver;
            }

            // Pitcounter is lower than number of swaps
            if ($participant->getPitstops() < $number_of_swaps)
            {
                // Set pitstop counter to the number of swaps
                $participant->setPitstops($number_of_swaps);
            }

            // Add vehicle to participant
            $participant->setVehicle($vehicle);

            // Add drivers to participant
            $participant->setDrivers($drivers);

            // Remember whether the drivers are human or not from the look at
            // the aids
            $is_human_by_aids = null;

            // Get lap aids information and convert to friendly array
            $aids_xml = $driver_xml->getElementsByTagName('ControlAndAids');
            $aids = array();
            foreach ($aids_xml as $aid_xml)
            {
                // Match the aids
                $matches = array();
                preg_match_all('/([a-z]+)(=([a-z0-9]))?[,]?/i',
                    (string) $this->nodeValue($aid_xml), $matches);

                // Prepare aid items array
                $aid_items = array();

                // Loop each matched aid
                if (isset($matches[1]))
                foreach ($matches[1] as $key => $aid_name)
                {
                    // Get value
                    $aid_value = $matches[3][$key];

                    // Is float
                    if (is_float($aid_value))
                    {
                        // Cast to float
                        $aid_value = (float) $aid_value;
                    }
                    // Is numeric, probably an int
                    elseif (is_numeric($aid_value))
                    {
                        // Cast to int
                        $aid_value = (int) $aid_value;
                    }

                    // Is a human player
                    if ($aid_name === 'PlayerControl')
                    {
                        // Remember this driver is human
                        $is_human_by_aids = true;
                    }
                    // Is a non-human player and no human detection yet
                    elseif ( ($aid_name === 'UnknownControl' OR
                              $aid_name === 'AIControl')
                            AND
                            $is_human_by_aids === null)
                    {
                        // Remember this driver is not human
                        $is_human_by_aids = false;
                    }

                    // Set key => value of aid
                    $aid_items[$aid_name] = $aid_value ? $aid_value : null;
                }

                // Add aid information per lap
                $aids[] = array(
                    'start_lap'   =>  (int) $aid_xml->getAttribute('startLap'),
                    'end_lap'     =>  (int) $aid_xml->getAttribute('endLap'),
                    'aids'        =>  $aid_items,
                );
            }

            // No aids
            if ( ! $aids)
            {
                // Always human
                $is_human_by_aids = true;
            }


            //-- Set laps

            // Loop each available lap
            /* @var $lap_xml \DOMNode */
            foreach ($driver_xml->getElementsByTagName('Lap') as $lap_xml)
            {
                // Create new lap
                $lap = new Lap;

                // Lap time zero or lower
                if (($lap_time = (float) $this->nodeValue($lap_xml)) <= 0.0)
                {
                    // No lap time
                    $lap_time = null;
                }

                // Get lap position and add it to known positions
                $lap_positions[] = $lap_position =
                    (int) $lap_xml->getAttribute('p');

                // Elapsed seconds by default null
                $elapsed_seconds = null;

                // Valid value
                if ($lap_xml->getAttribute('et') !== '--.---')
                {
                    // Create float value
                    $elapsed_seconds = (float) $lap_xml->getAttribute('et');
                }

                // Default compound values
                $front_compound = null;
                $rear_compound = null;

                // Front compound isset
                if (($fcompound = $lap_xml->getAttribute('fcompound')) !== '')
                {
                    $front_compound = $fcompound;
                }
                // Rear compound isset
                if (($rcompound = $lap_xml->getAttribute('rcompound')) !== '')
                {
                    $rear_compound = $rcompound;
                }

                // Has fuel info and is positive
                $fuel = NULL;
                if (($fuel_data = (float) $lap_xml->getAttribute('fuel')) AND
                    $fuel_data > 0 )
                {
                    // Get proper percentage
                    $fuel = round($fuel_data*100, 2);
                }




                $front_compound_left_wear = NULL;
                if (($wear_data = (float) $lap_xml->getAttribute('twfl')) AND
                    $wear_data > 0 )
                {
                    // Get proper percentage
                    $front_compound_left_wear = round($wear_data*100, 2);
                }

                $front_compound_right_wear = NULL;
                if (($wear_data = (float) $lap_xml->getAttribute('twfr')) AND
                    $wear_data > 0 )
                {
                    // Get proper percentage
                    $front_compound_right_wear = round($wear_data*100, 2);
                }

                $rear_compound_left_wear = NULL;
                if (($wear_data = (float) $lap_xml->getAttribute('twrl')) AND
                    $wear_data > 0 )
                {
                    // Get proper percentage
                    $rear_compound_left_wear = round($wear_data*100, 2);
                }

                $rear_compound_right_wear = NULL;
                if (($wear_data = (float) $lap_xml->getAttribute('twrr')) AND
                    $wear_data > 0 )
                {
                    // Get proper percentage
                    $rear_compound_right_wear = round($wear_data*100, 2);
                }


                // Set lap values
                $lap
                    ->setTime($lap_time)
                    ->setPosition($lap_position)
                    ->setNumber( (int) $lap_xml->getAttribute('num'))
                    ->setParticipant($participant)
                    ->setElapsedSeconds($elapsed_seconds)
                    ->setFrontCompound($front_compound)
                    ->setRearCompound($rear_compound)
                    ->setFrontCompoundLeftWear($front_compound_left_wear)
                    ->setFrontCompoundRightWear($front_compound_right_wear)
                    ->setRearCompoundLeftWear($rear_compound_left_wear)
                    ->setRearCompoundRightWear($rear_compound_right_wear)
                    ->setFuel($fuel)
                    ->setPitLap((boolean) $lap_xml->getAttribute('pit'));

                // Find lap aids
                foreach ($aids as $aid)
                {
                    // Lap match
                    if ($aid['start_lap'] <= $lap->getNumber() AND
                        $aid['end_lap'] >= $lap->getNumber())
                    {
                        // Set aids
                        $lap->setAids($aid['aids']);

                        // Stop searching
                        break;
                    }
                }

                // Find lap driver
                foreach ($drivers_per_laps as $driver_lap)
                {
                    // Lap match
                    if ($driver_lap['start_lap'] <= $lap->getNumber() AND
                        $driver_lap['end_lap'] >= $lap->getNumber())
                    {
                        // Set driver
                        $lap->setDriver($driver_lap['driver']);

                        // Stop searching
                        break;
                    }
                }

                // No driver yet
                if ( ! $lap->getDriver())
                {
                    // Just put first driver on lap
                    $lap->setDriver($drivers[0]);
                }

                // Add each sector available
                $sector = 1;
                while($lap_xml->hasAttribute($sector_attribute = 's'.$sector))
                {
                    // Add sector
                    $lap->addSectorTime(
                        (float) $lap_xml->getAttribute($sector_attribute));

                    // Increment number of sector
                    $sector++;
                }

                // Add lap to participant
                $participant->addLap($lap);

                // Remember lap
                $all_laps_by_lap_number[$lap->getNumber()][] = $lap;
            }

            // Detected human state by aids
            if ($is_human_by_aids !== null)
            {
                // Force human mark on all drivers
                foreach ($drivers as $driver)
                {
                    $driver->setHuman($is_human_by_aids);
                }
            }


            // Set driver to drivers array based on his position
            $participants[$position-1] = $participant;
        }


        // Make positions array unique and sort it
        $lap_positions = array_unique($lap_positions);
        sort($lap_positions);

        // Loop each position and detect corrupted and wrong position laps
        $corrupted_lap_positions = array();
        $wrong_lap_positions = array();
        foreach ($lap_positions as $key => $lap_position)
        {
            // Lap is 10 positions higher than previous, it's a too big gap,
            // we consider this full corruption
            if ($key > 0 AND ($lap_position - $lap_positions[$key-1]) > 9)
            {
                // Add lap position to corrupted list
                $corrupted_lap_positions[] = $lap_position;
            }

            // First position aint 1 OR lap is 2 positions higher than previous,
            // we have some wrong lap positions
            if ( ($key === 0 AND $lap_position > 1) OR
                 ($key > 0 AND ($lap_position - $lap_positions[$key-1]) > 1))
            {
                // Add lap position to wrong list
                $wrong_lap_positions[] = $lap_position;
            }
        }



        // We have corrupted lap positions
        if ($corrupted_lap_positions)
        {
            // Whether we need to refill $all_laps_by_lap_number
            $refill_all_laps_by_lap_number = false;

            // Loop each participant to find out if they are really all
            // corrupted
            foreach ($participants as $participant)
            {
                // By default all laps of participant are corrupted
                $all_corrupted = true;

                // By default we have no corruption at all
                $corruption = false;

                // Loop each lap to see whether all laps are corrupted
                foreach ($participant->getLaps() as $lap)
                {
                    // Lap position is not corrupted
                    if ( ! in_array($lap->getPosition(),
                            $corrupted_lap_positions))
                    {
                        // Not all corrupted
                        $all_corrupted = false;
                    }
                    // Corrupted
                    else
                    {
                        // No position known
                        $lap->setPosition(null);

                        // We have corruption
                        $corruption = true;
                    }
                }

                // All are corrupted
                if ($all_corrupted)
                {
                    // Unset all participant laps
                    $participant->setLaps(array());

                    // We need to refill all laps by lap number array
                    $refill_all_laps_by_lap_number = true;
                }
            }

            // Refill all laps by lap number array because laps are removed
            if ($refill_all_laps_by_lap_number)
            {
                $all_laps_by_lap_number = array();
                foreach ($participants as $participant)
                {
                    // Loop each lap
                    foreach ($participant->getLaps() as $lap)
                    {
                        // Remember lap
                         $all_laps_by_lap_number[$lap->getNumber()][] = $lap;
                    }
                }
            }
        }


        //--- Fix wrong positions of laps
        // We have wrong lap positions, we need to fix this
        if ($wrong_lap_positions)
        {
            // Loop all lap numbers and their laps
            foreach ($all_laps_by_lap_number as $lap_number => $laps)
            {
                // Lap number 1
                if ($lap_number === 1)
                {
                    // Just set each lap position to grid position
                    foreach ($laps as $lap)
                    {
                        $lap->setPosition($lap->getParticipant()->getGridPosition());
                    }

                    // Ignore futher
                    continue;
                }

                // Sort the laps by elapsed time
                $laps = $this->helper->sortLapsByElapsedTime($laps);

                // Fix the positions
                foreach ($laps as $lap_key => $lap)
                {
                    // Set new position if it's not null (null = corruption)
                    if ($lap->getPosition() !== null)
                    {
                        $lap->setPosition($lap_key+1);
                    }
                }
            }
        }


        // Sort participants by key
        ksort($participants);

        // Fix array keys to 0 - n
        $participants = array_values($participants);

        // Cache and return all participants
        return $this->participants = $participants;
    }


    /**
     * Sets the chats on a session instance
     *
     * @param  Session  $session
     */
    protected function setChats(Session $session)
    {
        // No chats by default
        $chats = array();

        // Loop each chat (if any)
        /* @var $chat_xml \DOMNode */
        foreach ($this->dom->getElementsByTagName('Chat') as $chat_xml)
        {
            // Create new chat
            $chat = new Chat;

            // Set message
            $chat->setMessage($this->nodeValue($chat_xml));

            // Clone session date
            $date = clone $session->getDate();

            // Add the seconds to date, ignoring any decimals
            $date->modify(sprintf(
                '+%d seconds',
                (int) $chat_xml->getAttribute('et')));

            // Set real estimated seconds
            $chat->setElapsedSeconds( (float) $chat_xml->getAttribute('et'));

            // Add date to chat
            $chat->setDate($date);

            // Add chat to chats
            $chats[] = $chat;
        }

        // Set chats on session
        $session->setChats($chats);
    }

    /**
     * Sets the incidents on a session instance
     *
     * @param  Session  $session
     */
    protected function setIncidents(Session $session)
    {
        // No incidents by default
        $incidents = array();

        // Get incidents from XML
        $incidents_dom = $this->dom->getElementsByTagName('Incident');

        // Way to many incidents!
        if ($incidents_dom->length > 2000)
        {
             // Create new dummy incident
            $incident = new Incident;

            $session->setIncidents(array(
                $incident->setMessage('Sorry, way too many incidents to show!')
                         ->setDate(clone $session->getDate()),
            ));
            return;
        }

        $parts_by_name = array();
        foreach ($session->getParticipants() as $part)
        {
            foreach ($part->getDrivers() as $driver)
            {
                $parts_by_name[$driver->getName()] = $part;
            }
        }

        // Loop each incident (if any)
        /* @var $incident_xml \DOMNode */
        foreach ($incidents_dom as $incident_xml)
        {
            // Create new incident
            $incident = new Incident;

            // Set message
            $incident->setMessage($this->nodeValue($incident_xml));

            // Clone session date
            $date = clone $session->getDate();

            // Add the seconds to date, ignoring any decimals
            $date->modify(sprintf(
                '+%d seconds',
                (int) $incident_xml->getAttribute('et')));

            // Set real estimated seconds
            $incident->setElapsedSeconds( (float) $incident_xml->getAttribute('et'));

            // Add date to incident
            $incident->setDate($date);

            // Default to environment incident
            $incident->setType(Incident::TYPE_ENV);


            // Is incident with another vehicle
            if (strpos(strtolower($incident->getMessage()),
                'with another vehicle'))
            {
                // Match impact
                preg_match('/(.*?)\(.*?reported contact \((.*)\) with '.
                           'another vehicle (.*?)\(/i',
                    $incident->getMessage(), $matches);

                // Worth reviewing when impact is >= 60%
                $incident->setForReview(
                    (isset($matches[2]) AND ((float) $matches[2]) >= 0.60)
                );

                $incident->setType(Incident::TYPE_CAR);

                // Participant known
                if (isset($parts_by_name[$matches[1]])) {
                    $incident->setParticipant($parts_by_name[$matches[1]]);
                }
                // Other participant known
                if (isset($parts_by_name[$matches[3]])) {
                    $incident->setOtherParticipant($parts_by_name[$matches[3]]);
                }
            }

            // Add incident to incidents
            $incidents[] = $incident;
        }

        // Set incidents on session
        $session->setIncidents($incidents);
    }

    /**
     * Sets the penalties on a session instance
     * @param  Session  $session
     */
    protected function setPenalties(Session $session)
    {
        // No penalties by default
        $penalties = array();

        // Participants by name so we can map
        $parts_by_name = array();
        foreach ($session->getParticipants() as $part)
        {
            foreach ($part->getDrivers() as $driver)
            {
                $parts_by_name[$driver->getName()] = $part;
            }
        }

        // Example:
        // <Penalty et="1001.6">mauserrifle received Stop/Go penalty, 10s, 0laps. Result: penalties=1, 1st=Stop/Go,10s</Penalty>

        // Loop each penalty (if any)
        /* @var $penalty_xml \DOMNode */
        foreach ($this->dom->getElementsByTagName('Penalty') as $penalty_xml)
        {
            // Create new penalty
            $penalty = new Penalty;

            // Set message
            $penalty->setMessage($penalty_message = $this->nodeValue($penalty_xml));

            if (preg_match('/^(.*?) (received|served|finished) (.*?) penalty.*/i',
                    $penalty_message, $matches))
            {
                // Participant known
                if (isset($parts_by_name[$matches[1]])) {
                    $penalty->setParticipant($parts_by_name[$matches[1]]);
                }

                if (strtolower($matches[3]) === 'drive thru')
                {
                    $penalty->setType(Penalty::TYPE_DRIVETHROUGH);
                }
                elseif (strtolower($matches[3]) === 'stop/go')
                {
                    $penalty->setType(Penalty::TYPE_STOPGO);
                }

                // We only know served status when served or received
                if (in_array($matches[2], array('served','received')))
                {
                    $penalty->setServed(strtolower($matches[2]) === 'served');
                }
            }

            // Clone session date
            $date = clone $session->getDate();

            // Add the seconds to date, ignoring any decimals
            $date->modify(sprintf(
                '+%d seconds',
                (int) $penalty_xml->getAttribute('et')));

            // Set real estimated seconds
            $penalty->setElapsedSeconds( (float) $penalty_xml->getAttribute('et'));

            // Add date to penalty
            $penalty->setDate($date);

            // Add penalty to penalties
            $penalties[] = $penalty;
        }

        // Set penalties on session
        $session->setPenalties($penalties);
    }

    /**
     * Get the allowed vehicles
     *
     * @return  array
     */
    protected function getAllowedVehicles()
    {
        // Get allowed vehicles array by exploding the value
        $allowed_vehicles = explode('|', $this->tagValue('VehiclesAllowed'));

        // Clean array
        $allowed_vehicles = array_values(
            array_filter(array_unique($allowed_vehicles)));

        // Create instances
        $allowed_vehicles_objects = array();
        foreach ($allowed_vehicles as $vehicle_name)
        {
            $vehicle = new Vehicle;
            $vehicle->setName($vehicle_name);
            $allowed_vehicles_objects[] = $vehicle;
        }

        // Return allowed vehicles
        return $allowed_vehicles_objects;
    }

    /**
     * Helper method to get the value of a tag from the dom using
     *     item(0)->nodeValue
     *
     * @param   string      $tag
     * @param   \DomElement  $dom   a own dom element
     * @return  mixed
     */
    protected function tagValue($tag, $dom=null)
    {
        // No dom param
        if ( ! $dom)
        {
            // Use root dom
            $dom = $this->dom;
        }

        // Has this tag
        if($tags = $dom->getElementsByTagName($tag))
        {
            // Has item
            if ($item = $tags->item(0))
            {
                if (is_string($item->nodeValue) AND strpos($item->nodeValue, '&') !== FALSE)
                {
                    return $this->html_entity_decode($item->nodeValue);
                }
                else
                {
                    return $item->nodeValue;
                }
            }
        }

        // Just return null
        return null;
    }


    /**
     * Helper to get decoded nodeValue of DOM node
     *
     * @param   \DOMNode $node
     * @return  mixed
     */
    protected function nodeValue(\DOMNode $node)
    {
        if (is_string($node->nodeValue) AND strpos($node->nodeValue, '&') !== FALSE)
        {
            return $this->html_entity_decode($node->nodeValue);
        }
        else
        {
            return $node->nodeValue;
        }
    }

    /**
     * Helper to html entity decode a string
     *
     * @param   string $string
     * @return  string
     */
    protected function html_entity_decode($string)
    {
        $flags = ENT_QUOTES;
        if (defined('ENT_XML1')) {
            $flags = $flags | ENT_XML1;
        } elseif (defined('ENT_XHTML')) {
            $flags = $flags | ENT_XHTML;
        } else {
            // PHP 5.3 fallback
            $flags = null;
            $string = str_replace('&apos;', "'", $string);
        }
        if ($flags) {
            return html_entity_decode($string, $flags);
        } else {
            return html_entity_decode($string);
        }
    }


    /**
     * Clean XML data
     *
     * @param   string  $xml
     * @return  string
     */
    protected static function cleanXML($xml)
    {
        // Make sure the data is utf-8 encoded, rFactor2 logs are windows
        // encoded by default
        // $xml = mb_convert_encoding($xml, 'UTF-8', mb_list_encodings());
        // $xml = iconv('ISO-8859-1', 'UTF-8', $xml);
        // $xml = \UConverter::transcode($xml, 'UTF8', 'ISO-8859-1');
        $xml = mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1');

        // Remove any unwanted chars after the end of the file (after last
        // XML closing tag)
        $xml = substr($xml, 0, 1+strrpos($xml, '>'));

        // Fix unescaped amp characters
        $xml = preg_replace('/&(?!amp;)/', '&amp;$1', $xml);

        return $xml;
    }

}
