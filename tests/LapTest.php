<?php
use Simresults\Lap;

/**
 * Tests for the lap.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class LapTest extends PHPUnit_Framework_TestCase {

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
     * Test getting a sector time by sector number
     */
    public function testSectorTimeByNumber()
    {
        // Init new lap
        $lap = new Lap;
        $lap->addSectorTime(53.2312);
        $lap->addSectorTime(32.2990);
        $lap->addSectorTime(45.2215);

        // Test sectors
        $this->assertSame(53.2312, $lap->getSectorTime(1));
        $this->assertSame(32.2990, $lap->getSectorTime(2));
        $this->assertSame(45.2215, $lap->getSectorTime(3));
    }

    /**
     * Test calculating total lap time based on the sector times only
     */
    public function testCalculatingLapTimeFromSectorTimes()
    {
        // Init new lap
        $lap = new Lap;
        $lap->addSectorTime(53.2312);
        $lap->addSectorTime(32.2990);
        $lap->addSectorTime(45.2215);

        // Test lap time
        $this->assertSame(130.7517, $lap->getTime());
    }

    /**
     * Test calculating the total lap time based on bad sector times
     */
    public function testCalculatingLapTimeFromBadSectorTimes()
    {
        // Init new lap
        $lap = new Lap;
        $lap->addSectorTime(53.2312);
        $lap->addSectorTime(32.2990);
        // Third is missing
        // .....

        // Test lap time
        $this->assertNull($lap->getTime());
    }

    /**
     * Test calculating the gap between lap times
     */
    public function testCalculatingGapBetweenLaps()
    {
        // Init new laps with times that cause floating point precision
        // problems
        $lap  = new Lap;
        $lap->setTime(43.9237);

        $lap2 = new Lap;
        $lap2->setTime(43.9362);

        // Validate gap
        $this->assertSame(0.0125, $lap->getGap($lap2));
        $this->assertSame(-0.0125, $lap2->getGap($lap));

        // Set lap 2 time to null
        $lap2->setTime(null);

        // Validate
        $this->assertNull($lap->getGap($lap2));
    }

    /**
     * Test calculating the gap between sector times
     */
    public function testCalculatingGapBetweenSectors()
    {
        // Init new laps with times that cause floating point precision
        // problems
        $lap  = new Lap;
        $lap->setSectorTimes(array(41.9237, 42.9237, 43.9237));

        $lap2 = new Lap;
        $lap2->setSectorTimes(array(41.9361, 42.9360, 43.9362));

        // Validate gap sector 1
        $this->assertSame(0.0124, $lap->getSectorGap($lap2, 1));
        $this->assertSame(-0.0124, $lap2->getSectorGap($lap, 1));
        // Validate gap sector 2
        $this->assertSame(0.0123, $lap->getSectorGap($lap2, 2));
        $this->assertSame(-0.0123, $lap2->getSectorGap($lap, 2));
        // Validate gap sector 3
        $this->assertSame(0.0125, $lap->getSectorGap($lap2, 3));
        $this->assertSame(-0.0125, $lap2->getSectorGap($lap, 3));

        // Unset sector times of lap 2
        $lap2->setSectorTimes(array());

        // Validate
        $this->assertNull($lap->getSectorGap($lap2, 3));
    }

}