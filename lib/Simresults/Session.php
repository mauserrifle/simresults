<?php
namespace Simresults;

/**
 * The session class. Main point for results.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Session {

    // The session types
    const TYPE_PRACTICE = 'practice';
    const TYPE_QUALIFY  = 'qualify';
    const TYPE_WARMUP   = 'warmup';
    const TYPE_RACE     = 'race';


    //------ Cache values

    /**
     * @var  array|null  The cache for laps sorted by time
     */
    protected $cache_laps_sorted_by_time;

    /**
     * @var  array  The cache for laps by lap number sorted by time
     */
    protected $cache_laps_by_lap_number_sorted_by_time = array();

    /**
     * @var  array  The cache for best lap by lap number
     */
    protected $cache_best_lap_by_lap_number = array();

    /**
     * @var  array|null  The cache for best laps grouped by participant
     */
    protected $cache_best_laps_grouped_by_participant;

    /**
     * @var  array  The cache for laps sorted by sector
     */
    protected $cache_laps_sorted_by_sector = array();

    /**
     * @var  array  The cache for best laps by sector grouped by participant
     */
    protected $cache_best_laps_by_sector_grouped_by_participant = array();

    /**
     * @var  array  The cache for laps sorted by sector by lap number
     */
    protected $cache_laps_sorted_by_sector_by_lap_number = array();

    /**
     * @var  array  The cache for best lap by sector by lap number
     */
    protected $cache_best_lap_by_sector_by_lap_number = array();

    /**
     * @var  array|null  The cache for bad laps
     */
    protected $cache_bad_laps;

    /**
     * @var  Participant|null  The cache for led most participant
     */
    protected $cache_led_most_participant;

    /**
     * @var  array  The cache for the leading participant per lap
     */
    protected $cache_leading_participant = array();

    /**
     * @var  array  The cache for the leading participant by elapsed time per
     *              lap
     */
    protected $cache_leading_participant_by_elapsed_time = array();

    /**
     * @var  int|null  The cache for the lasted laps
     */
    protected $cache_lasted_laps;

    /**
     * @var  int|null  The cache for the max position
     */
    protected $cache_max_position;


    //------ Session values

    /**
     * @var  string  The session type based on the constants
     */
    protected $type;

    /**
     * @var  string  The session name
     */
    protected $name;

    /**
     * @var  Game  The game this session was driven on
     */
    protected $game;

    /**
     * @var  Server  The server this session was driven on (if any)
     */
    protected $server;

    /**
     * @var  Track  The track this session was driven on
     */
    protected $track;

    /**
     * @var  array  The participants of this session. Contains Participant
     *              objects
     */
    protected $participants = array();

    /**
     * @var  \DateTime  The date and time this session started
     */
    protected $date;

    /**
     * @var  string  The date string originally parsed
     */
    protected $date_string;

    /**
     * @var  int  The max number of laps this session could of lasted
     */
    protected $max_laps;

    /**
     * @var  int  The max time in minutes this session could of lasted
     */
    protected $max_minutes;

    /**
     * @var  array  The chat messages sent within this session
     */
    protected $chats = array();

    /**
     * @var  array  The incidents within this session
     */
    protected $incidents = array();

    /**
     * @var  array  The penalties within this session
     */
    protected $penalties = array();

    /**
     * @var  string  The mod used for this session
     */
    protected $mod;

    /**
     * @var  array  The vehicles that were allowed during this session
     */
    protected $allowed_vehicles = array();

    /**
     * @var  boolean  Whether the session only allowed a fixed setup
     */
    protected $setup_fixed;

    /**
     * @var  array  Any other settings that were used. This is an assoc array
     *              (setting => value)
     */
    protected $other_settings = array();


    /**
     * Set the session type based on the constants
     *
     * @param   int      $type
     * @return  Session
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the session type based on the constants
     *
     * @return  int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the session name
     *
     * @param   int      $name
     * @return  Session
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the session name
     *
     * @return  int
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the game this session was driven on
     *
     * @param   Game     $game
     * @return  Session
     */
    public function setGame($game)
    {
        $this->game = $game;
        return $this;
    }

    /**
     * Get the game this session was driven on
     *
     * @return  Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Set the server this session was driven on (if any)
     *
     * @param   Server   $server
     * @return  Session
     */
    public function setServer($server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * Get the server this session was driven on (if any)
     *
     * @return  Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set the track this session was driven on
     *
     * @param   Track    $track
     * @return  Session
     */
    public function setTrack($track)
    {
        $this->track = $track;
        return $this;
    }

    /**
     * Get the track this session was driven on
     *
     * @return  Server
     */
    public function getTrack()
    {
        return $this->track;
    }

    /**
     * Set the participants of this session
     *
     * @param   array    $participants
     * @return  Session
     */
    public function setParticipants(array $participants)
    {
        $this->participants = $participants;
        return $this;
    }

    /**
     * Add participant to this session
     *
     * @param   Participant  $participant
     * @return  Session
     */
    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;
        return $this;
    }

    /**
     * Get the participants of this session
     *
     * @return  array
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Set the date and time this session started
     *
     * @param   \DateTime  $date
     * @return  Session
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get the date and time this session started
     *
     * @return  \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date string originally parsed
     *
     * @param   string  $date_string
     * @return  Session
     */
    public function setDateString($date_string)
    {
        $this->date_string = $date_string;
        return $this;
    }

    /**
     * Get the date string originally parsed
     *
     * @return  string
     */
    public function getDateString()
    {
        return $this->date_string;
    }

    /**
     * Set max number of laps this session could of lasted
     *
     * @param   int      $max_laps
     * @return  Session
     */
    public function setMaxLaps($max_laps)
    {
        $this->max_laps = $max_laps;
        return $this;
    }

    /**
     * Get max number of laps this session could of lasted
     *
     * @return  int
     */
    public function getMaxLaps()
    {
        return $this->max_laps;
    }

    /**
     * Set max time in minutes this session could of lasted
     *
     * @param   int      $max_minutes
     * @return  Session
     */
    public function setMaxMinutes($max_minutes)
    {
        $this->max_minutes = $max_minutes;
        return $this;
    }

    /**
     * Get max time in minutes this session could of lasted
     *
     * @return  int
     */
    public function getMaxMinutes()
    {
        return $this->max_minutes;
    }

    /**
     * Set the chats sent within this session
     *
     * @param   array    $chats
     * @return  Session
     */
    public function setChats(array $chats)
    {
        $this->chats = $chats;
        return $this;
    }

    /**
     * Add chat to this session
     *
     * @param   Chat     $chat
     * @return  Session
     */
    public function addChat(Chat $chat)
    {
        $this->chats[] = $chat;
        return $this;
    }

    /**
     * Get the chats sent within this session
     *
     * @return  array
     */
    public function getChats()
    {
        return $this->chats;
    }

    /**
     * Set the incidents within this session
     *
     * @param   array    $incidents
     * @return  Session
     */
    public function setIncidents(array $incidents)
    {
        $this->incidents = $incidents;
        return $this;
    }

    /**
     * Add incident to this session
     *
     * @param   Incident  $incident
     * @return  Session
     */
    public function addIncident(Incident $incident)
    {
        $this->incidents[] = $incident;
        return $this;
    }


    /**
     * Get the incidents within this session
     *
     * @return  array
     */
    public function getIncidents()
    {
        return $this->incidents;
    }

    /**
     * Get the incidents for reviewing
     *
     * @return  array
     */
    public function getIncidentsForReview()
    {
        // Return filtered incidents
        return array_values(
            array_filter($this->incidents,
            function(Incident $incident){return ($incident->isForReview());}
        ));
    }

    /**
     * Set the penalties within this session
     *
     * @param   array    $penalties
     * @return  Session
     */
    public function setPenalties(array $penalties)
    {
        $this->penalties = $penalties;
        return $this;
    }

    /**
     * Add penalty to this session
     *
     * @param   Penalty  $penalty
     * @return  Session
     */
    public function addPenalty(Penalty $penalty)
    {
        $this->penalties[] = $penalty;
        return $this;
    }

    /**
     * Get the penalties within this session
     *
     * @return  array
     */
    public function getPenalties()
    {
        return $this->penalties;
    }

    /**
     * Set the mod being used in this sessioh
     *
     * @param   string   $mod
     * @return  Session
     */
    public function setMod($mod)
    {
        $this->mod = $mod;
        return $this;
    }

    /**
     * Get the mod being used in this session
     *
     * @return  string
     */
    public function getMod()
    {
        return $this->mod;
    }

    /*
     * Set the allowed vehicles in this session
     *
     * @param   array    $vehicles
     * @return  Session
     */
    public function setAllowedVehicles(array $vehicles)
    {
        $this->allowed_vehicles = $vehicles;
        return $this;
    }


    /**
     * Add allowed vehicle to this session
     *
     * @param   Vehicle  $vehicle
     * @return  Session
     */
    public function addAllowedVehicle(Vehicle $vehicle)
    {
        $this->allowed_vehicles[] = $vehicle;
        return $this;
    }

    /**
     * Get the allowed vehicles in this session
     *
     * @return  array
     */
    public function getAllowedVehicles()
    {
        return $this->allowed_vehicles;
    }

    /**
     * Set whether setups were fixed
     *
     * @param   boolean  $setup_fixed
     * @return  Session
     */
    public function setSetupFixed($setup_fixed)
    {
        $this->setup_fixed = (bool) $setup_fixed;
        return $this;
    }

    /**
     * Returns whether setups were fixed for this session. Returns null when
     * it's unknown.
     *
     * @return  boolean
     */
    public function isSetupFixed()
    {
        return $this->setup_fixed;
    }

    /**
     * Set the other settings that were used in this session. Assoc array
     * is expected
     *
     * @param   array    $settings
     * @return  Session
     */
    public function setOtherSettings(array $settings)
    {
        $this->other_settings = $settings;
        return $this;
    }

    /**
     * Add a other setting
     *
     * @param   string    $setting
     * @param   mixed     $value
     * @return  Session
     */
    public function addOtherSetting($setting, $value)
    {
        $this->other_settings[$setting] = $value;
        return $this;
    }

    /**
     * Get the other settings used within this session. Returns an assoc array
     *
     * @return  array
     */
    public function getOtherSettings()
    {
        return $this->other_settings;
    }


    /**
     * Get the laps sorted by time (ASC)
     *
     * @return  array
     */
    public function getLapsSortedByTime()
    {
        // There is cache
        if ($this->cache_laps_sorted_by_time !== null)
        {
            return $this->cache_laps_sorted_by_time;
        }

        // Init laps
        $laps = array();

        // Loop each participant
        foreach ($this->getParticipants() as $participant)
        {
            // Collect laps of participant
            $laps = array_merge($laps, $participant->getLaps());
        }

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_time =
            Helper::sortLapsByTime($laps);
    }

    /**
     * Returns the (completed) best lap for this session
     *
     * @return  Lap|null
     */
    public function getBestLap()
    {
        // Get laps
        $laps = $this->getLapsSortedByTime();

        // Only return a completed lap
        foreach ($laps as $lap)
        {
            if ($lap->isCompleted())
            {
                return $lap;
            }
        }

        return NULL;
    }

    /**
     * Get the laps by lap number sorted by time (ASC)
     *
     * @param   int    $lap_number
     * @return  array
     */
    public function getLapsByLapNumberSortedByTime($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_laps_by_lap_number_sorted_by_time))
        {
            return $this->cache_laps_by_lap_number_sorted_by_time[$lap_number];
        }

        // Init laps
        $laps = array();

        // Loop each participant
        foreach ($this->getParticipants() as $participant)
        {
            // Has this lap
            if ($lap = $participant->getLap($lap_number))
            {
                // Collect lap of participant
                $laps[] = $lap;
            }
        }

        // Return sorted laps and cache it
        return $this->cache_laps_by_lap_number_sorted_by_time[$lap_number] =
            Helper::sortLapsByTime($laps);
    }

    /**
     * Get the best lap by lap number
     *
     * @param   int  $lap_number
     * @return  Lap
     */
    public function getBestLapByLapNumber($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_best_lap_by_lap_number))
        {
            return $this->cache_best_lap_by_lap_number[$lap_number];
        }

        $laps = $this->getLapsByLapNumberSortedByTime($lap_number);
        return $this->cache_best_lap_by_lap_number[$lap_number] =
            array_shift($laps);
    }

    /**
     * Get best laps grouped participant. The same participant is not returned
     * twice.
     *
     * @return  array
     */
    public function getBestLapsGroupedByParticipant()
    {
        // There is cache
        if ($this->cache_best_laps_grouped_by_participant !== null)
        {
            return $this->cache_best_laps_grouped_by_participant;
        }

        // Init laps
        $laps = array();

        // Loop each participant
        foreach ($this->getParticipants() as $participant)
        {
            // Has best lap
            if ($best_lap = $participant->getBestLap())
            {
                // Collect lap of participant
                $laps[] = $best_lap;
            }
        }

        // Return sorted laps and cache it
        return $this->cache_best_laps_grouped_by_participant =
            Helper::sortLapsByTime($laps);
    }

    /**
     * Get the laps sorted by sector
     *
     * @param  int  $sector
     */
    public function getLapsSortedBySector($sector)
    {
        // There is cache
        if (array_key_exists($sector, $this->cache_laps_sorted_by_sector))
        {
            return $this->cache_laps_sorted_by_sector[$sector];
        }

        // Get the laps
        $laps = array();
        foreach ($this->getParticipants() as $part)
        {
            $laps = array_merge($laps, $part->getLaps());
        }

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_sector[$sector] =
            Helper::sortLapsBySector($laps, $sector);
    }

    /**
     * Get the best lap by sector
     *
     * @param   int  $sector
     * @return  Lap
     */
    public function getBestLapBySector($sector)
    {
        $laps = $this->getLapsSortedBySector($sector);
        return array_shift($laps);
    }

    /**
     * Returns the best laps of a given sector grouped by participant. This
     * will not return a lap of the same participant twice. So this result can
     * be used for a ranking per sector.
     *
     * @param   int  $sector
     * @return  array
     */
    public function getBestLapsBySectorGroupedByParticipant($sector)
    {
        // There is cache
        if (array_key_exists($sector,
                $this->cache_best_laps_by_sector_grouped_by_participant))
        {
            return $this->cache_best_laps_by_sector_grouped_by_participant[
                       $sector];
        }

        // Init laps array
        $laps = array();

        // Get the best lap by for this sector for each participant
        foreach ($this->getParticipants() as $participant)
        {
            // Has best lap by this sector
            if ($best_lap = $participant->getBestLapBySector($sector))
            {
                // Store this lap
                $laps[] = $best_lap;
            }
        }

        // Return sorted laps and cache it
        return $this->cache_best_laps_by_sector_grouped_by_participant[$sector]
            = Helper::sortLapsBySector($laps, $sector);
    }

    /**
     * Returns the laps sorted by a given sector and lap number
     *
     * @param   int  $sector
     * @param   int  $lap_number
     * @return  array
     */
    public function getLapsSortedBySectorByLapNumber($sector, $lap_number)
    {
        // There is cache
        if (array_key_exists("$sector-$lap_number",
                $this->cache_laps_sorted_by_sector_by_lap_number))
        {
            return $this->cache_laps_sorted_by_sector_by_lap_number[
                       "$sector-$lap_number"];
        }

        // Init laps array
        $laps = array();

        // Store the sector times of the given lap
        foreach ($this->getParticipants() as $participant)
        {
            // Has lap for this lap number
            if ($lap = $participant->getLap($lap_number))
            {
                $laps[] = $lap;
            }
        }

        // Return sorted laps and cache it
        return $this->cache_laps_sorted_by_sector_by_lap_number[
            "$sector-$lap_number"] = Helper::sortLapsBySector($laps, $sector);
    }

    /**
     * Get the best lap by sector and lap number
     *
     * @param   int  $sector
     * @param   int  $lap_number
     * @return  Lap
     */
    public function getBestLapBySectorByLapNumber($sector, $lap_number)
    {
        // There is cache
        if (array_key_exists("$sector-$lap_number",
                $this->cache_best_lap_by_sector_by_lap_number))
        {
            return $this->cache_best_lap_by_sector_by_lap_number[
                       "$sector-$lap_number"];
        }

        $laps = $this->getLapsSortedBySectorByLapNumber($sector, $lap_number);
        return $this->cache_best_lap_by_sector_by_lap_number[
                   "$sector-$lap_number"] = array_shift($laps);
    }

    /**
     * Get bad laps that are above a percent of the best lap. Defaults to the
     * 107% rule.
     *
     * @param  int  $above_percent
     */
    public function getBadLaps($above_percent = 107)
    {
        // There is cache
        if ($this->cache_bad_laps !== null)
        {
            return $this->cache_bad_laps;
        }

        // No best lap
        if ( ! $best_lap = $this->getBestLap())
        {
            // return no laps and cache it
            return $this->cache_bad_laps = array();
        }

        // Get laps sorted by time
        $laps = $this->getLapsSortedByTime();

        // Get the time criteria
        $max_time = round(($best_lap->getTime() * ($above_percent/100)), 4);

        // Filter laps
        $laps = array_filter($laps, function(Lap $lap) use ($max_time) {
            return ($lap->isCompleted() AND $lap->getTime() > $max_time);
        });

        // Return the laps with proper keys and cache it
        return $this->cache_bad_laps = array_values($laps);
    }

    /**
     * Get the participant that led the most
     *
     * @return  Participant|null
     */
    public function getLedMostParticipant()
    {
        // There is cache
        if ($this->cache_led_most_participant !== null)
        {
            return $this->cache_led_most_participant;
        }

        $led_most_participant = null;

        // Check each participant
        foreach ($this->getParticipants() as $participant)
        {
            //No led most participant yet
            if ( ! $led_most_participant)
            {
                // Just set this
                $led_most_participant = $participant;

                // Continue to next participant
                continue;
            }

            // This participant led more
            if ($participant->getNumberOfLapsLed() >
                   $led_most_participant->getNumberOfLapsLed())
            {
                // Set this participant as most led
                $led_most_participant = $participant;
            }
        }

        // Return and cache
        return $this->cache_led_most_participant = $led_most_participant;
    }

    /**
     * Get the winning participant
     *
     * @return  Participant|null
     */
    public function getWinningParticipant()
    {
        // Has participants
        if ($participants = $this->getParticipants())
        {
            // Return top participant
            return array_shift($participants);
        }

        // Return no winner by default
        return null;
    }

    /**
     * Get the leading participant for a given lap number
     *
     * @param   int  $lap_number
     * @return  Participant
     */
    public function getLeadingParticipant($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number, $this->cache_leading_participant))
        {
            return $this->cache_leading_participant[$lap_number];
        }

        // Loop each participant
        foreach ($this->getParticipants() as $part)
        {
            // Participant does not have this
            if ( ! $lap = $part->getLap($lap_number))
            {
                // Skip this participant
                continue;
            }

            // Lap ran with position 1
            if ($lap->getPosition() === 1)
            {
                // Return this participant and cache it
                return $this->cache_leading_participant[$lap_number] = $part;
            }
        }
    }

    /**
     * Get the leading participant for a given lap number by elapsed time.
     * This method is useful because sometimes someone is marked p1 while
     * someone else is p1 by time. When using `getLeadingParticipant` method
     * and using that participant for gap calculations, it could cause wrong
     * gap values (minus values). This method should be used to fix this wrong
     * behavior.
     *
     * @param   int  $lap_number
     * @return  Participant|null
     */
    public function getLeadingParticipantByElapsedTime($lap_number)
    {
        // There is cache
        if (array_key_exists($lap_number,
                $this->cache_leading_participant_by_elapsed_time))
        {
            return $this->cache_leading_participant_by_elapsed_time[
                $lap_number];
        }

        $leading_lap = null;

        // Loop each participant
        foreach ($this->getParticipants() as $part)
        {
            // Participant does not have this
            if ( ! $lap = $part->getLap($lap_number))
            {
                // Skip this participant
                continue;
            }

            // Leading lap not known yet or (this lap has elapsed seconds and
            // (leading lap not or this is earlier than current))
            if ( ! $leading_lap OR ($lap->getElapsedSeconds() !== null AND
                 ($leading_lap->getElapsedSeconds() === null OR
                  $lap->getElapsedSeconds()
                      < $leading_lap->getElapsedSeconds())))
            {
                // Return this participant
                $leading_lap = $lap;
            }
        }

        // Return and cache it
        return $this->cache_leading_participant_by_elapsed_time[$lap_number] =
            $leading_lap ? $leading_lap->getParticipant() : null;
    }

    /**
     * Get the participants sorted by consistency
     *
     * @return  array
     */
    public function getParticipantsSortedByConsistency()
    {
        return Helper::sortParticipantsByConsistency($this->getParticipants());
    }

    /**
     * Get number of laps this session lasted
     *
     * @return  int
     */
    public function getLastedLaps()
    {
        // There is cache
        if ($this->cache_lasted_laps !== null)
        {
            return $this->cache_lasted_laps;
        }

        // No laps by default
        $laps = 0;

        // Loop each participant to get number of laps
        foreach ($this->getParticipants() as $part)
        {
            // Number of laps greater than current highest value
            if ($part->getNumberOfLaps() > $laps)
            {
                // Set number of laps
                $laps = $part->getNumberOfLaps();
            }
        }

        // Return number of laps lasted and cache it
        return $this->cache_lasted_laps = $laps;
    }

    /**
     * Get the max position within this session. Will search all laps for
     * highest position number. This method can be more safe instead of the
     * number of participants when need to know the max position, for example
     * for graphs. Sometimes the positions are higher than the actual number
     * of participants (e.g. in rfactor results).
     *
     * @return  int
     */
    public function getMaxPosition()
    {
        // There is cache
        if ($this->cache_max_position !== null)
        {
            return $this->cache_max_position;
        }

        // Max position
        $max_position = 1;

        // Loop each participant
        foreach ($this->getParticipants() as $part)
        {
            // Loop each lap
            foreach ($part->getLaps() as $lap)
            {
                // Position is higher than current max
                if ($lap->getPosition() > $max_position)
                {
                    // Set new max
                    $max_position = $lap->getPosition();
                }
            }
        }

        // Return max position and cache it
        return $this->cache_max_position = $max_position;
    }

    /**
     * Splits the session into multiple sessions by vehicle class. The sessions
     * are sorted by class name (asc)
     *
     * @return  array
     */
    public function splitByVehicleClass()
    {
        // Filter participants by vehicle class
        $participants = array();
        foreach ($this->getParticipants() as $part)
        {
            // No vehicle
            if ( ! $part->getVehicle()) continue;

            $participants[$part->getVehicle()->getClass()][] = $part;
        }

        // Sort by class name
        ksort($participants);

        // Create new sessions
        $sessions = array();
        foreach ($participants as $part_array)
        {
            // Clone session and set new participants
            $session = clone $this;
            $session->setParticipants($part_array);

            $sessions[] = $session;
        }

        return $sessions;
    }


    /**
     * Reset cache on cloning
     */
    public function __clone()
    {
        $this->cache_laps_sorted_by_time = NULL;
        $this->cache_laps_by_lap_number_sorted_by_time = array();
        $this->cache_best_laps_grouped_by_participant = NULL;
        $this->cache_laps_sorted_by_sector = array();
        $this->cache_best_laps_by_sector_grouped_by_participant = array();
        $this->cache_laps_sorted_by_sector_by_lap_number = array();
        $this->cache_bad_laps = NULL;
        $this->cache_led_most_participant = NULL;
        $this->cache_leading_participant = array();
        $this->cache_leading_participant_by_elapsed_time = array();
        $this->cache_lasted_laps = NULL;
        $this->cache_max_position = NULL;
    }

}
