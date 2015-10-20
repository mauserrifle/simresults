<?php
use Simresults\Lap;
use Simresults\Participant;
use Simresults\CachedParticipant;

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
     * Test the toString method of a Lap
     */
    public function testToString()
    {
        $lap = new Lap;
        $lap->setTime(130.7517);
        $this->assertEquals('02:10.7517',  $lap);
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

    /**
     * Test calculating the pitstop times based on laps
     *
     * Pit time is calculated using 2 sectors that were part of the pitstop
     * (e.g. L1S3->L2S1) MINUS the averages of all non pitstop sectors.
     *
     * For inner understanding how these values are calculated, please see
     * the document `docs/RfactorReaderTest_testReadingPitTimes.ods`
     */
    public function testCalculatingPitTimes()
    {
        // Init participant
        $participant = Participant::createInstance();
        $invalidate_cache = ($participant instanceof CachedParticipant);

        // Init new laps
        $laps = array();

        // Normal lap
        $lap1  = new Lap;
        $lap1->setSectorTimes(array(42.9237, 42.9237, 44.9237))
             ->setParticipant($participant);
        $laps[] = $lap1;

        // Pit lap
        $lap2  = new Lap;
        $lap2->setSectorTimes($lap2_sectors=array(41.9237, 42.9237, 53.9237))
             ->setParticipant($participant)
             ->setPitLap(true);
        $laps[] = $lap2;

        // Normal lap
        $lap3  = new Lap;
        $lap3->setSectorTimes(array(51.9237, 42.9237, 56.9237))
             ->setParticipant($participant);
        $laps[] = $lap3;

        // Set laps to participant
        $participant->setLaps($laps);

        //---- Validate pit times
        $this->assertSame(0, $lap1->getPitTime());
        $this->assertSame(
            // Sector 3 pit time MINUS averages of others
            // +
            // Sector 1 pit time MINUS averages of others
            (53.9237-((44.9237+56.9237)/2))+(51.9237-((42.9237+41.9237)/2)),
            $lap2->getPitTime());
        $this->assertSame(0, $lap3->getPitTime());



        //---- Validate special cases

        // Invalidate participant cache
        if ($invalidate_cache) $participant->invalidateCache();

        // Validate that when sector 3 is missing no calculation is done on that
        $lap2->setSectorTimes(array(41.9237, 42.9237, null));
        $this->assertSame((51.9237-((42.9237+41.9237)/2)),
                          $lap2->getPitTime());

        // Restore lap 2 sectors
        $lap2->setSectorTimes($lap2_sectors);

        //-----

        // Validate that when sector 1 of next lap is missing, any calculation
        // on that sector is ignored, thus pit time is only based on sector 3
        // of this pit lap. This also validates ignoring multipe pit sectors
        // that should be ignored in the averages as we're now marking a
        // second lap as pit lap

        // Invalidate participant cache
        if ($invalidate_cache) $participant->invalidateCache();

        // Set lap3 as pit lap
        $lap3->setPitLap(true);

        // Invalidate participant cache
        if ($invalidate_cache) $participant->invalidateCache();

        // Check time
        $this->assertSame(
            (56.9237-44.9237), // No average calculation, cause only 1 lap is
                               // non-pit
            $lap3->getPitTime()
        );

        //-----

        // Validate that calculation is done when hard pit time is available
        $lap2->setPitTime(21);
        $this->assertSame(21, $lap2->getPitTime());
    }

}