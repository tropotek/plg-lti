<?php
namespace Lti\Auth;

use IMSGlobal\LTI\LTI_Message_Launch;
use Lti\Plugin;
use Tk\Auth\Result;
use Tk\Event\AuthEvent;
use Uni\Db\Subject;


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
     * @throws \Exception
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
     * @return array
     */
    public function getLaunchData()
    {
        $data = array();
        if ($this->getLaunch())
            $data = $this->getLaunch()->get_launch_data();
        return $data;
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
        if ($username == 'admin') {
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $username, 'Administrators cannot log into this system via LTI.');
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
     * @throws \Exception
     */
    public function extractData()
    {
        if (!Plugin::isEnabled($this->getInstitution())) return;

        $ltiData = $this->getLaunchData();
        if (!$ltiData) return;

        // Gather user data
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
            'image' => ''       // TODO: Check the canvas LTI launchData
        );

        if (!empty($ltiData['given_name']))
            $userData['nameFirst'] = $ltiData['given_name'];
        if (!empty($ltiData['family_name']))
            $userData['nameLast'] = $ltiData['family_name'];
        if (!empty($ltiData['name'])) {
            list($nf, $nl) = $this->splitName($ltiData['name']);
            $userData['nameFirst'] = $nf;
            $userData['nameLast'] = $nl;
        }

        $this->set('userData', $userData);


        // Gather course/subject data
        $subjectCode = '';
        $courseCode = '';
        $courseId = 0;
        $subjectId = 0;
        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/lis']['course_section_sourcedid'])) {
            $subjectCode = $ltiData['https://purl.imsglobal.org/spec/lti/claim/lis']['course_section_sourcedid'];
            $subjectCode = trim(preg_replace('/[^a-z0-9_-]/i', '_', $subjectCode));
            /** @var Subject $subject */
            $subject = $this->getConfig()->getSubjectMapper()->findFiltered(
                array('code' => $subjectCode, 'institutionId' => $this->getInstitution()->getId())
            )->current();

            if ($subject) {
                $subjectId = $subject->getId();
                $courseId = $subject->getCourseId();
                $courseCode = $subject->getCourse()->getCode();
            } else {
                if (preg_match('/^(([A-Z]{4})([0-9]{5}))(\S*)/', $subjectCode, $regs)) {
                    $courseCode = $regs[1];
                } else if (preg_match('/^((MERGE|COM)_([0-9]{4}))_([0-9]+)/', $subjectCode, $regs)) {
                    $courseCode = $regs[1];
                } else {
                    $courseCode = $subjectCode;
                }
                $course = $this->getConfig()->getCourseMapper()->findFiltered(
                    array('code' => $courseCode, 'institutionId' => $this->getInstitution()->getId())
                )->current();
                if ($course) $courseId = $course->getId();
            }
        }

        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/custom']['courseId'])) {
            $courseId = (int)$ltiData['https://purl.imsglobal.org/spec/lti/claim/custom']['courseId'];
            $course = $this->getConfig()->getCourseMapper()->findFiltered(
                array('id' => $courseId, 'institutionId' => $this->getInstitution()->getId())
            )->current();
            if (!$course) $courseId = 0;
        }
        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/custom']['subjectId'])) {
            /** @var \Uni\Db\Subject $subject */
            $subject = $this->getConfig()->getSubjectMapper()->findFiltered(array(
                'id' => $ltiData['https://purl.imsglobal.org/spec/lti/claim/custom']['subjectId'],
                'institutionId' => $this->getInstitution()->getId()
            ))->current();
            if ($subject) {
                $subjectId = $subject->getId();
                $subjectCode = $subject->getCode();
                $courseId = $subject->getCourseId();
            }
        }

        $subjectData = array(
            'subjectId' => $subjectId,
            'institutionId' => $this->getInstitution()->getId(),
            'courseId' => $courseId,
            'subjectCode' => $subjectCode,
            'courseCode' => $courseCode,
            'name' => $ltiData['https://purl.imsglobal.org/spec/lti/claim/context']['title'],
            'email' => (!empty($ltiData['email'])) ? $ltiData['email'] : \Tk\Config::getInstance()->get('site.email'),
            'description' => '',
            // TODO: get this info from canvas in the future????
            'dateStart' => \Tk\Date::create(),
            'dateEnd' => \Tk\Date::create()->add(new \DateInterval('P1Y')),
            'active' => true,

            'id' => $subjectId,         // deprecated use subjectId
            'code' => $subjectCode,     // deprecated use subjectCode
        );

        $this->set('subjectData', $subjectData);
    }

    /**
     * @param array $ltiData
     * @return bool
     */
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

    protected function splitName(string $name)
    {
        $nf = $name;
        $nl = '';
        $name = trim($name);
        if ( preg_match('/\s/',$name) ) {
            $nf = substr($name, 0, strpos($name, ' '));
            $nl = substr($name, strpos($name, ' ') + 1);
        }
        return [$nf, $nl];
    }
}
/* Moodle Example Launch Data
Array[23]
(
  [nonce] => nonce-5e56d65bf3c839.63563322
  [iat] => 1582749276
  [exp] => 1582749336
  [iss] => https://mifsudm.unimelb.edu.au/Test/moodle
  [aud] => v23kN0NgpIoRgEW
  [https://purl.imsglobal.org/spec/lti/claim/deployment_id] => 4
  [https://purl.imsglobal.org/spec/lti/claim/target_link_uri] => https://mifsudm.unimelb.edu.au/Projects/tkuni
  [sub] => 4
  [https://purl.imsglobal.org/spec/lti/claim/lis] => Array[2]
    (
      [person_sourcedid] =>
      [course_section_sourcedid] => 00001

    )
  [https://purl.imsglobal.org/spec/lti/claim/roles] => Array[1]
    (
      [0] => http://purl.imsglobal.org/vocab/lis/v2/membership#Learner

    )
  [https://purl.imsglobal.org/spec/lti/claim/context] => Array[4]
    (
      [id] => 2
      [label] => LTI Course
      [title] => 2019 LTI Test Course
      [type] => Array[1]
        (
          [0] => CourseSection

        )

    )
  [https://purl.imsglobal.org/spec/lti/claim/resource_link] => Array[2]
    (
      [title] => TkUni LTI v1.3
      [id] => 4

    )
  [https://purl.imsglobal.org/spec/lti-bos/claim/basicoutcomesservice] => Array[2]
    (
      [lis_result_sourcedid] => {"data":{"instanceid":"4","userid":"4","typeid":"4","launchid":915842986},"hash":"3a07da1c48c094675f3f6dc838ad708af78fe442cc9d6069da9e763776cd2a2d"}
      [lis_outcome_service_url] => https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/service.php

    )
  [given_name] => Student
  [family_name] => One
  [name] => Student One
  [https://purl.imsglobal.org/spec/lti/claim/ext] => Array[2]
    (
      [user_username] => studentone
      [lms] => moodle-2

    )
  [email] => studentone@252s-dev.vet.unimelb.edu.au
  [https://purl.imsglobal.org/spec/lti/claim/launch_presentation] => Array[3]
    (
      [locale] => en
      [document_target] => iframe
      [return_url] => https://mifsudm.unimelb.edu.au/Test/moodle/mod/lti/return.php?course=2&launch_container=3&instanceid=4&sesskey=iahshVmH5z

    )
  [https://purl.imsglobal.org/spec/lti/claim/tool_platform] => Array[5]
    (
      [family_code] => moodle
      [version] => 2019052001
      [guid] => mifsudm.unimelb.edu.au
      [name] => 252s-dev
      [description] => 252s-dev Test LMS

    )
  [https://purl.imsglobal.org/spec/lti/claim/version] => 1.3.0
  [https://purl.imsglobal.org/spec/lti/claim/message_type] => LtiResourceLinkRequest
  [https://purl.imsglobal.org/spec/lti/claim/custom] => Array[12]
    (
      [courseparam1] => 123456
      [courseParam1] => 123456
      [courseparam2] => this another param
      [courseParam2] => this another param
      [courseid] => 1
      [courseId] => 1
      [subjectid] => 123
      [subjectId] => 123
      [testparam1] => 12345
      [testParam1] => 12345
      [testparam2] => this is a very long (param with specialChars)
      [testParam2] => this is a very long (param with specialChars)

    )

)
*/