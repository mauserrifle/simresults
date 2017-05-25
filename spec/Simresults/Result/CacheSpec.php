<?php

namespace spec\Simresults\Result;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class CacheSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Result\Cache');
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

    function it_can_help_implement_parent_cache_in_extended_classes(aClass $object)
    {
       $object->aMethod(1, 2)->willReturn(array('some data'));
       $this->cacheParentCall($object, 'aMethod', array(1, 2))
            ->shouldReturn(array('some data'));
    }
}


class AClass extends AOtherClass
{

    public function aMethod($argument1, $argument2)
    {
        return parent::aMethod($argument1, $argument2);
    }
}

class AOtherClass
{
    public function aMethod($argument1, $argument2)
    {
        return array('some data');
    }
}
