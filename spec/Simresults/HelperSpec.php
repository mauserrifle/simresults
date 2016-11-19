<?php

namespace spec\Simresults;

use Simresults\Helper;
use Simresults\Participant;
use Simresults\Lap;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HelperSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Helper::class);
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

    	$this->sortParticipantsByConsistency([$part1, $part2, $part3, $part4])
    	     ->shouldReturn([$part2, $part3, $part1, $part4]);
    }

    function it_sorts_laps_by_time()
    {
    	$lap1 = (new Lap)->setTime(155.730);
    	$lap2 = new Lap;
    	$lap3 = (new Lap)->setTime(128.211);
    	$lap4 = (new Lap)->setTime(128.211);
    	$lap5 = (new Lap)->setTime(128.730);

        $this->sortLapsByTime([$lap1, $lap2, $lap3, $lap4, $lap5])
             ->shouldReturn([$lap4, $lap3, $lap5, $lap1, $lap2]);
    }

    function it_sorts_laps_by_sector()
    {
    	$lap1 = (new Lap)->setSectorTimes([20.20]);
    	$lap2 = new Lap;
    	$lap3 = (new Lap)->setSectorTimes([20.10]);
    	$lap4 = (new Lap)->setSectorTimes([20.10]);
    	$lap5 = (new Lap)->setSectorTimes([23.50]);

        $this->sortLapsBySector([$lap1, $lap2, $lap3, $lap4, $lap5], 1)
             ->shouldReturn([$lap4, $lap3, $lap1, $lap5, $lap2]);
    }
}
