<?php
namespace Simresults;

/**
 * The server class.
 *
 * @author     Maurice van der Star <mauserrifle@gmail.com>
 * @copyright  (c) 2013 Maurice van der Star
 * @license    http://opensource.org/licenses/ISC
 */
class Server {

    /**
     * @var  string  The name of the server
     */
    protected $name;

    /**
     * @var  string  The message of the day used on this server
     */
    protected $motd;

    /**
     * Set the name of the server
     *
     * @param   string  $name
     * @return  Server
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the server
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the message of the day used on this server
     *
     * @param   string  $motd
     * @return  Server
     */
    public function setMotd($motd)
    {
        $this->motd = $motd;
        return $this;
    }

    /**
     * Get the message of the day used on this server
     *
     * @return  string
     */
    public function getMotd()
    {
        return $this->motd;
    }

}
