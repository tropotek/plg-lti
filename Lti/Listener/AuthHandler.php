<?php
namespace Lti\Listener;

use Lti\Provider;
use Lti\Plugin;
use Tk\Event\Subscriber;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class AuthHandler implements Subscriber
{

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        vd('LTI onLogin');
        /** @var \Lti\Auth\LtiAdapter $adapter */
        $adapter = $event->getAdapter();
        $ltiData = $adapter->get('ltiData');

        // Gather user details
        $role = 'student';
        if ($adapter->getLtiUser()->isAdmin() || $adapter->getLtiUser()->isStaff()) {
            $role = 'staff';
        }
        list($u, $d) = explode('@', $adapter->getLtiUser()->email);
        // There is a slim possibility that a username may exist, make it unique
        $username = $this->makeUniqueUsername($u, $adapter->getInstitution()->getId());
        $userData = array(
            'institutionId' => $adapter->getInstitution()->getId(),
            'username' => $username,
            'email' => $adapter->getLtiUser()->email,
            'role' => $role,
            'name' => $adapter->getLtiUser()->fullname,
            'active' => true
        );
        $event->set('userData', $userData);


        // Find a valid subject object if available
        if (empty($ltiData['context_label']))
            throw new \Tk\Exception('Subject not available, Please contact the LMS administrator.');

        $subjectCode = preg_replace('/[^a-z0-9_-]/i', '_', $ltiData['context_label']);
        $subject = null;

        // Force subject selection via passed param in the LTI launch url:  {launchUrl}?custom_subjectId=3
        if (!empty($ltiData['subjectId']) && empty($ltiData[Provider::LTI_SUBJECT_ID])) {
            $ltiData[Provider::LTI_SUBJECT_ID] = (int)$ltiData['subjectId'];
        }
        $subjectData = array(
            'subjectId' => $ltiData[Provider::LTI_SUBJECT_ID],
            'institutionId' => $adapter->getInstitution()->getId(),
            'name' => $ltiData['context_title'],
            'code' => $subjectCode,
            'email' => empty($ltiData['lis_person_contact_email_primary']) ? $ltiData['lis_person_contact_email_primary'] : \Tk\Config::getInstance()->get('site.email'),
            'description' => '',
            'dateStart' => \Tk\Date::create(),
            'dateEnd' => \Tk\Date::create()->add(new \DateInterval('P1Y')),
            'active' => true
        );
        $event->set('subjectData', $subjectData);

    }

    /**
     * @param $username
     * @param $institutionId
     * @return string
     */
    private function makeUniqueUsername($username, $institutionId)
    {
        $i = 0;
        $found = null;
        do {
            $i++;
            $found = Plugin::getPluginApi()->findUser($username, $institutionId);
            if ($found) {
                $username = $username.'_'.$i;
            }
        } while (!$found);
        return $username;
    }



    /**
     * @param \Tk\Event\AuthEvent $event
     * @return null|void
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onLoginProcess(\Tk\Event\AuthEvent $event)
    {
        vd('Lti onLoginProcess');
        if ($event->getAdapter() instanceof \Lti\Auth\LtiAdapter) {
            /** @var \Tk\Auth\Adapter\Ldap $adapter */
            $adapter = $event->getAdapter();
            $config = \App\Config::getInstance();

            // Find/create user data from lti data

            // Find/create subject data from lti data

            //$event->setResult(new \Tk\Auth\Result(\Tk\Auth\Result::SUCCESS, $user->getId()));

        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        vd('LTI onLoginSuccess');
        if ($event->get('isLti') === true) {
            //$event->setRedirect(Plugin::getPluginApi()->getLtiHome($event->get('user'), $event->get('subject')));
            $event->setRedirect(null);
            \App\Config::getInstance()->getSession()->set('auth.password.access', false);
            Plugin::getPluginApi()->getLtiHome($event->get('user'), $event->get('subject'))->redirect();
        }
    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogout(AuthEvent $event)
    {
        $ltiSess = \Lti\Provider::getLtiSession();
        if (\Lti\Provider::isLti() && !empty($ltiSess['launch_presentation_return_url'])) {
            $event->setRedirect(\Tk\Uri::create($ltiSess['launch_presentation_return_url']));
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
            AuthEvents::LOGIN => array('onLogin', 10),
            AuthEvents::LOGIN_PROCESS => array('onLoginProcess', 10),
            AuthEvents::LOGIN_SUCCESS => array('onLoginSuccess', 0),
            AuthEvents::LOGOUT => array('onLogout', 10)
        );
    }

    /**
     * @return \App\Config
     */
    public function getConfig()
    {
        return \App\Config::getInstance();
    }
    
}