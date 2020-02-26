<?php
namespace Lti\Auth;

use IMSGlobal\LTI\LTI_Message_Launch;
use Tk\Auth\Result;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class LtiAdapter extends \Tk\Auth\Adapter\Iface
{
    /**
     * @var LTI_Message_Launch
     */
    protected $launch = null;

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;


    /**
     * LtiAdapter constructor.
     * @param LTI_Message_Launch $launch
     * @param \Uni\Db\InstitutionIface $institution
     */
    public function __construct($launch, $institution)
    {
        parent::__construct();
        $ltiData = $launch->get_launch_data();

        $username = '';
        if (!empty($ltiData['email']))
            list($username, $domain) = explode('@', $ltiData['email']);
        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username']))
            $username = $ltiData['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username'];
        $this->set('username', $username);

        $this->launch = $launch;
        $this->institution = $institution;
    }

    /**
     * @return LTI_Message_Launch
     */
    public function getLaunch()
    {
        return $this->launch;
    }

    /**
     * @return \Uni\Db\InstitutionIface
     */
    public function getInstitution()
    {
        return $this->institution;
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
