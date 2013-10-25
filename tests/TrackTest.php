<?php
use Simresults\Track;

/**
 * Tests for the track.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class TrackTest extends PHPUnit_Framework_TestCase {

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
     * Test friendly track name
     */
    public function testFriendlyTrackName()
    {
        // Init new track
        $track = new Track;

        // Set names
        $track->setVenue('Sebring [Virtua_LM]');
        $track->setCourse('Sebring 12h Course (');
        $track->setEvent('12h Course');

        // Validate friendly name
        $this->assertSame(
            'Sebring [Virtua_LM], Sebring 12h Course (', // Set ( char to test
                                                         // for regex errors
            $track->getFriendlyName()
        );

        // Change event name
        $track->setEvent('12h Alternative course');

        // Validate friendly name
        $this->assertSame(
            'Sebring [Virtua_LM], Sebring 12h Course ( (12h Alternative course)',
            $track->getFriendlyName()
        );
    }

}