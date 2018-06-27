<?php
namespace Lti\Auth;

use Tk\Auth\Result;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class LtiAdapter extends \Tk\Auth\Adapter\NullAuth
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
     *
     * @return Result
     */
    public function authenticate()
    {
        $username = $this->get('username');
        if (!$username) {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Invalid username or password.');
        }
        try {

            /** @var \Tk\Event\Dispatcher $dispatcher */
            $dispatcher = $this->getConfig()->getEventDispatcher();
            if ($dispatcher) {
                $event = new \Tk\Event\AuthAdapterEvent($this);
                $dispatcher->dispatch(\Tk\Auth\AuthEvents::LOGIN_PROCESS, $event);
                if ($event->getResult()) {
                    return $event->getResult();
                }
            }
            return new Result(Result::SUCCESS, $username);
        } catch (\Exception $e) {
            \Tk\Log::warning($e->getMessage());
        }
        return new Result(Result::FAILURE_CREDENTIAL_INVALID, '', 'Invalid credentials.');
    }

}