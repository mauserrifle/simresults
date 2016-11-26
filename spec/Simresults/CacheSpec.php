<?php

namespace spec\Simresults;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * The cache class
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class CacheSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Cache');
    }

    function it_can_add_data()
    {
    	$this->put('a key', 'a value');
    	$this->get('a key')->shouldReturn('a value');
    }

    function it_can_remove_data()
    {
    	$this->put('a key', 'a value');
    	$this->forget('a key')->shouldReturn(true);
    	$this->get('a key')->shouldReturn(null);
    }

    function it_can_remove_all_data()
    {
        $this->put('a key', 'a value');
        $this->put('a key2', 'a value2');
        $this->flush()->shouldReturn(true);
        $this->get('a key')->shouldReturn(null);
        $this->get('a key2')->shouldReturn(null);
    }
}
