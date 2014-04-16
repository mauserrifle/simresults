<?php
namespace Simresults;

/**
 * The vehicle class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Vehicle {

    /**
     * @var  string  The name of the vehicle
     */
    private $name;

    /**
     * @var  string  What type of vehicle
     */
    private $type;

    /**
     * @var  string  The class of the vehicle
     */
    private $class;

    /**
     * @var  int  The number of the vehicle
     */
    private $number;

    /**
     * Set the name of the vehicle
     *
     * @param   string   $name
     * @return  Vehicle
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the vehicle
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the type of the vehicle
     *
     * @param   string   $type
     * @return  Vehicle
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the type of the vehicle
     *
     * @return  string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the class of the vehicle
     *
     * @param   string   $class
     * @return  Vehicle
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Get the class of the vehicle
     *
     * @return  string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the number of the vehicle
     *
     * @param  int  $number
     * @return  Vehicle
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Get the number of the vehicle
     *
     * @return  string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Get a friendly name version of the name, type and class. Tries to
     * remove any duplicates.
     *
     * @return  string
     */
    public function getFriendlyName()
    {
        //--- Build initial vehicle name
        $vehicle_name = $this->getName();

        // Has course name
        if ($vehicle_type = $this->getType())
        {
            // Course name is not already part of the name
            if ( ! preg_match(
                       sprintf('#\b(%s)\b#i', preg_quote($vehicle_type)),
                       $vehicle_name))
            {
                // Add course name to vehicle name
                $vehicle_name .= ' - '.$vehicle_type;
            }
        }

        // Has event name
        if ($vehicle_class = $this->getClass())
        {
            // Event name is not already part of the name
            if ( ! preg_match(
                       sprintf('#\b(%s)\b#i', preg_quote($vehicle_class)),
                       $vehicle_name))
            {
                // Add event to vehicle name
                $vehicle_name .= sprintf(' (%s)',$vehicle_class);
            }
        }

        return $vehicle_name;
    }

}
