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
     * @var  string  The driver id (for example Steam ID)
     */
    protected $driver_id;



    /**
     * @var  string  Cache shorten lastname for performance improvements
     */
    protected $cache_shorten_name;


    /**
     * Set the name of the driver
     *
     * @param   string  $name
     * @return  Driver
     */
    public function setName($name)
    {
        $this->name = trim($name);
        $this->cache_shorten_name = NULL;
        return $this;
    }

    /**
     * Get the name of the driver
     *
     * @param boolean $shorten_lastname
     * @param boolean $shorten_firstname
     * @return  string
     */
    public function getName($shorten_lastname=FALSE, $shorten_firstname=FALSE)
    {
        // No name, just return the empty value
        if (!$name = $this->name) {
            return $name;
        }

        if ($shorten_lastname OR $shorten_firstname)
        {
            if ($this->cache_shorten_name !== NULL)
            {
                return $this->cache_shorten_name;
            }

            $names = explode(' ', $name);
            if (count($names) > 1 AND $shorten_lastname) {
                $last_name = array_pop($names);

                // First character is not a letter, we will not treat this as
                // lastname and will try to get another part when more lastname
                // parts are available
                if (!preg_match('/[a-z]/i', $last_name[0]) and count($names) > 1) {
                    if ( ($last_name_new = trim(array_pop($names))) and preg_match('/[a-z]/i', $last_name_new) ) {
                        $last_name = $last_name_new;
		    }
                }

                $name = $names[0]." ".$last_name[0];
            }

            $names = explode(' ', $name);
            if (count($names) > 0 AND $shorten_firstname) {
                $first_name = array_shift($names);
                $name = $first_name[0].'. '.implode(' ', $names);
            }

            $name = trim($name);
            $this->cache_shorten_name = $name;
        }

        return $name;
    }

    /**
     * Get the name of the driver including AI mention when it's not a human
     * driver. For example: mauserrifle (AI).
     *
     * @param boolean $shorten_lastname
     * @param boolean $shorten_firstname
     * @return  string
     */
    public function getNameWithAiMention($shorten_lastname=FALSE, $shorten_firstname=FALSE)
    {
        // Get driver name
        $driver_name = $this->getName($shorten_lastname, $shorten_firstname);

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
