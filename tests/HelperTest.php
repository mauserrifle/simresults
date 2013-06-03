<?php
use Simresults\Helper;
use Simresults\Lap;

/**
 * Tests for the helper.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class HelperTest extends PHPUnit_Framework_TestCase {

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
     * Test formatting seconds to a readable minutes format
     */
    public function testFormattingTime()
    {
        // Validate
        $this->assertSame('01:40.5279', Helper::formatTime(100.5279));

        // Validate special case that had rounding problem (01:23.128099)
        $this->assertSame('01:23.1281', Helper::formatTime(83.1281));

        // Validate time with hours
        $this->assertSame('01:31:56.5879', Helper::formatTime(5516.5879));
    }

    /**
     * Test sorting laps by time
     */
    public function testSortingLapsByTime()
    {
        // Init laps
        $laps = array();

        $lap = new Lap;
        $lap->setTime(100.20);
        $laps[] = $lap;

        $lap = new Lap;
        $lap->setTime(100.10);
        $laps[] = $lap;

        $lap = new Lap;
        $lap->setTime(103.50);
        $laps[] = $lap;

        // Sort laps
        $laps = Helper::sortLapsByTime($laps);

        // Validate laps
        $this->assertSame(100.10, $laps[0]->getTime());
        $this->assertSame(100.20, $laps[1]->getTime());
        $this->assertSame(103.50, $laps[2]->getTime());
    }

    /**
     * Test soting laps by sector
     */
    public function testSortingLapsBySector()
    {
        // Init laps
        $laps = array();

        $lap = new Lap;
        $lap->setSectorTimes(array(100.20));
        $laps[] = $lap;

        $lap = new Lap;
        $lap->setSectorTimes(array(100.10));
        $laps[] = $lap;

        $lap = new Lap;
        $lap->setSectorTimes(array(103.50));
        $laps[] = $lap;

        // Sort laps
        $laps = Helper::sortLapsBySector($laps, 1);

        // Get sector info
        $sectors1 = $laps[0]->getSectorTimes();
        $sectors2 = $laps[1]->getSectorTimes();
        $sectors3 = $laps[2]->getSectorTimes();


        // Validate laps
        $this->assertSame(100.10, $sectors1[0]);
        $this->assertSame(100.20, $sectors2[0]);
        $this->assertSame(103.50, $sectors3[0]);
    }

}