<?php
namespace Lti\Listener;

use Lti\Plugin;
use Lti\Provider;
use Tk\Auth\AuthEvents;
use Tk\ConfigTrait;
use Tk\Event\AuthEvent;
use Tk\Event\Subscriber;
use Uni\Db\Subject;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{
    use ConfigTrait;

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

        $ltiData = $adapter->get('ltiData');
        if (!$ltiData) return;

        //vd($ltiData);

        // Gather user details
        $role = 'student';
        if ($adapter->getLtiUser()->isAdmin() || $adapter->getLtiUser()->isStaff()) {
            $role = 'staff';
        }

        // try to determin the users name
        list($username, $domain) = explode('@', $adapter->getLtiUser()->email);
        if (!empty($ltiData['custom_canvas_user_login_id']))
            $username = $ltiData['custom_canvas_user_login_id'];

        $userData = array(
            'institutionId' => $adapter->getInstitution()->getId(),
            'username' => $username,
            'email' => $adapter->getLtiUser()->email,
            'role' => $role,
            'type' => $role,
            'name' => $adapter->getLtiUser()->fullname,
            'active' => true,
            'lmsUserId' => '',
            'nameFirst' => '',
            'nameLast' => '',
            'image' => '',
        );

        if (!empty($ltiData['custom_canvas_enrollment_state']))
            $userData['lmsEnrollmentState'] = $ltiData['custom_canvas_enrollment_state'];
        if (!empty($ltiData['custom_canvas_user_id']))
            $userData['lmsUserId'] = $ltiData['custom_canvas_user_id'];
        if (!empty($ltiData['user_id']))
            $userData['lmsUserId'] = $ltiData['user_id'];
        if (!empty($ltiData['lis_person_name_given']))
            $userData['nameFirst'] = $ltiData['lis_person_name_given'];
        if (!empty($ltiData['lis_person_name_family']))
            $userData['nameLast'] = $ltiData['lis_person_name_family'];
        if (!empty($ltiData['user_image']))
            $userData['image'] = $ltiData['user_image'];

        $adapter->set('userData', $userData);

        // Find a valid subject object if available
        if (empty($ltiData['context_label'])) {
            throw new \Tk\Exception('Subject not available, Please contact the LMS administrator.');
        }

        // Gather course/subject data
        $courseId = 0;
        $subjectId = 0;
        $courseCode = '';
        $subjectCode = preg_replace('/[^a-z0-9_-]/i', '_', $ltiData['context_label']);

        /** @var Subject $subject */
        $subject = $this->getConfig()->getSubjectMapper()->findFiltered(
            array('code' => $subjectCode, 'institutionId' => $this->getConfig()->getInstitution()->getId())
        )->current();

        if ($subject) {
            $subjectId = $subject->getId();
            $courseId = $subject->getCourseId();
            $courseCode = $subject->getCourse()->getCode();
        } else {
            $courseCode = \Uni\Db\Course::makeCourseCode($subjectCode);
            $course = $this->getConfig()->getCourseMapper()->findFiltered(
                array('code' => $courseCode, 'institutionId' => $this->getConfig()->getInstitution()->getId())
            )->current();
            if ($course) $courseId = $course->getId();
        }

        // Force subject selection via passed param in the LTI launch url:  {launchUrl}?custom_subjectId=3
        if (!empty($ltiData['subjectId'])) {
            /** @var \Uni\Db\Subject $subject */
            $subject = $this->getConfig()->getSubjectMapper()->findFiltered(array(
                'id' => $ltiData['subjectId'],
                'institutionId' => $this->getConfig()->getInstitution()->getId()
            ))->current();
            if ($subject) {
                $subjectId = $ltiData[Provider::LTI_SUBJECT_ID] = $subject->getId();
                $subjectCode = $subject->getCode();
                $courseId = $subject->getCourseId();
            }
        }
        $subjectData = array(
            'subjectId' => $subjectId,
            'institutionId' => $adapter->getInstitution()->getId(),
            'courseId' => $courseId,
            'name' => $ltiData['context_title'],
            'courseCode' => $courseCode,
            'subjectCode' => $subjectCode,
            'email' => empty($ltiData['lis_person_contact_email_primary']) ? $ltiData['lis_person_contact_email_primary'] : \Tk\Config::getInstance()->get('site.email'),
            'description' => '',
            'dateStart' => \Tk\Date::create(),
            'dateEnd' => \Tk\Date::create()->add(new \DateInterval('P1Y')),
            'active' => true,

            'id' => $subjectId,         // deprecated use subjectId
            'code' => $subjectCode,     // deprecated use subjectCode
        );
        $adapter->set('subjectData', $subjectData);

        $auth = $this->getConfig()->getAuth();
        $result = $auth->authenticate($adapter);
        $event->setResult($result);

        // Set for project templates
        $this->getConfig()->getSession()->set('isLti', true);

        $event->stopPropagation();
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        //if (!\Lti\Provider::isLti()) return;
        if (!$this->getConfig()->isLti()) return;

        // TODO: handle this redirect in the app if requred
        $ltiSess = \Lti\Provider::getLtiSession();
        if (!empty($ltiSess['launch_presentation_return_url'])) {
            $event->setRedirect(\Tk\Uri::create($ltiSess['launch_presentation_return_url']));
        }
        if (!$this->getConfig()->getMasqueradeHandler()->isMasquerading())
            $this->getConfig()->getSession()->remove('isLti');
        // Clear the LTI session data
        \Lti\Provider::clearLtiSession();
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
