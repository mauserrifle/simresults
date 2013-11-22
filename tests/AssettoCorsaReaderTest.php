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


    /***
    **** Below tests use 1 log file
    ***/

    /**
     * Test reading the session
     */
    public function testReadingSession()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        //-- Validate
        $this->assertSame(Session::TYPE_PRACTICE, $session->getType());
        $this->assertSame('Hotlapping Session', $session->getName());
        $this->assertSame(0, $session->getMaxLaps());
        $this->assertSame(30, $session->getMaxMinutes());
        // TODO: Enable this
        // $this->assertSame(3, $session->getLastedLaps());
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
        $this->assertSame('imola', $track->getVenue());
    }

    /**
     * Test reading the participants of a session
     */
    public function testReadingSessionParticipants()
    {
        // Get participant
        $participants = $this->getWorkingReader()->getSession()
            ->getParticipants();
        $participant = $participants[0];

        $this->assertSame('Maurice van der Star',
                          $participant->getDriver()->getName());
        $this->assertSame('ferrari_458',
                          $participant->getVehicle()->getName());
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
        $this->assertSame(7, count($laps));

        // Get driver of first participant (only one cause there are no swaps)
        $driver = $participants[0]->getDriver();

        // Get first lap only
        $lap = $laps[0];

        // Validate lap
        $this->assertSame(1, $lap->getNumber());
        $this->assertSame(119.404, $lap->getTime());
        $this->assertSame($participants[0], $lap->getParticipant());
        $this->assertSame($driver, $lap->getDriver());

        // Get sector times
        $sectors = $lap->getSectorTimes();

        // Validate sectors
        $this->assertSame(34.668, $sectors[0]);
        $this->assertSame(46, $sectors[1]);
        $this->assertSame(38.736, $sectors[2]);
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
        $file_path = realpath(__DIR__.'/logs/assettocorsa/offline_hotlap_session.json');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }
}