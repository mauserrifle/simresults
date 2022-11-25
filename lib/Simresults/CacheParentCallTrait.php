<?php
namespace Simresults;

/**
 * The CacheParentCallTrait trait
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
trait CacheParentCallTrait {

    /**
     * Method to use so we can avoid PHP 8.2 callable parent errors  and test
     * easier when caching methods
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function parentCall($method, $args)
    {
        return  call_user_func_array(array(parent::class, $method), $args);
    }

}

