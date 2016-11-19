<?php

namespace spec\Simresults;

use Simresults\CachedSession;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Lap;
use Simresults\Vehicle;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

// require_once(__DIR__.'/SessionSpec.php');

/**
 * @todo  Find a proper way to test cache invalidation so
 *        tests below that compare laps are not needed
 */
class CachedSessionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(CachedSession::class);
    }

    function it_splits_sessions_by_vehicle_class_without_cache_conflicts(
    	Participant $part1, Participant $part2, Participant $part3,
    	Vehicle $vehicle1, Vehicle $vehicle2, Vehicle $vehicle3)
    {

    	$vehicle1->getClass()->willReturn('A class');
    	$vehicle2->getClass()->willReturn('Another class');
    	$vehicle3->getClass()->willReturn(null);

    	$part1->getVehicle()->willReturn($vehicle1);
    	$part1->getLaps()->willReturn([new Lap]);

    	$part2->getVehicle()->willReturn($vehicle2);
    	$part2->getLaps()->willReturn([new Lap]);

    	$part3->getVehicle()->willReturn($vehicle3);
    	$part3->getLaps()->willReturn([new Lap]);

    	$this->setParticipants([$part1, $part2, $part3]);

    	// Get sorted laps already. Will use lateron to check cache
    	// invalidation
    	$laps_sorted = $this->getLapsSortedByTime();

    	// Split and check laps not being same
    	$sessions = $this->splitByVehicleClass();
    	$sessions[0]->getLapsSortedByTime()->shouldNotReturn($laps_sorted);
    }
}
