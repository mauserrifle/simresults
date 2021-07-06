<?php

namespace spec\Simresults;

use Simresults\Vehicle;
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
        $this->shouldHaveType('Simresults\Vehicle');
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

    function it_returns_friendly_name_removing_duplicate_name_words_in_class()
    {
        $this->setName('Porsche 911')
             ->setClass('Porsche 911 GT3 Cup');

        $this->getFriendlyName()
             ->shouldReturn('Porsche 911 (GT3 Cup)');
    }

    function it_returns_friendly_name_removing_duplicate_name_words_in_type()
    {
        $this->setName('Porsche 911')
             ->setType('Porsche 911 GT3 Cup');

        $this->getFriendlyName()
             ->shouldReturn('Porsche 911 - GT3 Cup');
    }
}
