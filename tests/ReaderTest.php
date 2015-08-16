<?php
use Simresults\Data_Reader;

/**
 * Tests for the reader.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class ReaderTest extends PHPUnit_Framework_TestCase {

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
     * Test exception when no data is supplied
     *
     * @expectedException Simresults\Exception\NoData
     */
    public function testBuildingReaderWithoutData()
    {
        $reader = Data_Reader::factory('');
    }

    /**
     * Test exception when no reader is found
     *
     * @expectedException Simresults\Exception\CannotFindReader
     */
    public function testBuildingReaderWithUnkownData()
    {
        $reader = Data_Reader::factory('Unknown data to readers');
    }

    /**
     * Test the default timezone
     */
    public function testDefaultTimezone()
    {
        $this->assertSame('UTC', Data_Reader::$default_timezone);
    }

    /**
     * TODO: Test exception when no session has been found.
     *
     * Please see AssettoCorsaServerReaderTest::testNoSessionException. This
     * should be removed and replaced with the test below
     */
    // public function testNoSessionException() {}
}