<?php
use Simresults\Participant;

use Simresults\Lap;

/**
 * Tests for the participant.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class ParticipantTest extends PHPUnit_Framework_TestCase {

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
     * Test getting the lap by lap number
     */
    public function testLapByLapNumber()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get laps
        $laps = $participant->getLaps();

        // Validate second lap
        $this->assertSame($laps[1], $participant->getLap(2));

        // Validate non existing lap
        $this->assertNull($participant->getLap(5));
    }

    /**
     * Test getting the laps sorted by time
     */
    public function testLapsSortedByTime()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get laps
        $laps = $participant->getLaps();

        // Get laps by time
        $laps_by_time = $participant->getLapsSortedByTime();

        // Validate laps
        $this->assertSame($laps[1], $laps_by_time[0]);
        $this->assertSame($laps[0], $laps_by_time[1]);
        $this->assertSame($laps[2], $laps_by_time[2]);
    }

    /**
     * Test getting the best lap and the number of laps
     */
    public function testBestLapAndNumberOfLaps()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get best lap
        $best_lap = $participant->getBestLap();

        // Test lap time
        $this->assertSame(125.730, $best_lap->getTime());

        // Test number of laps
        $this->assertSame(4, $participant->getNumberOfLaps());

        // Test number of completed laps
        $this->assertSame(3, $participant->getNumberOfCompletedLaps());
    }

    /**
     * Test getting the total time of a participant
     */
    public function testTotalTime()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Validate total time
        $this->assertSame(409.671, $participant->getTotalTime());

        // Validate total time
        $this->assertSame(409.671, $participant->getTotalTime());

        // Force a new total time that overwrite lap calculation
        $participant->setTotalTime(409.678);

        // Validate new total time
        $this->assertSame(409.678, $participant->getTotalTime());
    }

    /**
     * Test getting the gap between total times of participants
     */
    public function testTotalTimeGap()
    {
        // Create 2 participants with 2 simple (long) lap times
        $participant = new Participant;

        $lap = new Lap;
        $lap->setTime(624.1161);
        $participant->addLap($lap);

        $lap = new Lap;
        $lap->setTime(626.1161);
        $participant->addLap($lap);

        $participant2 = new Participant;

        $lap = new Lap;
        $lap->setTime(625.3156);
        $participant2->addLap($lap);

        $lap = new Lap;
        $lap->setTime(627.3156);
        $participant2->addLap($lap);

        // Create participant that has been lapped
        $participant3 = new Participant;
        $lap = new Lap;
        $lap->setTime(1563.289);
        $participant3->addLap($lap);

        // Validate simple gaps
        $this->assertSame(2.3990, $participant->getTotalTimeGap($participant2));
        $this->assertSame(-2.3990, $participant2->getTotalTimeGap($participant));

        // Validate lapped gaps
        $this->assertSame(939.1729, $participant->getTotalTimeGap($participant3));
        $this->assertSame(-939.1729, $participant3->getTotalTimeGap($participant));
    }

    /**
     * Test getting the best lap for a particular sector
     */
    public function testBestSectorLap()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get sectors of best lap by sector
        $sectors1 = $participant->getBestLapBySector(1)->getSectorTimes();
        $sectors2 = $participant->getBestLapBySector(2)->getSectorTimes();
        $sectors3 = $participant->getBestLapBySector(3)->getSectorTimes();

        // Validate sectors
        $this->assertSame(39.601, $sectors1[0]);
        $this->assertSame(33.500, $sectors2[1]);
        $this->assertSame(47.929, $sectors3[2]);
    }

    /**
     * Test getting the laps orfdered by a particular sector
     */
    public function testLapsSortedByBySector()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get the laps sorted by sector 3
        $laps = $participant->getLapsSortedBySector(3);

        // Validate the laps
        $this->assertSame($participant->getLap(2), $laps[0]);
        $this->assertSame($participant->getLap(1), $laps[1]);
        $this->assertSame($participant->getLap(3), $laps[2]);
    }

    /**
     * Test the difference in starting and ending position of a participant
     */
    public function testPositionDifference()
    {
        // Create new participant
        $participant = new Participant;

        // Set position data
        $participant->setPosition(1);
        $participant->setGridPosition(5);

        // Validate positive position difference
        $this->assertSame(4, $participant->getPositionDifference());

        // Set position data
        $participant->setPosition(5);
        $participant->setGridPosition(2);

        // Validate negative position difference
        $this->assertSame(-3, $participant->getPositionDifference());

        // Set position data
        $participant->setPosition(4);
        $participant->setGridPosition(4);;

        // Validate no position difference
        $this->assertSame(0, $participant->getPositionDifference());
    }

    /**
     * Test the number of laps a participant has led
     */
    public function testParticipantNumerOfLapsLed()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Validate
        $this->assertSame(2, $participant->getNumberOfLapsLed());
    }


    /**
     * Test the aids used
     */
    public function testAids()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Validate aids
        $this->assertSame(
            array('PlayerControl' => null, 'TC' => 3, 'AutoShift' => 3),
            $participant->getAids()
        );
    }

    /**
     * Test the average lap
     */
    public function testAverageLap()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get average lap
        $average_lap = $participant->getAverageLap();

        // Validate
        $this->assertSame(136.557, $average_lap->getTime());
        $this->assertSame(
            array(43.1343, 39.9667, 53.456),
            $average_lap->getSectorTimes()
        );
        $this->assertSame($participant, $average_lap->getParticipant());
        $this->assertSame(array(), $average_lap->getAids());
        $this->assertNull($average_lap->getNumber());
        $this->assertNull($average_lap->getPosition());
        $this->assertNull($average_lap->getElapsedSeconds());

        // Validate empty participant
        $participant = new Participant;
        $this->assertNull($participant->getAverageLap());

        // Validate participant with one partial lap
        $participant = new Participant;
        $lap = new Lap;
        $this->assertNull($participant->addLap($lap
            ->setSectorTimes(array(14)))->getAverageLap());
    }

    /**
     * Test the best possible lap
     */
    public function testBestPossibleLap()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Get average lap
        $possible_lap = $participant->getBestPossibleLap();

        // Validate
        $this->assertSame(121.03, $possible_lap->getTime());
        $this->assertSame(
            array(39.601, 33.500, 47.929),
            $possible_lap->getSectorTimes()
        );
        $this->assertSame($participant, $possible_lap->getParticipant());
        $this->assertSame(array(), $possible_lap->getAids());
        $this->assertNull($possible_lap->getNumber());
        $this->assertNull($possible_lap->getPosition());
        $this->assertNull($possible_lap->getElapsedSeconds());

        // Validate empty participant
        $participant = new Participant;
        $this->assertNull($participant->getBestPossibleLap());

        // Validate participant with one partial lap
        $participant = new Participant;
        $lap = new Lap;
        $this->assertNull($participant->addLap($lap
            ->setSectorTimes(array(14)))->getBestPossibleLap());
    }


    /**
     * Returns a populated participant with laps
     *
     * @return  Participant
     */
    protected function getParticipantWithLaps()
    {
        // Create new participant
        $participant = new Participant;

        // Add some laps
        $lap = new Lap;
        $participant->addLap(
            $lap->setTime(128.211)
                 ->setSectorTimes(array(
                     40.201,
                     33.500,
                     54.510,
                 ))
                ->setPosition(1)
                ->setNumber(1)
                ->setAids(array(
                    'PlayerControl'  => null,
                    'TC'             => 3,
                ))
        );

        $lap = new Lap;
        $participant->addLap(
            $lap->setTime(125.730)
                 ->setSectorTimes(array(
                     39.601,
                     38.200,
                     47.929,
                 ))
                ->setPosition(2)
                ->setNumber(2)
                ->setAids(array(
                    'AutoShift'      => 3,
                ))
        );

        $lap = new Lap;
        $participant->addLap(
            $lap->setTime(155.730)
                 ->setSectorTimes(array(
                     49.601,
                     48.200,
                     57.929,
                 ))
                ->setPosition(1)
                ->setNumber(3)
        );

        // null lap as somtimes present in qualify sessions
        $lap = new Lap;
        $participant->addLap(
            $lap->setPosition(2)
                ->setNumber(4)
        );

        return $participant;
    }

}