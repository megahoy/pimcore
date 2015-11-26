<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */
namespace Pimcore\FIle\Transfer\Adapter;

trait AdapterTrait {

    /**
     * @var null | string
     */
    protected $username = '';

    /**
     * @var null | string
     */
    protected $password = '';

    /**
     * @var null | string
     */
    protected $host = '';


    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @var null
     */
    protected $sourceFile = null;

    /**
     * @var null
     */
    protected $destinationFile = null;

    /**
     * @param $sourceFile
     * @return $this
     */
    public function setSourceFile($sourceFile){
        $this->sourceFile = $sourceFile;
        return $this;
    }

    /**
     * @return null
     */
    public function getSourceFile(){
        return $this->sourceFile;
    }

    /**
     * @param $destinationFile
     *
     * @return $this
     */
    public function setDestinationFile($destinationFile){
        $this->destinationFile  = $destinationFile;
        return $this;
    }

    /**
     * @return null
     */
    public function getDestinationFile(){
        return $this->destinationFile;
    }


}