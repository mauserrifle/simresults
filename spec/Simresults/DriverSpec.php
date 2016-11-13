<?php

namespace spec\Simresults;

use Simresults\Driver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DriverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Driver::class);
    }

    function it_can_return_ai_driver_name()
    {
    	$this->setName('mauserrifle')->setHuman(false);
    	$this->getNameWithAiMention()->shouldReturn('mauserrifle (AI)');

    	$this->setHuman(true);
    	$this->getNameWithAiMention()->shouldReturn('mauserrifle');
    }
}
