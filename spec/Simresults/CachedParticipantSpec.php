<?php

namespace spec\Simresults;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CachedParticipantSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\CachedParticipant');
    }
}
