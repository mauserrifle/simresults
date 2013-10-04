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
     * Format seconds to a (h:)i:s.u format. Hours are optional by default and 
     * may be forced through param $force_hours
     *
     * @param   float    $seconds
     * @param   boolean  $force_hours  force hours format (even 00:)
     * @return  string
     */
    public static function formatTime($seconds, $force_hours=false)
    {
        // Make seconds without micro
        $round_seconds = (int) $seconds;

        // Get hours
        $hours = floor($round_seconds / 3600);

        // Get minutes
        $minutes = floor(($round_seconds - ($hours*3600)) / 60);

        // Get remaining seconds
        $secs = floor(($round_seconds - ($hours*3600) - ($minutes*60)));

        // Make the remanings seconds including micro to format easier
        $secs_micro = round($seconds - $round_seconds, 4) + $secs;

        // Format seconds. Decimal is always 4 digits, seconds has always
        // a leading zero, which is fixed through str_pad
        $secs_formatted = str_pad(
            sprintf('%02.4f', $secs_micro), 7, 0, STR_PAD_LEFT);

        // Format
        $format = sprintf('%02d:%s', $minutes, $secs_formatted);

        // Has hours or hours are forced into markup
        if ($hours OR $force_hours)
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