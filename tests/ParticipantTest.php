<?php
use Simresults\Driver;

use Simresults\Participant;

use Simresults\Lap;

use Simresults\Vehicle;

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

    /***
    **** Tests using simple data
    ***/

    /**
     * Test getting the vehicles from the participant using the laps
     * collection too
     */
    public function testGettingVehicles()
    {
        // Init participant
        $participant = new Participant;

        // Init two vehicles
        $vehicle1 = new Vehicle;
        $vehicle2 = new Vehicle;

        // Init laps with the vehicles
        $lap1 = new Lap; $lap1->setVehicle($vehicle1);
        $lap2 = new Lap; $lap2->setVehicle($vehicle2);

        // Set laps to participant
        $participant->setLaps(array($lap1, $lap2));

        // Test getting vehicles
        $this->assertSame(
            array($vehicle1, $vehicle2),
            $participant->getVehicles()
        );

        // Test getting one vehicle
        $this->assertSame($vehicle1, $participant->getVehicle());

        // Set new main vehicle on participant
        $participant->setVehicle($vehicle3 = new Vehicle);

        // Test getting the main set vehicle
        $this->assertSame($vehicle3, $participant->getVehicle());

        // Test that `getVehicle()` always returns the best lap vehicle
        $lap2->setTime(10);
        $this->assertSame($vehicle2, $participant->getVehicle());

    }

    /***
    **** Tests using the predefined test data
    ***/

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
        $this->assertNull($participant->getLap(7));
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

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get laps by time
            $laps_by_time = $participant->getLapsSortedByTime();

            // Validate laps
            $this->assertSame($laps[1], $laps_by_time[0]);
            $this->assertSame($laps[0], $laps_by_time[1]);
            $this->assertSame($laps[2], $laps_by_time[2]);
        }
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
        $this->assertSame(6, $participant->getNumberOfLaps());

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Test number of completed laps
            $this->assertSame(5, $participant->getNumberOfCompletedLaps());
        }


        //-- Create participant with 2 uncompleted laps and test NULL best lap
        $participant = new Participant;
        $participant->setLaps(array(
            new Lap,
            new Lap,
        ));
        // No best lap
        $this->assertNull($participant->getBestLap());
    }

    /**
     * Test getting the total time of a participant
     */
    public function testTotalTime()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Validate total time
        $this->assertSame(670.131, $participant->getTotalTime());

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

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the laps sorted by sector 3
            $laps = $participant->getLapsSortedBySector(3);

            // Validate the laps
            $this->assertSame($participant->getLap(2), $laps[0]);
            $this->assertSame($participant->getLap(3), $laps[1]);
            $this->assertSame($participant->getLap(4), $laps[2]);
            $this->assertSame($participant->getLap(1), $laps[3]);
            $this->assertSame($participant->getLap(5), $laps[4]);
        }
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

        // Set grid position to null
        $participant->setPosition(4);
        $participant->setGridPosition(null);

        // Validate no position difference
        $this->assertNull($participant->getPositionDifference());
    }

    /**
     * Test getting driver by number
     */
    public function testDriverByNumber()
    {
        // Create new participant
        $participant = new Participant;

        // Add drivers
        $participant->setDrivers(array(
            $driver1 = new Driver,
            $driver2 = new Driver,
        ));

        // Validate drivers
        $this->assertSame($driver1, $participant->getDriver());
        $this->assertSame($driver1, $participant->getDriver(1));
        $this->assertSame($driver2, $participant->getDriver(2));
    }

    /**
     * Test the number of laps a participant has led
     */
    public function testParticipantNumerOfLapsLed()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Validate
            $this->assertSame(2, $participant->getNumberOfLapsLed());
        }
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

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get average lap
            $average_lap = $participant->getAverageLap();

            // Validate
            $this->assertSame(134.0262, $average_lap->getTime());
            $this->assertSame(
                array(42.321, 39.86, 51.8452),
                $average_lap->getSectorTimes()
            );
            $this->assertSame($participant, $average_lap->getParticipant());
            $this->assertSame(array(), $average_lap->getAids());
            $this->assertNull($average_lap->getNumber());
            $this->assertNull($average_lap->getPosition());
            $this->assertNull($average_lap->getElapsedSeconds());

            // Get average lap excluding pitstop sectors and validate it
            $average_lap = $participant->getAverageLap(true);
            $this->assertSame(132.6853, $average_lap->getTime());
        }

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

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
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
        }

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
     * Test the consistency of a participant
     */
    public function testConsistency()
    {
        // Get populated participant
        $participant = $this->getParticipantWithLaps();

        // Add slow lap (exactly +21s of best lap)
        // This lap should be ignored in calculating
        //
        // NOTE: The lap 155.730 will also be ignored from populated test data
        $lap = new Lap;
        $participant->addLap(
            $lap->setTime($participant->getBestLap()->getTime()+21)
                 ->setSectorTimes(array(
                     $participant->getBestLap()->getSectorTime(1)+7,
                     $participant->getBestLap()->getSectorTime(2)+7,
                     $participant->getBestLap()->getSectorTime(3)+7,
                 ))
        );

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Test without ignoring first lap
            $this->assertSame(2.7405, $participant->getConsistency(false));
            $this->assertSame(97.82, $participant->getConsistencyPercentage(false));

            // Test with ignoring first lap
            $this->assertSame(3.0, $participant->getConsistency());
            $this->assertSame(97.61, $participant->getConsistencyPercentage());
        }

        // Validate empty participant
        $participant = new Participant; // Prevent cache
        $this->assertNull($participant->getConsistency(false));
        $this->assertNull($participant->getConsistencyPercentage(false));

        // Validate one lap participant
        $participant = new Participant; // Prevent cache
        $lap = new Lap; $participant->addLap($lap->setTime(128.211));
        $this->assertNull($participant->getConsistency(false));

        // Validate extra pit stop lap not causing devise by zero error
        $participant = new Participant; // Prevent cache
        $lap = new Lap; $participant->addLap(
            $lap->setTime(125.211));
        $lap = new Lap; $participant->addLap(
            $lap->setTime(128.211)->setPitLap(true));
        $this->assertNull($participant->getConsistency(false));
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
            $lap->setTime(128.730)
                 ->setSectorTimes(array(
                     40.601,
                     39.200,
                     48.929,
                 ))
                ->setPosition(2)
                ->setNumber(2)
                ->setAids(array(
                    'AutoShift'      => 3,
                ))
        );

        $lap = new Lap;
        $participant->addLap(
            $lap->setTime(131.730)
                 ->setSectorTimes(array(
                     41.601,
                     40.200,
                     49.929,
                 ))
                ->setPosition(2)
                ->setNumber(2)
                ->setAids(array(
                    'AutoShift'      => 3,
                ))
                ->setPitLap(true)
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