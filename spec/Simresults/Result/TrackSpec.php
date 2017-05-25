<?php

namespace spec\Simresults\Result;

use Simresults\Result\Track;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class TrackSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Simresults\Result\Track');
    }

    function it_returns_friendly_name_using_venue_course_event()
    {
        // Set names
        $this->setVenue('Sebring [Virtua_LM]')
             ->setCourse('Sebring 12h Course ( /1 # `') // Set (, /, #, ` chars
             ->setEvent('12h Course');                  // to test for regex
                                                        // errors that
                                                        // previously occured
        $this->getFriendlyName()->shouldReturn(
            'Sebring [Virtua_LM], Sebring 12h Course ( /1 # `');

        // Change event name
        $this->setEvent('12h Alternative course');

        $this->getFriendlyName() ->shouldReturn(
            'Sebring [Virtua_LM], Sebring 12h Course ( /1 # ` '.
            '(12h Alternative course)');
    }
}
