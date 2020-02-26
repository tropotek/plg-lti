<?php
namespace Lti;

use IMSGlobal\LTI\ToolProvider;
use IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector;
use Tk\ConfigTrait;
use Tk\Event\Dispatcher;
use Tk\EventDispatcher\EventDispatcher;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Provider
{
    use ConfigTrait;

    const LTI_LAUNCH = 'lti_launch';

    /**
     * @var \Uni\Db\SubjectIface
     */
    protected static $subject = null;

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher = null;

    /**
     * @var null|\Exception
     */
    protected $e = null;


    /**
     * Provider constructor.
     *
     * @param DataConnector $dataConnector
     * @param \Uni\Db\InstitutionIface $institution
     * @param EventDispatcher $dispatcher
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
        return \Tk\Config::getInstance()->getSession()->get(self::LTI_LAUNCH);
    }

    /**
     * Get the LTI session data array
     *
     * @return array
     */
    public static function clearLtiSession()
    {
        return \Tk\Config::getInstance()->getSession()->remove(self::LTI_LAUNCH);
    }

    /**
     * Is the user currently in an LTI session
     *
     * @return boolean
     */
    public static function isLti()
    {
        return \Uni\Config::getInstance()->getSession()->has(self::LTI_LAUNCH);
    }

    /**
     * Get the LTi session
     *
     * @return \Uni\Db\SubjectIface|\Tk\Db\Map\Model
     * @throws \Exception
     */
    public static function getLtiSubject()
    {
        if (!self::$subject && isset($ltiSes[self::LTI_SUBJECT_ID])) {
            $ltiSes = self::getLtiSession();
            self::$subject = \Uni\Config::getInstance()->getSubjectMapper()->find($ltiSes[self::LTI_SUBJECT_ID]);
        }
        return self::$subject;
    }

    /**
     * Get the LTi session institution
     *
     * @return \Uni\Db\InstitutionIface|\Tk\Db\Map\Model
     * @throws \Exception
     */
    public static function getLtiInstitution()
    {
        return self::getLtiSubject()->getInstitution();
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
                throw new \Tk\Exception('User email not found! Cannot log in. Please check that the lis_person_contact_email_primary fields is enabled in your LMS setup.');
            }
            // Lti Launch data
            $ltiData = array_merge($_GET, $_POST);
            //\Tk\Session::getInstance()->set(self::LTI_LAUNCH, $ltiData);
            $this->getConfig()->getSession()->set(self::LTI_LAUNCH, $ltiData);

            //vd($ltiData);

            $adapter = new \Lti\Auth\LtiAdapter($this->user, $this->institution);
            $adapter->set('ltiData', $ltiData);

            $event = new \Tk\Event\AuthEvent($adapter);
            $this->getConfig()->getEventDispatcher()->dispatch(\Tk\Auth\AuthEvents::LOGIN, $event);
            $result = $event->getResult();

            if (!$result || !$result->isValid()) {
                if ($result) {
                    throw new \Tk\Exception(implode("\n", $result->getMessages()));
                }
                throw new \Tk\Exception('Cannot connect to LTI interface, please contact your course coordinator.');
            }

            // Copy the event to avoid propagation issues
            $sEvent = new \Tk\Event\AuthEvent($adapter);
            $sEvent->replace($event->all());
            $sEvent->setResult($event->getResult());
            $sEvent->setRedirect($event->getRedirect());
            if (!$sEvent->getRedirect())
                $sEvent->setRedirect($adapter->getLoginProcessEvent()->getRedirect());
            $this->getConfig()->getEventDispatcher()->dispatch(\Tk\Auth\AuthEvents::LOGIN_SUCCESS, $sEvent);
            if ($sEvent->getRedirect())
                $sEvent->getRedirect()->redirect();

            \Tk\Log::warning('Remember to redirect to a valid LTI page.');
        } catch (\Exception $e) {
            $this->e = $e;
            $this->message = $e->getMessage();  // This will be shown in the host app
            $this->reason = '';
            if ($this->getConfig()->isDebug()) {
                $this->reason = $e->__toString();
            }
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
     * @return bool
     */
    function onError()
    {
//        vd($this->getConfig()->getSession()->all());
        vd($this->getConfig()->getSession()->get(self::LTI_LAUNCH));
//        if ($this->e) {
//            vdd($this->e->__toString());
//        }
        vd('LTI: onError', $this->reason, $this->message);
        return true;        // Stops redirect back to app, in-case you want to show an error messages locally
    }


    /* Blackboard
Array[34]
(
  [tool_consumer_info_product_family_code] => Blackboard Learn
  [resource_link_title] => EMS III [Current Subject]
  [context_title] => VOCE Vet Science LTI test
  [roles] => urn:lti:role:ims/lis/Instructor            // TODO: Find out what a Lecture permission looks like.
  [lis_person_name_family] => Mifsud
  [tool_consumer_instance_name] => The University of Melbourne
  [tool_consumer_instance_guid] => 1005cc36f90e4ed58af938c5cea2910a
  [resource_link_id] => _189167_1
  [oauth_signature_method] => HMAC-SHA1
  [oauth_version] => 1.0
  [custom_caliper_profile_url] => https://CASSANDRA.lms.unimelb.edu.au/learn/api/v1/telemetry/caliper/profile/_189167_1
  [launch_presentation_return_url] => https://sandpit.lms.unimelb.edu.au/webapps/blackboard/execute/blti/launchReturn?subject_id=_2051_1&content_id=_189167_1&toGC=false&launch_time=1488412989529&launch_id=43cac7ac-1ee0-45ac-853d-aaa638da9d30&link_id=_189167_1
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
    /* Canvas
Array[39]
(
  [oauth_consumer_key] => dev_tk2uni
  [oauth_signature_method] => HMAC-SHA1
  [oauth_timestamp] => 1554852893
  [oauth_nonce] => eJNKuzDZtG1S5IRVgbHlbv4SLMegwxXp3Pr01cERq0
  [oauth_version] => 1.0
  [context_id] => e77e815cb63ae689dbea461c6c08b6351987fe87
  [context_label] => EarlyPlaypenmifsudm
  [context_title] => EarlyPlaypenmifsudm
  [custom_canvas_api_domain] => unimelb-demo.instructure.com
  [custom_canvas_course_id] => 512
  [custom_canvas_enrollment_state] => active
  [custom_canvas_user_id] => 3474
  [custom_canvas_user_login_id] => mifsudm
  [custom_canvas_workflow_state] => claimed
  [ext_roles] => urn:lti:instrole:ims/lis/Instructor,urn:lti:instrole:ims/lis/Student,urn:lti:role:ims/lis/Instructor,urn:lti:sysrole:ims/lis/User
  [launch_presentation_document_target] => iframe
  [launch_presentation_locale] => en-AU
  [launch_presentation_return_url] => https://unimelb-demo.instructure.com/courses/512/external_content/success/external_tool_redirect
  [lis_course_offering_sourcedid] => PPEarlymifsudm
  [lis_person_contact_email_primary] => mifsudm@unimelb.edu.au
  [lis_person_name_family] => Mifsud
  [lis_person_name_full] => Mick Mifsud
  [lis_person_name_given] => Mick
  [lis_person_sourcedid] => 70038
  [lti_message_type] => basic-lti-launch-request
  [lti_version] => LTI-1p0
  [oauth_callback] => about:blank
  [resource_link_id] => b0f26bb168b11bbdfac16d3c22c3415277c0d287
  [resource_link_title] => tk2uni [Dev]
  [roles] => Instructor
  [tool_consumer_info_product_family_code] => canvas
  [tool_consumer_info_version] => cloud
  [tool_consumer_instance_contact_email] => notifications@instructure.com
  [tool_consumer_instance_guid] => ARzjzfMrSX3XawfM0DJyl2Rue5DDE1xuTmM96AE7:canvas-lms
  [tool_consumer_instance_name] => University of Melbourne [DEMO Instance]
  [user_id] => ca28e6b10d2206877b74863b1a1fda178785a7e7
  [user_image] => https://unimelb-demo.instructure.com/images/thumbnails/104758/4qmlz454Gsc9MXg38o7uF26xUMJDSAKNJlRgGNCZ
  [oauth_signature] => aiGn3jds1PvRsLybobmUvvJOWEs=
  [custom_tc_profile_url] =>

)
    */
}