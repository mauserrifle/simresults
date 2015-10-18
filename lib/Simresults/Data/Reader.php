<?php
namespace Simresults;

/**
 * The abstract data reader. It's the base for all readers. It's able to
 * find the proper reader using the factory method.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
abstract class Data_Reader {

    /**
     * @var  string  The default timezone to use
     */
    public static $default_timezone = 'UTC';

    /**
     * @var  string  The data to read
     */
    protected $data;


    /**
     * Create a new data reader for the given file or string.
     *
     * @param   string   $data   Data as file path or string
     *
     * @throws  Exception\CannotFindReader  when no reader can be found
     * @throws  Exception\NoData            when no data is given or found
     * @return  Data_Reader
     */
    public static function factory($data)
    {
        // Known readers
        $known_readers = array(
            'Simresults\Data_Reader_Rfactor2',
            'Simresults\Data_Reader_AssettoCorsa',
            'Simresults\Data_Reader_AssettoCorsaServerJson',
            'Simresults\Data_Reader_AssettoCorsaServer',
            'Simresults\Data_Reader_RaceRoomServer',
            'Simresults\Data_Reader_Race07',
        );

        // File checking
        try
        {
            // Data is a file
            if (is_file($data))
            {
                // Read contents of file
                $data = file_get_contents($data);
            }
        }
        // Ignore any errors (TODO: Unitest this somehow?)
        catch (\Exception $ex) {}

        // No data
        if ( ! $data)
        {
            throw new Exception\NoData(
                'Cannot find a reader for the given data');
        }

        // Loop each known reader and return the one which can handle the data
        foreach ($known_readers as $reader_class)
        {
            // Reader can read this data
            if ($reader_class::canRead($data))
            {
                // Create new reader instance and return it
                return new $reader_class($data);
            }
        }

        // Throw exception because we couldn't find any reader
        throw new Exception\CannotFindReader(
            'Cannot find a reader for the given data');
    }

    /**
     * Construct new reader with given string data
     *
     * @param   string  $data
     * @throws  Exception\CannotReadData
     */
    public function __construct($data)
    {
        // Cannot read the data
        if ( ! static::canRead($data))
        {
            // Throw exception
            throw new Exception\CannotReadData('Cannot read the given data');
        }

        // Set data to instance
        $this->data = $data;

        // Run init method so the object can init properly
        $this->init();
    }

    /**
     * Returns whether a data reader can read the data given
     *
     * @param   string   $data   Data as string
     * @throws  Exception\CannotReadData
     * @return  boolean  true for possible reading
     */
    public static function canRead($data)
    {
        // Throw exception
        throw new Exception\CannotReadData('canRead not implemented in Reader');
    }

    /**
     * Returns one session
     *
     * @throws  Exception\NoSession    when session is not found
     * @return  Session
     */
    public function getSession($session_number=1)
    {
        // Get sessions
        $sessions = $this->getSessions();

        // Session not found
        if ( ! isset($sessions[$session_number-1]) OR
             ! $session = $sessions[$session_number-1])
        {
            throw new Exception\NoSession(
                'Cannot find a session for session number '.$session_number);
        }

        // Return
        return $session;
    }

    /**
     * Returns all sessions
     *
     * @return  Session
     */
    abstract public function getSessions();

    /**
     * Optional init method
     */
    protected function init() { }

}
