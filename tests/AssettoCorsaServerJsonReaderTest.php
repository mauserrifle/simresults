    <?php
use Simresults\Data_Reader_AssettoCorsaServerJson;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Assetto Corsa Server JSON reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaServerJsonReaderTest extends PHPUnit_Framework_TestCase {

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
     * Test exception when no data is supplied
     *
     * @expectedException Simresults\Exception\CannotReadData
     */
    public function testCreatingNewAssettoCorsaReaderWithInvalidData()
    {
        $reader = new Data_Reader_AssettoCorsaServerJson('Unknown data for reader');
    }



    /**
     * Test qualify sessions
     */
    public function testQualifySession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server-json/'.
            '2015_10_17_9_30_QUALIFY.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Timo Haapala',
            $participants[0]->getDriver()->getName());
        $this->assertSame('blackbird0011',
            $participants[8]->getDriver()->getName());
    }

    /**
     * Test reading a log with null events (not array) without errors
     */
    public function testReadingNullEventsWithoutExceptions()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server-json/'.
            'race.changed.with.null.events.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();
    }

     /**
     * Test tyre info
     */
    public function testReadingTyreInfo()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server-json/tyre.info.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(1);

        $participants = $session->getParticipants();

        $this->assertSame('S', $participants[0]->getLap(1)->getFrontCompound());
        $this->assertSame('M', $participants[1]->getLap(1)->getRearCompound());
    }

     /**
     * Test servername
     */
    public function testReadingServer()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server-json/tyre.info.json');

        $session = Data_Reader::factory($file_path)->getSession();
        $this->assertSame('Custom server', $session->getServer()->getName());
    }
     /**
     * Test ignoring duplicate result info
     */
    public function testIgnoringDuplicateResultInfo()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server-json/duplicate.result.info.for.miguel.json');

        $session = Data_Reader::factory($file_path)->getSession();
        $participants = $session->getParticipants();

        // Make sure driver is not DNF anymore
        $this->assertSame(Participant::FINISH_NORMAL,
            $participants[4]->getFinishStatus());
    }




    /***
    **** Below tests use 1 race log file
    ***/

    /**
     * Test reading the session
     */
    public function testReadingSession()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(10, $session->getMaxLaps());
        $this->assertSame(0, $session->getMaxMinutes());
        $this->assertSame(10, $session->getLastedLaps());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('Unknown', $server->getName());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Assetto Corsa', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('monza', $track->getVenue());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get first participant
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();
        $participant = $participants[0];

        $this->assertSame('Andrea Sorlini',
                          $participant->getDriver()->getName());
        $this->assertSame('lotus_exos_125',
                          $participant->getVehicle()->getName());
        $this->assertSame(20, $participant->getVehicle()->getBallast());
        $this->assertSame('0_Lotus', $participant->getVehicle()->getSkin());
        $this->assertSame('TEST TEAM',
                          $participant->getTeam());
        $this->assertSame('76561198213775428',
                          $participant->getDriver()->getDriverId());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(1003.035, $participant->getTotalTime());

        // Get one of last participants. Not the most last because they
        // vary in order because of different usort behavior due pivots
        // in PHP 5.6 vs HHVM/PHP7. But thats ok, those are all meaningless
        // DNF anyway
        $participant = $participants[count($participants)-5];
        $this->assertSame('YoloAtGames',
                          $participant->getDriver()->getName());
        $this->assertSame('lotus_exos_125',
                          $participant->getVehicle()->getName());
        $this->assertSame(0, $participant->getVehicle()->getBallast());
        $this->assertSame('0_Lotus', $participant->getVehicle()->getSkin());
        $this->assertSame('',
                          $participant->getTeam());
        $this->assertSame('76561198156462312',
                          $participant->getDriver()->getDriverId());
        $this->assertSame(19, $participant->getPosition());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());
        $this->assertSame(0, $participant->getTotalTime());


        // Test participants to have a no finish status when they did not
        // succeed in 50% laps
        $this->assertSame(Participant::FINISH_NONE,
            $participants[10]->getFinishStatus());
    }

    /**
     * Test reading laps of participants
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Get the laps of first participants
        $laps = $participants[0]->getLaps();

        // Validate we have 10 laps
        $this->assertSame(10, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertNull($lap->getPosition());
        $this->assertSame(104.357, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(38.437, $sectors[0]);
        $this->assertSame(34.564, $sectors[1]);
        $this->assertSame(31.356, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(100.735, $lap->getTime());
        $this->assertSame(104.357, $lap->getElapsedSeconds());

        // Validate extra positions
        $laps = $participants[3]->getLaps();
        $this->assertNull($laps[0]->getPosition());
        $this->assertSame(5, $laps[1]->getPosition());
    }


    /**
     * Test reading incidents between cars
     */
    public function testIncidents()
    {
        // Get participants
        $incidents = $this->getWorkingReader()->getSession()
            ->getIncidents();

        // Validate first incident
        $this->assertSame(
            'PPolaina reported contact with another vehicle '
           .'Tabak. Impact speed: 7.37918',
            $incidents[0]->getMessage());
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server-json/2015_10_17_9_49_RACE.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}