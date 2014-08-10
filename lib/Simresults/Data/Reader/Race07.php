<?php
namespace Simresults;
use Simresults\Helper;

/**
 * The reader for Race07
 *
 * Supports the following games too:
 * * GTL
 * * GTR and GTR2
 * * F1 challenge 99-02
 *
 *  TODO:
 *  * For F1 challenge logs without laps, add dummy laps and one with best lap
 *  * DNF reasons ints in log? What do they mean?
 *  * Somehow include qualify times in a (race) result too?
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Data_Reader_Race07 extends Data_Reader {

    /**
     * @var  array  The data as array
     */
    protected $array_data;


    /**
     * @see Simresults\Data_Reader::canRead()
     */
    public static function canRead($data)
    {
        return (bool) self::parse_data($data);
    }

    /**
     * @see \Simresults\Data_Reader::getSessions()
     */
    public function getSessions()
    {
        // Get array data
        $data = $this->array_data;

        // Create new session instance
        $session = new Session;

        // Get date from human string.
        // WARNING: Default timezone used. Please note that this is not correct.
        // The date is the date of the server, but we will never know the
        // thezome because the data does not provide a timestamp or timezone
        // information
        $date = \DateTime::createFromFormat(
            'Y/m/d H:i:s',
            $data['header']['timestring'],
            new \DateTimeZone(self::$default_timezone)
        );
        $date->setTimezone(new \DateTimeZone(self::$default_timezone));
        $session->setDate($date);

        //--- Set game
        $game = new Game;
        $game->setName($data['header']['game'])
             -> setVersion($data['header']['version']);
        $session->setGame($game);

        //--- Set track

        // Match track data
        // (e.g. Scene=GameData\Locations\Monza_2007\2007_Monza.TRK)
        preg_match('/^.*\\\\(.*)\\\\(.*)\..*$/i',
            $data['race']['scene'], $track_matches);

        // Set track values and set to session
        $track = new Track;
        $track->setVenue($track_matches[1])
              ->setCourse($track_matches[2])
              ->setLength( (float) $data['race']['track length']);
        $session->setTrack($track);

        // Get participants
        $session->setParticipants($participants = $this->getParticipants());


        // Fix driver positions for laps
        $session_lasted_laps = $session->getLastedLaps();

        // Loop each lap number, beginning from 2, because we can't
        // figure out positions for lap 1 in
        // TODO: What about qualy time???
        // TODO: Make central function to prevent duplicate code. AC uses this
        // too!!
        for($i=2; $i <= $session_lasted_laps; $i++)
        {
            // Get laps sorted by elapsed time
            $laps_sorted = $session->getLapsByLapNumberSortedByTime($i);

            // Sort laps by elapsed time
            usort($laps_sorted, function($a,$b) {
                // Same time
                 if ($a->getElapsedSeconds() === $b->getElapsedSeconds()) {
                    return 0;
                }

                // A has no elapsed time
                if ( ! $a->getElapsedSeconds())
                {
                    // b is faster
                    return 1;
                }

                // B has no elapsed time
                if ( ! $b->getElapsedSeconds())
                {
                    // a is faster
                    return -1;
                }

                // Return normal comparison
                return ($a->getElapsedSeconds() < $b->getElapsedSeconds()) ? -1 : 1;
            });

            // Loop each lap and fix position data
            foreach ($laps_sorted as $lap_key => $lap)
            {
                // Only fix position if lap has a time, this way users of this
                // library can easier detect whether it's a dummy lap and
                // decide how to show them
                if ($lap->getTime() OR $lap->getElapsedSeconds())
                {
                    $lap->setPosition($lap_key+1);
                }
            }
        }


        // Set default session type to RACE
        $session->setType(Session::TYPE_RACE);

        // Get first participant
        if ($participants AND $participant = $participants[0])
        {
            // First participant has DNF status
            if ($participant->getFinishStatus() === Participant::FINISH_DNF)
            {
                // Assume we're dealing with qualify session
                $session->setType(Session::TYPE_QUALIFY);
                $session->setName('Qualify or practice session');
            }
        }

        return array($session);
    }


    protected function getParticipants()
    {
        $data = $this->array_data;

        // Init participants array
        $participants = array();

        // Collect drivers in array
        $driver_data_array = array();

        // No grid position by default
        $set_grid_position = false;
        foreach ($data as $key => $driver_data)
        {
            // Not a driver driver_data
            if ( ! preg_match('/slot([0-9]+)/i', $key, $matches))
            {
                continue;
            }

            // No qualtime
            if ( ! array_key_exists('qualtime', $driver_data))
            {
                // Create qualtime, we need it for easier closure usage
                $driver_data['qualtime'] = null;
            }
            // Has qualtime
            else
            {
                $set_grid_position = true;
            }

            $driver_data_array[] = $driver_data;
        }

        // Set grid positions
        if ($set_grid_position)
        {
            // Sort drivers by qualify time to figure out grid positions
            usort($driver_data_array, function($a, $b){
                // Same time
                if ($a['qualtime'] === $b['qualtime']) {
                    return 0;
                }

                // a has no time
                if ( ! $a['qualtime'])
                {
                    // $b is faster
                    return 1;
                }

                // b has no time
                if ( ! $b['qualtime'])
                {
                    // $a is faster
                    return -1;
                }

                return ($a['qualtime'] < $b['qualtime']) ? -1 : 1;
            });

            // Set grid positions
            foreach ($driver_data_array as $driver_key => &$driver_data_array_item)
            {
                $driver_data_array_item['grid_position'] = $driver_key+1;
            }
            unset($driver_data_array_item);
        }

       // Loop each driver
       foreach ($driver_data_array as $driver_data)
       {
            // Create driver
            $driver = new Driver;
            $driver->setName($driver_data['driver']);

            // Create participant and add driver
            $participant = new Participant;
            $participant->setDrivers(array($driver))
                        ->setTeam($this->get($driver_data, 'team'));
                        // Finish position will be set later using an special
                        // sort

            // We have laps and must set grid positions
            if($this->get($driver_data, 'laps_collection') AND
                $set_grid_position)
            {
                $participant->setGridPosition($driver_data['grid_position']);
            }

            // Create vehicle and add to participant
            $vehicle = new Vehicle;
            $vehicle->setName($this->get($driver_data, 'vehicle'));
            $participant->setVehicle($vehicle);

            // Has race time information
            if ($race_time = $this->get($driver_data, 'racetime'))
            {
                try
                {
                    // Get seconds
                    $seconds = Helper::secondsFromFormattedTime($race_time);

                    // Set total time
                    $participant->setTotalTime($seconds);

                    // Is finished
                    $participant->setFinishStatus(Participant::FINISH_NORMAL);

                }
                // Catch invalid argument, probably a string status like DNF
                catch (\InvalidArgumentException $ex)
                {
                    $participant->setFinishStatus(Participant::FINISH_DNF);

                    // Has reason
                    if (null !== $reason = $this->get($driver_data, 'reason'))
                    {
                        $participant->setFinishComment("DNF (reason $reason)");
                    }
                }
            }

            // Has laps data
            if ($laps_collection = $this->get($driver_data, 'laps_collection'))
            {
                // Loop laps by lap count due to missing laps in results
                // so we can fill up the gaps
                for ($lap_i=1; $lap_i <= $driver_data['laps']; $lap_i++)
                {
                    // Init new lap
                    $lap = new Lap;

                    // Set participant
                    $lap->setParticipant($participant);

                    // Set first driver of participant as lap driver. Race07 does
                    // not support swapping
                    $lap->setDriver($participant->getDriver());

                    // Set lap number
                    $lap->setNumber($lap_i);

                    // Lap data exists
                    if (isset($laps_collection[$lap_i]))
                    {
                        // Get lap data
                        $lap_data = $laps_collection[$lap_i];

                        // Set lap times
                        $lap->setTime($lap_data['time'])
                            ->setElapsedSeconds($lap_data['elapsed_time']);
                    }

                    // Add lap to participant
                    $participant->addLap($lap);
                }
            }

            // Add participant to collection
            $participants[] = $participant;
        }

        // Sort participants by total time
        // TODO: Move to helper with own unittest?
        // TODO: Sort lap distance too....
        usort($participants, function($a, $b) {

            // Same time
            if ($a->getTotalTime() === $b->getTotalTime()) {
                return 0;
            }

            // Both have DNF status
            if ($a->getFinishStatus() === Participant::FINISH_DNF AND
                $b->getFinishStatus() === Participant::FINISH_DNF)
            {
                // Both ran same amount of laps
                if ($a->getNumberOfLaps() === $b->getNumberOfLaps())
                {
                    // TODO: Check last lap distance...
                }

                // A is slower when having less laps than b
                return ($a->getNumberOfLaps() < $b->getNumberOfLaps()) ? 1 : -1;
            }

            // a has no time
            if ( ! $a->getTotalTime() OR
                $a->getFinishStatus() === Participant::FINISH_DNF)
            {
                // $b is faster
                return 1;
            }

            // b has no time
            if ( ! $b->getTotalTime() OR
                $b->getFinishStatus() === Participant::FINISH_DNF)
            {
                // $a is faster
                return -1;
            }

            return ($a->getTotalTime() < $b->getTotalTime()) ? -1 : 1;
        });


        // Fix participant positions
        foreach ($participants as $key => $part)
        {
            $part->setPosition($key+1);
        }

        return $participants;
    }

    /**
     * @see Simresults\Data_Reader::canRead()
     */
    protected function init()
    {
        $this->array_data = $this->parse_data($this->data);
    }



    /**
     * Parses and converts the data to an array. Keys will be converted to
     * lowercase names
     *
     * @return   array
     *
     */
    protected static function parse_data($data)
    {

        // Prepare array data
        $array_data = array();

        // Are laps zero based?
        $laps_zero_based = (bool) strpos($data, 'Lap=(0,');

        //----  Loop each line
        // Remember section
        $section = null;
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $line){
            // Empty line or META info, ignore this
            if ( ! $line OR strpos($line, '//') !== false ) continue;

            // Is header
            if (preg_match('/^\[(.*)\]$/i', $line, $matches))
            {
                // Set section and continue to next line
                $section = strtolower($matches[1]);
                continue;
            }

            // No section, we failed
            if ( ! $section) return FALSE;

            // Get key and value
            $split = explode('=', $line, 2);

            // Get key value for array
            $key = strtolower($split[0]);


            // Is lap value
            if ($key === 'lap')
            {
                // Laps array does not exist yet
                if ( ! array_key_exists('laps_collection', $array_data[$section]))
                {
                    // Init laps array
                    $array_data[$section]['laps_collection'] = array();
                }

                // Match lap information. e.g. (0, -1.000, 2:20.923)
                preg_match('/^\((.*), (.*), (.*)\)$/i', $split[1], $lap_matches);

                // Zero based laps
                if ($laps_zero_based)
                {
                    // Increment lap number by 1
                    $lap_number = $lap_matches[1]+1;
                }
                // No zero based laps
                else
                {
                    // Use lap numbers defined in file
                    $lap_number = $lap_matches[1];
                }

                // Elapsed time negative, make sure it's positive
                if ( 0 > $elapsed_time = (float) $lap_matches[2])
                {
                    $elapsed_time = 0;
                }

                // Set lap data
                $array_data[$section]['laps_collection'][$lap_number] = array (
                    'lap_number'     => (int) $lap_number,
                    'elapsed_time'   => $elapsed_time,
                    'time'           =>
                        Helper::secondsFromFormattedTime($lap_matches[3]),
                );

            }
            // Is normal value
            else
            {
                $array_data[$section][$key] = $split[1];
            }
        }

        return $array_data;
    }

    /**
     * Retrieve a single key from an array. If the key does not exist in the
     * array, the default value will be returned instead.
     *
     *     // Get the value "username" from $_POST, if it exists
     *     $username = Arr::get($_POST, 'username');
     *
     *     // Get the value "sorting" from $_GET, if it exists
     *     $sorting = Arr::get($_GET, 'sorting');
     *
     * This function is from the Kohana project (http://kohanaframework.org/).
     *
     * @param   array   $array      array to extract from
     * @param   string  $key        key name
     * @param   mixed   $default    default value
     * @return  mixed
     */
    protected function get($array, $key, $default = NULL)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

}
