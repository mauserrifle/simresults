    <?php
use Simresults\Data_Reader_Iracing;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for the iRacing reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class IracingReaderTest extends \PHPUnit\Framework\TestCase {

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
    **** Below tests uses log with three sessions (race, qualy, practice)
    ***/

    /**
     * Test reading the sessions
     */
    public function testReadingMultipleSessions()
    {
        // Get sessions
        $sessions = $this->getWorkingReader()->getSessions();

        // Validate the number of sessions
        $this->assertSame(3, sizeof($sessions));

        // Get last session
        $session = $sessions[2];


        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame('Race', $session->getName());
        // $this->assertSame(0, $session->getMaxLaps());
        // $this->assertSame(15, $session->getMaxMinutes());
        $this->assertSame('2021-01-08T19:00:12Z', $session->getDateString());
        $this->assertSame(1610132412, $session->getDate()->getTimestamp());
        // $allowed_vehicles = $session->getAllowedVehicles();
        // $this->assertSame('tatuusfa1', $allowed_vehicles[0]->getName());


        // Test second session
        $session = $sessions[1];

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Open Qualifying', $session->getName());


        // Get first session
        $session = $sessions[0];

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());
        $this->assertSame('Open Practice', $session->getName());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame("NXTGEN S19 PMR Porsche League", $server->getName());
    }


    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('iRacing', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Mount Panorama Circuit', $track->getVenue());
    }


    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        $participants = $this->getWorkingReader()->getSession(3)
            ->getParticipants();

        // Validate number of participants
        $this->assertSame(17, count($participants));

        // Validate first participant
        $participant = $participants[0];

        $this->assertSame('Dave N',
                          $participant->getDriver()->getName());
        $this->assertSame('Porsche 911', $participant->getVehicle()->getName());
        $this->assertSame('357237', $participant->getDriver()->getDriverId());
        $this->assertSame(8, $participant->getVehicle()->getNumber());
        $this->assertSame('Porsche 911 GT3 Cup', $participant->getVehicle()->getClass());
        $this->assertSame('UK and I', $participant->getTeam());
        $this->assertSame(1, $participant->getGridPosition());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(129.6209, $participant->getTotalTime());
    }


    /**
     * Test reading laps of participants
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession(3)
            ->getParticipants();

        // Get the laps of first participant
        $laps = $participants[0]->getLaps();

        // Validate number of laps
        $this->assertSame(5, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // First lap has no time
        $lap = $laps[0];
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition()); // Dummy position based on grid position
        $this->assertNull($lap->getTime());
        $this->assertNull($lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());
        $this->assertSame(array(), $lap->getSectorTimes());

        // Lap 4 is best lap time, so time is known
        $lap = $laps[3];
        $this->assertSame(4, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition()); // Dummy position based on grid position
        $this->assertSame(129.6209, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());
        $this->assertSame(array(), $lap->getSectorTimes());
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
        $file_path = realpath(__DIR__.'/logs/iracing/iracing-result-36788868.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}
