<?php

use Simresults\Data_Reader;

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
class RfactorReaderTest extends PHPUnit_Framework_TestCase {

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
     * Test reading the incidents (officially rfactor 2 XML does not include,
     * but we test this for compatibility with rf1 logs)
     */
    public function testReadingSessionIncidents()
    {
        // Get session
        $session = $this->getWorkingReader()->getSession();

        // Get incidents
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

        // Validate that we have no incidents for reviewing
        $this->assertSame(array(), $session->getIncidentsForReview());
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