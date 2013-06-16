<?php
namespace Simresults;

/**
 * The Helper class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Helper {

    /**
     * Format seconds to a (h:)i:s.u format
     *
     * @return  string
     */
    public static function formatTime($seconds)
    {
        // Default micro time
        $micro = 0;

        // Contains decimal separater
        if (strpos((string) $seconds, '.'))
        {
            // Get micro the nasty way so we always get the decimals as
            // original rounded
            $seconds_arr = explode('.', (string) $seconds);
            $micro = (int) $seconds_arr[1];
        }

        // Make seconds without micro
        $seconds = (int) $seconds;

        // Get hours
        $hours = floor($seconds / 3600);

        // Get minutes
        $minutes = floor(($seconds - ($hours*3600)) / 60);

        // Get remaining seconds
        $secs = floor(($seconds - ($hours*3600) - ($minutes*60)));

        // Make format
        $format = sprintf('%02d:%02d.%04d', $minutes, $secs, $micro);

        // Has hours
        if ($hours)
        {
            // Prefix format with hours
            $format = sprintf('%02d:', $hours).$format;
        }

        // Return the format
        return $format;
    }

    /**
     * Returns the given laps sorted by a sector (ASC)
     *
     * @return  array  the laps
     */
    public static function sortLapsBySector(array $laps, $sector)
    {
        // Sort laps
        usort($laps, function($a,$b) use ($sector) {
            // Get sectors of lap
            $a_sectors = $a->getSectorTimes();
            $b_sectors = $b->getSectorTimes();

            // Get sector index for array
            $sector_index = $sector-1;

            // Both laps don't have this sector time
            if ( ! isset($a_sectors[$sector_index]) AND
                 ! isset($b_sectors[$sector_index]))
            {
                // Same
                return 0;
            }

            // a lap has no given sector
            if ( ! isset($a_sectors[$sector_index]))
            {
                // $b is faster
                return 1;
            }

            // b lap has no given sector
            if ( ! isset($b_sectors[$sector_index]))
            {
                // $a is faster
                return -1;
            }

            // Same time
             if ($a_sectors[$sector_index] === $b_sectors[$sector_index]) {
                return 0;
            }

            // Return normal comparison
            return ($a_sectors[$sector_index] < $b_sectors[$sector_index] )
                ? -1 : 1;
        });

        // Return laps
        return $laps;
    }

    /**
     * Returns the given laps sorted by time (ASC)
     *
     * @return  array  the laps
     */
    public static function sortLapsByTime(array $laps)
    {
        // Sort laps
        usort($laps, function($a,$b) {
            // Same time
             if ($a->getTime() === $b->getTime()) {
                return 0;
            }

            // a lap is not completed
            if ( ! $a->isCompleted())
            {
                // $b is faster
                return 1;
            }

            // b lap is not completed
            if ( ! $b->isCompleted())
            {
                // $a is faster
                return -1;
            }

            // Return normal comparison
            return ($a->getTime() < $b->getTime()) ? -1 : 1;
        });

        // Return laps
        return $laps;
    }

}