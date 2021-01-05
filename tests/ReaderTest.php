<?php
use Simresults\Data_Reader;

/**
 * Tests for the reader.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class ReaderTest extends \PHPUnit\Framework\TestCase {

    /**
     * Set error reporting
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        error_reporting(E_ALL);
    }

    /**
     * Test exception when no data is supplied
     */
    public function testBuildingReaderWithoutData()
    {
        $this->expectException(\Simresults\Exception\NoData::class);
        $reader = Data_Reader::factory('');
    }

    /**
     * Test exception when no reader is found
     */
    public function testBuildingReaderWithUnkownData()
    {
        $this->expectException(\Simresults\Exception\CannotFindReader::class);
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