<?php

namespace spec\Simresults;

use Simresults\Helper;
use Simresults\Session;
use Simresults\Participant;
use Simresults\Lap;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class HelperSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Helper');
    }

    function it_formats_time()
    {
        // Validate
        $this->formatTime(100.5279)->shouldReturn('01:40.5279');

        // Validate leading zeros for seconds
        $this->formatTime(62.5279)->shouldReturn('01:02.5279');

        // Validate special case that had rounding problem (01:23.128099)
        $this->formatTime(83.1281)->shouldReturn('01:23.1281');

        // Validate the microseconds are always 4 digits
        $this->formatTime(71.0661)->shouldReturn('01:11.0661');

        // Make sure a smaller number, without leading zero, will not result in
        // formatting with leading zeros
        $this->formatTime(71.661)->shouldReturn('01:11.6610');

        // Validate negative seconds
        $this->formatTime(-71.0661)->shouldReturn('-01:11.0661');

        // Validate time with hours
        $this->formatTime(5516.5879)->shouldReturn('01:31:56.5879');

        // Validate forcing hours
        $this->formatTime(100.5279, true)->shouldReturn('00:01:40.5279');
    }

    function it_converts_time_strings_to_seconds()
    {
        $this->secondsfromformattedtime('01:40.5279')
             ->shouldReturn(100.5279);
        $this->secondsfromformattedtime('01:31:56.5879')
             ->shouldReturn(5516.5879);
        $this->secondsfromformattedtime('02:03:506', true)
             ->shouldReturn(123.506);
        $this->secondsfromformattedtime('01:02:03:506', true)
             ->shouldReturn(3723.506);

        $this->shouldThrow('InvalidArgumentException')
             ->duringSecondsfromformattedtime('40.5279');
    }

    function it_can_get_values_from_arrays()
    {
        $array = array('key' => 'value');

        $this->arrayGet($array, 'key')->shouldReturn('value');
        $this->arrayGet($array, 'nothing')->shouldReturn(null);
        $this->arrayGet($array, 'nothing', 'default value')
             ->shouldReturn('default value');
    }

    function it_sorts_participants_by_consistency(
        Participant $part1, Participant $part2,
        Participant $part3, Participant $part4)
    {
        $part1->getConsistency()->willReturn(1.8);
        $part2->getConsistency()->willReturn(1.3);
        $part3->getConsistency()->willReturn(1.79);
        $part4->getConsistency()->willReturn(null);

        $this->sortParticipantsByConsistency(array($part1, $part2,
                                                   $part3, $part4))
             ->shouldReturn(array($part2, $part3, $part1, $part4));
    }

    function it_sorts_laps_by_time()
    {
        $lap1 = new Lap; $lap1->setTime(155.730);
        $lap2 = new Lap;
        $lap3 = new Lap; $lap3->setTime(128.211);
        $lap5 = new Lap; $lap5->setTime(128.730);

        $this->sortLapsByTime(array($lap1, $lap2, $lap3, $lap5))
             ->shouldReturn(array($lap3, $lap5, $lap1, $lap2));
    }

    function it_sorts_laps_by_elapsed_time()
    {
        $lap1 = new Lap; $lap1->setTime(155.730)->setElapsedSeconds(160);
        $lap2 = new Lap;

        // Identical elapsed time but different lap times
        $lap3 = new Lap; $lap3->setTime(128.211)->setElapsedSeconds(130);
        $lap5 = new Lap; $lap5->setTime(128.111)->setElapsedSeconds(130);

        $this->sortLapsByElapsedTime(array($lap1, $lap2, $lap3, $lap5))
             ->shouldReturn(array($lap5, $lap3, $lap1, $lap2));
    }

    function it_sorts_laps_by_sector()
    {
        $lap1 = new Lap; $lap1->setSectorTimes(array(20.20));
        $lap2 = new Lap;
        $lap3 = new Lap; $lap3->setSectorTimes(array(20.10));
        $lap5 = new Lap; $lap5->setSectorTimes(array(23.50));

        $this->sortLapsBySector(array($lap1, $lap2, $lap3, $lap5), 1)
             ->shouldReturn(array($lap3, $lap1, $lap5, $lap2));
    }

    function it_sorts_participants_by_best_lap()
    {
        $part1 = new Participant; $part2 = new Participant;
        $part3 = new Participant; $part4 = new Participant;

        $lap1 = new Lap; $lap1->setTime(155.730)->setParticipant($part1);
        $lap2 = new Lap; $lap2->setParticipant($part2);
        $lap3 = new Lap; $lap3->setTime(128.211)->setParticipant($part3);
        $lap4 = new Lap; $lap4->setTime(128.730)->setParticipant($part4);

        $part1->addLap($lap1); $part2->addLap($lap2);
        $part3->addLap($lap3); $part4->addLap($lap4);

        $this->sortParticipantsByBestLap(array($part1, $part2, $part3, $part4))
             ->shouldReturn(array($part3, $part4, $part1, $part2));
    }

    function it_sorts_particpants_by_last_lap_position(
        Participant $part1, Participant $part2, Participant $part3,
        Participant $part4, Participant $part5,
        Lap $lap1, Lap $lap2, Lap $lap3, Lap $lap4, Lap $lap5)
    {
        $part1->getNumberOfLaps()->willReturn(3);
        $part1->getLastLap()->willReturn($lap1);
        $lap1->getPosition()->willReturn(4);

        $part2->getNumberOfLaps()->willReturn(0);
        $part2->getLastLap()->willReturn($lap2);
        $lap2->getPosition()->willReturn(5);

        $part3->getNumberOfLaps()->willReturn(4);
        $part3->getLastLap()->willReturn($lap3);
        $lap3->getPosition()->willReturn(2);

        $part4->getNumberOfLaps()->willReturn(4);
        $part4->getLastLap()->willReturn($lap4);
        $lap4->getPosition()->willReturn(1);

        $part5->getNumberOfLaps()->willReturn(4);
        $part5->getLastLap()->willReturn($lap5);
        $lap5->getPosition()->willReturn(3);

        $this->sortParticipantsByLastLapPosition(array($part1, $part2, $part3,
                                                       $part4, $part5))
             ->shouldReturn(array($part4, $part3, $part5, $part1, $part2));
    }

    function it_detects_session_by_session_value()
    {
        // Does not exist
        $session = $this->detectSession('4');
        $session->getType()->shouldReturn(SESSION::TYPE_PRACTICE);
        $session->getName()->shouldReturn('Unknown');

        // Existing tests
        $tests = array(
            'p' => array(Session::TYPE_PRACTICE, null),
            'fp' => array(Session::TYPE_PRACTICE, null),
            'practice' => array(Session::TYPE_PRACTICE, null),
            'practicing' => array(Session::TYPE_PRACTICE, 'Practicing'),
            ' practicing ' => array(Session::TYPE_PRACTICE, 'Practicing'), // With extra white space
            'PRACTICING' => array(Session::TYPE_PRACTICE, 'Practicing'), // All uppercase

            // Test short name not being used as session name
            'pract' => array(Session::TYPE_PRACTICE, 'Pract'),
            'test' => array(Session::TYPE_PRACTICE, null),

            'q' => array(Session::TYPE_QUALIFY, null),
            'qualify' => array(Session::TYPE_QUALIFY, null),
            'qualify1' => array(Session::TYPE_QUALIFY, null), // Numbering 1 should not happen
            'qualify2' => array(Session::TYPE_QUALIFY, 'Qualify2'),
            'qualify3' => array(Session::TYPE_QUALIFY, 'Qualify3'),
            'qualify session' => array(Session::TYPE_QUALIFY, 'Qualify session'),
            'qualifying' => array(Session::TYPE_QUALIFY, 'Qualifying'),

            // Numbering 1 on different spelling than type should not happen too
            'qualifying1' => array(Session::TYPE_QUALIFY, 'Qualifying'),

            'r' => array(Session::TYPE_RACE, null),
            'race' => array(Session::TYPE_RACE, null),
            'race2' => array(Session::TYPE_RACE, 'Race2'),
            'race3' => array(Session::TYPE_RACE, 'Race3'),
            'race11' => array(Session::TYPE_RACE, 'Race11'), // Test fix because it returned race1
            'race01' => array(Session::TYPE_RACE, 'Race01'),
            'quick race' => array(Session::TYPE_RACE, 'Quick race'),
            'racing' => array(Session::TYPE_RACE, 'Racing'),

            'w' => array(Session::TYPE_WARMUP, null),
            'warmup' => array(Session::TYPE_WARMUP, null),
            'warmup session' => array(Session::TYPE_WARMUP, 'Warmup session'),
            'warm up' => array(Session::TYPE_WARMUP, 'Warm up'),
            'warming' => array(Session::TYPE_WARMUP, 'Warming'),
        );

        // TODO: Should compare hardvalues instead of if/elses
        foreach ($tests as $session_value => $expected_values) {
            $session = $this->detectSession($session_value);
            $session->getType()->shouldReturn($expected_values[0]);
            $session->getName()->shouldReturn($expected_values[1]);
        }
    }

    function it_detects_session_by_custom_session_value()
    {
        // Exists by custom type value
        $session = $this->detectSession('4', array('4' => SESSION::TYPE_RACE));
        $session->getType()->shouldReturn(SESSION::TYPE_RACE);
        $session->getName()->shouldReturn(null);
    }
}
