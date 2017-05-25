<?php
namespace Simresults\Result;

/**
 * The driver class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Driver {

    /**
     * @var  int
     */
    protected $id;

    /**
     * @var  string  The name of the driver
     */
    protected $name;

    /**
     * @var  boolean  Whether this driver is human or not. Defaults to true.
     */
    protected $human = true;

    /**
     * @var  string  The driver id (for example Steam ID)
     */
    protected $driver_id;

    /**
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param   int      $id
     * @return  Driver
     */

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set the name of the driver
     *
     * @param   string  $name
     * @return  Driver
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the driver
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the name of the driver including AI mention when it's not a human
     * driver. For example: mauserrifle (AI).
     *
     * @return  string
     */
    public function getNameWithAiMention()
    {
        // Get driver name
        $driver_name = $this->getName();

        // Driver is not human
        if ( ! $this->isHuman())
        {
            // Mention it is a computer AI player
            $driver_name .= ' (AI)';
        }

        return $driver_name;
    }

    /**
     * Set whether the driver was human or not
     *
     * @param   boolean  $human
     * @return  Driver
     */
    public function setHuman($human)
    {
        $this->human = $human;
        return $this;
    }

    /**
     * Get whether the driver was human or not
     *
     * @return  boolean
     */
    public function isHuman()
    {
        return $this->human;
    }

    /**
     * Set the driver id (for example Steam ID)
     *
     * @param   string  $driver_id
     * @return  Driver
     */
    public function setDriverId($driver_id)
    {
        $this->driver_id = $driver_id;
        return $this;
    }

    /**
     * Get the driver id (for example Steam ID)
     *
     * @return  string
     */
    public function getDriverId()
    {
        return $this->driver_id;
    }
}
