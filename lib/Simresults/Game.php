<?php
namespace Simresults;

/**
 * The game class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Game {

    /**
     * @var  string  The name of the game
     */
    protected $name;

    /**
     * @var  string  The version of the game
     */
    protected $version;

    /**
     * Set the name of the game
     *
     * @param   string  $name
     * @return  Game
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the game
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the version of the game
     *
     * @param   string  $version
     * @return  Game
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Get the version of the game
     *
     * @return  string
     */
    public function getVersion()
    {
        return $this->version;
    }

}
