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
    public function formatTime($seconds, $force_hours=false)
    {
        // Is negative?
        $is_negative = false;
        if ($seconds < 0)
        {
            // Make positive for further formatting
            $is_negative = true;
            $seconds = abs($seconds);
        }

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

        // Add minus sign if negative
        if ($is_negative) $format = '-'.$format;

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
    public function secondsFromFormattedTime(
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
    public function sortLapsBySector(array $laps, $sector)
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
    public function sortLapsByTime(array $laps)
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
     * @return  array  the laps
     */
    public function sortLapsByElapsedTime(array $laps)
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
     * WARNING: This is not unittested and heavily relies on reader tests
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public function sortParticipantsByTotalTime(array $participants)
    {
        // DNF statusses
        $dnf_statusses = array(
            Participant::FINISH_DNF,
            Participant::FINISH_DQ,
            Participant::FINISH_NONE,
        );

        usort($participants, function($a, $b) use ($dnf_statusses) {

            /**
             * Laps
             */

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

            /**
             * Finish status
             */

            // Both not finished
            if (in_array($a->getFinishStatus(), $dnf_statusses) AND
                in_array($b->getFinishStatus(), $dnf_statusses))
            {

                // Both have no total time
                if ( ! $a->getTotalTime() AND ! $b->getTotalTime())
                {
                    // Both same status
                    if ($a->getFinishStatus() === $b->getFinishStatus())
                    {
                        // Both have a grid position
                        if ($a->getGridPosition() AND $b->getGridPosition())
                        {
                            // Participant a had a better grid position
                            if ($a->getGridPosition() < $b->getGridPosition())
                            {
                                return -1;
                            }

                            // Participant b had a better grid position
                            if ($b->getGridPosition() < $a->getGridPosition())
                            {
                                return 1;
                            }
                        }

                        // a has grid position
                        if ($a->getGridPosition())
                        {
                            return -1;
                        }

                        // b has grid position
                        if ($b->getGridPosition())
                        {
                            return 1;
                        }

                        // Same
                        return 0;
                    }

                    // Get finish order values
                    $a_order = array_search($a->getFinishStatus(),
                        Participant::$finish_sort_order);
                    $b_order = array_search($b->getFinishStatus(),
                        Participant::$finish_sort_order);

                    // Return normal comparison
                    return (($a_order < $b_order) ? -1 : 1);
                }

                // Return normal time comparison
                return (($a->getTotalTime() < $b->getTotalTime()) ? 1 : -1);
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



            /**
             * Actual time
             */

            // Same time
             if ($a->getTotalTime() === $b->getTotalTime())
             {
                // Both have a grid position
                if ($a->getGridPosition() AND $b->getGridPosition())
                {
                    // Participant a had a better grid position
                    if ($a->getGridPosition() < $b->getGridPosition())
                    {
                        return -1;
                    }

                    // Participant b had a better grid position
                    if ($b->getGridPosition() < $a->getGridPosition())
                    {
                        return 1;
                    }
                }

                // a has grid position
                if ($a->getGridPosition())
                {
                    return -1;
                }

                // b has grid position
                if ($b->getGridPosition())
                {
                    return 1;
                }

                // Same
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
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public function sortParticipantsByBestLap(array $participants)
    {
        $laps = array(); $parts = array(); $parts_without_best_lap = array();

        foreach ($participants as $part) {
            if ($best_lap = $part->getBestLap()) {
                $laps[] = $best_lap;
            }
            else {
                $parts_without_best_lap[] = $part;
            }
        }

        $laps = $this->sortLapsByTime($laps);

        foreach ($laps as $lap) {
            $parts[] = $lap->getParticipant();
        }

        return array_merge($parts, $parts_without_best_lap);
    }

    /**
     * Sort participants by consistency
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public function sortParticipantsByConsistency(array $participants)
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

    /**
     * Sort participants by last lap position
     *
     * @param   array   $participants
     * @return  array   The sorted participants
     */
    public function sortParticipantsByLastLapPosition(
        array $participants)
    {
        usort($participants, function($a, $b) {

            /**
             * Criteria 1: Number of laps
             */

            // Get the number of laps
            $a_number_of_laps = $a->getNumberOfLaps();
            $b_number_of_laps = $b->getNumberOfLaps();

            // Both participants have no laps
            if ( ! $a_number_of_laps AND ! $b_number_of_laps)
            {
                // Same
                return 0;
            }

            // a has no laps
            if ( ! $a_number_of_laps)
            {
                return 1;
            }

            // b has no laps
            if ( ! $b_number_of_laps)
            {
                return -1;
            }

            // Not same number of laps
             if ($a_number_of_laps !== $b_number_of_laps)
             {
                // Return number of laps comparison
                return (($a_number_of_laps < $b_number_of_laps) ? 1 : -1);
            }



            /**
             * Criteria 2: Last lap position
             */


            // Get last lap
            $a_last_lap = $a->getLastLap();
            $b_last_lap = $b->getLastLap();


            // Both participants have no last lap
            if ( ! $a_last_lap AND ! $b_last_lap)
            {
                // Same
                return 0;
            }

            // a has no last lap
            if ( ! $a_last_lap)
            {
                return 1;
            }

            // b has no last lap
            if ( ! $b_last_lap)
            {
                return -1;
            }

            // Same position
            //  if ($a_last_lap->getPosition() === $b_last_lap->getPosition()) {
            //     return 0;
            // }

            // Return normal comparison
            return ((
                $a_last_lap->getPosition() <
                    $b_last_lap->getPosition())
                ? -1 : 1);
        });

        return $participants;
    }

    /**
     * Retrieve a single key from an array. If the key does not exist in the
     * array, the default value will be returned instead.
     *
     *     // Get the value "username" from $_POST, if it exists
     *     $username = Arr::get($_POST, 'username');
     *
     *     // Get the value "sorting" from $_GET, if it exists
     *     $sorting = Arr::get($_GET, 'sorting');
     *
     * This function is from the Kohana project (http://kohanaframework.org/).
     *
     * @param   array   $array      array to extract from
     * @param   string  $key        key name
     * @param   mixed   $default    default value
     * @return  mixed
     */
    public function arrayGet($array, $key, $default = NULL)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Detect a session. Returns a Session object with proper session type.
     * If the session value differs from the session type detected, it will
     * be stored as session name.
     *
     * @param   string  $value
     * @param   array   $custom_values_to_type
     * @return  Session
     */
    public function detectSession($session_value, $custom_values_to_type=array())
    {
        $session_value = trim(preg_replace('#([^0-9])1$#', '$1', $session_value));
        $session_value_lower = strtolower($session_value);

        $type = null;
        $name = null;

        // Preg matches that catch most types
        if (preg_match('/(prac|test)/i', $session_value_lower)) {
            $type = Session::TYPE_PRACTICE;
        }
        elseif (preg_match('/qual/i', $session_value_lower)) {
            $type = Session::TYPE_QUALIFY;
        }
        elseif (preg_match('/rac/i', $session_value_lower)) {
            $type = Session::TYPE_RACE;
        }
        elseif (preg_match('/warm/i', $session_value_lower)) {
            $type = Session::TYPE_WARMUP;
        }

        if (!$type)
        {
            // Any fallback values like short names
            $values_to_type =
                array(
                    'p' => Session::TYPE_PRACTICE,
                    'fp' => Session::TYPE_PRACTICE,

                    'q' => Session::TYPE_QUALIFY,

                    'r' => Session::TYPE_RACE,

                    'w' => Session::TYPE_WARMUP,
                )
                +
                $custom_values_to_type
            ;

            $type = $this->arrayGet($values_to_type, $session_value_lower);
        }

        if (!$type) {
            $type = Session::TYPE_PRACTICE;
            $name = 'Unknown';
        }

        // No name and the session value is different than the type. So
        // we are dealing with a custom session name
        if (!$name AND
            strlen($session_value_lower) > 4 AND
            strtolower($type) !== $session_value_lower)
        {
            $custom_name = $session_value;
            // Everything is lowercase
            if (strtolower($custom_name) === $custom_name) {
                $custom_name = ucfirst($custom_name);
            }

            // Everything is uppercase. Just use our lowercase value with ucifrst
            if (strtoupper($custom_name) === $custom_name) {
                $custom_name = ucfirst($session_value_lower);
            }

            $name = $custom_name;
        }

        // Init session
        $session = Session::createInstance();

        // Set session values
        $session->setType($type)
                ->setName($name);

        return $session;
    }
}