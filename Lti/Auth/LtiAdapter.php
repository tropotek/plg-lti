<?php
namespace Lti\Auth;

use IMSGlobal\LTI\LTI_Message_Launch;
use Lti\Plugin;
use Tk\Auth\Result;
use Tk\Event\AuthEvent;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class LtiAdapter extends \Tk\Auth\Adapter\Iface
{
    const LD_ROLES = 'https://purl.imsglobal.org/spec/lti/claim/roles';
    const LD_ROLES_MEMBERSHIP = 'http://purl.imsglobal.org/vocab/lis/v2/membership#';


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
        $this->extractData();
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

    /**
     *
     */
    public function extractData()
    {
        if (!Plugin::isEnabled($this->getInstitution())) return;

        $ltiData = $this->getLaunch()->get_launch_data();
        if (!$ltiData) return;

        // Gather user details
        $type = 'student';
        if ($this->isStaff($ltiData)) {
            $type = 'staff';
        }
        $userData = array(
            'institutionId' => $this->getInstitution()->getId(),
            'username' => $this->get('username'),
            'email' => $ltiData['email'],
            'type' => $type,
            'active' => true,
            'nameFirst' => '',
            'nameLast' => '',
            'name' => '',

            'lmsUserId' => '',
            'image' => '',
        );

        if (!empty($ltiData['given_name']))
            $userData['nameFirst'] = $ltiData['given_name'];
        if (!empty($ltiData['family_name']))
            $userData['nameLast'] = $ltiData['family_name'];
        if (!empty($ltiData['name']))
            $userData['name'] = $ltiData['name'];

        // TODO: See if any of there are still viable.
//        if (!empty($ltiData['custom_canvas_enrollment_state']))
//            $userData['lmsEnrollmentState'] = $ltiData['custom_canvas_enrollment_state'];
//        if (!empty($ltiData['custom_canvas_user_id']))
//            $userData['lmsUserId'] = $ltiData['custom_canvas_user_id'];
//        if (!empty($ltiData['user_id']))
//            $userData['lmsUserId'] = $ltiData['user_id'];
//        if (!empty($ltiData['user_image']))
//            $userData['image'] = $ltiData['user_image'];

        $this->set('userData', $userData);

        // Find a valid subject object if available
        $subjectCode = '';
        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/lis']['course_section_sourcedid']))
            $subjectCode = $ltiData['https://purl.imsglobal.org/spec/lti/claim/lis']['course_section_sourcedid'];
        if ($subjectCode) {
            //throw new \Tk\Exception('Subject not available, Please contact the LMS administrator.');
            $subjectCode = preg_replace('/[^a-z0-9_-]/i', '_', $subjectCode);
            $subject = null;

        }

//        $subjectCode = preg_replace('/[^a-z0-9_-]/i', '_', $ltiData['context_label']);
//        $subject = null;

        // TODO: Force subject selection via passed param in the LTI launch url:  {launchUrl}?custom_subjectId=3
//        if (!empty($ltiData['subjectId']) && empty($ltiData[Plugin::LTI_SUBJECT_ID])) {
//            $ltiData[Plugin::LTI_SUBJECT_ID] = (int)$ltiData['subjectId'];
//        }

        $subjectData = array(
            // TODO:
            //'id' => !empty($ltiData[Plugin::LTI_SUBJECT_ID]) ? (int)$ltiData[Plugin::LTI_SUBJECT_ID] : 0,
            'institutionId' => $this->getInstitution()->getId(),
            'courseId' => 0,        // TODO: What will we do here???????
            'name' => $ltiData['https://purl.imsglobal.org/spec/lti/claim/context']['title'],
            'code' => $subjectCode,
            'email' => (!empty($ltiData['email'])) ? $ltiData['email'] : \Tk\Config::getInstance()->get('site.email'),
            'description' => '',
            // TODO: get this info from canvas in the future????
            'dateStart' => \Tk\Date::create(),
            'dateEnd' => \Tk\Date::create()->add(new \DateInterval('P1Y')),
            'active' => true
        );
        $this->set('subjectData', $subjectData);


    }


    private function isStaff(array $ltiData)
    {
        foreach ($ltiData[self::LD_ROLES] as $role) {
            $role = strtolower($role);
            if (
                $role == self::LD_ROLES_MEMBERSHIP.'instructor' ||
                $role == self::LD_ROLES_MEMBERSHIP.'administrator' ||
                $role == self::LD_ROLES_MEMBERSHIP.'contentdeveloper'
            ) {
                return true;
            }
        }
        return false;
    }
}
