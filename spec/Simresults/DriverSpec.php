<?php

namespace spec\Simresults;

use Simresults\Driver;
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
        $this->shouldHaveType('Simresults\Driver');
    }

    function it_can_return_ai_driver_name()
    {
        $this->setName('mauserrifle')->setHuman(false);
        $this->getNameWithAiMention()->shouldReturn('mauserrifle (AI)');

        $this->setHuman(true);
        $this->getNameWithAiMention()->shouldReturn('mauserrifle');
    }

    function it_can_return_a_shorten_lastname()
    {
        $this->setName('Maurice van der Star');
        $this->getName()->shouldReturn('Maurice van der Star');
        $this->getName(true)->shouldReturn('Maurice S');
        $this->getName(true)->shouldReturn('Maurice S'); // Test cache

        $this->setName('Maurice Star');
        $this->getName()->shouldReturn('Maurice Star');
        $this->getName(true)->shouldReturn('Maurice S');

        $this->setName('Maurice');
        $this->getName()->shouldReturn('Maurice');
        $this->getName(true)->shouldReturn('Maurice');

        // Test offset 0 error by trimming spaces at setting the name
        $this->setName('A name with a space at the end ');
        $this->getName()->shouldReturn('A name with a space at the end');
        $this->getName(true)->shouldReturn('A e');
    }
}
