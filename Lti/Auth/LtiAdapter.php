<?php
namespace Lti\Auth;

use Lti\Provider;
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
     * LtiAdapter constructor.
     * @param \IMSGlobal\LTI\ToolProvider\User $ltiUser
     * @param \Uni\Db\InstitutionIface $institution
     */
    public function __construct($ltiUser, $institution)
    {
        parent::__construct();
        $this->set('username', $ltiUser->email);
        $settings = $this->getLaunchData();
        if (!empty($settings['custom_canvas_user_login_id']))
            $this->set('username', $settings['custom_canvas_user_login_id']);
        else if (!empty($settings['ext_user_username']))
            $this->set('username', $settings['ext_user_username']);
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
     * @return array
     */
    public function getLaunchData()
    {
        return $this->getSession()->get(Provider::LTI_LAUNCH);
    }


    /**
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

}
