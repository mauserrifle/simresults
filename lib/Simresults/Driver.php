<?php
namespace Simresults;

/**
 * The driver class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Driver {

    /**
     * @var  string  The name of the driver
     */
    protected $name;

    /**
     * @var  boolean  Whether this driver is human or not. Defaults to true.
     */
    protected $human = true;


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
}

?>