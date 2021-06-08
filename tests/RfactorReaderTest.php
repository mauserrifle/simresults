<?php

use Simresults\Data_Reader;
use Simresults\Incident;

/**
 * Tests for the rfactor2 reader using some rFactor 1 differences.
 *
 * Contains tests specially for rfactor 1 results. Only differences
 * with the rfactor 2 logs are tested here.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class RfactorReaderTest extends \PHPUnit\Framework\TestCase {

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
     * Test reading the incidents (officially rfactor 2 XML does not include,
     * but we test this for compatibility with rf1 logs)
     */
    public function testReadingSessionIncidents()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        $participants = $session->getParticipants();
        $incidents = $session->getIncidents();

        // Validate first incident
        $this->assertSame(
            'BiteMe(2) reported contact (0.03) with another vehicle '
           .'[SLAK]tenebrea(3)',
            $incidents[0]->getMessage());

        // First incident difference in seconds
        $seconds = $incidents[0]->getDate()->getTimestamp() -
            $session->getDate()->getTimestamp();

        // Validate first incident seconds difference
        $this->assertSame(195, $seconds);

        // Validate the real estimated time including miliseconds
        $this->assertSame(195.5, $incidents[0]->getElapsedSeconds());

        // Type and participants
        $this->assertSame(Incident::TYPE_CAR, $incidents[0]->getType());
        $this->assertSame($participants[3], $incidents[0]->getParticipant());
        $this->assertSame($participants[1], $incidents[0]->getOtherParticipant());

        // Validate that we have no incidents for reviewing
        $this->assertSame(array(), $session->getIncidentsForReview());
    }

    /**
     * Test reading too many incidents
     */
    public function testReadingTooManyIncidenrs()
    {
        // Get data as raw text
        $data = file_get_contents(realpath(__DIR__.'/logs/rfactor1/race.xml'));

        // Make sure there are exactly 2000 incidents
        $extra_incidents = '';
        for ($i=1; $i<=2001-63; $i++) // 2001 because we replace last incident
        {                             // lateron
            $extra_incidents .= "<Incident et=\"1769.1\">...</Incident>\n";
        }

        // Replace incidents
        $data_replaced = str_replace(
            '<Incident et="1768.1">Lead(7) reported contact (0.13) with Track</Incident>',
            $extra_incidents,
            $data);

        // Get session
        $session =  $reader = Data_Reader::factory($data_replaced)->getSession();

        // Validate number of incidents
        $this->assertSame(2000, count($session->getIncidents()));


        // Add 1 more incident
        $extra_incidents .= "<Incident et=\"1769.1\">...</Incident>\n";
        $data_replaced = str_replace(
            '<Incident et="1768.1">Lead(7) reported contact (0.13) with Track</Incident>',
            $extra_incidents,
            $data);

        // Get session
        $session =  $reader = Data_Reader::factory($data_replaced)->getSession();

        // Validate number of incidents and incident itself
        $this->assertSame(1, count($incidents = $session->getIncidents()));
        $this->assertSame('Sorry, way too many incidents to show!',
            $incidents[0]->getMessage());
    }

    /**
     * Test reading without errors on incidents with safety car
     */
    public function testReadingWithoutErrorsOnSafetyCarIncidents()
    {
        $file_path = realpath(
            __DIR__.'/logs/automobilista/race.with.safety.car.incidents.xml');
        $reader = Data_Reader::factory($file_path);
        $session = $reader->getSession();
    }

    /**
     * Test reading the game of a session
     */
    public function testReadingSessionGame()
    {
        // Get the game
        $game = $this->getWorkingReader()->getSession()->getGame();

        // Validate game
        $this->assertSame('rFactor', $game->getName());
        $this->assertSame('1.255', $game->getVersion());
    }

    /**
     * Test reading pitstop times
     *
     * For inner understanding how these values are calculated, please see
     * the document `docs/RfactorReaderTest_testReadingPitTimes.ods`
     */
    public function testReadingPitTimes()
    {
        // Get the data reader for the given data source
        $reader = $this->getWorkingReader();

        // Get participants
        $participants = $reader->getSession()->getParticipants();

        // Get participant (Will Munney)
        $participant = $participants[0];

        // Get laps
        $laps = $participant->getLaps();

        // Validate pit lap
        $this->assertTrue($laps[12]->isPitLap());
        $this->assertSame(32.9445, $laps[12]->getPitTime());
    }


    /**
     * Test reading incidents worth reviewing
     */
    public function testReadingSessionIncidentsForReview()
    {
        // The path to the data source
        $file_path = realpath(
            __DIR__.'/logs/rfactor1/race_changed_incidents.xml');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Get the session
        $session = $reader->getSession();

        // Get the incidents for reviewing
        $review_incidents = $session->getIncidentsForReview();

        // Validate first incident
        $this->assertSame(
            'BiteMe(2) reported contact (0.60) with another vehicle '
           .'[SLAK]tenebrea(3)',
            $review_incidents[0]->getMessage());

        // Validate second incident
        $this->assertSame(
            'BiteMe(2) reported contact (0.75) with another vehicle '
           .'[SLAK]tenebrea(3)',
            $review_incidents[1]->getMessage());
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
        $file_path = realpath(__DIR__.'/logs/rfactor1/race.xml');

        // Get the data reader for the given data source
        $reader = Data_Reader::factory($file_path);

        // Return reader
        return $reader;
    }


}