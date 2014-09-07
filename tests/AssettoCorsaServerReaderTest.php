<?php
use Simresults\Data_Reader_AssettoCorsaServer;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Assetto Corsa Server reader
 *
 * TODO:
 * - Chats
 * - Looped sessions??
 * -
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaServerReaderTest extends PHPUnit_Framework_TestCase {

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
    public function testCreatingNewAssettoCorsaServerReaderWithInvalidData()
    {
        $reader = new Data_Reader_AssettoCorsaServer('Unknown data for reader');
    }


    /***
    **** Below tests use 1 big server log file. There are total of 43 sessions
    ****
    **** session 3 is 1 driver qualify (line 104)
    **** session 37 is multiple driver qualify (line 814)
    **** session 38 is multiple driver race (line 1007)
    ***/

    /**
     * Test reading multiple sessions. Sessiosn without data should be ignored
     * and not parsed.
     */
    public function testReadingMultipleSessions()
    {
        // Get sessions
        $sessions = $this->getWorkingReader()->getSessions();

        // Validate the number of sessions. All sessions without data are
        // filtered out
        $this->assertSame(5, sizeof($sessions));

        // Get first session
        $session = $sessions[0];

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Clasificacion', $session->getName());
        $this->assertSame(0, $session->getMaxLaps());
        $this->assertSame(15, $session->getMaxMinutes());
        $this->assertSame(4, $session->getLastedLaps());
        $allowed_vehicles = $session->getAllowedVehicles();
        $this->assertSame('tatuusfa1', $allowed_vehicles[0]->getName());

        // Get second session
        $session = $sessions[1];

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Clasificacion', $session->getName());
        $this->assertSame(0, $session->getMaxLaps());
        $this->assertSame(15, $session->getMaxMinutes());
        $this->assertSame(4, $session->getLastedLaps());

        // Get third session
        $session = $sessions[2];

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame('Carrera', $session->getName());
        $this->assertSame(6, $session->getMaxLaps());
        $this->assertSame(0, $session->getMaxMinutes());
        $this->assertSame(6, $session->getLastedLaps());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('AssettoCorsa.ForoArgentina.Net #2 Test', $server->getName());
        $this->assertTrue($server->isDedicated());
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
        $this->assertSame('doningtonpark', $track->getVenue());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get participants of third session
        $participants = $this->getWorkingReader()->getSession(3)
            ->getParticipants();

        $participant = $participants[0];

        $this->assertSame('Leonardo Ratafia',
                          $participant->getDriver()->getName());
        $this->assertSame('tatuusfa1*',
                          $participant->getVehicle()->getName());
        $this->assertSame(674.296, $participant->getTotalTime());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(3, $participant->getGridPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());

        // Get last participant with no laps
        $participant = $participants[5];
        $this->assertSame('Gerardo Primo',
                          $participant->getDriver()->getName());
        $this->assertSame('tatuusfa1*',
                          $participant->getVehicle()->getName());
        $this->assertSame(0, $participant->getTotalTime());
        $this->assertSame(6, $participant->getPosition());
        $this->assertNull($participant->getGridPosition());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());


        // Get participants of fith session
        $participants = $this->getWorkingReader()->getSession(3)
            ->getParticipants();

        // Test second participant having finish status. It was DNF for wrong
        // reasons (bug)
        $this->assertSame(Participant::FINISH_NORMAL,
            $participants[1]->getFinishStatus());
    }

    /**
     * Test reading laps of participants
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants of third session
        $participants = $this->getWorkingReader()->getSession(3)
            ->getParticipants();

        // Get the laps of first participant
        $laps = $participants[0]->getLaps();

        // Validate number of laps
        $this->assertSame(6, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertNull($lap->getPosition());
        // 01:41.9000
        $this->assertSame(101.9000, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        // 03:12.4800
        $this->assertSame(192.4800, $lap->getTime());
        $this->assertSame(101.9000, $lap->getElapsedSeconds());
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/output.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}