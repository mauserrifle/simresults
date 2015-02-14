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
     * Get seconds from time format: (h:)i:s.u.
     *
     * Set param `$same_micro_separator` to force micro parsing using colon
     * format: (h:)i:s:u
     *
     * @param   string    $formatted_time
     * @param   boolean   $colon_micro_separator Whether the micro seconds are
     *                                           separated using the regular
     *                                           colon. If true, the last
     *                                           digits will allways be parsed
     *                                           as micro. Format: (h:)i:s:u
     * @return  string
     */
    public static function secondsFromFormattedTime(
        $formatted_time,
        $colon_micro_separator=false)
    {
        // Always micro seconds using a colon separator
        if ($colon_micro_separator)
        {
            // Matched h:i:s:u
            if (preg_match (
                '/(.*):(.*):(.*):(.*)/i',
                $formatted_time, $time_matches))
            {
                // Get seconds
                $seconds = ($time_matches[1] * 3600) +
                           ($time_matches[2] * 60) +
                           $time_matches[3];

                // Add microseconds to seconds using string functions and convert back
                // to float
                $seconds = (float) ($seconds.'.'.$time_matches[4]);

                return $seconds;
            }

            // Matched i:s:u
            if (preg_match (
                '/(.*):(.*):(.*)/i',
                $formatted_time, $time_matches))
            {
                // Get seconds
                $seconds = ($time_matches[1] * 60) +
                           $time_matches[2];

                // Add microseconds to seconds using string functions and convert back
                // to float
                $seconds = (float) ($seconds.'.'.$time_matches[3]);

                return $seconds;
            }

        }
        // Matched h:i:s.u
        else if (preg_match (
            '/(.*):(.*):(.*)\.(.*)/i',
            $formatted_time, $time_matches))
        {
            // Get seconds
            $seconds = ($time_matches[1] * 3600) +
                       ($time_matches[2] * 60) +
                       $time_matches[3];

            // Add microseconds to seconds using string functions and convert back
            // to float
            $seconds = (float) ($seconds.'.'.$time_matches[4]);

            return $seconds;
        }

        // Matched i:s.u
        if (preg_match (
            '/(.*):(.*)\.(.*)/i',
            $formatted_time, $time_matches))
        {
            // Get seconds
            $seconds = ($time_matches[1] * 60) +
                       $time_matches[2];

            // Add microseconds to seconds using string functions and convert back
            // to float
            $seconds = (float) ($seconds.'.'.$time_matches[3]);

            return $seconds;
        }

        // Throw invalid argument by default
        throw new \InvalidArgumentException;
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

    /**
     * Returns the given laps sorted by elapsed time (ASC)
     *
     * TODO: Unittest
     *
     * @return  array  the laps
     */
    public static function sortLapsByElapsedTime(array $laps)
    {
        usort($laps, function($a,$b) {
            // Same elapsed seconds
             if ($a->getElapsedSeconds() === $b->getElapsedSeconds()) {

                 // Same time
                 if ($a->getTime() === $b->getTime())
                 {
                     return 0;
                 }

                // Return time comparison as fallback
                return ($a->getTime() < $b->getTime()) ? -1 : 1;
            }

            // a has no elapsed seconds
            if ( ! $a->getElapsedSeconds())
            {
                // $b is the faster
                   return 1;
            }

            // b has no elapsed seconds
            if ( ! $b->getElapsedSeconds())
            {
                // $a is faster
                   return -1;
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
            return ($a->getElapsedSeconds() < $b->getElapsedSeconds())
                      ? -1 : 1;
        });

        // Return laps
        return $laps;
    }


    /**
     * Sort participants by total time, also checks finish statusses
     *
     * TODO: Unittest
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public static function sortParticipantsByTotalTime(array $participants)
    {
        // DNF statusses
        $dnf_statusses = array(
            Participant::FINISH_DNF,
            Participant::FINISH_DQ,
            Participant::FINISH_NONE,
        );

        usort($participants, function($a, $b) use ($dnf_statusses) {

            // Participant a has less laps than b. He is lapped
            if ($a->getNumberOfLaps() < $b->getNumberOfLaps())
            {
                return 1;
            }

            // Participant b has less laps than a. He is lapped
            if ($b->getNumberOfLaps() < $a->getNumberOfLaps())
            {
                return -1;
            }

            // Both not finished
            if (in_array($a->getFinishStatus(), $dnf_statusses) AND
                in_array($b->getFinishStatus(), $dnf_statusses))
            {
                // Same
                return 0;
            }

            // a not finished
            if (in_array($a->getFinishStatus(), $dnf_statusses))
            {

                return 1;
            }

            // b not finished
            if (in_array($b->getFinishStatus(), $dnf_statusses))
            {
                return -1;
            }

            // Same time
             if ($a->getTotalTime() === $b->getTotalTime()) {
                return 0;
            }

            // Return normal comparison
            return (($a->getTotalTime() < $b->getTotalTime()) ? -1 : 1);
        });

        return $participants;
    }

    /**
     * Sort participants by best lap
     *
     * TODO: Unittest
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public static function sortParticipantsByBestLap(array $participants)
    {
        usort($participants, function($a, $b) {

            // Get best laps
            $a_best_lap = $a->getBestLap();
            $b_best_lap = $b->getBestLap();

            // Both participants have no best lap
            if ( ! $a_best_lap AND ! $b_best_lap)
            {
                // Same
                return 0;
            }

            // a has no best lap
            if ( ! $a_best_lap)
            {
                return 1;
            }

            // b has no best lap
            if ( ! $b_best_lap)
            {
                return -1;
            }

            // Same time
             if ($a_best_lap->getTime() === $b_best_lap->getTime()) {
                return 0;
            }

            // Return normal comparison
            return ((
                $a_best_lap->getTime() <
                    $b_best_lap->getTime())
                ? -1 : 1);
        });

        return $participants;
    }

    /**
     * Sort participants by consistency
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public static function sortParticipantsByConsistency(array $participants)
    {
        usort($participants, function($a, $b) {

            // Get consistencies
            $a_consistency = $a->getConsistency();
            $b_consistency = $b->getConsistency();

            // Both participants have no consistency
            if ($a_consistency === null AND $b_consistency === null)
            {
                // Same
                return 0;
            }

            // a has no consistency
            if ( $a_consistency === null)
            {
                return 1;
            }

            // b has no consistency
            if ( $b_consistency === null)
            {
                return -1;
            }

            // Same consistency
             if ($a_consistency === $b_consistency) {
                return 0;
            }

            // Return normal comparison
            return (($a_consistency < $b_consistency) ? -1 : 1);
        });

        return $participants;
    }
}