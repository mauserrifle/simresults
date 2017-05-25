<?php

namespace spec\Simresults\Result;

use Simresults\CachedSession;
use Simresults\Result\Participant;
use Simresults\Result\Vehicle;
use Simresults\Result\Lap;
use Simresults\Result\Cache;
use Simresults\Result\Helper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class CachedSessionSpec extends ObjectBehavior
{
    protected $cache;
    protected $lap1;
    protected $part1;

    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Result\CachedSession');
    }

    function let(Cache $cache)
    {
        $this->cache = $cache;

        $this->beConstructedWith(new Helper, $cache);

        $this->part1 = new Participant;
        $this->lap1 =  new Lap;
    }

    function it_can_invalidate_cache(Cache $cache)
    {
        $cache->flush()->shouldBeCalled();
        $this->invalidateCache();
    }

    function it_caches_sorted_laps_by_time()
    {
        $this->itCaches('getLapsSortedByTime', array($this->lap1));
    }

    function it_caches_laps_by_number_sorted_by_time()
    {
        $this->itCaches('getLapsByLapNumberSortedByTime',
            array($this->lap1), array(1));
    }

    function it_caches_best_lap_by_number()
    {
        // This test cache
        $this->itCaches('getBestLapByLapNumber', $this->lap1, array(1));
    }

    function it_caches_best_laps_grouped_by_participant()
    {
        $this->itCaches('getBestLapsGroupedByParticipant', array($this->lap1));
    }

    function it_caches_laps_sorted_by_sector()
    {
        $this->itCaches('getLapsSortedBySector', array($this->lap1), array(1));
    }

    function it_caches_laps_by_sector_grouped_by_participant()
    {
        $this->itCaches('getBestLapsBySectorGroupedByParticipant',
            array($this->lap1), array(1));
    }

    function it_caches_laps_sorted_by_sector_by_lap_number()
    {
        $this->itCaches('getLapsSortedBySectorByLapNumber',
            array($this->lap1), array(2, 1));

    }

    function it_caches_best_lap_by_sector_by_lap_number(Cache $cache)
    {
        $this->itCaches('getBestLapBySectorByLapNumber',
            $this->lap1, array(2, 1));
    }

    function it_caches_bad_laps(Cache $cache)
    {
        // This test cache
        $this->itCaches('getBadLaps', array(), array(107));
    }

    function it_caches_led_most_participant()
    {
        $this->itCaches('getLedMostParticipant', $this->part1);
    }

    function it_caches_leading_participant()
    {
        $this->itCaches('getLeadingParticipant',
            $this->part1, array(1));
    }

    function it_caches_leading_participant_by_elapsed_time()
    {
        $this->itCaches('getLeadingParticipantByElapsedTime',
            $this->part1, array(1));
    }

    function it_caches_lasted_laps()
    {
        $this->itCaches('getLastedLaps', 1);
    }

    function it_caches_max_position()
    {
        $this->itCaches('getMaxPosition', 1);
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
        // Construct with real cache object
        $this->beConstructedWith(new Helper, new Cache);

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
        $this->cache->cacheParentCall($this, $method, $args)->willReturn($data);

        call_user_func_array(array($this, $method), $args)
            ->shouldReturn($data);
    }

}
