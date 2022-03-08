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

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class SessionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Session');
    }

    function it_lasted_laps(Participant $part1, Participant $part2)
    {
        $part1->getNumberOfLaps()->willReturn(3);
        $part2->getNumberOfLaps()->willReturn(4);

        $this->setParticipants(array($part1, $part2));
        $this->getLastedLaps()->shouldReturn(4);
    }

    function it_sorts_laps_by_time(
        Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

           $part1_laps = array(new Lap, new Lap);
        $part1->getLaps()->willReturn($part1_laps);
           $part2_laps = array(new Lap, new Lap);
        $part2->getLaps()->willReturn($part2_laps);

        $this->setParticipants(array($part1, $part2));

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

        $this->setParticipants(array($part1, $part2));

        $expect = array($lap2, $lap1);
        $helper->sortLapsByTime(array($lap1, $lap2))
               ->willReturn($expect);
        $this->getLapsByLapNumberSortedByTime(2)->shouldReturn($expect);
    }

    function it_has_best_laps_grouped_by_participant(
        Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

        $part1->getBestLap()->willReturn($lap1 = new Lap);
        $part2->getBestLap()->willReturn($lap2 = new Lap);

        $this->setParticipants(array($part1, $part2));

        $expect = array($lap2, $lap1);
        $helper->sortLapsByTime(array($lap1, $lap2))
               ->willReturn($expect);
        $this->getBestLapsGroupedByParticipant()->shouldReturn($expect);
    }

    function it_has_best_lap(Participant $part1, Participant $part2, Cut $cut)
    {
        $this->getBestLap()->shouldReturn(null);

        $this->setParticipants(array($part1, $part2));

        $part1->getLaps()->willReturn(array(new Lap));
        $part2->getLaps()->willReturn(array());

        $this->getBestLap()->shouldReturn(null);

        $lap1 = new Lap; $lap1->setTime(30);
        $lap2 = new Lap; $lap2->setTime(20.99);
        $lap3 = new Lap; $lap3->setTime(60);

        $part1->getLaps()->willReturn(array($lap1, $lap2));
        $part2->getLaps()->willReturn(array($lap3));


        $this->getBestLap()->shouldReturn($lap2);

        // Exclude laps with cuts
        $lap2->addCut(new Cut);
        $this->getBestLap()->shouldReturn($lap1);
    }

    function it_has_bad_laps(Participant $part1, Participant $part2)
    {
        $this->getBadLaps()->shouldReturn(array());

        $lap1 = new Lap; $lap1->setTime(30);
        $lap2 = new Lap; $lap2->setTime(20.99);
        $lap3 = new Lap; $lap3->setTime(60);
        $lap4 = new Lap; $lap4->setTime(23);

        $part1->getLaps()->willReturn(array($lap1, $lap2));
        $part2->getLaps()->willReturn(array($lap3, $lap4));

        $this->setParticipants(array($part1, $part2));

        // Default 107%
        $this->getBadLaps()->shouldReturn(array($lap4, $lap1, $lap3));

        // Different percentage than default
        $this->getBadLaps(285)->shouldReturn(array($lap3));
        $this->getBadLaps(286)->shouldReturn(array());
    }

    function it_has_ledmost_participant(Participant $part1, Participant $part2)
    {
        $this->getLedMostParticipant()->shouldReturn(null);

        $part1->getNumberOfLaps()->willReturn(3);
        $part1->getNumberOfLapsLed()->willReturn(1);
        $part2->getNumberOfLaps()->willReturn(4);
        $part2->getNumberOfLapsLed()->willReturn(3);

        $this->setParticipants(array($part1, $part2));
        $this->getLedMostParticipant()->shouldReturn($part2);
    }

    function it_has_winning_participant(Participant $part1, Participant $part2)
    {
        $this->getWinningParticipant()->shouldReturn(null);

        $this->setParticipants(array($part1, $part2));
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

        $this->setParticipants(array($part1, $part2));

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

        $this->setParticipants(array($part1, $part2, $part3));
        $this->getLeadingParticipantByElapsedTime(3)->shouldReturn($part2);
    }

    function it_has_max_position(
        Participant $part1, Participant $part2, Lap $lap1,Lap $lap2,Lap $lap3)
    {
        // Test pure laps
        $lap1->getPosition()->willReturn(1);
        $lap2->getPosition()->willReturn(7);
        $lap3->getPosition()->willReturn(3);

        $part1->getLaps()->willReturn(array($lap1));
        $part2->getLaps()->willReturn(array($lap2, $lap3));

        $part1->getGridPosition()->willReturn(null);
        $part2->getGridPosition()->willReturn(null);

        $this->setParticipants(array($part1, $part2));
        $this->getMaxPosition()->shouldReturn(7);

        // Test grid positions too
        $part1->getGridPosition()->willReturn(1);
        $part2->getGridPosition()->willReturn(8);
        $this->getMaxPosition()->shouldReturn(8);

        // Test only reading grid positions when part has laps
        $part2->getLaps()->willReturn(array());
        $this->getMaxPosition()->shouldReturn(1);
    }

    function it_can_sort_laps_by_sector(
        Helper $helper, Participant $part1, Participant $part2,
        Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);

        $part1->getLaps()->willReturn(array($lap1));
        $part2->getLaps()->willReturn(array($lap2, $lap3));

        $this->setParticipants(array($part1, $part2));

        $helper->sortLapsBySector(array($lap1, $lap2, $lap3), 2)
               ->willReturn(array($lap2, $lap3, $lap1));

        $this->getLapsSortedBySector(2)->shouldReturn(array($lap2, $lap3, $lap1));
    }

    function it_has_best_lap_by_sector(
        Helper $helper, Participant $part1, Participant $part2,
        Lap $lap1, Lap $lap2, Lap $lap3)
    {
        $this->beConstructedWith($helper);

        $part1->getLaps()->willReturn(array($lap1));
        $part2->getLaps()->willReturn(array($lap2, $lap3));

        $this->setParticipants(array($part1, $part2));

        $helper->sortLapsBySector(array($lap1, $lap2, $lap3), 2)
               ->willReturn(array($lap2, $lap3, $lap1));

        $this->getBestLapBySector(2)->shouldReturn($lap2);
    }

    function it_has_best_laps_by_sector_grouped_by_participant(
        Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

        $part1->getBestLapBySector(1)->willReturn($lap1 = new Lap);
        $part2->getBestLapBySector(1)->willReturn($lap2 = new Lap);

        $this->setParticipants(array($part1, $part2));

        $expect = array($lap2, $lap1);
        $helper->sortLapsBySector(array($lap1, $lap2), 1)
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

        $this->setParticipants(array($part1, $part2));

        $helper->sortLapsBySector(array($lap1, $lap2), 2)
               ->willReturn(array($lap2, $lap1));

        $this->getLapsSortedBySectorByLapNumber(2, 5)
             ->shouldReturn(array($lap2, $lap1));
    }

    function it_has_best_lap_by_lap_number(
        Helper $helper, Participant $part1, Participant $part2,
        Lap $lap1, Lap $lap2)
    {
        $this->beConstructedWith($helper);

        $lap1->isValidForBest()->willReturn(true);
        $lap2->isValidForBest()->willReturn(true);

        $part1->getLap(2)->willReturn($lap1);
        $part2->getLap(2)->willReturn($lap2);

        $this->setParticipants(array($part1, $part2));

        $helper->sortLapsByTime(array($lap1, $lap2))
               ->willReturn(array($lap2, $lap1));

        $this->getBestLapByLapNumber(2)->shouldReturn($lap2);

        $lap2->isValidForBest()->willReturn(false);
        $this->getBestLapByLapNumber(2)->shouldReturn($lap1);
    }

    function it_has_incidents_for_review(
        Incident $incident1, Incident $incident2)
    {
        $incident1->isForReview()->willReturn(false);
        $incident2->isForReview()->willReturn(true);

        $this->setIncidents(array($incident1, $incident2));
        $this->getIncidentsForReview()->shouldReturn(array($incident2));
    }

    function it_splits_sessions_by_vehicle_class(
        Participant $part1, Participant $part2, Participant $part3,
        Participant $part4,
        Vehicle $vehicle1, Vehicle $vehicle2, Vehicle $vehicle3,
        Vehicle $vehicle4)
    {

        $vehicle1->getClass()->willReturn('A class');
        $vehicle2->getClass()->willReturn('Another class');
        $vehicle3->getClass()->willReturn(null);
        $vehicle4->getClass()->willReturn('A class');

        $part1->getVehicle()->willReturn($vehicle1);
        $part2->getVehicle()->willReturn($vehicle2);
        $part3->getVehicle()->willReturn($vehicle3);
        $part4->getVehicle()->willReturn($vehicle4);

        $this->setParticipants(array($part1, $part2, $part3, $part4));

        // We expect new position to be set
        $part1->setPosition(1)->shouldBeCalled();
        $part2->setPosition(1)->shouldBeCalled();
        $part3->setPosition(1)->shouldBeCalled();
        $part4->setPosition(2)->shouldBeCalled(); // Same class as part1

        // Split and test session participants
        $sessions = $this->splitByVehicleClass();
        $sessions[0]->getParticipants()->shouldReturn(array($part3));
        $sessions[1]->getParticipants()->shouldReturn(array($part1, $part4));
        $sessions[2]->getParticipants()->shouldReturn(array($part2));

    }

    function it_can_sort_participants_by_consistency(
        Helper $helper, Participant $part1, Participant $part2)
    {
        $this->beConstructedWith($helper);

        $this->setParticipants(array($part1, $part2));

        $helper->sortParticipantsByConsistency(array($part1, $part2))
               ->willReturn(array($part2, $part1));

        $this->getParticipantsSortedByConsistency()
             ->shouldReturn(array($part2, $part1));
    }

    function it_has_cuts(
        Participant $part1, Participant $part2,
        Cut $cut1, Cut $cut2, Cut $cut3,
        Lap $lap1, Lap $lap2)
    {
        $date1 = new \DateTime; $date1->setTimestamp(time()-10);
        $date2 = new \DateTime; $date2->setTimestamp(time()-40);
        $date3 = new \DateTime; $date3->setTimestamp(time());

        $cut1->getDate()->willReturn($date1);
        $cut2->getDate()->willReturn($date2);
        $cut3->getDate()->willReturn($date3);

        $lap1->getCuts()->willReturn(array($cut1));
        $lap2->getCuts()->willReturn(array($cut2, $cut3));

        $part1->getLaps()->willReturn(array($lap1));
        $part2->getLaps()->willReturn(array($lap2));

        $this->setParticipants(array($part1, $part2));

        $this->getCuts()->shouldReturn(array($cut2, $cut1, $cut3));
    }


}
