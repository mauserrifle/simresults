<?php
use Simresults\Data_Reader_Race07;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Race07 reader
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Race07Test extends PHPUnit_Framework_TestCase {

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
    public function testCreatingNewRace07ReaderWithInvalidData()
    {
        $reader = new Data_Reader_Race07('Unknown data for reader');
    }


    /***
    **** Simple tests that do not fit in the full race log used for testing.
    **** Most of the below tests are done on modfied files
    ***/


    /**
     * Test non-zero based logs on laps. Found on F1 challenge log files
     */
    public function testNonZeroBasedLaps()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/race07/prosracing Clio Cup_2013_02_12_22_06_19_'
                   .'Race2_changed_lap_numbers.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Get participant "flashdepau"
        $participants = $session->getParticipants();
        $laps = $participants[1]->getLaps();

        // Validate using time, to prevent any false positives due to number
        // fixes
        $this->assertSame(147.888, $laps[0]->getTime());
    }


    /**
     * Test no grid positions when missing qualitimes
     */
    public function testNoGridPositionWhenMissingQualitimes()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/race07/SPEEDV CLIO T2 2013_2013_07_16_22_18_32_'
                   .'Qualify.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate
        $this->assertNull($participants[0]->getGridPosition());
    }

    /**
     * Test qualify session type. We expect qualify because the participant
     * in position 1 has DNF status. Also validates the order of participants
     */
    public function testQualifySessionType()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/race07/SPEEDV CLIO T2 2013_2013_07_16_22_18_32_'
                   .'Qualify.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Qualify or practice session', $session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Validate order of participants
        $this->assertSame(
            'Silvio Goes', $participants[0]->getDriver()->getName());
        $this->assertSame(
            'Fabio Feliciano', $participants[10]->getDriver()->getName());
        $this->assertSame(
            'Andersom Cunha', $participants[19]->getDriver()->getName());
    }


    /**
     * Test whether the parser adds dummy laps so we still know how much laps
     * drivers have ran
     */
    public function testDummyLapsOnMissingAllLapsInResult()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/f1c/race.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Get first paerticipant (Gummy)
        $participant = $participants[0];

        // Validate laps
        $laps = $participant->getLaps();
        $this->assertSame(7, $participant->getNumberOfLaps());
        $this->assertSame(75.645, $laps[0]->getTime());
        $this->assertNull($laps[1]->getTime());
    }

    /**
     * Test whether lapped drivers are positioned properly
     */
    public function testLappedDriversPosition()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/f1c/race.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Get first paerticipant (Kostia)
        $participant = $participants[4];

        // Validate
        $this->assertSame('Kostia', $participant->getDriver()->getName());
    }

    /**
     * Test there is no error on parsing a GTR2 file with no spaces after
     * lap data commas (e.g. Lap=(1,180.671,1:46.557))
     */
    public function testNoErrorsOnGTR2Log()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/gtr2/GTRSpain_2011_04_27_20_11_02_UNIDO.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();
    }

    /**
     * Test race room log differences. It includes multiple sessions and only
     * best lap of drivers
     */
    public function testRaceroomLogDifferences()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/raceroom/race.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get sessions
        $sessions = $reader->getSessions();

        // Validate number of sessions
        $this->assertSame(2, count($sessions));

        // Validate tracks
        $this->assertSame('Grand Prix', $sessions[0]->getTrack()->getVenue());
        $this->assertSame('Grand Prix', $sessions[1]->getTrack()->getVenue());

        // Participants best laps are parsed
        $this->assertNotNull($sessions[0]->getBestLap());
        $this->assertNotNull($sessions[1]->getBestLap());

        // Validate winners to test proper participant parsing between the
        // two sessions
        $participants = $sessions[0]->getParticipants();
        $this->assertSame('Frank Berndt',
            $participants[0]->getDriver()->getName());
        $participants = $sessions[1]->getParticipants();
        $this->assertSame('Jamie Green',
            $participants[0]->getDriver()->getName());
    }

    /**
     * Test race room log differences.
     *
     * BUG REPORT:
     * Paul B didn't finish Race 1 (race2.txt) and won Race 2 (race3.txt).
     * The race 2 results showed Ma H won but Paul B won that race.
     *
     * Paul B is not in the race3.txt log file :(
     */
    public function testRaceroomLogDifferences2()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/raceroom/race2.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get sessions
        $sessions = $reader->getSessions();

        $this->assertSame(Session::TYPE_RACE, $sessions[0]->getType());

        $participants = $sessions[0]->getParticipants();
        $this->assertSame('Nicky Catsburg',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Ma Qing Hua',
            $participants[16]->getDriver()->getName());
        $this->assertSame('Paul B',
            $participants[25]->getDriver()->getName());

        // Fix strange race time values:
        // -2147483648:-2147483648:-340282346638528860000000000000000000000.000
        $this->assertSame(0.0, $participants[0]->getTotalTime());

        $this->assertSame(Participant::FINISH_DNF,
            $participants[16]->getFinishStatus());
        $this->assertSame(Participant::FINISH_DNF,
            $participants[25]->getFinishStatus());

    }

    /**
     * Test whether there are no errors when the TimeString is missing from
     * the file
     */
    public function testNoErrorsOnMissingTimeString()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/race07/no.time.string.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get sessions
        $sessions = $reader->getSessions();
    }

    /**
     * Test whether there are no errors when the race data is missing from
     * the file
     */
    public function testNoErrorsOnMissingRaceData()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/race07/no.race.data.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get sessions
        $sessions = $reader->getSessions();
    }

    /**
     * Test whether there are no errors when the date is not properly
     * formatted. Test for null date too.
     */
    public function testNoErrorsAndNullValueOnIncorrectDate()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/race07/incorrect.date.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();

        // Validate null date
        $this->assertNull($session->getDate());
    }


    /***
    **** Below tests use a full valid race log file
    ***/


    /**
     * Test reading the session
     */
    public function testReadingSession()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        // Get session date
        $date = $session->getDate();

        // Validate timestamp of date
        $this->assertSame(1360706779, $date->getTimestamp());

        // Test default timezone (UTC)
        $this->assertSame('2013-02-12 22:06:19', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());

        //-- Validate other
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(12, $session->getLastedLaps());
        $this->assertSame('Unknown or offline', $session->getServer()->getName());
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('RACE 07', $game->getName());
        $this->assertSame('1.2.1.10', $game->getVersion());
    }


    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Monza_2007', $track->getVenue());
        $this->assertSame('2007_Monza', $track->getCourse());
        $this->assertSame(5782.6406, $track->getLength());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Validate first participant (winner, slotXXX)
        $participant = $participants[0];
        $this->assertSame('zezette racing', $participant->getTeam());
        $this->assertSame('[PRG]Yozeze34',
                          $participant->getDriver()->getName());
        $this->assertSame('Renault Sport Clio CUP France 2008',
                          $participant->getVehicle()->getName());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(2, $participant->getGridPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());

        // Validate last participant (Seb63)
        $participant = $participants[20];
        $this->assertSame('Seb63',
                          $participant->getDriver()->getName());
        $this->assertSame('Renault Sport Clio CUP France 2008',
                          $participant->getVehicle()->getName());
        $this->assertSame(21, $participant->getPosition());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());
        $this->assertSame('DNF (reason 0)',
            $participant->getFinishStatusComment());
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

        // Validate we have 12 laps
        $this->assertSame(12, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        $this->assertSame(138.685, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(126.276, $lap->getTime());
        $this->assertSame(128.404, $lap->getElapsedSeconds());


        // // Last lap
        $lap = $laps[11];
        $this->assertSame(12, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(131.264, $lap->getTime());
        $this->assertSame(1412.984, $lap->getElapsedSeconds());


        // // Validate extra positions
        $laps = $participants[3]->getLaps(); // totomms laps
        $this->assertSame(7, $laps[0]->getPosition());
        $this->assertSame(5, $laps[2]->getPosition());
        $this->assertSame(7, $laps[4]->getPosition());
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
        $file_path = realpath(__DIR__
            .'/logs/race07/prosracing Clio Cup_2013_02_12_22_06_19_Race2.txt');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}