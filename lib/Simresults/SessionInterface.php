<?php
namespace Simresults;

interface SessionInterface {

    /**
     * Set the session type based on the constants
     *
     * @param   int      $type
     * @return  Session
     */
    public function setType($type);

    /**
     * Get the session type based on the constants
     *
     * @return  int
     */
    public function getType();

    /**
     * Set the session name
     *
     * @param   int      $name
     * @return  Session
     */
    public function setName($name);

    /**
     * Get the session name
     *
     * @return  int
     */
    public function getName();

    /**
     * Set the game this session was driven on
     *
     * @param   Game     $game
     * @return  Session
     */
    public function setGame($game);

    /**
     * Get the game this session was driven on
     *
     * @return  Game
     */
    public function getGame();

    /**
     * Set the server this session was driven on (if any)
     *
     * @param   Server   $server
     * @return  Session
     */
    public function setServer($server);

    /**
     * Get the server this session was driven on (if any)
     *
     * @return  Server
     */
    public function getServer();

    /**
     * Set the track this session was driven on
     *
     * @param   Track    $track
     * @return  Session
     */
    public function setTrack($track);

    /**
     * Get the track this session was driven on
     *
     * @return  Server
     */
    public function getTrack();

    /**
     * Set the participants of this session
     *
     * @param   array    $participants
     * @return  Session
     */
    public function setParticipants(array $participants);

    /**
     * Add participant to this session
     *
     * @param   Participant  $participant
     * @return  Session
     */
    public function addParticipant(Participant $participant);

    /**
     * Get the participants of this session
     *
     * @return  array
     */
    public function getParticipants();

    /**
     * Set the date and time this session started
     *
     * @param   \DateTime  $date
     * @return  Session
     */
    public function setDate(\DateTime $date);

    /**
     * Get the date and time this session started
     *
     * @return  \DateTime
     */
    public function getDate();

    /**
     * Set the date string originally parsed
     *
     * @param   string  $date_string
     * @return  Session
     */
    public function setDateString($date_string);

    /**
     * Get the date string originally parsed
     *
     * @return  string
     */
    public function getDateString();

    /**
     * Set max number of laps this session could of lasted
     *
     * @param   int      $max_laps
     * @return  Session
     */
    public function setMaxLaps($max_laps);

    /**
     * Get max number of laps this session could of lasted
     *
     * @return  int
     */
    public function getMaxLaps();

    /**
     * Set max time in minutes this session could of lasted
     *
     * @param   int      $max_minutes
     * @return  Session
     */
    public function setMaxMinutes($max_minutes);

    /**
     * Get max time in minutes this session could of lasted
     *
     * @return  int
     */
    public function getMaxMinutes();

    /**
     * Set the chats sent within this session
     *
     * @param   array    $chats
     * @return  Session
     */
    public function setChats(array $chats);

    /**
     * Add chat to this session
     *
     * @param   Chat     $chat
     * @return  Session
     */
    public function addChat(Chat $chat);

    /**
     * Get the chats sent within this session
     *
     * @return  array
     */
    public function getChats();

    /**
     * Set the incidents within this session
     *
     * @param   array    $incidents
     * @return  Session
     */
    public function setIncidents(array $incidents);

    /**
     * Add incident to this session
     *
     * @param   Incident  $incident
     * @return  Session
     */
    public function addIncident(Incident $incident);

    /**
     * Get the incidents within this session
     *
     * @return  array
     */
    public function getIncidents();

    /**
     * Get the incidents for reviewing
     *
     * @return  array
     */
    public function getIncidentsForReview();

    /**
     * Set the penalties within this session
     *
     * @param   array    $penalties
     * @return  Session
     */
    public function setPenalties(array $penalties);

    /**
     * Add penalty to this session
     *
     * @param   Penalty  $penalty
     * @return  Session
     */
    public function addPenalty(Penalty $penalty);

    /**
     * Get the penalties within this session
     *
     * @return  array
     */
    public function getPenalties();

    /**
     * Set the mod being used in this sessioh
     *
     * @param   string   $mod
     * @return  Session
     */
    public function setMod($mod);

    /**
     * Get the mod being used in this session
     *
     * @return  string
     */
    public function getMod();

    /*
     * Set the allowed vehicles in this session
     *
     * @param   array    $vehicles
     * @return  Session
     */
    public function setAllowedVehicles(array $vehicles);


    /**
     * Add allowed vehicle to this session
     *
     * @param   Vehicle  $vehicle
     * @return  Session
     */
    public function addAllowedVehicle(Vehicle $vehicle);

    /**
     * Get the allowed vehicles in this session
     *
     * @return  array
     */
    public function getAllowedVehicles();

    /**
     * Set whether setups were fixed
     *
     * @param   boolean  $setup_fixed
     * @return  Session
     */
    public function setSetupFixed($setup_fixed);

    /**
     * Returns whether setups were fixed for this session. Returns null when
     * it's unknown.
     *
     * @return  boolean
     */
    public function isSetupFixed();

    /**
     * Set the other settings that were used in this session. Assoc array
     * is expected
     *
     * @param   array    $settings
     * @return  Session
     */
    public function setOtherSettings(array $settings);

    /**
     * Add a other setting
     *
     * @param   string    $setting
     * @param   mixed     $value
     * @return  Session
     */
    public function addOtherSetting($setting, $value);


    /**
     * Get the other settings used within this session. Returns an assoc array
     *
     * @return  array
     */
    public function getOtherSettings();


    /**
     * Get the laps sorted by time (ASC)
     *
     * @return  array
     */
    public function getLapsSortedByTime();

    /**
     * Returns the (completed) best lap for this session
     *
     * @return  Lap|null
     */
    public function getBestLap();

    /**
     * Get the laps by lap number sorted by time (ASC)
     *
     * @param   int    $lap_number
     * @return  array
     */
    public function getLapsByLapNumberSortedByTime($lap_number);

    /**
     * Get the best lap by lap number
     *
     * @param   int  $lap_number
     * @return  Lap
     */
    public function getBestLapByLapNumber($lap_number);

    /**
     * Get best laps grouped participant. The same participant is not returned
     * twice.
     *
     * @return  array
     */
    public function getBestLapsGroupedByParticipant();

    /**
     * Get the laps sorted by sector
     *
     * @param  int  $sector
     */
    public function getLapsSortedBySector($sector);

    /**
     * Get the best lap by sector
     *
     * @param   int  $sector
     * @return  Lap
     */
    public function getBestLapBySector($sector);

    /**
     * Returns the best laps of a given sector grouped by participant. This
     * will not return a lap of the same participant twice. So this result can
     * be used for a ranking per sector.
     *
     * @param   int  $sector
     * @return  array
     */
    public function getBestLapsBySectorGroupedByParticipant($sector);

    /**
     * Returns the laps sorted by a given sector and lap number
     *
     * @param   int  $sector
     * @param   int  $lap_number
     * @return  array
     */
    public function getLapsSortedBySectorByLapNumber($sector, $lap_number);

    /**
     * Get the best lap by sector and lap number
     *
     * @param   int  $sector
     * @param   int  $lap_number
     * @return  Lap
     */
    public function getBestLapBySectorByLapNumber($sector, $lap_number);

    /**
     * Get bad laps that are above a percent of the best lap. Defaults to the
     * 107% rule.
     *
     * @param  int  $above_percent
     */
    public function getBadLaps($above_percent = 107);

    /**
     * Get the participant that led the most
     *
     * @return  Participant|null
     */
    public function getLedMostParticipant();

    /**
     * Get the winning participant
     *
     * @return  Participant|null
     */
    public function getWinningParticipant();

    /**
     * Get the leading participant for a given lap number
     *
     * @param   int  $lap_number
     * @return  Participant
     */
    public function getLeadingParticipant($lap_number);

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
    public function getLeadingParticipantByElapsedTime($lap_number);


    /**
     * Get the participants sorted by consistency
     *
     * @return  array
     */
    public function getParticipantsSortedByConsistency();


    /**
     * Get number of laps this session lasted
     *
     * @return  int
     */
    public function getLastedLaps();


    /**
     * Get the max position within this session. Will search all laps for
     * highest position number. This method can be more safe instead of the
     * number of participants when need to know the max position, for example
     * for graphs. Sometimes the positions are higher than the actual number
     * of participants (e.g. in rfactor results).
     *
     * @return  int
     */
    public function getMaxPosition();

    /**
     * Splits the session into multiple sessions by vehicle class. The sessions
     * are sorted by class name (asc)
     *
     * @return  array
     */
    public function splitByVehicleClass();

}

