    <?php
use Simresults\Data_Reader_RaceRoom;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

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
        $this->assertSame(1437748751, $date->getTimestamp());
        $this->assertSame('UTC', $date->getTimezone()->getName());


        // Get second session
        $session = $sessions[1];

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(1437748751, $date->getTimestamp());
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
        $this->assertSame('[[KOC]] R3E DTM MASTERS R02', $server->getName());
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
        $this->assertSame('EuroSpeedway Lausitz', $track->getVenue());
    }


    /**
     * Test reading the participants and their laps of a session
     */
    public function testReadingSessionParticipantsAndLaps()
    {
        // Test first participant
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();
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


        // Test last participant
        $participant = $participants[count($participants)-1];
        $this->assertSame('BigShot', $participant->getDriver()->getName());
        $this->assertSame('BMW M3 DTM', $participant->getVehicle()->getName());
        $this->assertSame(15, $participant->getPosition());
        $this->assertNull($participant->getLap(1));
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
                'FlagRules'          =>  'All',
                'CutRules'           =>  'StopAndGo',
                'RaceSeriesFormat'   =>  'CustomRRE',
                'WreckerPrevention'  =>  'On',
                'MandatoryPitstop'   =>  'Off',
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