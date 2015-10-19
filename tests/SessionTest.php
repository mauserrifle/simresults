<?php
use Simresults\Participant;
use Simresults\Vehicle;

use Simresults\Session;
use Simresults\Incident;
use Simresults\Lap;

/**
 * Tests for the session.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class SessionTest extends PHPUnit_Framework_TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        error_reporting(E_ALL);
    }

    /**
     * Test the lasted laps number
     */
    public function testLastedLaps()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Validate lasted laps
            $this->assertSame(3, $session->getLastedLaps());
        }
    }

    /**
     * Test the laps sorted by time
     */
    public function testLapsSortedByTime()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the best lap
            $laps = $session->getLapsSortedByTime();

            // Get participants
            $participants = $session->getParticipants();

            // Validate laps
            $this->assertSame($participants[2]->getLap(3), $laps[0]);
        }
    }

    /**
     * Test the laps by lap number sorted by time
     */
    public function testLapsByLapNumberSortedByTime()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the second laps
            $laps = $session->getLapsByLapNumberSortedByTime(2);

            // Get participants
            $participants = $session->getParticipants();

            // Validate laps
            $this->assertSame($participants[0]->getLap(2), $laps[0]);
        }
    }

    /**
     * Get best laps by participant
     */
    public function testBestLapsByParticipant()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the best lap
            $laps = $session->getBestLapsGroupedByParticipant();

            // Get participants
            $participants = $session->getParticipants();

            // Validate laps
            $this->assertSame($participants[2]->getLap(3), $laps[0]);
            $this->assertSame($participants[0]->getLap(3), $laps[1]);
            $this->assertSame($participants[1]->getLap(2), $laps[2]);
        }
    }

    /**
     * Test the best lap
     */
    public function testBestLap()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get the best lap
        $lap = $session->getBestLap();

        // Get participants
        $participants = $session->getParticipants();

        // Validate best lap
        $this->assertSame($participants[2]->getBestLap(), $lap);


        //-- Create participant with 2 uncompleted laps and test NULL best lap
        //   from the session
        $participant = new Participant;
        $participant->setLaps(array(
            new Lap,
            new Lap,
        ));
        $session = Session::createInstance();
        $session->setParticipants(array($participant));

        // No best lap
        $this->assertNull($session->getBestLap());
    }

    /**
     * Test getting bad laps above 107 percent of best lap
     */
    public function testBadLaps()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get laps above percent of the fastest lap
            $laps = $session->getBadLaps($above_percent = 107);

            // Get participants
            $participants = $session->getParticipants();

            // Validate laps
            $this->assertSame($participants[0]->getLap(1), $laps[0]);
            $this->assertSame($participants[1]->getLap(1), $laps[1]);
            $this->assertSame($participants[2]->getLap(1), $laps[2]);
        }
    }

    /**
     * Test the participant who led most laps
     */
    public function testLedMostParticipant()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the led most participant
            $led_most_participant = $session->getLedMostParticipant();

            // Get participants
            $participants = $session->getParticipants();

            // Validate participant
            $this->assertSame($participants[0], $led_most_participant);
        }
    }

    /**
     * Test the winning participant
     */
    public function testWinningParticipant()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get the winning participant
        $winning_participant = $session->getWinningParticipant();

        // Get participants
        $participants = $session->getParticipants();

        // Validate participant
        $this->assertSame($participants[0], $winning_participant);

        // Validate the position to be sure
        $this->assertSame(1, $winning_participant->getPosition());
    }

    /**
     * Test the leading participant by lap number
     */
    public function testLeadingParticipant()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the leading participant for lap 3
            $leading_participant = $session->getLeadingParticipant(3);

            // Get participants
            $participants = $session->getParticipants();

            // Validate participant
            $this->assertSame($participants[0], $leading_participant);

            // Validate the position to be sure
            $this->assertSame(1, $leading_participant->getPosition());
        }
    }

    /**
     * Test the leading participant by lap number and elapsed time. We need this
     * functionality for precise gap calculations etc, as the 'position' of a
     * lap is not always correct in this matter
     *
     * TODO: Actually test using laps that have elapsed time and test whether
     *       the method skips laps with no elapsed time (already implemented).
     *       Make sure the test passes like it is now too (without elapsed time).
     *       That way we test that the method fallbacks on normal behavior like
     *       `getLeadingParticipant`
     */
    public function testLeadingParticipantByElapsedTime()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get the leading participant for lap 3
            $leading_participant = $session->getLeadingParticipantByElapsedTime(3);

            // Get participants
            $participants = $session->getParticipants();

            // Validate participant
            $this->assertSame($participants[0], $leading_participant);

            // Validate the position to be sure
            $this->assertSame(1, $leading_participant->getPosition());
        }
    }

    /**
     * Test getting the max position. Sometimes rfactor produces higher
     * grid positions than number of participants. So we need this method
     * to be 100% sure what the max position was (mostly at start)
     */
    public function testMaxPosition()
    {
        // Get session
        $session = $this->getSessionWithData();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Validate
            $this->assertSame(12, $session->getMaxPosition());
        }
    }

    /**
     * Test the laps sorted by sector
     */
    public function testLapsSortedBySector()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get laps sorted by sector 1
            $laps = $session->getLapsSortedBySector(1);

            // Validate laps
            $this->assertSame($participants[5]->getLap(1), $laps[0]);
            $this->assertSame($participants[2]->getLap(3), $laps[1]);
            $this->assertSame($participants[0]->getLap(3), $laps[2]);
            $this->assertSame($participants[0]->getLap(2), $laps[3]);
            $this->assertSame($participants[1]->getLap(2), $laps[4]);
        }
    }

    public function testBestLapBySector()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        // Get best lap by sector 1
        $lap = $session->getBestLapBySector(1);

        // Validate lap
        $this->assertSame($participants[5]->getLap(1), $lap);
    }

    /**
     * Test the best laps by sector and participant
     */
    public function testBestLapsBySectorByParticipant()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get best laps of sector 1
            $laps = $session->getBestLapsBySectorGroupedByParticipant(1);

            // NOTE: Below tests by time. Could be refactored to just check by lap
            // instance

            // Validate couple sector 1 laps. The first sector is a lap that
            // is NOT completed.
            $this->assertSame(42.4389, $laps[0]->getSectorTime(1));
            $this->assertSame(46.2715, $laps[1]->getSectorTime(1));
            $this->assertSame(46.6382, $laps[2]->getSectorTime(1));

            // Get best laps of sector 3
            $laps = $session->getBestLapsBySectorGroupedByParticipant(3);

            // Validate couple sector 3 laps
            $this->assertSame(43.9237, $laps[0]->getSectorTime(3));
            $this->assertSame(44.5677, $laps[1]->getSectorTime(3));
            $this->assertSame(44.6712, $laps[2]->getSectorTime(3));
        }
    }

    /**
     * Test the laps sorted by sector and lap number
     */
    public function testLapsSortedBySectorByLapNumber()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        //-- Run twice to test cache
        for($i=0; $i<2; $i++)
        {
            // Get best laps of sector 3 of lap number 2
            $laps = $session->getLapsSortedBySectorByLapNumber(3, 2);

            // Validate laps
            $this->assertSame($participants[2]->getLap(2), $laps[0]);
            $this->assertSame($participants[1]->getLap(2), $laps[1]);
            $this->assertSame($participants[0]->getLap(2), $laps[2]);

            // Validate the fatsets lap
            $this->assertSame($participants[2]->getLap(2), $session->getBestLapBySectorByLapNumber(3, 2));
        }
    }


    /**
     * Test the best lap by lap number
     */
    public function testBestLapByLapNumber()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        // Get the best lap for lap number 3
        $lap = $session->getBestLapByLapNumber(3);

        // Validate lap
        $this->assertSame($participants[2]->getLap(3), $lap);
    }

    /**
     * Test getting incidents for reviewing
     */
    public function testIncidentsForReview()
    {
        // Create session
        $session = Session::createInstance();

        // Create some incidents
        $incident1 = new Incident;
        $session->addIncident($incident1->setForReview(true));
        $incident2 = new Incident;
        $session->addIncident($incident2->setForReview(false));
        $incident3 = new Incident;
        $session->addIncident($incident3->setForReview(true));

        // Validate incidents for review
        $this->assertSame(
            array($incident1, $incident3),
            $session->getIncidentsForReview()
        );
    }

    /*
     * Test getting multiple sessions based on the original, split by vehicle
     * classes
     */
    public function testSplitSessionsByVehicleClass()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants
        $participants = $session->getParticipants();

        // Get session data splitted by vehicle class
        $sessions_by_class = $session->splitByVehicleClass();

        // Get first class session
        $class_session = $sessions_by_class[0];

        // Test participants
        $class_participants = $class_session->getParticipants();
        $this->assertSame($participants[0], $class_participants[0]);
        $this->assertSame($participants[3], $class_participants[1]);

        // Test whether the session is the same when resetting the participants
        // to its original participants. This way we test whether the session
        // is just a clone with new participants. This tests through Equals
        // so PHP does attribute comparison instead of object reference
        $class_session->setParticipants($session->getParticipants());
        $this->assertEquals($session, $class_session);

        // Test whether the second class session is the second vehicle by name
        // (abc sort)
        $class_participants = $sessions_by_class[1]->getParticipants();
        $this->assertSame($participants[2], $class_participants[0]);

        // Add participant with no vehicle and make sure we get no
        // exception on splitting
        $session->addParticipant(new Participant());
        $sessions_by_class = $session->splitByVehicleClass();
    }

    /*
     * Test whether we don't have any cache conflicts when getting multiple
     * sessions based on the original, split by vehicle classes.
     *
     * We're getting laps sorted by time first, then we split session (thus
     * are cloned) and test whether the sorted laps of a split are not the
     * same as the original session.
     */
    public function testSplitSessionsByVehicleClassCacheConflicts()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get laps sorted by time, we do this before splitting to test
        // cache conflicts
        $laps_sorted = $session->getLapsSortedByTime();

        // Get session data splitted by vehicle class
        $sessions_by_class = $session->splitByVehicleClass();

        // Get first class session
        $class_session = $sessions_by_class[0];

        // Test whether the laps sorted by time of the class session are not
        // the same as the original
        $this->assertNotSame($laps_sorted,
            $class_session->getLapsSortedByTime());
    }

    public function testGettingParticipantsSortedByConsistency()
    {
        // Get session
        $session = $this->getSessionWithData();

        // Get participants sorted by consistency
        $participants = $session->getParticipantsSortedByConsistency();

        // Array not the same as normal participants order. We do not test the
        // actual sorting, the tested Helper is used for this
        $this->assertNotSame($participants, $session->getParticipants());
    }


    /**
     * Get a Session instance populated with test data.
     *
     * NOTE: Every time this method is ran, a different instance will be
     *       returned! Keep this in mind when comparing things by reference
     *
     * @return Session
     */
    protected function getSessionWithData()
    {
        // Create new session
        $session = Session::createInstance();

        // Participants testdata array
        $participants_data = array(
            array(
                'position'     =>  1,
                'vehicle'      => array(
                    'class'  =>  'class1',
                ),
                'laps'           => array(
                    array(
                        'time'      => 130.7517,
                        'sectors'    => array(53.2312, 32.2990, 45.2215),
                        'position'  => 1,
                    ),
                    array(
                        'time'      => 125.2989,
                        'sectors'    => array(47.4511, 32.0630, 45.7848),
                        'position'  => 1,
                    ),
                    array(
                        'time'      => 123.3179,
                        'sectors'    => array(46.6382, 32.0084, 44.6712),
                        'position'  => 1,
                    ),
                ),
            ),
            array(
                'position'     =>  2,
                'vehicle'      => array(
                    'class'  =>  'class3',
                ),
                'laps'           => array(
                    array(
                        'time'      => 130.9077,
                        'sectors'    => array(54.0223, 32.3176, 44.5677),
                        'position'  => 2,
                    ),
                    array(
                        'time'      => 125.6976,
                        'sectors'    => array(47.5271, 32.4621, 45.7083),
                        'position'  => 2,
                    ),
                    array(
                        'time'      => 126.0620,
                        'sectors'    => array(47.7989, 32.7721, 45.4910),
                        'position'  => 2,
                    ),
                ),
            ),
            array(
                'position'     =>  3,
                'vehicle'      => array(
                    'class'  =>  'class2',
                ),
                'laps'           => array(
                    array(
                        'time'      => 134.8484,
                        'sectors'    => array(56.0119, 32.4913, 46.3452),
                        'position'  => 12,  // very high number to test max pos
                    ),
                    array(
                        'time'      => 126.2454,
                        'sectors'    => array(50.4389, 31.8827, 43.9237),
                        'position'  => 3,
                    ),
                    array(
                        'time'      => 122.0663,
                        'sectors'    => array(46.2715, 31.8696, 43.9252),
                        'position'  => 3,
                    ),
                ),
            ),
            // Lapped participant
            array(
                'position'     =>  4,
                'vehicle'      => array(
                    'class'  =>  'class1',
                ),
                'laps'           => array(
                    array(
                        'time'      => 155.1491,
                        'sectors'    => array(60.0119, 40.4913, 54.6459),
                        'position'  => 4,  // very high number to test max pos
                    ),
                    array(
                        'time'      => 156.1491,
                        'sectors'    => array(60.0119, 40.4913, 55.6459),
                        'position'  => 4,  // very high number to test max pos
                    ),
                ),
            ),
            array(
                'position'     =>  5,
                'vehicle'      => array(
                    'class'  =>  'class2',
                ),

                // No laps for this participant, to check code not failing
                // on null objects
                'laps'           => array(),
            ),

            array(
                'position'     =>  6,
                'vehicle'      => array(
                    'class'  =>  'class3',
                ),

                // Non completed laps to check calculating best laps
                'laps'           => array(
                    array(
                        'time'      =>  null,
                        'sectors'    => array(42.4389),
                        'position'  => 6
                    ),
                ),
            ),
        );

        // Loop each participant data
        foreach ($participants_data as $participant_data)
        {
            // Create the new participant and populate
            $participant = new Participant;
            $participant->setPosition($participant_data['position']);

            $vehicle = new Vehicle;
            $vehicle->setClass($participant_data['vehicle']['class']);
            $participant->setVehicle($vehicle);

            // Create each lap
            foreach ($participant_data['laps'] as $lap_key => $lap_data)
            {
                $lap = new Lap;
                $lap->setTime($lap_data['time'])
                    ->setSectorTimes($lap_data['sectors'])
                    ->setNumber(($lap_key+1))
                    ->setPosition($lap_data['position'])
                    ->setParticipant($participant);

                // Add lap to participant
                $participant->addLap($lap);
            }

            // Add participant to session
            $session->addParticipant($participant);
        }

        // Return the session
        return $session;
    }

}