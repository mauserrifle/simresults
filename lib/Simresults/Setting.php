<?php
namespace Simresults;

/**
 * The setting class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Setting {

    /**
     * @var  int
     */
    protected $id;

    /**
     * @var  string  The setting
     */
    protected $setting;

    /**
     * @var  string  The value for the setting
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
     * @return  Setting
     */

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set the setting
     *
     * @param   string  $setting
     * @return  Setting
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;
        return $this;
    }

    /**
     * Get the setting
     *
     * @return  string
     */
    public function getSetting()
    {
        return $this->setting;
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
