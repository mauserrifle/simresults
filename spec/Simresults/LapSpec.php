<?php

namespace spec\Simresults;

use Simresults\Lap;
use Simresults\Participant;
use Simresults\Vehicle;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LapSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Lap::class);
    }

    function it_can_cast_to_string()
    {
        $this->setTime(130.7517)->__toString()->shouldReturn('02:10.7517');
    }

    function it_adds_sector()
    {
        $this->addSectorTime(53.2312)->getSectorTime(1)->shouldReturn(53.2312);
        $this->addSectorTime(32.2990)->getSectorTime(2)->shouldReturn(32.2990);
        $this->addSectorTime(45.2215)->getSectorTime(3)->shouldReturn(45.2215);
    }

    function it_adds_multiple_sectors()
    {
    	$this->setSectorTimes(array(53.2312, 32.2990, 45.2215));

        $this->getSectorTime(1)->shouldReturn(53.2312);
        $this->getSectorTime(2)->shouldReturn(32.2990);
        $this->getSectorTime(3)->shouldReturn(45.2215);
    }

    function it_calculates_total_time_from_sectors()
    {
    	$this->setSectorTimes(array(53.2312, 32.2990, 45.2215));
    	$this->getTime()->shouldReturn(130.7517);
    }

    function it_does_not_calculate_total_time_on_missing_sectors()
    {
    	$this->setSectorTimes(array(53.2312, 32.2990));
    	$this->getTime()->shouldReturn(null);
    }

    function it_calculates_gap_between_laps()
    {
    	$this->setTime(43.9237);
    	$lap2 = new Lap; $lap2->setTime(43.9362);
    	$this->getGap($lap2)->shouldReturn(0.0125);

    	$this->setTime(43.9362); $lap2->setTime(43.9237);
    	$this->getGap($lap2)->shouldReturn(-0.0125);

    	$lap2->setTime(null);
    	$this->getGap($lap2)->shouldReturn(null);
    }

    function it_calculates_gap_between_sectors()
    {
        $this->setSectorTimes(array(41.9237, 42.9237, 43.9237));

    	$lap2 = new Lap;
    	$lap2->setSectorTimes(array(41.9361, 42.9360, 43.9362));

    	$this->getSectorGap($lap2, 1)->shouldReturn(0.0124);
    	$this->getSectorGap($lap2, 2)->shouldReturn(0.0123);
    	$this->getSectorGap($lap2, 3)->shouldReturn(0.0125);

        $this->setSectorTimes(array(41.9361, 42.9360, 43.9362));
    	$lap2->setSectorTimes(array(41.9237, 42.9237, 43.9237));

    	$this->getSectorGap($lap2, 1)->shouldReturn(-0.0124);
    	$this->getSectorGap($lap2, 2)->shouldReturn(-0.0123);
    	$this->getSectorGap($lap2, 3)->shouldReturn(-0.0125);

    	$lap2->setSectorTimes(array());
    	$this->getSectorGap($lap2, 3)->shouldReturn(null);
    }

    function it_has_a_vehicle_or_uses_participants_vehicle(
    	Participant $participant)
    {
    	// Vehicle using participant relation
        $participant->getVehicle(true)->willReturn($vehicle = new Vehicle);
        $this->setParticipant($participant);
        $this->getVehicle()->shouldReturn($vehicle);

        // Vehicle using vehicle relation
        $this->setVehicle($vehicle2 = New Vehicle);
        $this->getVehicle()->shouldReturn($vehicle2);
    }

    function its_pit_lap_is_mutable()
    {
    	$this->setPitLap(true)->isPitLap()->shouldReturn(true);
    	$this->setPitLap(false)->isPitLap()->shouldReturn(false);
    }

    function its_pit_time_is_mutable()
    {
    	$this->setPitTime(46.5656);
    	$this->getPitTime()->shouldReturn(46.5656);
    }

    /**
     * Test calculating the pitstop times based on laps
     *
     * Pit time is calculated using 2 sectors that were part of the pitstop
     * (e.g. L1S3->L2S1) MINUS the averages of all non pitstop sectors.
     *
     * For inner understanding how these values are calculated, please also
     * the document `docs/RfactorReaderTest_testReadingPitTimes.ods`
     *
     * THIS TEST SCENARO:
     *
     * 	PREV    Lap 1  sectors:    42.9237,    42.9237,   44.9237
     * 	PIT     Lap 2  sectors:    41.9237,    42.9237,   53.9237
     * 	NEXT    Lap 3  sectors:    51.9237,    42.9237,   56.9237
     *
     * Pit time will be:
     *
     * (53.9237-((44.9237+56.9237)/2))    S3 pit time MINUS averages of others
     *           +                        PLUS
     * (51.9237-((42.9237+41.9237)/2))    S1 pit time MINUS averages of others
     *           =
     *        12.5000
     *
     * Note that when there are multiple laps that are pit. All of these are
     * ignored in average calculation.
     *
     */
    function it_calculates_pit_times(
    	Participant $participant,
    	Lap $prev_lap,
    	Lap $next_lap,
    	Lap $average_lap)
    {
    	// This is pit lap
    	$this->setSectorTimes(array(41.9237, 42.9237, 53.9237))
    	     ->setPitLap(true)
    	     ->setParticipant($participant);

    	// We only require sector 1 time of next lap
    	$next_lap->getSectorTime(1)->willReturn(51.9237);

    	// Participant returns laps
    	$participant->getLaps()->willReturn(array(
    		$prev_lap,
    		$this, // Pit lap!
    		$next_lap
    	));

    	// Participant returns average lap info
    	$average_lap->getSectorTime(3)->willReturn(50.9237);
    	$average_lap->getSectorTime(1)->willReturn(42.4237);
    	$participant->getAverageLap(true)->willReturn($average_lap);

    	// Validate 12.5000
    	$this->getPitTime()->shouldReturn(12.5000);

        // Validate that when sector 1 of next lap is missing, any calculation
        // on that sector is ignored, thus pit time is only based on sector 3
        // of this pit lap.
        $next_lap->getSectorTime(1)->willReturn(null);
        $this->getPitTime()->shouldReturn(
        	53.9237 - 50.9237 // Own sector 3 MINUS average of others
        );

        // Restore sector 1 time for next assertions
        $next_lap->getSectorTime(1)->willReturn(51.9237);


    	// Validate that when sector 3 is missing no calculation is done on
    	// that. We assume sector 1 of next lap would contain the time
    	$this->setSectorTimes(array(41.9237, 42.9237, null));
    	$this->getPitTime()->shouldReturn(
    		51.9237 // next lap sector
    		- ((42.9237+41.9237)/2) // MINUS average of others
    	);

    	// Validate that no calculation is done when hard pit time is available
    	$this->setPitTime(21);
    	$this->getPitTime()->shouldReturn(21);
    }

    function it_does_not_calulate_pit_times_on_missing_data(
    	Participant $participant)
    {
    	$this->setParticipant($participant);

    	// Not a pit lap
    	$this->setPitLap(false)->getPitTime()->shouldReturn(0);

    	// Is pit lap but participant does not have an average lap
    	$participant->getAverageLap(true)->willReturn(null);
    	$this->setPitLap(true)->getPitTime()->shouldReturn(0);
    }
}
