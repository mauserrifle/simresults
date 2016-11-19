<?php

namespace spec\Simresults;

use Simresults\Session;
use Simresults\Participant;
use Simresults\Lap;
use Simresults\Incident;
use Simresults\Vehicle;
use Simresults\Cut;
use Simresults\Helper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SessionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Session::class);
    }

    function it_lasted_laps(Participant $part1, Participant $part2)
    {
    	$part1->getNumberOfLaps()->willReturn(3);
    	$part2->getNumberOfLaps()->willReturn(4);

    	$this->setParticipants([$part1, $part2]);
    	$this->getLastedLaps()->shouldReturn(4);
    }

    function it_sorts_laps_by_time(
    	Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

   		$part1_laps = [new Lap, new Lap];
    	$part1->getLaps()->willReturn($part1_laps);
   		$part2_laps = [new Lap, new Lap];
    	$part2->getLaps()->willReturn($part2_laps);

    	$this->setParticipants([$part1, $part2]);

    	$expect = array_merge($part2_laps, $part1_laps);
    	$helper->sortLapsByTime(array_merge($part1_laps, $part2_laps))
    	       ->willReturn($expect);
    	$this->getLapsSortedByTime()->shouldReturn($expect);
    }

    function it_sorts_laps_by_number(
    	Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

    	$part1->getLap(2)->willReturn($lap1 = new Lap);
    	$part2->getLap(2)->willReturn($lap2 = new Lap);

    	$this->setParticipants([$part1, $part2]);

    	$expect = [$lap2, $lap1];
    	$helper->sortLapsByTime([$lap1, $lap2])
    	       ->willReturn($expect);
    	$this->getLapsByLapNumberSortedByTime(2)->shouldReturn($expect);
    }

    function it_has_best_laps_grouped_by_participant(
    	Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

    	$part1->getBestLap()->willReturn($lap1 = new Lap);
    	$part2->getBestLap()->willReturn($lap2 = new Lap);

    	$this->setParticipants([$part1, $part2]);

    	$expect = [$lap2, $lap1];
    	$helper->sortLapsByTime([$lap1, $lap2])
    	       ->willReturn($expect);
    	$this->getBestLapsGroupedByParticipant()->shouldReturn($expect);
    }

    function it_has_best_lap(Participant $part1, Participant $part2)
    {
    	$this->getBestLap()->shouldReturn(null);

    	$this->setParticipants([$part1, $part2]);

    	$part1->getLaps()->willReturn([new Lap]);
    	$part2->getLaps()->willReturn([]);

    	$this->getBestLap()->shouldReturn(null);

    	$lap1 = (new Lap)->setTime(30);
    	$lap2 = (new Lap)->setTime(20.99);
    	$lap3 = (new Lap)->setTime(60);

    	$part1->getLaps()->willReturn([$lap1, $lap2]);
    	$part2->getLaps()->willReturn([$lap3]);


    	$this->getBestLap()->shouldReturn($lap2);
    }

    function it_has_bad_laps(Participant $part1, Participant $part2)
    {
    	$this->getBadLaps()->shouldReturn([]);

    	$lap1 = (new Lap)->setTime(30);
    	$lap2 = (new Lap)->setTime(20.99);
    	$lap3 = (new Lap)->setTime(60);
    	$lap4 = (new Lap)->setTime(23);

    	$part1->getLaps()->willReturn([$lap1, $lap2]);
    	$part2->getLaps()->willReturn([$lap3, $lap4]);

    	$this->setParticipants([$part1, $part2]);

    	// Default 107%
    	$this->getBadLaps()->shouldReturn([$lap4, $lap1, $lap3]);

    	// Different percentage than default
    	$this->getBadLaps(285)->shouldReturn([$lap3]);
    	$this->getBadLaps(286)->shouldReturn([]);
    }

    function it_has_ledmost_participant(Participant $part1, Participant $part2)
    {
    	$this->getLedMostParticipant()->shouldReturn(null);

    	$part1->getNumberOfLaps()->willReturn(3);
    	$part1->getNumberOfLapsLed()->willReturn(1);
    	$part2->getNumberOfLaps()->willReturn(4);
    	$part2->getNumberOfLapsLed()->willReturn(3);

    	$this->setParticipants([$part1, $part2]);
    	$this->getLedMostParticipant()->shouldReturn($part2);
    }

    function it_has_winning_participant(Participant $part1, Participant $part2)
    {
    	$this->getWinningParticipant()->shouldReturn(null);

    	$this->setParticipants([$part1, $part2]);
    	$this->getWinningParticipant()->shouldReturn($part1);
    }

    function it_has_leading_participant_by_lap_number(
    	Participant $part1, Participant $part2, Lap $lap1, Lap $lap2)
    {
    	$this->getLeadingParticipant(3)->shouldReturn(null);

    	$lap1->getPosition()->willReturn(2);
    	$lap2->getPosition()->willReturn(1);

    	$part1->getLap(3)->willReturn($lap1);
    	$part2->getLap(3)->willReturn($lap2);

    	$this->setParticipants([$part1, $part2]);

    	$this->getLeadingParticipant(3)->shouldReturn($part2);
    }

    function it_has_leading_participant_by_elapsed_time(
    	Participant $part1, Participant $part2, Participant $part3,
    	Lap $lap1,Lap $lap2,Lap $lap3)
    {
    	$this->getLeadingParticipantByElapsedTime(3)->shouldReturn(null);

    	$lap1->getElapsedSeconds()->willReturn(23);
    	$lap2->getElapsedSeconds()->willReturn(20);
    	$lap3->getElapsedSeconds()->willReturn(null);

    	$lap2->getParticipant()->willReturn($part2);

    	$part1->getLap(3)->willReturn($lap1);
    	$part2->getLap(3)->willReturn($lap2);
    	$part3->getLap(3)->willReturn($lap3);

    	$this->setParticipants([$part1, $part2, $part3]);
    	$this->getLeadingParticipantByElapsedTime(3)->shouldReturn($part2);
    }

    function it_has_max_position(
    	Participant $part1, Participant $part2, Lap $lap1,Lap $lap2,Lap $lap3)
    {
    	$lap1->getPosition()->willReturn(1);
    	$lap2->getPosition()->willReturn(7);
    	$lap3->getPosition()->willReturn(3);

    	$part1->getLaps()->willReturn([$lap1]);
    	$part2->getLaps()->willReturn([$lap2, $lap3]);

    	$this->setParticipants([$part1, $part2]);

    	$this->getMaxPosition()->shouldReturn(7);
    }

    function it_can_sort_laps_by_sector(
    	Helper $helper, Participant $part1, Participant $part2,
    	Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);

        $part1->getLaps()->willReturn([$lap1]);
        $part2->getLaps()->willReturn([$lap2, $lap3]);

        $this->setParticipants([$part1, $part2]);

        $helper->sortLapsBySector([$lap1, $lap2, $lap3], 2)
               ->willReturn([$lap2, $lap3, $lap1]);

        $this->getLapsSortedBySector(2)->shouldReturn([$lap2, $lap3, $lap1]);
    }

    function it_has_best_lap_by_sector(
        Helper $helper, Participant $part1, Participant $part2,
        Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);

        $part1->getLaps()->willReturn([$lap1]);
        $part2->getLaps()->willReturn([$lap2, $lap3]);

        $this->setParticipants([$part1, $part2]);

        $helper->sortLapsBySector([$lap1, $lap2, $lap3], 2)
               ->willReturn([$lap2, $lap3, $lap1]);

        $this->getBestLapBySector(2)->shouldReturn($lap2);
    }

    function it_has_best_laps_by_sector_grouped_by_participant(
    	Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

    	$part1->getBestLapBySector(1)->willReturn($lap1 = new Lap);
    	$part2->getBestLapBySector(1)->willReturn($lap2 = new Lap);

    	$this->setParticipants([$part1, $part2]);

    	$expect = [$lap2, $lap1];
    	$helper->sortLapsBySector([$lap1, $lap2], 1)
    	       ->willReturn($expect);
    	$this->getBestLapsBySectorGroupedByParticipant(1)
    	     ->shouldReturn($expect);
    }

    function it_can_sort_laps_by_sector_and_lap_number(
    	Helper $helper, Participant $part1, Participant $part2,
    	Lap $lap1, Lap $lap2)
    {
        $this->beConstructedWith($helper);

        $part1->getLap(5)->willReturn($lap1);
        $part2->getLap(5)->willReturn($lap2);

        $this->setParticipants([$part1, $part2]);

        $helper->sortLapsBySector([$lap1, $lap2], 2)
               ->willReturn([$lap2, $lap1]);

        $this->getLapsSortedBySectorByLapNumber(2, 5)
             ->shouldReturn([$lap2, $lap1]);
    }

    function it_has_best_lap_by_lap_number(
    	Helper $helper, Participant $part1, Participant $part2,
    	Lap $lap1, Lap $lap2)
    {
        $this->beConstructedWith($helper);

        $part1->getLap(2)->willReturn($lap1);
        $part2->getLap(2)->willReturn($lap2);

        $this->setParticipants([$part1, $part2]);

        $helper->sortLapsByTime([$lap1, $lap2])
               ->willReturn([$lap2, $lap1]);

        $this->getBestLapByLapNumber(2)->shouldReturn($lap2);
    }

    function it_has_incidents_for_review(
    	Incident $incident1, Incident $incident2)
    {
    	$incident1->isForReview()->willReturn(false);
    	$incident2->isForReview()->willReturn(true);

    	$this->setIncidents([$incident1, $incident2]);
    	$this->getIncidentsForReview()->shouldReturn([$incident2]);
    }

    function it_splits_sessions_by_vehicle_class(
    	Participant $part1, Participant $part2, Participant $part3,
    	Vehicle $vehicle1, Vehicle $vehicle2, Vehicle $vehicle3)
    {

    	$vehicle1->getClass()->willReturn('A class');
    	$vehicle2->getClass()->willReturn('Another class');
    	$vehicle3->getClass()->willReturn(null);

    	$part1->getVehicle()->willReturn($vehicle1);
    	$part2->getVehicle()->willReturn($vehicle2);
    	$part3->getVehicle()->willReturn($vehicle3);

    	$this->setParticipants([$part1, $part2, $part3]);

    	$sessions = $this->splitByVehicleClass();

    	$sessions[0]->getParticipants()->shouldReturn([$part3]);
    	$sessions[1]->getParticipants()->shouldReturn([$part1]);
    	$sessions[2]->getParticipants()->shouldReturn([$part2]);
    }

    function it_can_sort_participants_by_consistency(
    	Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

        $this->setParticipants([$part1, $part2]);

        $helper->sortParticipantsByConsistency([$part1, $part2])
               ->willReturn([$part2, $part1]);

    	$this->getParticipantsSortedByConsistency()
    	     ->shouldReturn([$part2, $part1]);
    }

    function it_has_cuts(
    	Participant $part1, Participant $part2,
    	Cut $cut1, Cut $cut2, Cut $cut3,
    	Lap $lap1, Lap $lap2)
    {
    	$cut1->getDate()->willReturn((new \DateTime)->setTimestamp(time()-10));
    	$cut2->getDate()->willReturn((new \DateTime)->setTimestamp(time()-40));
    	$cut3->getDate()->willReturn((new \DateTime)->setTimestamp(time()));

    	$lap1->getCuts()->willReturn([$cut1]);
    	$lap2->getCuts()->willReturn([$cut2, $cut3]);

    	$part1->getLaps()->willReturn([$lap1]);
    	$part2->getLaps()->willReturn([$lap2]);

    	$this->setParticipants([$part1, $part2]);

    	$this->getCuts()->shouldReturn([$cut2, $cut1, $cut3]);
    }


}
