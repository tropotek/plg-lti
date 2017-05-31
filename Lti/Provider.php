<?php
namespace Lti;

use App\Db\CourseMap;
use App\Db\Institution;
use App\Db\UserGroup;
use App\Db\UserMap;
use IMSGlobal\LTI\ToolProvider;
use IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector;
use Tk\EventDispatcher\Dispatcher;

/**
 * Class Provider
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 *
 * @todo Should we optimize the entire app level LTI objects to be more abstract ?????
 * @todo I have implemented this as a working example primarily
 *
 */
class Provider extends ToolProvider\ToolProvider
{
    const LTI_LAUNCH = 'lti_launch';
    const LTI_COURSE_ID = 'lti_courseId';

    /**
     * @var \App\Db\Course
     */
    protected static $course = null;

    /**
     * @var Institution
     */
    protected $institution = null;

    /**
     * @var Dispatcher
     */
    protected $dispatcher = null;


    /**
     * Provider constructor.
     *
     * @param DataConnector $dataConnector
     * @param Institution $institution
     * @param Dispatcher $dispatcher
     */
    public function __construct(DataConnector $dataConnector, $institution = null, $dispatcher = null)
    {
        parent::__construct($dataConnector);
        $this->institution = $institution;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get the LTI session data array
     *
     * @return array
     */
    public static function getLtiSession()
    {
        return \App\Factory::getSession()->get(self::LTI_LAUNCH);
    }

    /**
     * Is the user currently in an LTI session
     *
     * @return boolean
     */
    public static function isLti()
    {
        return \App\Factory::getSession()->has(self::LTI_LAUNCH);
    }

    /**
     * Get the LTi session course
     *
     * @return \App\Db\Course|\Tk\Db\Map\Model
     */
    public static function getLtiCourse()
    {
        if (!self::$course) {
            $ltiSes = self::getLtiSession();
            self::$course = CourseMap::create()->find($ltiSes[self::LTI_COURSE_ID]);
        }
        return self::$course;
    }

    /**
     * Get the LTi session institution
     *
     * @return Institution|\Tk\Db\Map\Model
     */
    public static function getLtiInstitution()
    {
        return self::getLtiCourse()->getInstitution();
    }

    /**
     * @return bool
     */
    public function isCoordinator()
    {
        return ($this->hasRole('Instructor'));
    }

    /**
     * @return bool
     */
    public function isLecturer()
    {
        return ($this->hasRole('ContentDeveloper') || $this->hasRole('TeachingAssistant'));
    }

    /**
     * Check whether the user has a specified role name.
     *
     * @param string $role Name of role
     *
     * @return boolean True if the user has the specified role
     */
    private function hasRole($role) {

        if (substr($role, 0, 4) !== 'urn:') {
            $role = 'urn:lti:role:ims/lis/' . $role;
        }
        return in_array($role, $this->user->roles);
    }

    /*
Array[34]
(
  [tool_consumer_info_product_family_code] => Blackboard Learn
  [resource_link_title] => EMS III [Current Course]
  [context_title] => VOCE Vet Science LTI test
  [roles] => urn:lti:role:ims/lis/Instructor            // TODO: Find out what a Lecture permission looks like.
  [lis_person_name_family] => Mifsud
  [tool_consumer_instance_name] => The University of Melbourne
  [tool_consumer_instance_guid] => 1005cc36f90e4ed58af938c5cea2910a
  [resource_link_id] => _189167_1
  [oauth_signature_method] => HMAC-SHA1
  [oauth_version] => 1.0
  [custom_caliper_profile_url] => https://CASSANDRA.lms.unimelb.edu.au/learn/api/v1/telemetry/caliper/profile/_189167_1
  [launch_presentation_return_url] => https://sandpit.lms.unimelb.edu.au/webapps/blackboard/execute/blti/launchReturn?course_id=_2051_1&content_id=_189167_1&toGC=false&launch_time=1488412989529&launch_id=43cac7ac-1ee0-45ac-853d-aaa638da9d30&link_id=_189167_1
  [ext_launch_id] => 43cac7ac-1ee0-45ac-853d-aaa638da9d30
  [ext_lms] => bb-3000.1.3-rel.70+214db31
  [lti_version] => LTI-1p0
  [lis_person_contact_email_primary] => michael.mifsud@unimelb.edu.au
  [oauth_signature] => O+HwrQrxPuxChH5pmfTcpHcKm0k=
  [oauth_consumer_key] => unimelb_00002
  [launch_presentation_locale] => en-AU
  [custom_caliper_federated_session_id] => https://caliper-mapping.cloudbb.blackboard.com/v1/sites/41943a4f-ec98-419c-8aa2-c7147a833858/sessions/0FFA07ED68CCF3F4729F63FF4317B7E4
  [oauth_timestamp] => 1488412989
  [lis_person_name_full] => Michael Mifsud
  [tool_consumer_instance_contact_email] => dba-support@unimelb.edu.au
  [lis_person_name_given] => Michael
  [custom_tc_profile_url] =>
  [oauth_nonce] => 33170809663331833
  [lti_message_type] => basic-lti-launch-request
  [user_id] => e178575f054e46bfbfaadfb1438d099b
  [oauth_callback] => about:blank
  [tool_consumer_info_version] => 3000.1.3-rel.70+214db31
  [context_id] => 7cd5258c04e749a5b67d184f6f200328
  [context_label] => VOCE10001_2014_SM5
  [launch_presentation_document_target] => window
  [ext_launch_presentation_css_url] => https://sandpit.lms.unimelb.edu.au/common/shared.css,https://sandpit.lms.unimelb.edu.au/branding/themes/unimelb-201410-08/theme.css,https://sandpit.lms.unimelb.edu.au/branding/colorpalettes/unimelb-201404.08/generated/colorpalette.generated.modern.css

)
    */



    /**
     * Insert code here to handle incoming connections - use the user,
     * context and resourceLink properties of the class instance
     * to access the current user, context and resource link.
     *
     * The onLaunch method may be used to:
     *
     *  - create the user account if it does not already exist (or update it if it does);
     *  - create any workspace required for the resource link if it does not already exist (or update it if it does);
     *  - establish a new session for the user (or otherwise log the user into the tool provider application);
     *  - keep a record of the return URL for the tool consumer (for example, in a session variable);
     *  - set the URL for the home page of the application so the user may be redirected to it.
     *
     */
    function onLaunch()
    {
        try {
            if (!$this->user->email) {
                throw new \Tk\Exception('User email not found! Cannot log in.');
            }
            // Try to locate an existing user...
            $user = UserMap::create()->findByEmail($this->user->email, $this->institution->id);

            if (!$user) {
                // Create new user
                $role = UserGroup::ROLE_STUDENT;
                if ($this->user->isAdmin() || $this->user->isStaff()) {
                    $role = UserGroup::ROLE_STAFF;
                }

                list($username, $domain) = explode('@', $this->user->email);
                // There is a possibility that the usernames clash so auto create a unique one.
                $un = $username;
                $i = 0;
                $found = null;
                do {
                    $found = UserMap::create()->findByUsername($un, $this->institution->id);
                    if (!$found) {
                        $username = $un;
                    }
                    $un = $username.'_'.$i;
                    $i++;
                } while ($found);

                $user = \App\Factory::createNewUser($this->institution->id, $username, $this->user->email, $role, '', $this->user->fullname);
            }

            if (!$user->active) {
                throw new \Tk\Exception('User has no permission to access this resource. Contact your administrator.');
            }
            $ltiSesh = array_merge($_GET, $_POST);
            //vd($ltiSesh);

            // Add user to course if found.
            if (empty($ltiSesh['context_label'])) throw new \Tk\Exception('Course not available, Please contact LMS administrator.');

            $courseCode = preg_replace('/[^a-z0-9_-]/i', '_', $ltiSesh['context_label']);
            $course = null;
            if (!empty($ltiSesh['lti_courseId'])) {     // Force course selection via passed param in the LTI launch url:  {launchUrl}?lti_courseId=3
                /** @var \App\Db\Course $course */
                $course = CourseMap::create()->find($ltiSesh['lti_courseId']);
                if (!$course || ($course->institutionId != $this->institution->id)) {
                    $course = null;
                }
            } else {
                $course = CourseMap::create()->findByCode(array('code' => $courseCode, 'institutionId' => $this->institution->id));
            }

            if (!$course) {
                if (!$this->user->isStaff()) throw new \Tk\Exception('Course not available, Please contact course coordinator.');
                $course = new \App\Db\Course();
                $course->institutionId = $this->institution->id;
                $course->name = $ltiSesh['context_title'];
                $course->code = $courseCode;
                $course->email = empty($ltiSesh['lis_person_contact_email_primary']) ? $ltiSesh['lis_person_contact_email_primary'] : \Tk\Config::getInstance()->get('site.email');
                $course->description = '';
                $course->start = \Tk\Date::create();
                $course->finish = $course->start->add(new \DateInterval('P1Y'));
                $course->active = true;
                $course->save();
            }
            CourseMap::create()->addStudent($course->id, $user->id);
            \Tk\Session::getInstance()->set(self::LTI_LAUNCH, $ltiSesh);


            // Add user to auth
            $auth = \App\Factory::getAuth();
            \App\Listener\MasqueradeHandler::masqueradeClear();
            $authResult = new \Tk\Auth\Result(\Tk\Auth\Result::SUCCESS, $user->id);
            $auth->clearIdentity()->getStorage()->write($user->id);
            \Tk\Config::getInstance()->setUser($user);

            // fire loginSuccess....
            if ($this->dispatcher) {    // This event should redirect the user to their homepage.
                $event = new \Tk\Event\AuthEvent($auth, $ltiSesh);
                $event->setResult($authResult);
                $event->set('user', $user);
                $event->set('isLti', true);
                $this->dispatcher->dispatch(\Tk\Auth\AuthEvents::LOGIN_SUCCESS, $event);
            }
            // Redirect to the course page
            \App\Uri::createHomeUrl($course->code . '/index.html')->redirect();

        } catch (\Exception $e) {
            vd($e->__toString());
            $this->reason = $e->__toString();
            $this->message = $e->getMessage();  // This will be shown in the host app
            $this->ok = false;
            return;
        }

    }

    /**
     * Insert code here to handle incoming content-item requests - use the user and context
     * properties to access the current user and context.
     *
     */
    function onContentItem()
    {
        vd('LTI: onContentItem');
    }

    /**
     * Insert code here to handle incoming registration requests - use the user
     * property of the $tool_provider parameter to access the current user.
     *
     */
    function onRegister()
    {
        vd('LTI: onRegister');
    }

    /**
     * Insert code here to handle errors on incoming connections - do not expect
     * the user, context and resourceLink properties to be populated but check the reason
     * property for the cause of the error.
     * Return TRUE if the error was fully handled by this method.
     *
     * @return null|bool
     */
    function onError()
    {
        vd('LTI: onError', $this->reason, $this->message);
        //return true;        // Stops redirect back to app, in-case you want to show an error messages locally
    }

}