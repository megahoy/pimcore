<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document;

use Pimcore\Model;

class Email extends Model\Document\PageSnippet
{
    /**
     * Contains a \Zend_Validate_EmailAddress object
     *
     * @var \Zend_Validate_EmailAddress
     */
    protected static $validator;

    /**
     * Static type of the document
     *
     * @var string
     */
    public $type = "email";

    /**
     * Contains the email subject
     *
     * @var string
     */
    public $subject = "";

    /**
     * Contains the from email address
     *
     * @var string
     */
    public $from = "";

    /**
     * Contains the email addresses of the recipients
     *
     * @var string
     */
    public $to = "";

    /**
     * Contains the carbon copy recipients
     *
     * @var string
     */
    public $cc = "";

    /**
     * Contains the blind carbon copy recipients
     *
     * @var string
     */
    public $bcc = "";

    /**
     * Contains the email subject
     *
     * @param string $subject
     * @return void
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Returns the email subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Sets the "to" receiver
     *
     * @param string $to
     * @return void
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Returns the "to" receivers
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Returns the "to" receivers as array
     *
     * @return array
     */
    public function getToAsArray()
    {
        return $this->getAsArray('To');
    }

    /**
     * Helper to return receivers as array
     *
     * @param $key
     * @return array
     */
    protected function getAsArray($key)
    {
        $emailAddresses = preg_split('/,|;/', $this->{'get' . ucfirst($key)}());

        foreach ($emailAddresses as $key => $emailAddress) {
            if ($validAddress = self::validateEmailAddress(trim($emailAddress))) {
                $emailAddresses[$key] = $validAddress;
            } else {
                unset($emailAddresses[$key]);
            }
        }

        return $emailAddresses;
    }

    /**
     * Helper to validate a email address
     *
     * @static
     * @param $emailAddress
     * @return string | null - returns "null" if the email address is invalid otherwise the email address is returned
     */
    public static function validateEmailAddress($emailAddress)
    {
        if (is_null(self::$validator)) {
            self::$validator = new \Zend_Validate_EmailAddress();
        }

        $emailAddress = trim($emailAddress);
        if (self::$validator->isValid($emailAddress)) {
            return $emailAddress;
        }
    }

    /**
     * Sets the "from" email address
     *
     * @param string $from
     * @return void
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Returns the "from" email address
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Returns the "from" email address as array
     *
     * @return array
     */
    public function getFromAsArray()
    {
        return $this->getAsArray('From');
    }

    /**
     * Sets the carbon copy receivers (multiple receivers should be separated with a ",")
     *
     * @param string $cc
     * @return void
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
        return $this;
    }

    /**
     * Returns the carbon copy receivers
     *
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Returns the carbon copy receivers as array
     *
     * @return array
     */
    public function getCcAsArray()
    {
        return $this->getAsArray('Cc');
    }

    /**
     * Sets the blind carbon copy receivers (multiple receivers should be separated with a ",")
     *
     * @param string $bcc
     * @return void
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;
        return $this;
    }

    /**
     * Returns the blind carbon copy receivers
     *
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Returns the blind carbon copy receivers as array
     *
     * @return array
     */
    public function getBccAsArray()
    {
        return $this->getAsArray('Bcc');
    }

}
