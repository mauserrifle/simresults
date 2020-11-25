    <?php
use Simresults\Data_Reader_RaceRoom;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for the RaceRoom reader
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class RaceRoomReaderTest extends PHPUnit_Framework_TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        error_reporting(E_ALL);
    }



    /**
     * Test reading laps using the best lap and check for unknown usernames
     */
    public function testLogWithoutLapsAndUnknownUsernames()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/no.laps.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(1);

        // Test first participant
        $participants = $session->getParticipants();
        $participant = $participants[0];

        $this->assertSame('YHKIM', $participant->getDriver()->getName());
        $this->assertSame('DTM Mercedes AMG C-Coupé',
                          $participant->getVehicle()->getName());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(80.1380, $participant->getLap(1)->getTime());


        // Test participant without name
        $this->assertSame('unknown', $participants[5]->getDriver()->getName());

        // Test FullName fallback
        $this->assertSame('junkim', $participants[6]->getDriver()->getName());

        // Test favor FullName over Username
        $this->assertSame('DunkinCupF', $participants[7]->getDriver()->getName());



        // Test last participant
        $participant = $participants[count($participants)-1];
        $this->assertSame('BigShot', $participant->getDriver()->getName());
        $this->assertSame('BMW M3 DTM', $participant->getVehicle()->getName());
        $this->assertSame(15, $participant->getPosition());
        $this->assertNull($participant->getLap(1));
    }


    /**
     * Test reading bestlap when times are missing for qualify
     */
    public function testBestQualifyLapOnMissingLapsData()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/only.bestlap.for.qualy.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(2);
        $participants = $session->getParticipants();

        //-- Validate
        $this->assertNotNull($participants[0]->getBestLap());
    }


    /**
     * Test pit stop marking and skipping negative lap times
     */
    public function testPitLapsAndSkippingNegativeLapTimes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/pit.lap.and.negative.lap.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(2);

        $participants = $session->getParticipants();
        $participant = $participants[15];

        $this->assertTrue($participant->getLap(3)->isPitLap());
        $this->assertNull($participant->getLap(4));
    }

    /**
     * Test supporting race2
     */
    public function testRace2Session()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/log.with.race2.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(3);

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame('Race2', $session->getName());
    }

    /**
     * Test finish status "none"
     */
    public function testFinishStatusNone()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/finish.status.none.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(1);
        $participants = $session->getParticipants();

        //-- Validate
        $this->assertSame(Participant::FINISH_NORMAL,
            $participants[0]->getFinishStatus());
    }

    /**
     * Test reading incidents
     */
    public function testReadingIncidents()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/log.with.incidents.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(3);
        $participants = $session->getParticipants();
        $incidents = $session->getIncidents();

        // Validate first incident
        $this->assertSame(
            'LAP 1, Magnus Stjerneby, Going off track, Points: 1',
            $incidents[0]->getMessage());
        $this->assertSame(Incident::TYPE_OTHER, $incidents[0]->getType());
        $this->assertSame($participants[0], $incidents[0]->getParticipant());

        $this->assertSame(
            'LAP 1, Jason Monds, Car to car collision, Points: 3',
            $incidents[1]->getMessage());
        $this->assertSame(Incident::TYPE_CAR, $incidents[1]->getType());
        $this->assertSame($participants[1], $incidents[1]->getParticipant());

    }

    /**
     * Test reading new incident indexes in json
     */
    public function testReadingNewIncidentIndexes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/race.with.new.incident.indexes.and.sector.times.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(3);
        $participants = $session->getParticipants();
        $incidents = $session->getIncidents();

        // Validate first incident
        $this->assertSame(
            'LAP 1, Bence, Invalid Lap, Points: 1',
            $incidents[0]->getMessage());
        $this->assertSame(Incident::TYPE_OTHER, $incidents[0]->getType());
        $this->assertSame($participants[0], $incidents[0]->getParticipant());
    }

    /**
     * Test reading sectors
     */
    public function testReadingSectors()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/race.with.new.incident.indexes.and.sector.times.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(3);
        $participants = $session->getParticipants();

        // Get sector times
        $sectors = $participants[0]->getBestLap()->getSectorTimes();

        // Validate sectors
        $this->assertSame(22.478, $sectors[0]);
        $this->assertSame(40.117, $sectors[1]);
        $this->assertSame(34.557, $sectors[2]);
    }

    /**
     * Test ignoring invalid laps
     */
    public function testIgnoringInvalidLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/raceroom-server/qualify.with.invalid.laps.json');

        // Get session
        $session = Data_Reader::factory($file_path)->getSession(2);
        $participants = $session->getParticipants();
        $participant = $participants[0];

        $this->assertSame('Bence', $participant->getDriver()->getName());
        $this->assertNull($participant->getLap(1)->getTime());
    }


    /***
    **** Below tests use 1 race log file
    ***/


    /**
     * Test reading multiple sessions. Sessiosn without data should be ignored
     * and not parsed.
     */
    public function testReadingMultipleSessions()
    {
        // Get sessions
        $sessions = $this->getWorkingReader()->getSessions();

        // Validate the number of sessions
        $this->assertSame(2, sizeof($sessions));


        // Get first session
        $session = $sessions[0];
        $date = $session->getDate();

        //-- Validate

        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame(1459003463, $date->getTimestamp());
        $this->assertSame('UTC', $date->getTimezone()->getName());


        // Get second session
        $session = $sessions[1];

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertNull($session->getName());
        $this->assertSame(1459003463, $date->getTimestamp());
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('!grass-saba!!', $server->getName());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('RaceRoom Racing Experience', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Portimao Circuit', $track->getVenue());
    }


    /**
     * Test reading the participants and their laps of a session
     */
    public function testReadingSessionParticipantsAndLaps()
    {
        // Test first participant
        $participants = $this->getWorkingReader()->getSession(2)
            ->getParticipants();
        $participant = $participants[0];

        $this->assertSame('chin matsuo', $participant->getDriver()->getName());
        $this->assertSame('Ford GT GT1',
                          $participant->getVehicle()->getName());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1216.072, $participant->getTotalTime());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(115.0910, $participant->getLap(1)->getTime());
        $this->assertSame(100.417, $participant->getLap(5)->getTime());

        // Test DQ participant
        $participant = $participants[count($participants)-2];
        $this->assertSame(Participant::FINISH_DQ,
            $participant->getFinishStatus());

        // Test last participant
        $participant = $participants[count($participants)-1];
        $this->assertSame('Akihiro Nakao', $participant->getDriver()->getName());
        $this->assertSame('Saleen S7R', $participant->getVehicle()->getName());
        $this->assertSame(4, $participant->getPosition());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());
    }

    /**
     * Test reading session settings
     */
    public function testReadingSessionSettings()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        // Validate drift data
        $this->assertSame(
            array(
                'Experience'         =>  'RaceRoom Experience',
                'Difficulty'         =>  'GetReal',
                'FuelUsage'          =>  'Normal',
                'MechanicalDamage'   =>  'Off',
                'FlagRules'          =>  'Black',
                'CutRules'           =>  'SlowDown',
                'RaceSeriesFormat'   =>  'DTM2013',
                'WreckerPrevention'  =>  'Off',
                'MandatoryPitstop'   =>  'Off',
            ),
            $session->getOtherSettings()
        );
    }



    /**
     * Get a working reader
     */
    protected function getWorkingReader()
    {
        static $reader;

        // Reader aready created
        if ($reader)
        {
            return $reader;
        }

        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/raceroom-server/qualify.and.race.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}