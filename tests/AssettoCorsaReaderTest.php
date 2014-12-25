    <?php
use Simresults\Data_Reader_AssettoCorsa;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Assetto Corsa reader
 *
 * TODO: Rebuild using a much bigger and better log when the game offers
 * this.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaReaderTest extends PHPUnit_Framework_TestCase {

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
        $reader = new Data_Reader_AssettoCorsa('Unknown data for reader');
    }


    /***
    **** Simple tests that do not fit in the 1 log file
    ***/


    /**
     * Test exception when the log file has no session included
     *
     * @expectedException Simresults\Exception\Reader
     */
    public function testCreatingNewAssettoCorsaReaderWithNoSessions()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa/nosessions.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();
    }


    /**
     * Test reading drift data as session settings data
     *
     * TODO: Find a clean API for this when more extra info is available from
     *       AC.
     */
    public function testReadingSessionDriftData()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa/offline_drift_session.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Validate drift data
        $this->assertSame(
            array(
                'Drift points'   =>  29,
                'Drift combos'   =>  2,
                'Drift levels'   =>  1,
            ),
            $session->getOtherSettings()
        );
    }

    /**
     * Test reading qualify and race sessions from the AC 1.0 release. Session
     * names are changed.
     */
    public function testReadingAlternativeSessionTypeNames()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa/qualify.and.race.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get first session
        $session = $reader->getSession(1);

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Qualify', $session->getName());

        // Get second session
        $session = $reader->getSession(2);

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame('Race', $session->getName());
    }

     /**
     * Test reading qualify positions. This als covers proper laps. Laps are
     * missing for qualify and we need to parse best laps, which results in
     * proper positions
     */
    public function testReadingQualifyPositions()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa/qualify.and.race.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get first session
        $session = $reader->getSession(1);

        // Get first participant
        $participants = $session->getParticipants();
        $participant = $participants[0];

        $this->assertSame('Petr Dolezal',
                          $participant->getDriver()->getName());

        // Get last participant
        $participant = $participants[count($participants)-1];
        $this->assertSame('Tomas Ledenyi',
                          $participant->getDriver()->getName());
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
        $this->assertSame('Quick Race', $session->getName());
        $this->assertSame(5, $session->getMaxLaps());
        $this->assertSame(30, $session->getMaxMinutes());
        $this->assertSame(5, $session->getLastedLaps());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('Unknown or offline', $server->getName());
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
        $this->assertSame('silverstone', $track->getVenue());
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

        $this->assertSame('Alex Cardinali',
                          $participant->getDriver()->getName());
        $this->assertSame('mclaren_mp412c',
                          $participant->getVehicle()->getName());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());

        // Get last participant
        $participant = $participants[11];
        $this->assertSame('Hugh Lemont',
                          $participant->getDriver()->getName());
        $this->assertSame('lotus_exige_scura',
                          $participant->getVehicle()->getName());
        $this->assertSame(12, $participant->getPosition());
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

        // Validate we have 7 laps
        $this->assertSame(5, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertNull($lap->getPosition());
        $this->assertSame(173.883, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(69.147, $sectors[0]);
        $this->assertSame(64.934, $sectors[1]);
        $this->assertSame(39.802, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(142.660, $lap->getTime());
        $this->assertSame(173.883, $lap->getElapsedSeconds());

        // Validate extra positions
        $laps = $participants[3]->getLaps();
        $this->assertNull($laps[0]->getPosition());
        $this->assertSame(6, $laps[1]->getPosition());
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa/offline_quick_race_session.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}