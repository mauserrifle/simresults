    <?php
use Simresults\Data_Reader_SecondMonitor;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for Second Monitor reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class SecondMonitorReaderTest extends \PHPUnit\Framework\TestCase {

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
        $this->assertSame(69, $session->getLastedLaps());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Automobilista 2', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Indianapolis', $track->getVenue());
        $this->assertSame('Indianapolis Motor Speedway Road Course', $track->getCourse());
        $this->assertSame(3888.773681640625, $track->getLength());
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

        $this->assertSame('TacticalNuclearPingu',
                          $participant->getDriver()->getName());
        $this->assertSame('Cadillac DPi-VR',
                          $participant->getVehicle()->getName());
        $this->assertSame('TacticalNuclearPingu', $participant->getDriver()->getDriverId());
        $this->assertSame('DPI', $participant->getVehicle()->getClass());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame(2, $participant->getGridPosition());
        $this->assertSame(2, $participant->getClassGridPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
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

        // Validate number of laps
        $this->assertSame(69, count($laps));

        $driver = $participants[0]->getDriver();
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        $this->assertSame(124.674, $lap->getTime());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());
        $this->assertSame(false, $lap->isValidForBest());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(41.237, $sectors[0]);
        $this->assertSame(40.586, $sectors[1]);
        $this->assertSame(42.851, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        $this->assertSame(78.525, $lap->getTime());
        $this->assertSame(true, $lap->isValidForBest());
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
        $file_path = realpath(__DIR__.'/logs/automobilista2-secondmonitor/race-multiclass.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }

}