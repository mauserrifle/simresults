<?php
namespace Simresults\Result;

/**
 * The aid class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Aid {

    /**
     * @var  int
     */
    protected $id;

    /**
     * @var  string  The aid used
     */
    protected $aid;

    /**
     * @var  string  The value for the aid
     */
    protected $value;

    /**
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param   int      $id
     * @return  Aid
     */

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set the aid
     *
     * @param   string  $aid
     * @return  Aid
     */
    public function setAid($aid)
    {
        $this->aid = $aid;
        return $this;
    }

    /**
     * Get the aid
     *
     * @return  string
     */
    public function getAid()
    {
        return $this->aid;
    }

    /**
     * Set the value
     *
     * @param   string  $value
     * @return  value
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the value
     *
     * @return  string
     */
    public function getValue()
    {
        return $this->value;
    }

}
