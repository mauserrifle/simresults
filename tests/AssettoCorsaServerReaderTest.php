<?php
use Simresults\Data_Reader_AssettoCorsaServer;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the Assetto Corsa Server reader
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

    /**
     * Test reading that failed due to different connect format of drivers.
     */
    public function testReadingAlternativeParticipantFormat()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/different.connecting.format.log');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate vehicle
        $this->assertSame('abarth500',
            $participants[0]->getVehicle()->getName());
    }

    /**
     * Test reading new connect format since 1.2 update:
     *
     *      CAR: 0 ks_bmw_m235i_racing (0) [Daniel Wolf [iSimRace.de]] Daniel
     *      Wolf [iSimRace.de] 76561198000275466 0 kg
     *
     */
    public function testReadingAlternativeParticipantFormat2()
    {
        // The path to the data source
        $file_path = realpath(__DIR__. '/logs/assettocorsa-server/'.
            'different.connecting.format.update.1.2.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate vehicle, name, guid and team
        $this->assertSame('ks_bmw_m235i_racing',
            $participants[0]->getVehicle()->getName());
        $this->assertSame('Ronny-Stoepsel',
            $participants[0]->getDriver()->getName());
        $this->assertSame('76561198001923656',
            $participants[0]->getDriver()->getDriverId());
        $this->assertSame('iSimRace.de', $participants[0]->getTeam());
    }

    /**
     * Test reading laps data with different format regarding the ":]" chars:
     *
     *     1) Zimtpatrone :] BEST: 7:00:688 TOTAL: 21:20:237 Laps:2 SesID:3
     */
    public function testReadingAlternativeLapFormat()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/'
            .'alternative.lap.format.and.special.chars.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate first lap (2:49:921)
        $this->assertSame(563.884, $participants[0]->getLap(1)->getTime());
    }

    /**
     * Test reading lap lines with a number in the end of driver names.
     * This caused an exception due to bad regex matching. Example line:
     *
     *     LAP Sven RS 201 2:03:643
     */
    public function testReadingLapsWithNumericDriverName()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/number.in.end.driver.name.txt');

        // Just get the session (without exception)
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test reading participants with special chars
     */
    public function testReadingParticipantsWithSpecialChars()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/'
            .'alternative.lap.format.and.special.chars.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate name
        $this->assertSame('GummiGeschoß',
            $participants[0]->getDriver()->getName());
    }

    /**
     * Test that discarded laps are not included in the parsing
     */
    public function testExcludingDiscardedLaps()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/discarded.laps.txt');

        // Get the last race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Validate numer of laps of winner
        $this->assertSame(30, count($participants[0]->getLaps()));
    }

    /**
     * Test that refused laps are not included in the parsing
     */
    public function testExcludingRefusedLaps()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server/refused.laps.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate winner
        $this->assertSame('Francis Savere',
            $participants[0]->getDriver()->getName());

        // Validate numer of laps of remy
        $this->assertSame('remy vanlierde',
            $participants[4]->getDriver()->getName());
        $this->assertSame(5, count($participants[0]->getLaps()));


        //--- Test another log that had refused lap line not on next line of
        //    lap. TODO: Cut this log so it's smaller?

        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/different.refused.laps.format.txt');

        // Get the last race session
        $session = Data_Reader::factory($file_path)->getSession(4);

        // Get participants
        $participants = $session->getParticipants();

        // Validate winner
        $this->assertSame('seruno',
            $participants[0]->getDriver()->getName());

        // Validate numer of laps of ShijouR26B
        $this->assertSame('ShijouR26B',
            $participants[4]->getDriver()->getName());
        $this->assertSame(18, count($participants[4]->getLaps()));
    }


    /**
     * Test DNF for no total time in log
     */
    public function testDnfForNoTotalTimeInLog()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/discarded.laps.txt');

        // Get the last race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Get last participant
        $participant = $participants[sizeof($participants)-1];

        // Test DNF
        $this->assertSame('Thiago Almeida', $participant->getDriver()->getName());
        $this->assertSame(Participant::FINISH_DNF,
            $participant->getFinishStatus());
    }

    /**
     * Test reading GUIDs (steam ids) and Teams
     */
    public function testReadingGuidAndTeam()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server/log.with.guids.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate guid and team
        $this->assertSame('76561198023156518',
            $participants[0]->getDriver()->getDriverId());
        $this->assertSame('Ma team', $participants[0]->getTeam());


        //-- Test another source that was missing guids due to overwriting
        //   empty guids data from next sessions (bug)

        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/assettocorsa-server/log.with.guids2.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate guids
        $this->assertSame('76561198059896913',
            $participants[0]->getDriver()->getDriverId());
        $this->assertSame('76561197986209847',
            $participants[1]->getDriver()->getDriverId());
    }

    /**
     * Test reading the guids from a other connect format (Adding car: ...)
     */
    public function testReadingOtherGuidsFormat()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/different.connecting.format.log');

        // Get the first session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate some guids
        $this->assertSame('76561198153260382',
                          $participants[0]->getDriver()->getDriverId());
        $this->assertSame('76561197991946485',
                          $participants[5]->getDriver()->getDriverId());
    }

    /**
     * Test additional fix for missing guid
     */
    public function testFixForMissingGuid()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'driver.antoine.with.two.spaces.in.name.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate guid that was missing
        $this->assertSame('76561198018799568',
                          $participants[12]->getDriver()->getDriverId());
    }

    /**
     * Test reading allowed car list not containing other log info (bugfix)
     */
    public function testReadingAllowedVehicles()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/assettocorsa-server/'
            .'extra.car.list.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Validate allowed vehicles
        $allowed_vehicles = $session->getAllowedVehicles();
        $this->assertSame('bmw_m3_gt2', $allowed_vehicles[0]->getName());
        $this->assertSame('bmw_m3_gtr', $allowed_vehicles[1]->getName());
        $this->assertSame('bmw_m3_gt1', $allowed_vehicles[2]->getName());
        $this->assertFalse(isset($allowed_vehicles[3]));
    }

    /**
     * Test reading DNF status for a driver that quited but not disconnected.
     * By not disconnecting the driver maintained a total time after race was
     * over, which causes a FINISH status. To fix this, we search lines like:
     *
     *     Angelo Lima BEST: 1:48:483 TOTAL: 46:03:213 Laps:22 SesID:4
     *
     * When the last 3 matches have the same laps. This means this driver has
     * not made any progress. We mark this driver as DNF.
     */
    public function testDnfForQuitingDriver()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/driver.angelo.quiting.race.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate finish status of Angelo
        $this->assertSame('Angelo Lima',
            $participants[12]->getDriver()->getName());
        $this->assertSame(Participant::FINISH_DNF,
            $participants[12]->getFinishStatus());
    }

    /**
     * Test reading log with windows lines. Matching for laps went wrong.
     */
    public function testLogWithWindowsLines()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/log.with.windows.lines.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate session type (was containing new lines..)
        $this->assertSame(Session::TYPE_RACE, $session->getType());

        // Validate driver
        $this->assertSame(1, count($participants));
        $this->assertSame('Test',
            $participants[0]->getDriver()->getName());
        $this->assertSame(2, $participants[0]->getNumberOfLaps());
    }

    /**
     * Test reading log without track. Should not throw an exception
     */
    public function testLogWithoutTrack()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/no.track.info.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get the track
        $track = $session->getTrack();

        // Validate track
        $this->assertNull($track);
    }

    /**
     * Test fix for a bug that car names were missing or incorrect because
     * drivers were not properly parsed from connect info
     */
    public function testFixedMissingAndWrongCars()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/messy.driver.connect.info.txt');

        // Get the session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        $this->assertSame('Andrej Trost',
            $participants[0]->getDriver()->getName());
        $this->assertSame('mclaren_mp412c_gt3*',
            $participants[0]->getVehicle()->getName());

        $this->assertSame('Benjamin Kronaveter',
            $participants[16]->getDriver()->getName());
        $this->assertSame('bmw_z4_gt3*',
            $participants[16]->getVehicle()->getName());

        $this->assertSame('Miha Lencek',
            $participants[19]->getDriver()->getName());
        $this->assertSame('mclaren_mp412c_gt3*',
            $participants[19]->getVehicle()->getName());
    }

    /**
     * Test reading the cars from a log that contains date prefixes
     *
     * Unrelated idea: Maybe use these prefixes to add a date to chats in the
     *                 future?
     */
    public function testReadingCarsFromLogWithDatePrefixes()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/log.with.date.prefixes.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate all to have proper vehicle
        foreach ($participants as $participant)
        {
            // Validate vehicle
            $this->assertSame('honda_nsx_s1*',
                               $participant->getVehicle()->getName());
        }


    }


    /**
     * Test reading times without regex errors
     */
    public function testReadingTimesWithoutRegexErrors()
    {
        // The path to the data source
        $file_path = realpath(__DIR__
            .'/logs/assettocorsa-server/forward.slash.in.name.txt');

        // Get the session without exception
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /**
     * Test reading multiple cars per participant that will be stored per lap
     *
     * Reading Nakuni which should have these laps:
     *
     *     ferrari
     *     LAP1: 1:52
     *     LAP2: 1:13
     *
     *     lotus
     *     LAP3: 1:42
     *     LAP4: 1:24
     */
    public function testReadingMultipleCarsPerParticipant()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/reconnect.with.different.car.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Get first participant
        $participant = $participants[0];

        // Validate vehicles
        $vehicles = $participant->getVehicles();

        $this->assertSame(2, count($vehicles));
        $this->assertSame('ferrari_599xxevo*', $vehicles[0]->getName());
        $this->assertSame('lotus_evora_gte*', $vehicles[1]->getName());

        // Validate vehicle for each lap
        $laps = $participant->getLaps();

        $this->assertSame($laps[0]->getVehicle(), $vehicles[0]);
        $this->assertSame($laps[1]->getVehicle(), $vehicles[0]);
        $this->assertSame($laps[2]->getVehicle(), $vehicles[1]);
        $this->assertSame($laps[3]->getVehicle(), $vehicles[1]);
    }


    /**
     * Fix a bug where best laps were cached in the reader because of calling
     * `getVehicle()`
     *
     */
    public function testFixForBestLapCacheBug()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/log.to.fix.best.lap.cache.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Get first participant
        $participant = $participants[0];

        // Validate best lap
        $this->assertNotNull($participant->getBestLap());
    }

    /**
     * Test log with missing session type and driver vehicles. The session type
     * should default to practice and alot of vehicles are defaulted to the
     * only vehicle used by other participants
     */
    public function testMissingSessionTypeAndDriverVehicles()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'missing.session.info.and.alot.of.connect.info.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Validate session type that defaulted to practice
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());

        // Validate server that defaulted to unknown
        $this->assertSame('Unknown', $session->getServer()->getName());

        // Validate all vehicles. Alot are missing due to bad connect info.
        // This tests that the parser defaults to the only car everybody else
        // uses. We assume this is the only one allowed
        foreach ($session->getParticipants() as $part)
        {
            $this->AssertSame('ferrari_458_gt2*',
                              $part->getVehicle()->getName());
        }
    }


    /**
     * Test additional fix for missing vehicles
     */
    public function testFixForMissingVehicles()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/3Up.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate vehicle that was missing
        $this->assertSame('ferrari_458_gt2',
                          $participants[8]->getVehicle()->getName());
    }

    /**
     * Test additional fix for missing vehicles. This tests succesful parsing
     * of log lines:
     *  /SUB|tatuusfa1|tatuus_honda_b|Aaron Wilson||76561198021449105|piratella
     */
    public function testFixForMissingVehicles2()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'different.connecting.format.with.some.missing.data.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Validate vehicle and guid that was missing of Aaron Wilson
        $this->assertSame('tatuusfa1',
                          $participants[13]->getVehicle()->getName());
        $this->assertSame('76561198021449105',
                          $participants[13]->getDriver()->getDriverId());
    }

    /**
     * Test fixing bad new lines
     */
    public function testFixingBadNewLines()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'bad.new.lines.txt');

        // Get the data reader for the given data source
        $session = Data_Reader::factory($file_path)->getSession();

        // Test that is practice session. We assume the rest is correct too
        // when this is positive
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());
    }

    /**
     * Test new AC log format where PASSWORD line was removed. Should not
     * generate any error
     */
    public function testNoErrorsOnNewLogFormat()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'new.format.txt');

        // Get the session without errors
        $session = Data_Reader::factory($file_path)->getSession();
    }

    /*
     * Test that we do not parse any extra laps after finishing
     */
    public function testNotParsingExtraLapsAfterFinish()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'driver.rafael.crossed.finish.twice.after.RACE.OVER.DETECTED.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Assert driver on position 10
        $this->assertSame('Rafael Nogueira',
            $participants[9]->getDriver()->getName());
    }

    /*
     * Test that we do not parse any extra laps after finishing. In this case
     * there were both "RACE OVER DETECTED!" and "RACE OVER PACKET, FINAL RANK"
     * lines in the log which caused bad lap parsing
     */
    public function testNotParsingExtraLapsAfterFinish2()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'driver.fabio.guarezi.crossed.finish.twice.after.RACE.OVER.DETECTED.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Assert driver on position 12
        $this->assertSame('Fabio Guarezi',
            $participants[11]->getDriver()->getName());
    }

    /**
     * Test exception when no session has been found
     *
     * TODO: This should be in ReaderTest. But we used static methods within
     * the Reader so testing was bit of a pain.
     *
     * @expectedException Simresults\Exception\NoSession
     */
    public function testNoSessionException()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'no.session.data.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession(1);
    }

    /*
     * Test whether empty driver names are not matched in a bad way.
     *
     * WRONG:
     *     GUID: Found car CAR_1 SESSION_ID:1 MODEL: tatuusfa1 (1) [ []]
     *     DRIVERNAME: GUID: Found car CAR_2 SESSION_ID:2 MODEL: tatuusfa1 (2)
     *     [Chindog [Team ASR]] DRIVERNAME: Chindog
     *
     * GOOD:
     *     Chindog
     */
    public function testEmptyDriverNames()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/'.
            'empty.driver.names.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession(3);

        // Get participants
        $participants = $session->getParticipants();

        // Assert driver name on position 14
        $this->assertSame('Chindog',
            $participants[11]->getDriver()->getName());
    }


    /*
     * Test better race over rank data which contains proper ranking. Testing
     * a log that is really depended on this ranking
     */
    public function testBetterRaceOverRankData()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.
            '/logs/assettocorsa-server/different.ranks.on.final.rank.txt');

        // Get the race session
        $session = Data_Reader::factory($file_path)->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Assert drivers on position 16, 17 and 18
        $this->assertSame('Jean-claude Menke',
            $participants[15]->getDriver()->getName());
        $this->assertSame('Yoan Morcamp',
            $participants[16]->getDriver()->getName());
        $this->assertSame('Arnaud Marechal',
            $participants[17]->getDriver()->getName());

        /**
         * Code below was changed on 2016-08-22. It seems that the person who
         * requested a proper RANK did not take into account that some of his
         * drivers did extra laps AFTER the finish. Based on the information of
         * test `testNotParsingExtraLapsAfterFinish2` I couldn't support this
         * supposely "fix" anymore
         */
        // // Assert drivers on position 16, 17 and 18
        // $this->assertSame('Yoan Morcamp',
        //     $participants[15]->getDriver()->getName());
        // $this->assertSame('Arnaud Marechal',
        //     $participants[16]->getDriver()->getName());
        // $this->assertSame('Jean-claude Menke',
        //     $participants[17]->getDriver()->getName());
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
        $this->assertSame(10, sizeof($sessions));

        // Get first session
        $session = $sessions[0];


        //-- Validate
        $this->assertSame(Session::TYPE_QUALIFY, $session->getType());
        $this->assertSame('Clasificacion', $session->getName());
        $this->assertSame(0, $session->getMaxLaps());
        $this->assertSame(15, $session->getMaxMinutes());
        $this->assertSame(4, $session->getLastedLaps());
        $this->assertSame('2014-08-31 16:50:59.575808 -0300 UYT',
            $session->getDateString());
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
        $this->assertSame('2014-08-31 16:50:59.575808 -0300 UYT',
            $session->getDateString());



        // Get third session
        $session = $sessions[2];

        //-- Validate
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame('Carrera', $session->getName());
        $this->assertSame(6, $session->getMaxLaps());
        $this->assertSame(0, $session->getMaxMinutes());
        $this->assertSame(6, $session->getLastedLaps());
        $this->assertSame('2014-08-31 16:50:59.575808 -0300 UYT',
            $session->getDateString());

        // Get tith session
        $session = $sessions[4];

        //-- Validate
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

        // Get 4th participant. Not the most last because they
        // vary in order because of different usort behavior due pivots
        // in PHP 5.6 vs HHVM/PHP7. But thats ok, those are all meaningless
        // DNF anyway
        $participant = $participants[3];
        $this->assertSame('Luis Miguel Barrera',
                          $participant->getDriver()->getName());
        $this->assertSame('tatuusfa1*',
                          $participant->getVehicle()->getName());
        $this->assertSame(687.901, $participant->getTotalTime());
        $this->assertSame(4, $participant->getPosition());
        $this->assertNull($participant->getGridPosition());
        $this->assertSame(Participant::FINISH_NONE,
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
     * Test reading the chat messages
     */
    public function testReadingSessionChat()
    {

        // Get third session
        $session = $this->getWorkingReader()->getSession(3);

        // Get chats
        $chats = $session->getChats();

        // Validate
        $this->assertSame(
            '[Leanlp Tava]: aca trantado de aprender la pista...jaja!',
            $chats[0]->getMessage());
        $this->assertSame(
            '[Leonardo Ratafia]: bien',
            $chats[1]->getMessage());
        $this->assertSame(
            '[Edu-Uruguay]: buenas noches',
            $chats[2]->getMessage());
        $this->assertSame(
            '[Leonardo Ratafia]: ahora',
            $chats[3]->getMessage());
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