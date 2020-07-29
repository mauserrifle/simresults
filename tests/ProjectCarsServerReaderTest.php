    <?php
use Simresults\Data_Reader_ProjectCarsServer as PcReader;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Incident;

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
    public function testCreatingNewProjectCarsReaderWithInvalidData()
    {
        $reader = new PcReader('Unknown data for reader');
    }


    /**
     * Test reading finish statusses and positions of race that was not
     * finished
     */
    public function testReadingStatussesAndPositionsOfRaceWithoutFinish()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/race.without.finish.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(5);

        // Get participants
        $participants = $session->getParticipants();

        // Validate statusses
        foreach ($participants as $part)
        {
            $this->assertSame(Participant::FINISH_NONE,
                $part->getFinishStatus());
        }

        // Validation positions
        $this->assertSame('I am Reginald',
            $participants[0]->getDriver()->getName());
        $this->assertSame('xCrazydogx',
            $participants[4]->getDriver()->getName());

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

    /**
     * Test reading the best laps from a log that has no events (thus no laps).
     * We fallback to "FastestLapTime" within the results data.
     */
    public function testReadingBestLapFromLogWithoutEvents()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/stages.without.events.json');

        // Get the data reader for the given data source
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Get participants
        $participants = $sessions[0]->getParticipants();

        // Test the best lap
        $this->assertSame(119.417, $participants[0]->getBestLap()->getTime());
    }

    /**
     * Test reading  DNF states
     */
    public function testReadingDNFstates()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/stages.without.events.json');

        // Get the data reader for the given data source
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Get participants of 10th session
        $participants = $sessions[9]->getParticipants();

        // Test DNF status
        $this->assertSame(Participant::FINISH_DNF,
            $participants[1]->getFinishStatus());

        // Get participants of last session
        $participants = $sessions[count($sessions)-1]->getParticipants();

        // Test retired status as DNF
        $this->assertSame('[CAV] F1_Racer68',
            $participants[count($participants)-1]->getDriver()->getName());
        $this->assertSame(Participant::FINISH_DNF,
            $participants[count($participants)-1]->getFinishStatus());
    }



    /**
     * Test no exceptions and proper participants on unknown/bad participant
     * ids. Participants will be collected using events as a fallback
     */
    public function testDataOnUnknownOrBadParticipantIds()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/unknown.participant.ids.json');

        // Get the data reader for the given data source
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Get participants
        $participants = $sessions[0]->getParticipants();

        // Validate number of participants
        $this->assertSame(17, count($participants));

        // Validate human state
        $this->assertTrue($participants[0]->getDriver()->isHuman());
        $this->assertTrue($participants[1]->getDriver()->isHuman());
        $this->assertFalse($participants[2]->getDriver()->isHuman());
    }

    /**
     * Test no exceptions on another log missing participant ids
     */
    public function testDataOnUnknownOrBadParticipantIds2()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/unknown.participant.ids2.json');

        // Get the data reader for the given data source
        $sessions = Data_Reader::factory($file_path)->getSessions();
    }

    /**
     * Test reading a log with two forward slashes in the content that caused
     * bad log cleaning
     */
    public function testReadingLogContainingContentWithForwardSlashes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/forward.slashes.in.content.json');

        $sessions = Data_Reader::factory($file_path)->getSessions();
        $this->assertSame(8, count($sessions));
    }

    /**
     * Test whether proper cut times are read. This tests a bug fix where too
     * many cut ends were read. Fixed using break in for loop when END for cut
     * is found.
     */
    public function testProperCutTimesWithAlotOfCutEvents()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/race.with.alot.of.cuts.json');

        // Get the data reader for the given data source
        // TODO: Why is this 5? Can we exclude the other sessions because of
        // having empty data?
        $session = Data_Reader::factory($file_path)->getSession(5);

        // Get participants
        $participants = $session->getParticipants();

        // Get laps of second participant
        $laps = $participants[1]->getLaps();

        // Validate cuts
        $this->assertSame(1.434, $laps[1]->getCutsTime());
    }

    /**
     * Test whether we filter out cuts without end data
     */
    public function testFilteringOutCutsWithoutEndData()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/missing.cut.end.data.json');

        // Get race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Get laps of third participant
        $laps = $participants[2]->getLaps();

        // Validate that lap 20 does not have cuts and lap 24 does
        $this->assertSame(0, $laps[19]->getNumberOfCuts());
        $this->assertSame(1, $laps[23]->getNumberOfCuts());
    }

    /**
     * Test whether we have proper lap and cut matches. The library earlier read
     * laps by number using the laps array keys. But in some cases the first
     * key [0] could be lap number 2 (by reading `getNumber()`. So we can't
     * rely on array keys and this tests a scenario where we had bugged cuts
     *
     * Participant `getLap` has been modified for this.
     */
    public function testFixingBadLapNumberCutMatching()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/missing.cut.end.data.json');

        // Get qualify session
        $session = Data_Reader::factory($file_path)->getSession(1);

        // Get participants
        $participants = $session->getParticipants();

        // Get laps of third participant
        $laps = $participants[2]->getLaps();

        // Validate that lap 1 does not have cuts
        $this->assertSame(0, $laps[1]->getNumberOfCuts());


        // BELOW WAS THE OLD TEST THAT IS NOT VALID ANY MORE DUE TO
        // IMPROVEMENTS IN NOT INCLUDING INVALID LAPS:
        //
        // Validate that lap 1 and 2 have no cuts. Validate that lap 3 does not
        // exist (but there is a cut for this). By testing this we make sure
        // the unfinished lap 3 cut has not been misread into the first two
        // laps
        //
        // $this->assertSame(0, $laps[1]->getNumberOfCuts());
        // $this->assertSame(0, $laps[2]->getNumberOfCuts());
        // $this->assertFalse(isset($laps[3]));
    }

    /**
     * Test ignoring invalid laps and proper finishes. Tests a fix where
     * the results array from the json is not parsed when the leading
     * participant is not in it.
     */
    public function testIgnoringInvalidLapsAndProperFinishes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/invalid.laps.json');

        // Get qualify session
        $session = Data_Reader::factory($file_path)->getSession(1);

        // Get participants
        $participants = $session->getParticipants();

        // Validate first participant
        $this->assertSame('Markus Walter',
            $participants[0]->getDriver()->getName());

        // Validate proper lap numbers increment (there were double laps)
        $lap_num = 1;
        foreach($participants[0]->getLaps() as $lap)
        {
            $this->assertSame($lap_num, $lap->getNumber());
            $lap_num++;
        }


        // Get race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Validate first participant
        $this->assertSame('Markus Walter',
            $participants[0]->getDriver()->getName());
    }

    /**
     * Tests not mixing up races data. This happend because an sorted result
     * array was not (re)initiated for each stage, so it kept building up
     */
    public function testNotMixingUpRaces()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/races.to.test.mixed.races.json');

        // Get sessions
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Validate each race session winner
        $drivers = array('-T2R-Julien', '-T2R-Julien', '-T2R-Julien',
                         'GTS - Ipod [FR]', 'GTS - Ipod [FR]');

        $session_key = 1;
        foreach ($drivers as $driver)
        {
            $this->assertSame($driver, $sessions[$session_key]
                ->getWinningParticipant()->getDriver()->getName());

            $session_key += 2;
        }

        // Validate number of participants last session
        $this->assertSame(6, count($sessions[9]->getParticipants()));
    }

    /**
     * Test ignoring empty driver names
     */
    public function testIgnoringEmptyDriverNames()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/practice.causing.empty.AI.driver.json');

        // Get sessions without error
        $session = Data_Reader::factory($file_path)->getSession(1);
        $participants = $session->getParticipants();

        // Test number of participants (14th was driver with no name)
        $this->assertCount(13, $participants);
    }

    /**
     * Undefined index 0 on participants fix
     */
    public function testFixUndefinedIndexParticipants()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/log.causing.parts.offset.0.error.json');

        // Get sessions without error
        $sessions = Data_Reader::factory($file_path)->getSessions();
    }


    /**
     * Test reading pit stops
     */
    public function testReadingPitStops()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/race.without.finish.json');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession(5);

        // Get participants
        $participants = $session->getParticipants();

        // Validate pitstop lap
        $this->assertFalse($participants[6]->getLap(9)->isPitLap());
        $this->assertTrue($participants[6]->getLap(10)->isPitLap());
        $this->assertFalse($participants[6]->getLap(11)->isPitLap());
    }

    /**
     * Project Cars 2 fixes
     */
    public function testProjectCars2Fixes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars2-server/aborted.race.json');

        // Get sessions without error
        $sessions = Data_Reader::factory($file_path)->getSessions();

        // Validate game name for all sessions
        foreach ($sessions as $session)
        {
            $game = $session->getGame();
            $this->assertSame('Project Cars 2', $game->getName());
        }


        $participants = $sessions[4]->getParticipants();

        // Test vehicle friendly name
        $this->assertSame('Ligier JS P2 Nissan (LMP2)',
                          $participants[0]->getVehicle()->getFriendlyName());


        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars2-server/'.
            'multiple.races.from.modified.dsms_stats.lua.file.json');

        $sessions = Data_Reader::factory($file_path)->getSessions();
        $participants = $sessions[0]->getParticipants();

        // Test driver being human
        $this->assertTrue($participants[0]->getDriver()->isHuman());

        // Test vehicle friendly name
        $this->assertSame('Mitsubishi Lancer Evolution IX FQ360 (Road C1)',
                          $participants[0]->getVehicle()->getFriendlyName());



        /**
         * Test fixing missing admin driver and missing vehicle name
         */

        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars2-server/'.
            'admin.driver.missing.from.results.json');

        // Find Race 4 to test
        $sessions = Data_Reader::factory($file_path)->getSessions();

        $races_looped = 0;
        $race_session = null;
        foreach ($sessions as $session)
        {
            if ($session->getType() === Session::TYPE_RACE AND
                $participants = $session->getParticipants())
            {
                $races_looped++;

                if ($races_looped === 4) {
                    $race_session = $session;
                }
            }
        }

        $participants = $race_session->getParticipants();
        $this->assertSame(
            'Rob Milliken', $participants[5]->getDriver()->getName());
        $this->assertSame(
            'Formula Renault 3.5', $participants[5]->getVehicle()->getName());
    }

    /**
     * Test reading log that did not parse due bad comment cleaning.
     *
     * The following was badly replaced due the 2 slashes breaking the json:
     *
     *     "name" : "WWW.GEF-GAMING.DE // GT3 MASTERS #02",
     *
     */
    public function testReadingLogThatDidNotParseDueBadCommentCleaning()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars2-server/not.parsing.due.slashes.in.server.name.json');

        // Get sessions without error
        $sessions = Data_Reader::factory($file_path)->getSessions();
    }


    /**
     * Test reading log that did not parse anymore due changes in above test.
     * Fixed by testing on "stages" in reader whether it is a PC log.
     *
     */
    public function testOldLog()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/projectcars-server/old.log.json');

        // Get sessions without error
        $sessions = Data_Reader::factory($file_path)->getSessions();
    }

    /**
     * Test whether we can detect Automobilista2
     *
     */
    public function testAutomobilista2Fixes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/automobilista2/practice.and.race.json');

        // Get sessions
        $reader = Data_Reader::factory($file_path);
        $sessions = $reader->getSessions();

        foreach ($sessions as $session)
        {
            // Validate game name
            $game = $session->getGame();
            $this->assertSame('Automobilista 2', $game->getName());

            $track = $session->getTrack();
            $this->assertSame('Imola_GP_2018', $track->getVenue());
        }

        // Test vehicle names
        foreach (array('Roco 001', 'MetalMoro MRX Duratec Turbo P3') as $key => $name)
        {
            foreach ($sessions[$key]->getParticipants() as $part) {
                $vehicle = $part->getVehicle();
                $this->assertSame($name, $vehicle->getName());
            }
        }

        // Make sure the defined vehicle ids are not shared with Project Cars
        // attribute files to prevent bad detection
        foreach (PcReader::$automobilista2_vehicle_ids as $vehicleId)
        {
            // Get attribute json files from server api
            $attribute_names = json_encode($reader->getAttributeNames());
            $attribute_names2 = json_encode($reader->getAttributeNames2());

            // Cast to string otherwise strpos won't work as expected
            $vehicleId = (string) $vehicleId;

            // Test whether vehicleId is in in attribute files
            $vehicleIdShared = (
                strpos($attribute_names, $vehicleId) !== FALSE OR
                strpos($attribute_names2, $vehicleId) !== FALSE
            );
            $this->assertFalse($vehicleIdShared,
                'Automobilista2 vehicle id '.
                $vehicleId.' is shared with Project Cars');
        }
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
                'name'     => null,
                'max_laps' => 15,
                'time'     => 1446146942,
            ),
            array(
                'type'     => Session::TYPE_PRACTICE,
                'name'     => 'Practice2',
                'max_laps' => 15,
                'time'     => 1446147862,
            ),
            array(
                'type'     => Session::TYPE_QUALIFY,
                'name'     => 'Qualifying',
                'max_laps' => 15,
                'time'     => 1446148782,
            ),
            array(
                'type'     => Session::TYPE_WARMUP,
                'name'     => null,
                'max_laps' => 5,
                'time'     => 1446149702,
            ),
            array(
                'type'     => Session::TYPE_RACE,
                'name'     => null,
                'max_laps' => 7,
                'time'     => 1446150022,
            ),
        );


        // Get sessions and test them
        $sessions = $this->getWorkingReader()->getSessions();
        foreach ($tests as $test_key => $test)
        {
            $session = $sessions[$test_key];

            //-- Validate
            $this->assertSame($test['type'], $session->getType());
            $this->assertSame($test['name'], $session->getName());
            $this->assertSame($test['max_laps'], $session->getMaxLaps());
            $this->assertSame($test['time'],
                $session->getDate()->getTimestamp());
            $this->assertSame('UTC',
                $session->getDate()->getTimezone()->getName());

            $this->assertSame(array(
                'DamageType'                  => 3,
                'FuelUsageType'               => 0,
                'PenaltiesType'               => 0,
                'ServerControlsSetup'         => 1,
                'ServerControlsTrack'         => 1,
                'ServerControlsVehicle'       => 0,
                'ServerControlsVehicleClass'  => 1,
                'TireWearType'                => 6,
                ),
                $session->getOtherSettings());
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
        $participants = $this->getWorkingReader()->getSession(5)
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
        $this->assertSame(11, $participant->getGridPosition());
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
     *
     * TODO: Double check gaps calculation. See gaps Race 1 @
     *       http://simresults.net/151107-1jd
     *       First driver should be second
     */
    public function testReadingLapsOfParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession(5)
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
        $this->assertSame(2.872, $lap->getCutsTimeSkipped());
        $this->assertSame(3.023, $lap->getCutsTime());

        // Validate extra positions
        $laps = $participants[3]->getLaps();
        $this->assertSame(6, $laps[0]->getPosition());
        $this->assertSame(7, $laps[2]->getPosition());
    }

    /**
     * Test reading detailed cuts data
     */
    public function testCuts()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession(5)
            ->getParticipants();


        // Get the laps of second participants (first is missing a lap)
        $participant = $participants[1];
        $laps = $participant->getLaps();

        // Second lap cuts
        $cuts = $laps[1]->getCuts();

        // Validate
        $this->assertSame(3, count($cuts));
        $this->assertSame(2.8780, $cuts[0]->getCutTime());
        $this->assertSame(2.7480, $cuts[0]->getTimeSkipped());
        $this->assertSame(1446150159, $cuts[0]->getDate()->getTimestamp());
        $this->assertSame(137, $cuts[0]->getElapsedSeconds());
        $this->assertSame(9.888, $cuts[0]->getElapsedSecondsInLap());
        $this->assertSame($laps[1], $cuts[0]->getLap());
    }


    /**
     * Test reading incidents between cars
     */
    public function testIncidents()
    {
        $session = $this->getWorkingReader()->getSession(5);

        $participants = $session->getParticipants();
        $incidents = $session->getIncidents();

        // Validate first incident
        $this->assertSame(
            'JarZon reported contact with another vehicle '
           .'Seb Solo. CollisionMagnitude: 780',
            $incidents[4]->getMessage());
        $this->assertSame(1446150075,
            $incidents[4]->getDate()->getTimestamp());
        $this->assertSame(53, $incidents[4]->getElapsedSeconds());
        $this->assertSame(Incident::TYPE_CAR, $incidents[4]->getType());
        $this->assertSame($participants[4], $incidents[4]->getParticipant());
        $this->assertSame($participants[9], $incidents[4]->getOtherParticipant());

        // Validate incident that would have a unknown participant. But now
        // it should not because we ignore these
        $this->assertSame(
            'JarZon reported contact with another vehicle '
           .'Trey. CollisionMagnitude: 327',
            $incidents[5]->getMessage());
        $this->assertSame(1446150147,
            $incidents[5]->getDate()->getTimestamp());
        $this->assertSame(125, $incidents[5]->getElapsedSeconds());
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