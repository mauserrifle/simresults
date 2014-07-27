<?php
use Simresults\Data_Reader_Rfactor2;
use Simresults\Data_Reader;
use Simresults\Session;
use Simresults\Participant;

/**
 * Tests for the rfactor2 reader.
 *
 * First tests are simple, the rest is all based on a full multiplayer race
 * log.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Rfactor2ReaderTest extends PHPUnit_Framework_TestCase {

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
    public function testCreatingNewRfactor2ReaderWithInvalidData()
    {
        $reader = new Data_Reader_Rfactor2('Unknown data for reader');
    }

    //---------------------------------------------------------------


    /***
    **** Simple tests that do not fit in the full race log used for testing.
    **** Most of the below tests are done on modfied XML files
    ***/


    /**
     * Test exception when the log file has no session included
     *
     * @expectedException Simresults\Exception\Reader
     */
    public function testCreatingNewRfactor2ReaderWithNoSession()
    {
        // The path to the data source
        $file_path = realpath(__DIR__.'/logs/rfactor2/nosession.xml');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get session
        $session = $reader->getSession();
    }



    /**
     * Test reading with different timezones
     *
     * WARNING: This test restores to UTC, otherwise other tests will fail
     */
    public function testReadingDifferentTimezones()
    {
        // GMT+2 time
        Data_Reader::$default_timezone = 'Etc/GMT+2';

        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race.xml'));

        // Get session
        $session = $reader->getSession();

        // Get session date
        $date = $session->getDate();

        // Validate timestamp of date
        $this->assertSame(1364153781, $date->getTimestamp());

        // Test timezone
        $this->assertSame('2013-03-24 17:36:21', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Etc/GMT+2', $date->getTimezone()->getName());


        // UTC time (also restore default)
        Data_Reader::$default_timezone = 'UTC';

        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race.xml'));

        // Get session
        $session = $reader->getSession();

        // Get session date
        $date = $session->getDate();

        // Validate timestamp of date
        $this->assertSame(1364153781, $date->getTimestamp());

        // Test timezone
        $this->assertSame('2013-03-24 19:36:21', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }


    /**
     * Test reading of bad laps without laps
     */
    public function testReadingBadLapsWithoutLaps()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice_without_laps.xml'));

        // Validate session type
        $this->assertSame(array(), $reader->getSession()->getBadLaps());
    }

    /**
     * Test reading of the session type of different log files
     */
    public function testReadingSessionType()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/qualify.xml'));

        // Validate session type
        $this->assertSame(Session::TYPE_QUALIFY,
            $reader->getSession()->getType());

        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice.xml'));

        // Validate session type
        $this->assertSame(Session::TYPE_PRACTICE,
            $reader->getSession()->getType());

        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice_changed_as_warmup.xml'));

        // Validate session type
        $this->assertSame(Session::TYPE_WARMUP,
            $reader->getSession()->getType());
    }

    /**
     * Test reading a uncompleted lap
     */
    public function testReadingUncompletedLap()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/qualify.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get the first participant
        $participant = $participants[0];

        // Get laps
        $laps = $participant->getLaps();

        // Validate lap time to be null
        $this->assertNull($laps[0]->getTime());
    }

    /**
     * Test reading compound information on laps
     *
     * This test uses numeric values as string because it uses an old result
     * XML, newer results include strings like 'soft'
     */
    public function testReadingCompoundOfLaps()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice_with_compound_info.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get participant
        $participant = $participants[9];

        // Get laps
        $laps = $participant->getLaps();

        // Validate compound values. This test uses numeric values as string
        // because it uses an old result XML, newer results include strings
        // like 'soft'
        $this->assertSame('0', $laps[1]->getFrontCompound());
        $this->assertSame('0', $laps[1]->getRearCompound());
        $this->assertSame('1', $laps[4]->getFrontCompound());
        $this->assertSame('1', $laps[4]->getRearCompound());
        $this->assertSame('2', $laps[7]->getFrontCompound());
        $this->assertSame('2', $laps[7]->getRearCompound());
        $this->assertSame('3', $laps[10]->getFrontCompound());
        $this->assertSame('3', $laps[10]->getRearCompound());
        $this->assertSame('4', $laps[13]->getFrontCompound());
        $this->assertSame('4', $laps[13]->getRearCompound());
        $this->assertSame('5', $laps[16]->getFrontCompound());
        $this->assertSame('5', $laps[16]->getRearCompound());

        // Test for different front and rear values
        $this->assertSame('1', $laps[18]->getFrontCompound());
        $this->assertSame('4', $laps[18]->getRearCompound());

        // Test for proper NULL values
        $this->assertNull($laps[19]->getFrontCompound());
        $this->assertNull($laps[19]->getRearCompound());
    }

    public function testReadingFuelUsage()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice_with_compound_info.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get the first participant
        $participant = $participants[0];

        // Get laps
        $laps = $participant->getLaps();

        // Validate fuel percentage on laps
        $this->assertSame(8.6, $laps[0]->getFuel());
        $this->assertSame(7.8, $laps[1]->getFuel());
        $this->assertSame(7.1, $laps[2]->getFuel());
        $this->assertSame(5.9, $laps[3]->getFuel());
        $this->assertSame(5.1, $laps[4]->getFuel());
        $this->assertSame(4.3, $laps[5]->getFuel());

        // Get the data reader for source data with bad fuel info (negative
        // values)
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get the first participant
        $participant = $participants[0];

        // Get laps
        $laps = $participant->getLaps();

        // Check whether fuel is NULL
        $this->assertNull($laps[0]->getFuel());
    }

    /**
     * Test reading pitstop laps
     */
    public function testReadingPitLaps()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice_with_compound_info.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get participant
        $participant = $participants[2];

        // Get laps
        $laps = $participant->getLaps();

        // Validate laps
        $this->assertFalse($laps[7]->isPitLap());
        $this->assertTrue($laps[8]->isPitLap());
        $this->assertFalse($laps[9]->isPitLap());
    }

    /**
     * Test reading finish statusses of participants
     */
    public function testReadingParticipantFinishStatus()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race_changed_finish_states.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate first participant
        $this->assertSame(
            Participant::FINISH_NORMAL,
            $participants[0]->getFinishStatus()
        );

        // Validate second participant
        $this->assertSame(
            Participant::FINISH_DQ,
            $participants[1]->getFinishStatus()
        );

        // Validate second last participant
        $this->assertSame(
            Participant::FINISH_DNF,
            $participants[3]->getFinishStatus()
        );
        $this->assertSame(
            'Engine',
            $participants[3]->getFinishStatusComment()
        );

        // Validate last participant
        $this->assertSame(
            Participant::FINISH_DNF,
            $participants[4]->getFinishStatus()
        );
        $this->assertSame(
            'Accident',
            $participants[4]->getFinishStatusComment()
        );

        // Get extra reader for finish status "None"
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/practice.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate first participant
        $this->assertSame(
            Participant::FINISH_NONE,
            $participants[0]->getFinishStatus()
        );
    }

    /**
     * Test reading the human state of a player when the data says not but
     * the aids say it is
     */
    public function testReadingDriverHumanWhenXmlSaysNot()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race_changed_human_state.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate human state of first driver
        $this->assertTrue($participants[0]->getDriver()->isHuman());

        // Validate a non-human driver where the log files it is a human.
        // It should be non-human based on its "UnknownControl" aid
        $this->assertFalse($participants[1]->getDriver()->isHuman());

        // Validate a driver with PlayerControl,UnknownControl,AIControl.
        // It should be human based on PlayerControl
        $this->assertTrue($participants[2]->getDriver()->isHuman());

         // Validate a driver with no aids at all. Should always be human
        $this->assertTrue($participants[3]->getDriver()->isHuman());

        // Validate a driver with AIControl. It should be non-human.
        $this->assertFalse($participants[4]->getDriver()->isHuman());
    }

    /**
     * Test reading corrupted race positions in XML. Someone is position 1, but
     * is clearly not looking at finish times.
     */
    public function testReadingCorruptedRacePositions()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race_with_corrupted_positions_added_DQ_driver.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate the drivers
        $this->assertSame('mauserrifle', $participants[0]->getDriver()->getName());
        $this->assertSame(1, $participants[0]->getPosition());

        $this->assertSame('Leonardo Saponti', $participants[1]->getDriver()->getName());
        $this->assertSame(2, $participants[1]->getPosition());

        $this->assertSame('Tig_green', $participants[2]->getDriver()->getName());
         $this->assertSame(3, $participants[2]->getPosition());

        $this->assertSame('Malek1th', $participants[3]->getDriver()->getName());
        $this->assertSame(4, $participants[3]->getPosition());
    }

    /**
     * Test reading corrupted qualify positions in XML. Someone is position 1,
     * but is clearly not looking at the best laps. This test would be the same
     * for warmup, practice and qualify.
     */
    public function testReadingCorruptedQualifyPositions()
    {
        // Get reader
        $reader = Data_Reader::factory(realpath(
               __DIR__.
            '/logs/rfactor2/race_with_corrupted_positions_changed_as_quali'
            .'fy.xml'));


        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate the drivers
        $this->assertSame('Tig_green', $participants[0]->getDriver()->getName());
        $this->assertSame(1, $participants[0]->getPosition());

        $this->assertSame('Leonardo Saponti', $participants[1]->getDriver()->getName());
        $this->assertSame(2, $participants[1]->getPosition());

        $this->assertSame('mauserrifle', $participants[2]->getDriver()->getName());
         $this->assertSame(3, $participants[2]->getPosition());

        $this->assertSame('Malek1th', $participants[3]->getDriver()->getName());
        $this->assertSame(4, $participants[3]->getPosition());


        //-- Do another test with a log file that bugged on a specific typo in
        //   sorting of participants

        // Get reader
        $reader = Data_Reader::factory(realpath(
               __DIR__.
            '/logs/rfactor2/qualify_with_corrupted_positions.xml'));

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate the first 4 drivers
        $this->assertSame('Capeta', $participants[0]->getDriver()->getName());
        $this->assertSame(1, $participants[0]->getPosition());

        $this->assertSame('lemming77', $participants[1]->getDriver()->getName());
        $this->assertSame(2, $participants[1]->getPosition());

        $this->assertSame('Coutie', $participants[2]->getDriver()->getName());
         $this->assertSame(3, $participants[2]->getPosition());

        $this->assertSame('CoNa', $participants[3]->getDriver()->getName());
        $this->assertSame(4, $participants[3]->getPosition());
    }


    /**
     * Same as test testReadingCorruptedQualifyPositions but player at position
     * 2 has no laps, thus should be last
     */
    public function testReadingCorruptedQualifyPositionsWithRemovedLaps()
    {
        // Get reader
           $reader = Data_Reader::factory(realpath(
               __DIR__.
               '/logs/rfactor2/race_with_corrupted_positions_changed_as_quali'
               .'fy_and_removed_best_lap_for_position_2_driver.xml'));


        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate the drivers
        $this->assertSame('Tig_green', $participants[0]->getDriver()->getName());
        $this->assertSame(1, $participants[0]->getPosition());

        $this->assertSame('mauserrifle', $participants[1]->getDriver()->getName());
         $this->assertSame(2, $participants[1]->getPosition());

        $this->assertSame('Malek1th', $participants[2]->getDriver()->getName());
        $this->assertSame(3, $participants[2]->getPosition());

        $this->assertSame('Leonardo Saponti', $participants[3]->getDriver()->getName());
        $this->assertSame(4, $participants[3]->getPosition());
    }

    /**
     * Test reading corrupted lap positions. Sometimes laps are marked 105 and
     * all lap info is missing. The reader needs to remove the laps if they
     * are ALL corrupted. When only one is corrupted (or more), the laps must
     * not be deleted and the values should be null.
     */
    public function testReadingCorruptedLapPositions()
    {
        // Get reader
           $reader = Data_Reader::factory(realpath(
               __DIR__.
               '/logs/rfactor2/race_with_corrupted_lap_positions.xml'));


        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get the position 6 driver with only corrupted laps
        $participant = $participants[5];

        // Validate laps, which should be all deleted
        $this->assertSame(array(), $participant->getLaps());

        // Get the position 7 driver with only corrupted laps
        $participant = $participants[6];

        // Validate laps,which should be all deleted
        $this->assertSame(array(), $participant->getLaps());



        // Get the position 2 driver laps two corrupted laps
           $laps = $participants[1]->getLaps();

           // Validate the number of laps, there should be no deletion
           $this->assertSame(11, count($laps));

           // Validate corrupted lap 2. It should have no info
           $this->assertNull($laps[1]->getPosition());
           $this->assertNull($laps[1]->getTime());
           $this->assertNull($laps[1]->getElapsedSeconds());

           // Validate corrupted lap 6. It should have no info
           $this->assertNull($laps[5]->getPosition());
           $this->assertNull($laps[5]->getTime());
           $this->assertNull($laps[5]->getElapsedSeconds());
    }


    /**
     * Test reading corrupted lap positions. Sometimes positions just do not
     * make sense. For example driver that wins has all these laps with pos 4.
     * That's really silly....
     */
    public function testReadingCorruptedLapPositions2()
    {
        // Get reader
           $reader = Data_Reader::factory(realpath(
               __DIR__.
               '/logs/rfactor2/race_with_corrupted_lap_positions2.xml'));


        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Validate first participant laps
        foreach ($participants[0]->getLaps() as $key => $lap)
        {
            $this->assertSame(1, $lap->getPosition());
        }

    }


    /**
     * Test reading the penalty messages
     */
    public function testReadingSessionPenalties()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race_with_penalties.xml'));

        // Get session
        $session = $reader->getSession();

        // Get penalties
        $penalties = $session->getPenalties();

        // Validate first penalty message
        $this->assertSame(
            'mauserrifle received Stop/Go penalty, 10s, 0laps. Result: '
           .'penalties=1, 1st=Stop/Go,10s',
            $penalties[0]->getMessage());

        // First penalty difference in seconds
        $seconds = $penalties[0]->getDate()->getTimestamp() -
            $session->getDate()->getTimestamp();

        // Validate first penalty seconds difference
        $this->assertSame(1001, $seconds);

        // Validate the real estimated time including miliseconds
        $this->assertSame(1001.6, $penalties[0]->getElapsedSeconds());
    }


    /**
     * Test reading a session with driver swaps. The result contains a driver
     * who drives twice. He should not be part of drivers list twice.
     */
    public function testReadingSessionWithDriverSwaps()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/race_changed_with_swaps.xml'));

        // Get session
        $session = $reader->getSession();

        // Get participants
        $participants = $session->getParticipants();

        // Get first participant drivers
        $drivers = $participants[0]->getDrivers();

        // Validate pitstop counter, it should be incremented when lower
        // than the actual swaps. XML says 1 in this case.
        $this->assertSame(3, $participants[0]->getPitstops());

        // Validate drivers
        $this->assertSame(3, count($drivers));
        $this->assertSame('Paul Schuman', $drivers[0]->getName());
        $this->assertTrue($drivers[0]->isHuman());
        $this->assertSame('Malek1th', $drivers[1]->getName());
        $this->assertTrue($drivers[2]->isHuman());
        $this->assertSame('Nick Woodbury', $drivers[2]->getName());
        $this->assertTrue($drivers[2]->isHuman());

        // Get first participant laps
        $laps = $participants[0]->getLaps();

        // Validate laps
        for ($lap=1; $lap <= 3; $lap++)
        {
            // Paul
            $this->assertSame($drivers[0], $laps[$lap-1]->getDriver());
        }
        for ($lap=4; $lap <= 5; $lap++)
        {
            // Malek
            $this->assertSame($drivers[1], $laps[$lap-1]->getDriver());
        }
        for ($lap=6; $lap <= 8; $lap++)
        {
            // Nick
            $this->assertSame($drivers[2], $laps[$lap-1]->getDriver());
        }
        for ($lap=9; $lap <= 10; $lap++)
        {
            // Malek
            $this->assertSame($drivers[1], $laps[$lap-1]->getDriver());
        }


        //--- Test second participant that has a empty swap tag

        // Get second participant drivers
        $drivers = $participants[1]->getDrivers();

        // Validate
        $this->assertSame(1, count($drivers));
        $this->assertSame('Jo Bonnier', $drivers[0]->getName());


    }

    /**
     * Test reading a file that contains invalid characters at the end. It
     * should read without errors. The issue happend for an user of
     * simresults.net. Maybe the user edited this file by hand, but library
     * should be able to detect, fix and read it
     */
    public function testReadingFileWithInvalidCharacters()
    {
        // Get the data reader for the given data source
        $reader = Data_Reader::factory(
            realpath(__DIR__.'/logs/rfactor2/qualify_with_invalid_chars.xml'));
    }



    //---------------------------------------------------------------


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
        $this->assertSame(1364153781, $date->getTimestamp());

        // Tets default timezone (UTC)
        $this->assertSame('2013-03-24 19:36:21', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());

        //-- Validate other
        $this->assertSame(Session::TYPE_RACE, $session->getType());
        $this->assertSame(0, $session->getMaxLaps());
        $this->assertSame(20, $session->getMaxMinutes());
        $this->assertSame(10, $session->getLastedLaps());
        $this->assertSame('RNLOALS_10.RFM', $session->getMod());

        $allowed_vehicles = $session->getAllowedVehicles();
        $this->assertSame('LolaT280', $allowed_vehicles[0]->getName());
        $this->assertFalse($session->isSetupFixed());
        $this->assertSame(
            array(
                'MechFailRate'   =>  2,
                'DamageMult'     =>  50,
                'FuelMult'       =>  0,
                'TireMult'       =>  7,
            ),
            $session->getOtherSettings()
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
        $this->assertSame('rFactor 2', $game->getName());
        $this->assertSame('1.155', $game->getVersion());
    }

    /**
     * Test reading the server of a session
     */
    public function testReadingSessionServer()
    {
        // Get the server
        $server = $this->getWorkingReader()->getSession()->getServer();

        // Validate server
        $this->assertSame('RookiesNight_WSu', $server->getName());
        $this->assertNull($server->getMotd());
        $this->assertFalse($server->isDedicated());
    }

    /**
     * Test reading the track of a session
     */
    public function testReadingSessionTrack()
    {
        // Get the track
        $track = $this->getWorkingReader()->getSession()->getTrack();

        // Validate track
        $this->assertSame('Sebring [Virtua_LM]', $track->getVenue());
        $this->assertSame('Sebring 12h Course', $track->getCourse());
        $this->assertSame(5856.1, $track->getLength());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Validate data
        $validate = array(
            array(
                'team'           => 'jl lafosse',
                'vehicle_name'   =>  'Lola T280 JL Lafosse',
                'vehicle_type'   =>  'LolaT280',
                'vehicle_class'  =>  'LolaT280',
                'vehicle_number' =>  31,
                'position'       =>  1,
                'grid_position'  =>  3,
                'class_position' =>  1,
                'class_grid_position'
                                 =>  3,
                'finish_status'  => Participant::FINISH_NORMAL,
                'finish_status_comment'
                                 => null,
            ),
            array(
                'team'           => 'Ecurie Bonnier',
                'vehicle_name'   =>  'Lola T280 le mans 1972',
                'vehicle_type'   =>  'LolaT280',
                'vehicle_class'  =>  'LolaT280',
                'vehicle_number' =>  1,
                'position'       =>  2,
                'grid_position'  =>  1,
                'class_position' =>  2,
                'class_grid_position'
                                 =>  1,
                'finish_status'  => Participant::FINISH_NORMAL,
                'finish_status_comment'
                                 => null,
            ),
            array(
                'team'           => 'Ecurie Bonnier',
                'vehicle_name'   =>  'Lola T280 1000 Kms Paris',
                'vehicle_type'   =>  'LolaT280',
                'vehicle_class'  =>  'LolaT280',
                'vehicle_number' =>  1,
                'position'       =>  3,
                'grid_position'  =>  5,
                'class_position' =>  3,
                'class_grid_position'
                                 =>  5,
                'finish_status'  => Participant::FINISH_NORMAL,
                'finish_status_comment'
                                 => null,
            ),
            array(
                'team'           => 'Noritake Takahara',
                'vehicle_name'   =>  'Lola T280 Fudji 1972',
                'vehicle_type'   =>  'LolaT280',
                'vehicle_class'  =>  'LolaT280',
                'vehicle_number' =>  1,
                'position'       =>  4,
                'grid_position'  =>  2,
                'class_position' =>  4,
                'class_grid_position'
                                 =>  2,
                'finish_status'  => Participant::FINISH_NORMAL,
                'finish_status_comment'
                                 => null,
            ),
            array(
                'team'           => 'scuderia filipinetti',
                'vehicle_name'   =>  'Lola T280 Tanaka 1972',
                'vehicle_type'   =>  'LolaT280',
                'vehicle_class'  =>  'LolaT280',
                'vehicle_number' =>  31,
                'position'       =>  5,
                'grid_position'  =>  4,
                'class_position' =>  5,
                'class_grid_position'
                                 =>  4,
                'finish_status'  => Participant::FINISH_NORMAL,
                'finish_status_comment'
                                 => null,
            ),
        );

        // Validate each validate array
        foreach ($validate as $index => $validate_data)
        {
            $this->assertSame($validate_data['team'],
                $participants[$index]->getTeam());
            $this->assertSame($validate_data['position'],
                $participants[$index]->getPosition());
            $this->assertSame($validate_data['class_position'],
                $participants[$index]->getClassPosition());
            $this->assertSame($validate_data['grid_position'],
                $participants[$index]->getGridPosition());
            $this->assertSame($validate_data['class_grid_position'],
                $participants[$index]->getClassGridPosition());
            $this->assertSame($validate_data['vehicle_name'],
                $participants[$index]->getVehicle()->getName());
            $this->assertSame($validate_data['vehicle_type'],
                $participants[$index]->getVehicle()->getType());
            $this->assertSame($validate_data['vehicle_class'],
                $participants[$index]->getVehicle()->getClass());
            $this->assertSame($validate_data['vehicle_number'],
                $participants[$index]->getVehicle()->getNumber());
            $this->assertSame($validate_data['finish_status'],
                $participants[$index]->getFinishStatus());
            $this->assertSame($validate_data['finish_status_comment'],
                $participants[$index]->getFinishStatusComment());
        }
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

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Aids used by driver
        $validate_aids = array(
            'PlayerControl'   => null,
            'TC'              => 3,
            'ABS'             => 2,
            'Stability'       => 2,
            'AutoShift'       => 3,
            'Clutch'          => null,
            'Invulnerable'    => null,
            'Opposite'        => null,
            'AutoLift'        => null,
            'AutoBlip'        => null,
        );

        // Validate lap
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(1, $lap->getPosition());
        $this->assertSame(130.7517, $lap->getTime());
        $this->assertSame(40.7067, $lap->getElapsedSeconds());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get aids
        $aids = $lap->getAids();

        // Validate aids
        $this->assertSame($validate_aids, $aids);

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(53.2312, $sectors[0]);
        $this->assertSame(32.2990, $sectors[1]);
        $this->assertSame(45.2215, $sectors[2]);
    }

    /**
     * Test reading the number of pitstops of participant
     */
    public function testReadingNumberOfPitstopsOfParticipant()
    {
        // Get participants
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();

        // Validate the number of pitstops
        $this->assertSame(0, $participants[0]->getPitstops());
        $this->assertSame(1, $participants[1]->getPitstops());
        $this->assertSame(1, $participants[2]->getPitstops());
        $this->assertSame(0, $participants[3]->getPitstops());
        $this->assertSame(0, $participants[4]->getPitstops());
    }

    /**
     * Test reading the chat messages
     */
    public function testReadingSessionChat()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        // Get chats
        $chats = $session->getChats();

        // Validate first chat message
        $this->assertSame(
            'Malek1th has left the race but vehicle has been stored in the '
           .'garage',
            $chats[0]->getMessage());

        // First chat difference in seconds
        $seconds = $chats[0]->getDate()->getTimestamp() -
            $session->getDate()->getTimestamp();

        // Validate first chat seconds difference
        $this->assertSame(1413, $seconds);

        // Validate the real estimated time including miliseconds
        $this->assertSame(1413.2, $chats[0]->getElapsedSeconds());
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
        $file_path = realpath(__DIR__.'/logs/rfactor2/race.xml');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}