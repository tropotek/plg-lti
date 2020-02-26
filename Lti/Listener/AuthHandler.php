<?php
namespace Lti\Listener;

use IMSGlobal\LTI\LTI_Message_Launch;
use Lti\Plugin;
use Lti\Provider;
use Tk\Auth\AuthEvents;
use Tk\ConfigTrait;
use Tk\Event\AuthEvent;
use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{
    use ConfigTrait;

    const LD_ROLES = 'https://purl.imsglobal.org/spec/lti/claim/roles';
    const LD_ROLES_MEMBERSHIP = 'http://purl.imsglobal.org/vocab/lis/v2/membership#';


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
//    private function isStudent(array $ltiData)
//    {
//        foreach ($ltiData[self::LD_ROLES] as $role) {
//            $role = strtolower($role);
//            if (
//                $role == self::LD_ROLES_MEMBERSHIP.'learner' ||
//                $role == self::LD_ROLES_MEMBERSHIP.'administrator' ||
//                $role == self::LD_ROLES_MEMBERSHIP.'contentdeveloper'
//            ) {
//                return true;
//            }
//        }
//        return false;
//    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        /** @var \Lti\Auth\LtiAdapter $adapter */
        $adapter = $event->getAdapter();
        if (!$adapter instanceof \Lti\Auth\LtiAdapter) return;
        if (!Plugin::isEnabled($adapter->getInstitution())) return;

        $ltiData = $adapter->getLaunch()->get_launch_data();
        if (!$ltiData) return;

        // Gather user details
        $type = 'student';
        if ($this->isStaff($ltiData)) {
            $type = 'staff';
        }
        $userData = array(
            'institutionId' => $adapter->getInstitution()->getId(),
            'username' => $adapter->get('username'),
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

        $adapter->set('userData', $userData);

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
//        if (!empty($ltiData['subjectId']) && empty($ltiData[Provider::LTI_SUBJECT_ID])) {
//            $ltiData[Provider::LTI_SUBJECT_ID] = (int)$ltiData['subjectId'];
//        }

        $subjectData = array(
            // TODO:
            //'id' => !empty($ltiData[Provider::LTI_SUBJECT_ID]) ? (int)$ltiData[Provider::LTI_SUBJECT_ID] : 0,
            'institutionId' => $adapter->getInstitution()->getId(),
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
        $adapter->set('subjectData', $subjectData);

        $auth = $this->getConfig()->getAuth();
        $result = $auth->authenticate($adapter);
        $event->setResult($result);

        $event->stopPropagation();
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $ltiData = $this->getConfig()->getSession()->get(Plugin::LTI_LAUNCH);
        if (!empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url'])) {
            $event->setRedirect(\Tk\Uri::create($ltiData['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url']));
        }
    }



    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            AuthEvents::LOGIN => array('onLogin', -10), // Must run before app AuthHandler
            AuthEvents::LOGOUT => array('onLogout', 100)
        );
    }
    
}