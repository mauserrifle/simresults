<?php

namespace spec\Simresults;

use Simresults\Participant;
use Simresults\Lap;
use Simresults\Vehicle;
use Simresults\Driver;
use Simresults\Helper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class ParticipantSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Participant');
    }

    function it_has_vehicle_or_multiple_vehicles_from_laps()
    {
        // Init laps
        $lap1 = new Lap; $lap1->setVehicle($vehicle1 = new Vehicle);
        $lap2 = new Lap; $lap2->setVehicle($vehicle2 = new Vehicle);
        $this->setLaps(array($lap1, $lap2));

        // Returns vehicles based on laps
        $this->getVehicles()->shouldReturn(array($vehicle1, $vehicle2));
        $this->getVehicle()->shouldReturn($vehicle1);

        // Does not return vehicle from laps if requested to ignore them
        $this->getVehicle(true)->shouldReturn(null);

        // Returns main vehicle
        $this->setVehicle($vehicle3 = new Vehicle);
        $this->getVehicle()->shouldReturn($vehicle3);

        // Returns best lap vehicle
        $lap2->setTime(10);
        $this->getVehicle()->shouldReturn($vehicle2);
    }

    function it_returns_lap_by_number(Lap $lap1, Lap $lap2)
    {
        $lap1->getNumber()->willReturn(1); $lap2->getNumber()->willReturn(2);
        $this->setLaps(array($lap1, $lap2));

        $this->getLap(2)->shouldReturn($lap2);
        $this->getLap(7)->shouldReturn(null);
    }

    function it_can_sort_laps_by_time(Helper $helper, Lap $lap1, Lap $lap2)
    {
        $this->beConstructedWith($helper);
        $this->setLaps(array($lap1, $lap2));

        $helper->sortLapsByTime(array($lap1, $lap2))
               ->willReturn(array($lap2, $lap1));

        $this->getLapsSortedByTime()->shouldReturn(array($lap2, $lap1));
    }

    function it_counts_number_of_laps(Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->setLaps(array($lap1, $lap2, $lap3));
        $this->getNumberOfLaps()->shouldReturn(3);

        $lap3->isCompleted()->willReturn(true);
        $this->getNumberOfCompletedLaps()->shouldReturn(1);
    }

    function it_counts_number_of_pit_laps(Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $lap1->isPitLap()->willReturn(true);
        $lap2->isPitLap()->willReturn(true);
        $lap3->isPitLap()->willReturn(false);

        $this->setLaps(array($lap1, $lap2, $lap3));
        $this->getPitstops()->shouldReturn(2);
    }

    function it_has_best_lap(Helper $helper, Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);
        $this->setLaps(array($lap1, $lap2, $lap3));

        $helper->sortLapsByTime(array($lap1, $lap2, $lap3))
               ->willReturn(array($lap2, $lap1, $lap3));

        // No best lap when all laps are not completed
        $this->getBestLap()->shouldReturn(null);

        // One completed lap
        $lap1->isCompleted()->willReturn(true);
        $lap1->getTime()->willReturn(50.20);
        $this->getBestLap()->shouldReturn($lap1);

        // Another faster lap completed
        $lap2->isCompleted()->willReturn(true);
        $lap2->getTime()->willReturn(49.10);
        $this->getBestLap()->shouldReturn($lap2);
    }

    function it_has_total_time_or_calculates_it_from_laps(
        Lap $lap1, Lap $lap2, Lap $lap3)
    {
        // Two proper laps
        $lap1->getTime()->willReturn(55.1234);
        $lap1->isCompleted()->willReturn(true);
        $lap2->getTime()->willReturn(46.1234);
        $lap2->isCompleted()->willReturn(true);

        // Non completed lap
        $lap3->getTime()->willReturn(102.3512);
        $lap3->isCompleted()->willReturn(false);

        $this->setLaps(array($lap1, $lap2, $lap3));
        $this->getTotalTime()->shouldReturn(101.2468);

        $this->setTotalTime(101.0000);
        $this->getTotalTime()->shouldReturn(101.0000);
    }

    function it_calculates_gaps_between_participants(
       Lap $lap1, Lap $lap2,
       Participant $participant2, Participant $participant3,
       Participant $participant4)
    {
        // This participant with total time 1250.2322
        $lap1 = new Lap; $lap1->setTime(624.1161)->setNumber(1);
        $lap2 = new Lap; $lap2->setTime(626.1161)->setNumber(2);
        $this->setLaps(array($lap1, $lap2));

        // Other slower participant with same amount of laps
        $participant2->getTotalTime()->willReturn(1252.6312);
        $participant2->getNumberOfLaps()->willReturn(2);

        // Test gap (1252.6312 - 1250.2322)
        $this->getTotalTimeGap($participant2)->shouldReturn(2.3990);


        // Other slow and lapped participant because it ran only 1 lap
        $participant3->getTotalTime()->willReturn(1563.289);
        $participant3->getNumberOfLaps()->willReturn(1);

        // Test (1563.289 - 1250.2322   + 626.1161 (LAST LAP OF LEADING))
        $this->getTotalTimeGap($participant3)->shouldReturn(939.1729);


        // Other faster leading participant
        // We expect that adding time is done on the third lap of this
        // leadng participant
        $participant4->getTotalTime()->willReturn(1673.2322);
        $participant4->getNumberOfLaps()->willReturn(3);

        $lap = new Lap; $lap->setTime(621.1234);
        $participant4->getLap(3)->willReturn($lap);

        // Test (1673.2322 - 1250.2322  - 621.1234)
        $this->getTotalTimeGap($participant4)->shouldReturn(-198.1234);
    }

    function it_can_sort_laps_by_sector(Helper $helper, Lap $lap1, Lap $lap2)
    {
        $this->beConstructedWith($helper);
        $this->setLaps(array($lap1, $lap2));

        $helper->sortLapsBySector(array($lap1, $lap2), 2)
               ->willReturn(array($lap2, $lap1));
        $this->getLapsSortedBySector(2)->shouldReturn(array($lap2, $lap1));
    }

    function it_has_best_lap_by_sector(
        Helper $helper, Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);
        $this->setLaps(array($lap1, $lap2, $lap3));

        $helper->sortLapsBySector(array($lap1, $lap2, $lap3), 2)
               ->willReturn(array($lap2, $lap3, $lap1));

        $this->getBestLapBySector(2)->shouldReturn($lap2);
    }

    function it_calculates_position_difference_between_grid_and_finish()
    {
        $this->setPosition(1)->setGridPosition(5);
        $this->getPositionDifference()->shouldReturn(4);

        $this->setPosition(5)->setGridPosition(2);
        $this->getPositionDifference()->shouldReturn(-3);

        $this->setPosition(4)->setGridPosition(4);
        $this->getPositionDifference()->shouldReturn(0);

        $this->setGridPosition(null);
        $this->getPositionDifference()->shouldReturn(null);
    }

    public function it_returns_driver_by_number(
        Driver $driver1, Driver $driver2)
    {
        $this->setDrivers(array($driver1, $driver2));

        $this->getDriver()->shouldReturn($driver1);
        $this->getDriver(1)->shouldReturn($driver1);
        $this->getDriver(2)->shouldReturn($driver2);
    }

    public function it_calculates_the_percentage_a_driver_has_driven(
        Driver $driver1, Driver $driver2, Driver $driver3, Driver $driver4,
        Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $lap1->getDriver()->willReturn($driver1);
        $lap2->getDriver()->willReturn($driver1);
        $lap3->getDriver()->willReturn($driver2);

        $this->setDrivers(array($driver1, $driver2, $driver3));
        $this->setLaps(array($lap1, $lap2, $lap3));

        $this->getDriverPercentage($driver1)->shouldReturn(66.67);
        $this->getDriverPercentage($driver2)->shouldReturn(33.33);
        $this->getDriverPercentage($driver3)->shouldReturn(0.00);

        // No division by zero error
        $this->setDrivers(array($driver4));
        $this->setLaps(array());
        $this->getDriverPercentage($driver4)->shouldReturn(0.00);
    }

    function it_calculates_how_much_laps_led(Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $lap1->getPosition()->willReturn(3);
        $lap2->getPosition()->willReturn(1);
        $lap3->getPosition()->willReturn(1);

        $this->setLaps(array($lap1, $lap2, $lap3));
        $this->getNumberOfLapsLed()->shouldReturn(2);
    }

    function it_has_aids(Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $lap1->getAids()->willReturn(array('PlayerControl' => null, 'TC' => 3));
        $lap2->getAids()->willReturn(array('TC' => 3));
        $lap3->getAids()->willReturn(array('AutoShift' => 3));

        $this->setLaps(array($lap1, $lap2, $lap3));
        $this->getAids()->shouldReturn(
            array('PlayerControl' => null, 'TC' => 3, 'AutoShift' => 3));
    }

    function it_calculates_an_average_lap()
    {
        // No average on missing data
        $this->getAverageLap()->shouldReturn(null);
        $lap = new Lap; $lap->setSectorTimes(array(14));
        $this->addLap($lap)
             ->getAverageLap()->shouldReturn(null);

        $this->setLaps(array()); // Reset
        $lap1 = new Lap; $lap1->setSectorTimes(array(40.201, 33.500, 54.510));
        $lap2 = new Lap; $lap2->setSectorTimes(array(49.601, 48.200, 57.929))
                              ->setPitLap(true);
        $lap3 = new Lap; $lap3->setSectorTimes(array(41.601, 40.200, 49.929));
        $this->setLaps(array($lap1, $lap2, $lap3, new Lap));


        // Complete average
        $average = $this->getAverageLap();
        $average->getTime()->shouldReturn(138.557);
        $average->getSectorTimes()->shouldReturn(array(43.801, 40.6333, 54.1227));
        $average->getNumber()->shouldReturn(null);
        $average->getPosition()->shouldReturn(null);
        $average->getElapsedSeconds()->shouldReturn(null);
        $average->getParticipant()->shouldReturn($this);

        // Without pit sectors (Sector 3 of pit lap and sector 1 of next lap)
        $average = $this->getAverageLap($exclude_pitstop_sectors=true);
        $average->getTime()->shouldReturn(137.7538);
        $average->getSectorTimes()->shouldReturn(array(44.901, 40.6333, 52.2195));
    }

    function it_calculates_best_possible_lap()
    {
        // No best on missing data
        $this->getBestPossibleLap()->shouldReturn(null);
        $lap = new Lap; $lap->setSectorTimes(array(14));
        $this->addLap($lap)
             ->getBestPossibleLap()->shouldReturn(null);

        $this->setLaps(array()); // Reset
        $lap1 = new Lap; $lap1->setSectorTimes(array(40.201, 33.500, 54.510));
        $lap2 = new Lap; $lap2->setSectorTimes(array(49.601, 48.200, 57.929));
        $lap3 = new Lap; $lap3->setSectorTimes(array(41.601, 40.200, 49.929));
        $this->setLaps(array($lap1, $lap2, $lap3, new Lap));

        $best = $this->getBestPossibleLap();
        $best->getTime()->shouldReturn(123.63);
        $best->getSectorTimes()->shouldReturn(array(40.201, 33.500, 49.929));
        $best->getNumber()->shouldReturn(null);
        $best->getPosition()->shouldReturn(null);
        $best->getElapsedSeconds()->shouldReturn(null);
        $best->getParticipant()->shouldReturn($this);
    }

    function it_calculates_consistency()
    {
        // No consistency on missing data
        $this->getConsistency(false)->shouldReturn(null);
        $this->getConsistencyPercentage(false)->shouldReturn(null);

        // No consistency on 1 lap
        $this->getConsistency(false)->shouldReturn(null);
        $this->getConsistencyPercentage(false)->shouldReturn(null);

        // No devision by zero error on 1 normal and 1 pit lap
        $lap1 = new Lap; $lap1->setTime(125.211);
        $lap2 = new Lap; $lap2->setTime(128.211)->setPitLap(true);
        $this->setLaps(array($lap1, $lap2))
             ->getConsistency(false)->shouldReturn(null);

        $lap1 = new Lap; $lap1->setTime(155.73);
        $lap2 = new Lap; $lap2->setTime(152.211); // Second is the best lap
        $lap3 = new Lap; $lap3->setTime(158.73);

        //-- Laps below should be ignored
        $lap4 = new Lap; $lap4->setTime(152.211+21); // +21s of best lap
        $lap5 = new Lap; $lap5->setTime(161.731)->setPitLap(true);

        $this->setLaps(array($lap1, $lap2, $lap3, $lap4, $lap5, new Lap));

        $this->getConsistency(false)->shouldReturn(5.019);
        $this->getConsistencyPercentage(false)->shouldReturn(96.70);

        $this->getConsistency()->shouldReturn(6.519);
        $this->getConsistencyPercentage()->shouldReturn(95.72);
    }

    function it_adds_laps_and_will_fix_missing_number(Lap $lap1, Lap $lap2)
    {
        // First lap
        $lap1->getNumber()->willReturn(null);
        $lap1->setNumber(1)->shouldBeCalled();
        $this->addLap($lap1);

        // Second lap will be numbered based on lap1
        $lap1->getNumber()->willReturn(1);
        $lap2->getNumber()->willReturn(null);
        $lap2->setNumber(2)->shouldBeCalled();
        $this->addLap($lap2);
    }
}
