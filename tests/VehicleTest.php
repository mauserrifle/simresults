<?php
use Simresults\Vehicle;

/**
 * Tests for the vehicle.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class VehicleTest extends PHPUnit_Framework_TestCase {

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
     * Test friendly vehicle name
     */
    public function testFriendlyVehicleName()
    {
        // Init new Vehicle
        $vehicle = new Vehicle;

        // Set names
        $vehicle->setName('Lola T280 JL Lafosse');
        $vehicle->setType('LolaT280');
        $vehicle->setClass('LolaT280');

        // Validate friendly name
        $this->assertSame(
                'Lola T280 JL Lafosse - LolaT280',
                $vehicle->getFriendlyName()
        );

        // Change class name
        $vehicle->setClass('LolaT280B');

        // Validate friendly name
        $this->assertSame(
                'Lola T280 JL Lafosse - LolaT280 (LolaT280B)',
                $vehicle->getFriendlyName()
        );
    }

}