<?php

namespace spec\Simresults\Result;

use Simresults\Result\Driver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class DriverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Result\Driver');
    }

    function it_can_return_ai_driver_name()
    {
        $this->setName('mauserrifle')->setHuman(false);
        $this->getNameWithAiMention()->shouldReturn('mauserrifle (AI)');

        $this->setHuman(true);
        $this->getNameWithAiMention()->shouldReturn('mauserrifle');
    }
}
