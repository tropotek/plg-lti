<?php
namespace Lti\Db;

use Bs\Db\Traits\TimestampTrait;
use Uni\Db\Traits\InstitutionTrait;

/**
 * @author Mick Mifsud
 * @created 2020-02-27
 * @link http://tropotek.com.au/
 * @license Copyright 2020 Tropotek
 */
class Platform extends \Tk\Db\Map\Model implements \Tk\ValidInterface
{
    use InstitutionTrait;
    use TimestampTrait;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $institutionId = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $clientId = '';

    /**
     * @var string
     */
    public $authLoginUrl = '';

    /**
     * @var string
     */
    public $authTokenUrl = '';

    /**
     * @var string
     */
    public $keySetUrl = '';

    /**
     * @var string
     */
    public $deploymentId = '';

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var \DateTime
     */
    public $modified = null;

    /**
     * @var \DateTime
     */
    public $created = null;


    /**
     * Platform
     */
    public function __construct()
    {
        $this->_TimestampTrait();

    }

    /**
     * @param string $name
     * @return Platform
     */
    public function setName($name) : Platform
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $clientId
     * @return Platform
     */
    public function setClientId($clientId) : Platform
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientId() : string
    {
        return $this->clientId;
    }

    /**
     * @param string $authLoginUrl
     * @return Platform
     */
    public function setAuthLoginUrl($authLoginUrl) : Platform
    {
        $this->authLoginUrl = $authLoginUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthLoginUrl() : string
    {
        return $this->authLoginUrl;
    }

    /**
     * @param string $authTokenUrl
     * @return Platform
     */
    public function setAuthTokenUrl($authTokenUrl) : Platform
    {
        $this->authTokenUrl = $authTokenUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthTokenUrl() : string
    {
        return $this->authTokenUrl;
    }

    /**
     * @param string $keySetUrl
     * @return Platform
     */
    public function setKeySetUrl($keySetUrl) : Platform
    {
        $this->keySetUrl = $keySetUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeySetUrl() : string
    {
        return $this->keySetUrl;
    }

    /**
     * @param string $deploymentId
     * @return Platform
     */
    public function setDeploymentId($deploymentId) : Platform
    {
        $this->deploymentId = $deploymentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeploymentId() : string
    {
        return $this->deploymentId;
    }

    /**
     * @param bool $active
     * @return Platform
     */
    public function setActive($active) : Platform
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive() : bool
    {
        return $this->active;
    }

    /**
     * @return array
     */
    public function validate()
    {
        $errors = array();

        if (!$this->institutionId) {
            $errors['institutionId'] = 'Invalid value: institutionId';
        }

        if (!$this->name) {
            $errors['name'] = 'Invalid value: name (platform ID)';
        }

        if (!$this->clientId) {
            $errors['clientId'] = 'Invalid value: clientId';
        }

        if (!$this->authLoginUrl) {
            $errors['authLoginUrl'] = 'Invalid value: authLoginUrl';
        }

        if (!$this->authTokenUrl) {
            $errors['authTokenUrl'] = 'Invalid value: authTokenUrl';
        }

        if (!$this->keySetUrl) {
            $errors['keySetUrl'] = 'Invalid value: keySetUrl';
        }

        if (!$this->deploymentId) {
            $errors['deploymentId'] = 'Invalid value: deploymentId';
        }

        return $errors;
    }

}
