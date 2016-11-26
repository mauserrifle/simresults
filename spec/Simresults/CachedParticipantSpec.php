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
        $this->lap1->setTime(15)
                   ->setSectorTimes(array(5,5,5))
                   ->setElapsedSeconds(16)
                   ->setNumber(1)
                   ->setPosition(1)
                   ->setParticipant($this->getWrappedObject());

        $this->setLaps(array($this->lap1));

        $this->vehicle1 = new Vehicle;
        $this->setVehicle($this->vehicle1);
    }




    function it_can_invalidate_cache(Cache $cache)
    {
        $cache->flush()->shouldBeCalled();
        $this->invalidateCache();
    }


    /**
     * getLap
     */
    function it_caches_lap()
    {
        $this->itCaches('getLap', $this->lap1, array(1));
    }

    function it_retrieves_lap_from_cache()
    {
        $this->itGetsCache('getLap', $this->lap1, array(1));
    }

    /**
     * getVehicles
     */
    function it_caches_vehicles()
    {
        $this->itCaches('getVehicles', array($this->vehicle1));
    }

    function it_retrieves_vehicles_from_cache()
    {
        $this->itGetsCache('getVehicles', array($this->vehicle1));
    }

    /**
     * getLapsSortedByTime
     */
    function it_caches_laps_sorted_by_time()
    {
        $this->itCaches('getLapsSortedByTime', array($this->lap1));
    }

    function it_retrieves_laps_sorted_by_time_from_cache()
    {
        $this->itGetsCache('getLapsSortedByTime', array($this->lap1));
    }

    /**
     * getBestLap
     */
    function it_caches_best_lap(Cache $cache)
    {
        // Relies on other cache
        $cache->put('P:getLapsSortedByTime', array($this->lap1))
              ->shouldBeCalled();
        $cache->get('P:getLapsSortedByTime')
              ->willReturn(null);

        $this->itCaches('getBestLap', $this->lap1);
    }

    function it_retrieves_best_lap_from_cache()
    {
        $this->itGetsCache('getBestLap', $this->lap1);
    }

    /**
     * getNumberOfCompletedLaps
     */
    function it_caches_number_of_completed_laps()
    {
        $this->itCaches('getNumberOfCompletedLaps', 1);
    }

    function it_retrieves_number_of_completed_laps_from_cache()
    {
        $this->itGetsCache('getNumberOfCompletedLaps', 1);
    }

    /**
     * getNumberOfLapsLed
     */
    function it_caches_number_of_laps_led()
    {
        $this->itCaches('getNumberOfLapsLed', 1);
    }

    function it_retrieves_number_of_laps_led_from_cache()
    {
        $this->itGetsCache('getNumberOfLapsLed', 1);
    }


    /**
     * getLapsSortedBySector
     */
    function it_caches_laps_sorted_by_sector()
    {
        $this->itCaches('getLapsSortedBySector', array($this->lap1), array(1));
    }

    function it_retrieves_laps_sorted_by_sector_from_cache()
    {
        $this->itGetsCache('getLapsSortedBySector', array($this->lap1), array(1));
    }


    /**
     * getBestLapBySector
     */
    function it_caches_best_lap_by_sector(Cache $cache)
    {
        // Relies on other cache too
        $cache->put('P:getLapsSortedBySector-1',
                        array($this->lap1))
             ->shouldBeCalled();
        $cache->get('P:getLapsSortedBySector-1')
              ->willReturn(null);

        $this->itCaches('getBestLapBySector', $this->lap1, array(1));
    }

    function it_retrieves_best_lap_by_sector_from_cache()
    {
        $this->itGetsCache('getBestLapBySector', $this->lap1, array(1));
    }

    /**
     * getAverageLap
     */
    function it_caches_average_lap(Cache $cache)
    {
        // Relies on other cache too
        $cache->put('P:getNumberOfCompletedLaps', 1)
             ->shouldBeCalled();
        $cache->get('P:getNumberOfCompletedLaps')
              ->willReturn(null);

        $cache->put('P:getAverageLap-1', Argument::type(Lap::class))
        	  ->shouldBeCalled();
        $cache->get('P:getAverageLap-1')
              ->willReturn(null);

        $this->getAverageLap(true)->shouldReturnAnInstanceOf(Lap::class);
    }

    function it_retrieves_average_lap_from_cache()
    {
        $this->itGetsCache('getAverageLap', Argument::type(Lap::class), array(true));
    }


    /**
     * getBestPossibleLap
     */
    function it_caches_best_possible_lap(Cache $cache)
    {
        foreach (array(1,2,3) as $sector) {
	        $cache->put('P:getBestLapBySector-'.$sector, Argument::type(Lap::class))
	             ->shouldBeCalled();
	        $cache->get('P:getBestLapBySector-'.$sector)
	              ->willReturn(null);
	    }

        $cache->put('P:getBestPossibleLap', Argument::type(Lap::class))
        	  ->shouldBeCalled();
        $cache->get('P:getBestPossibleLap')
              ->willReturn(null);

        foreach (array(1,2,3) as $sector) {
	        $cache->put('P:getLapsSortedBySector-'.$sector, array($this->lap1))
	        	  ->shouldBeCalled();
	        $cache->get('P:getLapsSortedBySector-'.$sector)
	              ->willReturn(null);
        }

        $this->getBestPossibleLap()->shouldReturnAnInstanceOf(Lap::class);
    }

    function it_retrieves_best_possible_lap_from_cache()
    {
        $this->itGetsCache('getBestPossibleLap', Argument::type(Lap::class));
    }


    /**
     * getConsistency
     */
    function it_caches_consistency(Cache $cache)
    {
        // Relies on other cache too
        $cache->put('P:getNumberOfCompletedLaps', 1)
             ->shouldBeCalled();
        $cache->get('P:getNumberOfCompletedLaps')
              ->willReturn(null);

        $this->itCaches('getConsistency', null, array(true));
    }

    function it_retrieves_consistency_from_cache(Cache $cache)
    {
        // Relies on other cache too
        $cache->get('P:getNumberOfCompletedLaps')
              ->willReturn(1);

        $this->itGetsCache('getConsistency', 1, array(true));
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
        $cache_key = 'P:'.$method;
        if ($args) {
            $cache_key .= '-'.implode('-', $args);
        }

        return $cache_key;
    }
}
