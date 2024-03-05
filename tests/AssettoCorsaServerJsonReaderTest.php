    <?php
use Simresults\Data_Reader_AssettoCorsaServerJson;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for the Assetto Corsa Server JSON reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaServerJsonReaderTest extends \PHPUnit\Framework\TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        error_reporting(E_ALL);
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
        $this->assertNull($session->getName());
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
        $this->assertSame('full course', $track->getCourse());
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
        $this->assertSame(15, $participant->getVehicle()->getRestrictor());
        $this->assertSame('0_Lotus', $participant->getVehicle()->getSkin());
        $this->assertSame('TEST TEAM',
                          $participant->getTeam());
        $this->assertSame('76561198213775428',
                          $participant->getDriver()->getDriverId());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(1003.035, $participant->getTotalTime());

        // Test last (DNF) participant. But not check specific driver
        // attributes because the DNF drivers vary in order because of
        // different usort behavior for equal values in PHP 5.6/HHVM/PHP7/PHP8.
        // But thats ok, those are all meaningless DNF anyway
        $participant = $participants[count($participants)-1];
        $this->assertSame('lotus_exos_125', $participant->getVehicle()->getName());
        $this->assertSame(0, $participant->getVehicle()->getBallast());
        $this->assertSame(0, $participant->getVehicle()->getRestrictor());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());
        $this->assertSame(0, $participant->getTotalTime());


        // Test participants to have a no finish status when they did not
        // succeed in 50% laps
        $participant = $participants[10];
        $this->assertSame(Participant::FINISH_NONE,
            $participant->getFinishStatus());
        $this->assertSame('PPolaina', $participant->getDriver()->getName());
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
        $this->assertSame(1, $lap->getPosition());
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
        $this->assertSame(5, $laps[0]->getPosition());
        $this->assertSame(7, $laps[1]->getPosition());
    }


    /**
     * Test reading incidents between cars
     */
    public function testIncidents()
    {
        // Get participants
        $session = $this->getWorkingReader()->getSession();

        $incidents = $session->getIncidents();
        $participants = $session->getParticipants();

        // Validate first incident
        $this->assertSame(Incident::TYPE_CAR, $incidents[0]->getType());
        $this->assertSame($participants[10], $incidents[0]->getParticipant());
        $this->assertSame($participants[7], $incidents[0]->getOtherParticipant());
        $this->assertSame(
            'PPolaina reported contact with another vehicle '
           .'Tabak. Impact speed: 7.37918',
            $incidents[0]->getMessage());

        $this->assertSame(Incident::TYPE_ENV, $incidents[14]->getType());
        $this->assertSame($participants[1], $incidents[14]->getParticipant());
        $this->assertNull($incidents[14]->getOtherParticipant());
        $this->assertSame(
            'End 222 reported contact with environment. Impact speed: 77.08348',
            $incidents[14]->getMessage());
    }






    /***
    **** Below tests use different logs to test differences and bugs
    ***/


    /**
     * Test exception when no data is supplied
     */
    public function testCreatingNewAssettoCorsaReaderWithInvalidData()
    {
        $this->expectException(\Simresults\Exception\CannotReadData::class);
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
        $this->assertNull($session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Timo Haapala',
            $participants[0]->getDriver()->getName());
        $this->assertSame('blackbird0011',
            $participants[7]->getDriver()->getName());
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
     * Test filtering empty driver name
     */
    public function testFilteringEmptyDriverName()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server-json/tyre.info.json');

        $session = Data_Reader::factory($file_path)->getSession();
        $participants = $session->getParticipants();

        $this->assertCount(6, $participants);
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

     /**
     * Test no exceptions when BallastKG is missing
     */
    public function testFixingBallastExceptions()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server-json/'.
            'no.ballastkg.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test cuts
     */
    public function testCuts()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server-json/'.
            '2015_10_17_9_30_QUALIFY.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Test first known cut
        $lap = $participants[0]->getLap(2);
        $cuts = $lap->getCuts();

        // Not values known
        $this->assertSame(null, $cuts[0]->getCutTime());
        $this->assertSame(null, $cuts[0]->getTimeSkipped());
        $this->assertSame(null, $cuts[0]->getElapsedSeconds());
        $this->assertSame(null, $cuts[0]->getDate());

        // TODO: Should we invalidate the lap on non-race or leave it
        // to AC to decide? Need community feedback on this one before
        // enabling
        // $this->assertSame(null, $lap->getTime());
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