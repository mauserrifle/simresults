<?php
use Simresults\Data_Reader;
use Simresults\Data_Reader_AssettoCorsaEvo;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Assetto Corsa Evo JSON reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaEvoReaderTest extends \PHPUnit\Framework\TestCase {

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
        $this->assertSame(29, $session->getLastedLaps());
        $this->assertSame(null, $session->getMaxLaps());
        $this->assertSame(50, $session->getMaxMinutes());
//        $this->assertSame(array(
//            'isWetSession' => 1
//        ), $session->getOtherSettings());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame("Ox seiner", $server->getName());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Assetto Corsa Evo', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Fuji Speedway', $track->getVenue());
        $this->assertSame('GP', $track->getCourse());
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

        $this->assertSame('Nico der X',
            $participant->getDriver()->getName());
        $this->assertSame('Ferrari 296 GT3',
            $participant->getVehicle()->getName());
        $this->assertSame('76561198935804583', $participant->getDriver()->getDriverId());
        $this->assertSame(3, $participant->getVehicle()->getNumber());
        // TODO: Parse class and cup?
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        //$this->assertSame('Overall', $participant->getVehicle()->getCup());
        $this->assertSame(null, $participant->getTeam());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(3050.033, $participant->getTotalTime());

        // Second
        $participant = $participants[1];
        $this->assertSame(2, $participant->getPosition());
        $this->assertSame(2, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        //$this->assertSame('Pro-Am', $participant->getVehicle()->getCup());
        $participant = $participants[2];
        $this->assertSame(3, $participant->getPosition());
        $this->assertSame(3, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        //$this->assertSame('Overall', $participant->getVehicle()->getCup());
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

        // Validate laps
        $this->assertSame(29, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(3, $lap->getPosition());
        // First sector based on lap 2+ averages + grid start correction.
        // We cannot trust lap 1 sector 1  and total time. The  timer starts
        // when the player enters the session or presses drive.
        $this->assertSame(108.197, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(49.394, $sectors[0]);
        $this->assertSame(58.803, $sectors[1]);
        $this->assertSame(0.0, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(3, $lap->getPosition());
        $this->assertSame(103.689, $lap->getTime());
        $this->assertSame(108.197, $lap->getElapsedSeconds());

        // Validate extra positions
        $laps = $participants[2]->getLaps();
        $this->assertSame(4, $laps[0]->getPosition());
        $this->assertSame(4 , $laps[1]->getPosition());
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
        $reader = new Data_Reader_AssettoCorsaEvo('Unknown data for reader');
    }

    /**
     * Test qualify sessions
     */
    public function testQualifySession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-evo/'.
            'qualify.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertNull($session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Daniel Nx',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Juegor Jx',
            $participants[2]->getDriver()->getName());
    }

    /**
     * Test practice sessions
     */
    public function testPracticeSession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-evo/'.
            'practice.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());
        $this->assertNull($session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Nico der X',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Daniel Nx',
            $participants[2]->getDriver()->getName());
    }

    /**
     * Test warmup sessions
     */
    public function testWarmupSession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-evo/'.
            'warmup.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_WARMUP, $session->getType());
        $this->assertNull($session->getName());

        // No participants because no lap data that can assign vehicle
        $participants = $session->getParticipants();
        $this->assertEmpty($participants);
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa-evo/race.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }


}