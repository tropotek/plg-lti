<?php
namespace Lti\Auth;

use Lti\Plugin;
use Tk\Auth\Result;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class LtiAdapter extends \Tk\Auth\Adapter\Iface
{
    /**
     * @var \IMSGlobal\LTI\ToolProvider\User
     */
    protected $ltiUser = null;

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;

    /**
     * @var null|\Uni\Db\UserIface
     */
    protected  $user = null;

    /**
     * @var null|\Uni\Db\SubjectIface
     */
    protected $subject = null;



    /**
     * LtiAdapter constructor.
     * @param \IMSGlobal\LTI\ToolProvider\User $ltiUser
     * @param \Uni\Db\InstitutionIface $institution
     */
    public function __construct($ltiUser, $institution)
    {
        parent::__construct();
        $this->set('username', $ltiUser->email);
        $this->ltiUser = $ltiUser;
        $this->institution = $institution;
    }

    /**
     * @return \IMSGlobal\LTI\ToolProvider\User
     */
    public function getLtiUser()
    {
        return $this->ltiUser;
    }

    /**
     * @return \Uni\Db\InstitutionIface
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @return null|\Uni\Db\UserIface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|\Uni\Db\UserIface $user
     * @return LtiAdapter
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return null|\Uni\Db\SubjectIface
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param null|\Uni\Db\SubjectIface $subject
     * @return LtiAdapter
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }


    /**
     *
     * @return Result
     */
    public function authenticate()
    {
        $msg = 'Invalid credentials.';
        $username = $this->get('username');
        if (!$username) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid username or password.');
        }
        try {
            $this->dispatchLoginProcess();
            if ($this->getLoginProcessEvent()->getResult()) {
                return $this->getLoginProcessEvent()->getResult();
            }
            return new Result(Result::SUCCESS, $username);
        } catch (\Exception $e) {
            \Tk\Log::warning($e->getMessage());
            $msg = $e->getMessage();
        }
        return new Result(Result::FAILURE_CREDENTIAL_INVALID, '', $msg);
    }

    /**
     * @return \App\Config
     */
    public function getConfig()
    {
        return \App\Config::getInstance();
    }
}