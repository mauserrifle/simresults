<?php

namespace spec\Simresults;

use Simresults\Participant;
use Simresults\CachedParticipant;
use Simresults\Lap;
use Simresults\Vehicle;
use Simresults\Cache;
use Simresults\Helper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CachedParticipantSpec extends ObjectBehavior
{
    protected $cache;
    protected $lap1;
    protected $vehicle1;

    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\CachedParticipant');
    }

    function let(Cache $cache)
    {
        $this->beConstructedWith(new Helper, $cache);

        $this->cache = $cache;
        $this->lap1 =  new Lap;
        $this->vehicle1 = new Vehicle;
    }

    function it_can_invalidate_cache(Cache $cache)
    {
        $cache->flush()->shouldBeCalled();
        $this->invalidateCache();
    }

    function it_caches_lap()
    {
        $this->itCaches('getLap', $this->lap1, array(1));
    }

    function it_caches_vehicles()
    {
        $this->itCaches('getVehicles', array($this->vehicle1));
    }

    function it_caches_laps_sorted_by_time()
    {
        $this->itCaches('getLapsSortedByTime', array($this->lap1));
    }

    function it_caches_best_lap(Cache $cache)
    {
        $this->itCaches('getBestLap', $this->lap1);
    }

    function it_caches_number_of_completed_laps()
    {
        $this->itCaches('getNumberOfCompletedLaps', 1);
    }

    function it_caches_number_of_laps_led()
    {
        $this->itCaches('getNumberOfLapsLed', 1);
    }

    function it_caches_laps_sorted_by_sector()
    {
        $this->itCaches('getLapsSortedBySector', array($this->lap1), array(1));
    }

    function it_caches_best_lap_by_sector(Cache $cache)
    {
        $this->itCaches('getBestLapBySector', $this->lap1, array(1));
    }

    function it_caches_average_lap(Cache $cache)
    {
        $this->itCaches('getAverageLap', $this->lap1, array(true));
    }

    function it_caches_best_possible_lap(Cache $cache)
    {
        $this->itCaches('getBestPossibleLap', $this->lap1);
    }

    function it_caches_consistency(Cache $cache)
    {
        $this->itCaches('getConsistency', null, array(true));
    }


    protected function itCaches($method, $data, $args=array())
    {
        $this->cache->cacheParentCall($this, $method, $args)->willReturn($data);

        call_user_func_array(array($this, $method), $args)
            ->shouldReturn($data);
    }
}
