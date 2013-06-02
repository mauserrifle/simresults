<?php
use Simresults\Driver;

/**
 * Tests for the driver.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class DriverTest extends PHPUnit_Framework_TestCase {

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
     * Test driver name with AI mention
     */
    public function testNameWithAiMention()
    {
        // Init new Driver
        $driver = new Driver;

        // Set name and whether its human or not
        $driver->setName('mauserrifle');
        $driver->setHuman(true);


        // Validate friendly name
        $this->assertSame(
                'mauserrifle',
                $driver->getNameWithAiMention()
        );

        // Is not human
        $driver->setHuman(false);

        // Validate friendly name
        $this->assertSame(
                'mauserrifle (AI)',
                $driver->getNameWithAiMention()
        );
    }

}