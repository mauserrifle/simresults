<?php

namespace spec\Simresults;

use Simresults\CachedSession;
use Simresults\Participant;
use Simresults\Vehicle;
use Simresults\Lap;
use Simresults\Cache;
use Simresults\Helper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

// require_once(__DIR__.'/SessionSpec.php');

/**
 * @todo  Find a proper way to test cache invalidation so
 *        tests below that compare laps are not needed
 */
class CachedSessionSpec extends ObjectBehavior
{
    protected $lap1;
    protected $part1;

    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\CachedSession');
    }

    function let(Cache $cache)
    {
        $this->beConstructedWith(new Helper, $cache);

        $this->part1 = new Participant;

        $this->lap1 =  new Lap;
        $this->lap1->setTime(15)
                   ->setSectorTimes(array(5,5,5))
                   ->setElapsedSeconds(16)
                   ->setNumber(1)
                   ->setPosition(1)
                   ->setParticipant($this->part1);

        $this->part1->setLaps(array($this->lap1));

        $this->setParticipants(array($this->part1));
    }

    function it_can_invalidate_cache(Cache $cache)
    {
        $cache->flush()->shouldBeCalled();
        $this->invalidateCache();
    }

    /**
     * @todo Fix below test
     */
    // function it_invalidates_cache_on_clone(Cache $cache)
    // {
    //     $cache->flush()->shouldBeCalled();
    //     $clone = clone $this;
    // }


    /**
     * getLapsSortedByTime
     */

    function it_caches_sorted_laps_by_time(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedByTime',
                        array($this->lap1))
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedByTime')
              ->willReturn(null);

        $this->getLapsSortedByTime()->shouldReturn(array($this->lap1));

    }
    function it_retrieves_sorted_laps_by_time_from_cache(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedByTime',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedByTime')
              ->willReturn(array($this->lap1));

        $this->getLapsSortedByTime()->shouldReturn(array($this->lap1));
    }

    /**
     * getLapsByLapNumberSortedByTime
     */

    function it_caches_laps_by_number_sorted_laps_by_time(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsByLapNumberSortedByTime-1',
                        array($this->lap1))
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLapsByLapNumberSortedByTime-1')
              ->willReturn(null);

        $this->getLapsByLapNumberSortedByTime(1)->shouldReturn(array($this->lap1));

    }
    function it_retrieves_laps_by_number_sorted_laps_by_time(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsByLapNumberSortedByTime-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsByLapNumberSortedByTime-1')
              ->willReturn(array($this->lap1));

        $this->getLapsByLapNumberSortedByTime(1)->shouldReturn(array($this->lap1));
    }

    /**
     * getBestLapByLapNumber
     */

    function it_caches_best_lap_by_number(
        Cache $cache)
    {
        // Relies on other cache too
        $cache->put(CachedSession::class.'::getLapsByLapNumberSortedByTime-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsByLapNumberSortedByTime-1')
              ->willReturn(array($this->lap1));

        // This test cache
        $cache->put(CachedSession::class.'::getBestLapByLapNumber-1', $this->lap1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getBestLapByLapNumber-1')
              ->willReturn(null);

        $this->getBestLapByLapNumber(1)->shouldReturn($this->lap1);

    }
    function it_retrieves_best_lap_by_number(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapByLapNumber-1', $this->lap1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getBestLapByLapNumber-1')
              ->willReturn($this->lap1);

        $this->getBestLapByLapNumber(1)->shouldReturn($this->lap1);
    }

    /**
     * getBestLapsGroupedByParticipant
     */

    function it_caches_best_laps_grouped_by_participant(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapsGroupedByParticipant',
                        array($this->lap1))
             ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getBestLapsGroupedByParticipant')
              ->willReturn(null);

        $this->getBestLapsGroupedByParticipant()->shouldReturn(
            array($this->lap1));

    }
    function it_retrieves_best_laps_grouped_by_participant(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapsGroupedByParticipant',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getBestLapsGroupedByParticipant')
              ->willReturn(array($this->lap1));

        $this->getBestLapsGroupedByParticipant()->shouldReturn(array($this->lap1));
    }

    /**
     * getLapsSortedBySector
     */

    function it_caches_laps_sorted_by_sector(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedBySector-1',
                        array($this->lap1))
             ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedBySector-1')
              ->willReturn(null);

        $this->getLapsSortedBySector(1)->shouldReturn(
            array($this->lap1));

    }
    function it_retrieves_laps_sorted_by_sector(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedBySector-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedBySector-1')
              ->willReturn(array($this->lap1));

        $this->getLapsSortedBySector(1)->shouldReturn(array($this->lap1));
    }

    /**
     * getBestLapsBySectorGroupedByParticipant
     */

    function it_caches_laps_by_sector_grouped_by_participant(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapsBySectorGroupedByParticipant-1',
                        array($this->lap1))
             ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getBestLapsBySectorGroupedByParticipant-1')
              ->willReturn(null);

        $this->getBestLapsBySectorGroupedByParticipant(1)->shouldReturn(
            array($this->lap1));

    }
    function it_retrieves_laps_by_sector_grouped_by_participant(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapsBySectorGroupedByParticipant-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getBestLapsBySectorGroupedByParticipant-1')
              ->willReturn(array($this->lap1));

        $this->getBestLapsBySectorGroupedByParticipant(1)->shouldReturn(array($this->lap1));
    }


    /**
     * getLapsSortedBySectorByLapNumber
     */

    function it_caches_laps_sorted_by_sector_by_lap_number(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1',
                        array($this->lap1))
             ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1')
              ->willReturn(null);

        $this->getLapsSortedBySectorByLapNumber(2, 1)->shouldReturn(
            array($this->lap1));

    }
    function it_retrieves_laps_sorted_by_sector_by_lap_number(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1')
              ->willReturn(array($this->lap1));

        $this->getLapsSortedBySectorByLapNumber(2, 1)->shouldReturn(array($this->lap1));
    }


    /**
     * getBestLapBySectorByLapNumber
     */

    function it_caches_best_lap_by_sector_by_lap_number(
        Cache $cache)
    {
        // Relies on other cache too
        $cache->put(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1',
                        array($this->lap1))
             ->shouldBeCalled();
        $cache->get(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1')
              ->willReturn(null);

        // This test cache
        $cache->put(CachedSession::class.'::getBestLapBySectorByLapNumber-2-1',
                        $this->lap1)
             ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getBestLapBySectorByLapNumber-2-1')
              ->willReturn(null);

        $this->getBestLapBySectorByLapNumber(2, 1)->shouldReturn(
            $this->lap1);

    }
    function it_retrieves_best_lap_by_sector_by_lap_number(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBestLapBySectorByLapNumber-2-1',
                        $this->lap1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getBestLapBySectorByLapNumber-2-1')
              ->willReturn($this->lap1);

        $this->getBestLapBySectorByLapNumber(2, 1)->shouldReturn($this->lap1);
    }


    /**
     * getBadLaps
     */

    function it_caches_bad_laps(
        Cache $cache)
    {
        // Relies on other cache
        $cache->put(CachedSession::class.'::getLapsSortedByTime',
                        array($this->lap1))
              ->shouldBeCalled();
        $cache->get(CachedSession::class.'::getLapsSortedByTime')
              ->willReturn(null);

        // This test cache
        $cache->put(CachedSession::class.'::getBadLaps-107', array())
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getBadLaps-107')
              ->willReturn(null);

        $this->getBadLaps(107)->shouldReturn(array());

    }
    function it_retrieves_bad_laps(
        Cache $cache)
    {
        $cache->put(CachedSession::class.'::getBadLaps-107', array())
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getBadLaps-107')
              ->willReturn(array());

        $this->getBadLaps(107)->shouldReturn(array());
    }


    /**
     * getLedMostParticipant
     */

    function it_caches_led_most_participant(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLedMostParticipant',
                        $this->part1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLedMostParticipant')
              ->willReturn(null);

        $this->getLedMostParticipant()->shouldReturn($this->part1);

    }
    function it_retrieves_led_most_participant(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLedMostParticipant',
                        $this->part1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLedMostParticipant')
              ->willReturn($this->part1);

        $this->getLedMostParticipant()->shouldReturn($this->part1);
    }


    /**
     * getLeadingParticipant
     */

    function it_caches_leading_participant(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLeadingParticipant-1',
                        $this->part1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLeadingParticipant-1')
              ->willReturn(null);

        $this->getLeadingParticipant(1)->shouldReturn($this->part1);

    }
    function it_retrieves_leading_participant(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLeadingParticipant-1',
                        $this->part1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLeadingParticipant-1')
              ->willReturn($this->part1);

        $this->getLeadingParticipant(1)->shouldReturn($this->part1);
    }



    /**
     * getLeadingParticipantByElapsedTime
     */

    function it_caches_leading_participant_by_elapsed_time(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLeadingParticipantByElapsedTime-1',
                        $this->part1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLeadingParticipantByElapsedTime-1')
              ->willReturn(null);

        $this->getLeadingParticipantByElapsedTime(1)->shouldReturn($this->part1);

    }
    function it_retrieves_leading_participant_by_elapsed_time(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLeadingParticipantByElapsedTime-1',
                        $this->part1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLeadingParticipantByElapsedTime-1')
              ->willReturn($this->part1);

        $this->getLeadingParticipantByElapsedTime(1)->shouldReturn($this->part1);
    }

    /**
     * getLastedLaps
     */

    function it_caches_lasted_laps(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLastedLaps', 1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getLastedLaps')
              ->willReturn(null);

        $this->getLastedLaps()->shouldReturn(1);

    }
    function it_retrieves_lasted_laps(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getLastedLaps', 1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLastedLaps')
              ->willReturn(1);

        $this->getLastedLaps()->shouldReturn(1);
    }

    /**
     * getMaxPosition
     */

    function it_caches_max_position(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getMaxPosition', 1)
              ->shouldBeCalled();

        $cache->get(CachedSession::class.'::getMaxPosition')
              ->willReturn(null);

        $this->getMaxPosition()->shouldReturn(1);

    }
    function it_retrieves_max_position_from_cache(Cache $cache)
    {
        $cache->put(CachedSession::class.'::getMaxPosition', 1)
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getMaxPosition')
              ->willReturn(1);

        $this->getMaxPosition()->shouldReturn(1);
    }



    function it_splits_sessions_by_vehicle_class_without_cache_conflicts(
        Participant $part1, Participant $part2, Participant $part3,
        Vehicle $vehicle1, Vehicle $vehicle2, Vehicle $vehicle3)
    {

        $vehicle1->getClass()->willReturn('A class');
        $vehicle2->getClass()->willReturn('Another class');
        $vehicle3->getClass()->willReturn(null);

        $part1->getVehicle()->willReturn($vehicle1);
        $part1->getLaps()->willReturn(array(new Lap));

        $part2->getVehicle()->willReturn($vehicle2);
        $part2->getLaps()->willReturn(array(new Lap));

        $part3->getVehicle()->willReturn($vehicle3);
        $part3->getLaps()->willReturn(array(new Lap));

        $this->setParticipants(array($part1, $part2, $part3));

        // Get sorted laps already. Will use lateron to check cache
        // invalidation
        $laps_sorted = $this->getLapsSortedByTime();

        // Split and check laps not being same
        $sessions = $this->splitByVehicleClass();
        $sessions[0]->getLapsSortedByTime()->shouldNotReturn($laps_sorted);
    }
}
