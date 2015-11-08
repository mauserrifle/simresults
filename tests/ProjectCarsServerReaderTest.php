    <?php
use Simresults\Data_Reader_AssettoCorsaServerJson;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Project Cars Server reader
 *
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class ProjectCarsServerReaderTest extends PHPUnit_Framework_TestCase {

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
     * Test reading finish statusses of race that was not finished
     */
    public function testReadingFinishStatussesOfRaceWithoutFinish()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/race.without.finish.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(4);

        // Get participants
        $participants = $session->getParticipants();

        foreach ($participants as $part)
        {
            $this->assertSame(Participant::FINISH_NONE,
                $part->getFinishStatus());
        }
    }

    /**
     * Test  proper positions for qualify and practice. Drivers were not
     * properly positioned because we ordered mainly on 'results' data.
     * But apperently some drivers are missing so this tests that we do
     * not rely on this info any more for practice and qualify sessions
     */
    public function testReadingProperPositionsForNonRace()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/race.without.finish.json');

        // Get the data reader for the given data source
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Get participants for practice 2
        $participants = $sessions[1]->getParticipants();

        $this->assertSame('ivanmille',
            $participants[0]->getDriver()->getName());

        // Validate Zockerbursche
        $this->assertSame('Zockerbursche',
            $participants[9]->getDriver()->getName());


        // Get participants for qualify
        $participants = $sessions[2]->getParticipants();

        $this->assertSame('ivanmille',
            $participants[0]->getDriver()->getName());

        // Validate patrok
        $this->assertSame('patrok1207³',
            $participants[9]->getDriver()->getName());
    }





    /***
    **** Below tests use 1 race log file
    ***/

    /**
     * Test reading the first 5 sessions
     */
    public function testReadingMultipleSessions()
    {
        $tests = array(
            array(
                'type'     => Session::TYPE_PRACTICE,
                'max_laps' => 15,
                'time'     => 1446146942,
            ),
            array(
                'type'     => Session::TYPE_PRACTICE,
                'max_laps' => 15,
                'time'     => 1446147862,
            ),
            array(
                'type'     => Session::TYPE_QUALIFY,
                'max_laps' => 15,
                'time'     => 1446148782,
            ),
            array(
                'type'     => Session::TYPE_RACE,
                'max_laps' => 7,
                'time'     => 1446150022,
            ),

            // TODO: Warmup should be before race!
            array(
                'type'     => Session::TYPE_WARMUP,
                'max_laps' => 5,
                'time'     => 1446149702,
            ),
        );


        // Get sessions and test them
        $sessions = $this->getWorkingReader()->getSessions();
        foreach ($tests as $test_key => $test)
        {
            $session = $sessions[$test_key];

            //-- Validate
            $this->assertSame($test['type'], $session->getType());
            $this->assertSame($test['max_laps'], $session->getMaxLaps());
            $this->assertSame($test['time'],
                $session->getDate()->getTimestamp());
            $this->assertSame('UTC',
                $session->getDate()->getTimezone()->getName());
        }


    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('[ITA]www.racingnetwork.eu', $server->getName());
        // TODO: Settings
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('Project Cars', $game->getName());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Mazda Raceway Laguna Seca', $track->getVenue());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession(4)
            ->getParticipants();


        // Validatet he number because we filter all who have no events
        $this->assertSame(10, count($participants));


        /**
         * Validate first participant
         */
        $participant = $participants[0];

        $this->assertSame('ItchyTrigaFinga',
                          $participant->getDriver()->getName());
        $this->assertSame('Ford Mustang Cobra TransAm',
                          $participant->getVehicle()->getName());
        $this->assertSame('Trans-Am',
                          $participant->getVehicle()->getClass());
        $this->assertSame('76561198015591839',
                          $participant->getDriver()->getDriverId());
        $this->assertTrue($participant->getDriver()->isHuman());
        $this->assertSame(1, $participant->getPosition());
        $this->assertSame(Participant::FINISH_NORMAL,
            $participant->getFinishStatus());
        $this->assertSame(516.67499999999995, $participant->getTotalTime());


        // Test any other participants to validate proper position
        $participant = $participants[8];
        $this->assertSame('SUCKER', $participant->getDriver()->getName());
    }

    /**
     * Test reading laps of participants
     *       Elapsed seconds based on session start and lap time?
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession(4)
            ->getParticipants();


        // Get the laps of second participants (first is missing a lap)
        $participant = $participants[1];
        $laps = $participant->getLaps();

        // Validate we have 7 laps
        $this->assertSame(7, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participant->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate laps
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(3, $lap->getPosition());
        $this->assertSame(90.030, $lap->getTime());
        $this->assertSame(0, $lap->getElapsedSeconds());
        $this->assertSame($participant, $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());
        $this->assertSame(0, $lap->getNumberOfCuts());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(36.008, $sectors[0]);
        $this->assertSame(22.301, $sectors[1]);
        $this->assertSame(31.7210, $sectors[2]);

        // Second lap
        $lap = $laps[1];
        $this->assertSame(2, $lap->getNumber());
        $this->assertSame(3, $lap->getPosition());
        $this->assertSame(84.2240, $lap->getTime());
        $this->assertSame(90.0300, $lap->getElapsedSeconds());
        $this->assertSame(3, $lap->getNumberOfCuts());

        // Validate extra positions
        $laps = $participants[3]->getLaps();
        $this->assertSame(6, $laps[0]->getPosition());
        $this->assertSame(7, $laps[2]->getPosition());
    }


    /**
     * Test reading incidents between cars
     */
    public function testIncidents()
    {
        // Get participants
        $incidents = $this->getWorkingReader()->getSession(4)
            ->getIncidents();

        // Validate first incident
        $this->assertSame(
            'Seb Solo reported contact with another vehicle '
           .'Trey. CollisionMagnitude: 1000',
            $incidents[0]->getMessage());
        $this->assertSame(1446150056,
            $incidents[0]->getDate()->getTimestamp());

        // Validate unkown other participant
        $this->assertSame(
            'Tazio Nuvolari reported contact with another vehicle '
           .'unknown. CollisionMagnitude: 1000',
            $incidents[5]->getMessage());
        $this->assertSame(1446150118,
            $incidents[5]->getDate()->getTimestamp());
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
        $file_path = realpath(__DIR__.'/logs/projectcars-server/sms_stats_data.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}