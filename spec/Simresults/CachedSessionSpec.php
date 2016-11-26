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

class CachedSessionSpec extends ObjectBehavior
{
    protected $cache;
    protected $lap1;
    protected $part1;

    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\CachedSession');
    }

    function let(Cache $cache)
    {
        $this->cache = $cache;

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
     * getLapsSortedByTime
     */
    function it_caches_sorted_laps_by_time(Cache $cache)
    {
        $this->itCaches('getLapsSortedByTime', array($this->lap1));
    }

    function it_retrieves_sorted_laps_by_time_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLapsSortedByTime', array($this->lap1));
    }

    /**
     * getLapsByLapNumberSortedByTime
     */
    function it_caches_laps_by_number_sorted_by_time(Cache $cache)
    {
        $this->itCaches('getLapsByLapNumberSortedByTime',
            array($this->lap1), array(1));
    }
    function it_retrieves_laps_by_number_sorted_by_time_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLapsByLapNumberSortedByTime',
            array($this->lap1), array(1));
    }

    /**
     * getBestLapByLapNumber
     */
    function it_caches_best_lap_by_number(Cache $cache)
    {
        // Relies on other cache too
        $cache->put(CachedSession::class.'::getLapsByLapNumberSortedByTime-1',
                        array($this->lap1))
              ->shouldNotBeCalled();

        $cache->get(CachedSession::class.'::getLapsByLapNumberSortedByTime-1')
              ->willReturn(array($this->lap1));

        // This test cache
        $this->itCaches('getBestLapByLapNumber', $this->lap1, array(1));
    }
    function it_retrieves_best_lap_by_number_from_cache(Cache $cache)
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
    function it_caches_best_laps_grouped_by_participant(Cache $cache)
    {
        $this->itCaches('getBestLapsGroupedByParticipant',
            array($this->lap1));

    }
    function it_retrieves_best_laps_grouped_by_participant_from_cache(
        Cache $cache)
    {
        $this->itGetsCache('getBestLapsGroupedByParticipant',
            array($this->lap1));
    }

    /**
     * getLapsSortedBySector
     */
    function it_caches_laps_sorted_by_sector(Cache $cache)
    {
        $this->itCaches('getLapsSortedBySector',
            array($this->lap1), array(1));
    }
    function it_retrieves_laps_sorted_by_sector_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLapsSortedBySector',
            array($this->lap1), array(1));

    }

    /**
     * getBestLapsBySectorGroupedByParticipant
     */
    function it_caches_laps_by_sector_grouped_by_participant(Cache $cache)
    {
        $this->itCaches('getBestLapsBySectorGroupedByParticipant',
            array($this->lap1), array(1));
    }
    function it_retrieves_laps_by_sector_grouped_by_participant_from_cache(
        Cache $cache)
    {
        $this->itGetsCache('getBestLapsBySectorGroupedByParticipant',
            array($this->lap1), array(1));
    }


    /**
     * getLapsSortedBySectorByLapNumber
     */
    function it_caches_laps_sorted_by_sector_by_lap_number(Cache $cache)
    {
        $this->itCaches('getLapsSortedBySectorByLapNumber',
            array($this->lap1), array(2, 1));

    }
    function it_retrieves_laps_sorted_by_sector_by_lap_number_from_cache(
        Cache $cache)
    {
        $this->itGetsCache('getLapsSortedBySectorByLapNumber',
            array($this->lap1), array(2, 1));
    }


    /**
     * getBestLapBySectorByLapNumber
     */
    function it_caches_best_lap_by_sector_by_lap_number(Cache $cache)
    {
        // Relies on other cache too
        $cache->put(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1',
                        array($this->lap1))
             ->shouldBeCalled();
        $cache->get(CachedSession::class.'::getLapsSortedBySectorByLapNumber-2-1')
              ->willReturn(null);

        // This test cache
        $this->itCaches('getBestLapBySectorByLapNumber',
            $this->lap1, array(2, 1));
    }
    function it_retrieves_best_lap_by_sector_by_lap_number_from_cache(Cache $cache)
    {
        $this->itGetsCache('getBestLapBySectorByLapNumber',
            $this->lap1, array(2, 1));
    }


    /**
     * getBadLaps
     */
    function it_caches_bad_laps(Cache $cache)
    {
        // Relies on other cache
        $cache->put(CachedSession::class.'::getLapsSortedByTime',
                        array($this->lap1))
              ->shouldBeCalled();
        $cache->get(CachedSession::class.'::getLapsSortedByTime')
              ->willReturn(null);

        // This test cache
        $this->itCaches('getBadLaps', array(), array(107));
    }
    function it_retrieves_bad_laps_from_cache(Cache $cache)
    {
        $this->itGetsCache('getBadLaps', array(), array(107));
    }


    /**
     * getLedMostParticipant
     */
    function it_caches_led_most_participant(Cache $cache)
    {
        $this->itCaches('getLedMostParticipant', $this->part1);
    }
    function it_retrieves_led_most_participant_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLedMostParticipant', $this->part1);
    }


    /**
     * getLeadingParticipant
     */
    function it_caches_leading_participant(Cache $cache)
    {
        $this->itCaches('getLeadingParticipant',
            $this->part1, array(1));
    }
    function it_retrieves_leading_participant_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLeadingParticipant',
            $this->part1, array(1));
    }



    /**
     * getLeadingParticipantByElapsedTime
     */
    function it_caches_leading_participant_by_elapsed_time(Cache $cache)
    {
        $this->itCaches('getLeadingParticipantByElapsedTime',
            $this->part1, array(1));
    }
    function it_retrieves_leading_participant_by_elapsed_time_from_cache(
        Cache $cache)
    {
        $this->itGetsCache('getLeadingParticipantByElapsedTime',
            $this->part1, array(1));
    }

    /**
     * getLastedLaps
     */
    function it_caches_lasted_laps(Cache $cache)
    {
        $this->itCaches('getLastedLaps', 1);
    }
    function it_retrieves_lasted_laps_from_cache(Cache $cache)
    {
        $this->itGetsCache('getLastedLaps', 1);
    }

    /**
     * getMaxPosition
     */
    function it_caches_max_position(Cache $cache)
    {
        $this->itCaches('getMaxPosition', 1);
    }
    function it_retrieves_max_position_from_cache(Cache $cache)
    {
        $this->itGetsCache('getMaxPosition', 1);
    }




    /**
     * @todo Fix below test? Somehow we cant test __clone :(. If we fix we dont
     *       need the big test at end of this file
     */
    // function it_invalidates_cache_on_clone(Cache $cache)
    // {
    //     $cache->flush()->shouldBeCalled();
    //     $clone = clone $this;
    // }
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



    protected function itCaches($method, $data, $args=array())
    {
        $cache_key = $this->cacheKey($method, $args);

        $this->cache->put($cache_key, $data)->shouldBeCalled();
        $this->cache->get($cache_key)->willReturn(null);

        call_user_func_array(array($this, $method), $args)
            ->shouldReturn($data);
    }

    protected function itGetsCache($method, $data, $args=array())
    {
        $cache_key = $this->cacheKey($method, $args);

        $this->cache->put($cache_key, $data)->shouldNotBeCalled();
        $this->cache->get($cache_key)->willReturn($data);

        call_user_func_array(array($this, $method), $args)
            ->shouldReturn($data);
    }

    protected function cacheKey($method, $args)
    {
        $cache_key = CachedSession::class.'::'.$method;
        if ($args) {
            $cache_key .= '-'.implode('-', $args);
        }

        return $cache_key;
    }

}
