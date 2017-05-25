<?php

namespace spec\Simresults\Result;

use Simresults\Result\Vehicle;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class VehicleSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Result\Vehicle');
    }

    function it_returns_friendly_name_using_grouping_name_type_class()
    {
        // Set names (Set ( char to test for regex errors)
        $this->setName('Lola T280 JL Lafosse')
             ->setType('LolaT280 (')
             ->setClass('LolaT280');

        $this->getFriendlyName()
             ->shouldReturn('Lola T280 JL Lafosse - LolaT280 (');

        // Change class name
        $this->setClass('LolaT280B');

        $this->getFriendlyName()
              ->shouldReturn('Lola T280 JL Lafosse - LolaT280 ( (LolaT280B)');
    }
}
