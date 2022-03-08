    <?php
use Simresults\Data_Reader_AssettoCorsaCompetizione;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

/**
 * Tests for the Assetto Corsa Competizione JSON reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class AssettoCorsaCompetizioneReaderTest extends \PHPUnit\Framework\TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        error_reporting(E_ALL);
    }


    /**
     * Test exception when no data is supplied
     */
    public function testCreatingNewAssettoCorsaReaderWithInvalidData()
    {
        $this->expectException(\Simresults\Exception\CannotReadData::class);
        $reader = new Data_Reader_AssettoCorsaCompetizione('Unknown data for reader');
    }



    public function testFixingProperPositions()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.to.fix.positions.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        // Get participants
        $participants = $session->getParticipants();
        $participant = $participants[5];

        // Assert driver
        $this->assertSame('Kevin',
            $participant->getDriver()->getName());
    }


    public function testDriverSwapping()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.modified.with.swaps.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get first participant we modified with extra driver
        $participants = $session->getParticipants();
        $participant = $participants[0];

        // Assert driver of first and second lap
        $this->assertSame('Second Driver',
            $participant->getLap(1)->getDriver()->getName());
        $this->assertSame('Andrea Mel',
            $participant->getLap(2)->getDriver()->getName());
    }


    /**
     * Test qualify sessions
     */
    public function testQualifySession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_225901_Q.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertNull($session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Federico Siv',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Andrea Mel',
            $participants[2]->getDriver()->getName());
    }


    /**
     * Test numbers in session names, such as Q2
     */
    public function testNumericSessions()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'numeric.session.type.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
    }


    /**
     * Test practice sessions
     */
    public function testPracticeSession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());
        $this->assertNull($session->getName());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Federico Siv',
            $participants[0]->getDriver()->getName());
        $this->assertSame('Andrea Mel',
            $participants[2]->getDriver()->getName());
    }



    /**
     * Test invalid laps
     */
    public function testInvalidLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Get participants
        $participants = $session->getParticipants();
        $laps = $participants[0]->getLaps();

        $invalid_lap_keys = array(0,2,3,6,10,12,13,14);
        foreach ($invalid_lap_keys as $invalid_lap) {
            $this->assertNull($laps[$invalid_lap]->getTime());
            $this->assertSame(array(), $laps[$invalid_lap]->getSectorTimes());
        }
    }


    /**
     * Test penalties
     */
    public function testPenalties()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            '191003_224358_FP.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Get penalties
        $penalties = $session->getPenalties();

        // Assert drivers
        $this->assertSame('Federico Siv - Cutting - RemoveBestLaptime - violation in lap 3 - cleared in lap 3',
            $penalties[0]->getMessage());
        $this->assertTrue($penalties[0]->isServed());
        $this->assertSame('Federico Siv',$penalties[0]->getParticipant()->getDriver()->getName());

        $this->assertSame('Andrea Mel - Cutting - RemoveBestLaptime - violation in lap 13 - cleared in lap 13',
            $penalties[3]->getMessage());

        // Assert lap cuts data
        $participants = $session->getParticipants();
        $cuts = $participants[0]->getLap(3)->getCuts();

        // Not values known
        $this->assertSame(null, $cuts[0]->getCutTime());
        $this->assertSame(null, $cuts[0]->getTimeSkipped());
        $this->assertSame(null, $cuts[0]->getElapsedSeconds());
        $this->assertSame(null, $cuts[0]->getDate());
    }

    /**
     * Test parsing penalties when theres no penalty given (such as cutting in
     * race)
     */
    public function testPenaltiesWithNoPenalty()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'cuts.with.no.penalty.json');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get penalties
        $penalties = $session->getPenalties();

        // Assert drivers
        $this->assertSame('Federico Siv - Cutting - No penalty - violation in lap 3 - cleared in lap 3',
            $penalties[0]->getMessage());
        $this->assertTrue($penalties[0]->isServed());
        $this->assertSame('Federico Siv',$penalties[0]->getParticipant()->getDriver()->getName());

        $this->assertSame('Andrea Mel - Cutting - No penalty - violation in lap 13 - cleared in lap 13',
            $penalties[3]->getMessage());

        // Assert lap cuts data
        $participants = $session->getParticipants();
        $cuts = $participants[0]->getLap(3)->getCuts();

        // Not values known
        $this->assertSame(null, $cuts[0]->getCutTime());
        $this->assertSame(null, $cuts[0]->getTimeSkipped());
        $this->assertSame(null, $cuts[0]->getElapsedSeconds());
        $this->assertSame(null, $cuts[0]->getDate());
    }

    /**
     * Test no exception on missing carId in cars
     */
    public function testNoExceptionOnMissingCarIdInCars()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.with.missing.carId.attribute.json');

        $session = Data_Reader::factory($file_path)->getSession();
        $participants = $session->getParticipants();
        $this->assertSame('Alberto For',
            $participants[0]->getDriver()->getName());
    }

    /**
     * Test no exception on missing driver id used in laps
     */
    public function testNoExceptionOnMissingDriverIdUsedInLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'laps.with.unknown.carid.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Alberto For',
            $participants[0]->getDriver()->getName());
    }


    /**
     * Test no exception on missing laps attribute
     */
    public function testNoExceptionOnMissingLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'no.laps.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test no exception on missing leaderBoardLines
     */
    public function testNoExceptionOnMissingLeaderBoardLines()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.without.leaderBoardLines,attribute.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test no exception on missing teamName
     */
    public function testNoExceptionOnMissingTeamName()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.with.missing.driver.teamName.attribute.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test no exception and name unknown on missing carModel
     */
    public function testNoExceptionAndNameUnknownOnMissingCarModel()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.with.missing.carModel.attribute.json');

        // Assert vehicle name
        $session = Data_Reader::factory($file_path)->getSession();
        $participants = $session->getParticipants();
        $this->assertSame('Unknown', $participants[0]->getVehicle()->getName());
    }

    /**
     * Test client log file differences
     */
    public function testClientRaceLog()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione-client/'.
            'race.json');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Is race
        $this->assertSame(Session::TYPE_RACE, $session->getType());

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers
        $this->assertSame('Rob R',
            $participants[0]->getDriver()->getName());
        $this->assertSame(23,
            $participants[0]->getNumberOfLaps());

        // Validate track
        $track = $session->getTrack();
        $this->assertSame('Unknown', $track->getVenue());

        // Other settings
        $this->assertSame(array(
            'isWetSession' => 0,
            'dateHour' => '14',
            'dateMinute' => '0',
            'raceDay' => '2',
            'timeMultiplier' => '1.5',
            'preSessionDuration' => '1',
            'sessionDuration' => '2400',
            'overtimeDuration' => '180',
            'round' => '1',
            'sessionType' => '10',
            'dynamicTrackMultiplier' => '1',
            'idealLineGrip' => '0.98000001907349',
            'outsideLineGrip' => '0.5',
            'marblesLevel' => '0',
            'puddlesLevel' => '0',
            'wetDryLineLevel' => '0',
            'wetLevel' => '0',
        ), $session->getOtherSettings());
    }

    /**
     * Test mixed GT3 and GT4 vehicle classes
     */
    public function testGtVehicleClassesWhenGt4IsDetected()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-competizione/'.
            'race.modified.with.gt4.json');

        $session = Data_Reader::factory($file_path)->getSession();
        $participants = $session->getParticipants();

        $participant = $participants[0];
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame('GT4', $participant->getVehicle()->getClass());

        $participant = $participants[1];
        $this->assertSame(2, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());

        $participant = $participants[2];
        $this->assertSame(3, $participant->getPosition());
        $this->assertSame(2, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
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
        $this->assertSame(23, $session->getLastedLaps());
        $this->assertSame(array(
            'isWetSession' => 1
        ), $session->getOtherSettings());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame(
            "Simresults ServerName 7 of 10 (Practice 90' RACE Sept-27th)",
            $server->getName()
        );
    }


    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Assetto Corsa Competizione', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('brands_hatch', $track->getVenue());
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

        $this->assertSame('Andrea Mel',
                          $participant->getDriver()->getName());
        $this->assertSame('Mercedes AMG GT3',
                          $participant->getVehicle()->getName());
        $this->assertSame('123', $participant->getDriver()->getDriverId());
        $this->assertSame(82, $participant->getVehicle()->getNumber());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        $this->assertSame('Overall', $participant->getVehicle()->getCup());
        $this->assertSame('',
                          $participant->getTeam());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(1, $participant->getClassPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(2329.129, $participant->getTotalTime());

        // Different cup
        $participant = $participants[1];
        $this->assertSame(2, $participant->getPosition());
        $this->assertSame(2, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        $this->assertSame('Pro-Am', $participant->getVehicle()->getCup());
        $participant = $participants[2];
        $this->assertSame(3, $participant->getPosition());
        $this->assertSame(3, $participant->getClassPosition());
        $this->assertSame('GT3', $participant->getVehicle()->getClass());
        $this->assertSame('Overall', $participant->getVehicle()->getCup());

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
        $this->assertSame(23, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(2, $lap->getPosition());
        // First sector based on lap 2+ averages + grid start correction.
        // We cannot trust lap 1 sector 1  and total time. The  timer starts
        // when the player enters the session or presses drive.
        $this->assertSame(124.7316, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        // First sector based on lap 2+ averages + grid start correction.
        // We cannot trust lap 1 sector 1  and total time. The  timer starts
        // when the player enters the session or presses drive.
        $this->assertSame(51.7746, $sectors[0]);
        $this->assertSame(27.762, $sectors[1]);
        $this->assertSame(45.195, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(336.123, $lap->getTime());
        $this->assertSame(124.7316, $lap->getElapsedSeconds());

        // Validate extra positions
        $laps = $participants[2]->getLaps();
        $this->assertSame(3, $laps[0]->getPosition());
        $this->assertSame(2 , $laps[1]->getPosition());
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa-competizione/191003_235558_R.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}
