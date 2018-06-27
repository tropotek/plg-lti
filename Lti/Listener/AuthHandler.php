<?php
namespace Lti\Listener;

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
     * @param \Tk\Event\AuthAdapterEvent $event
     * @return null|void
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onLoginProcess(\Tk\Event\AuthAdapterEvent $event)
    {
        vd('Lti onLoginProcess');
        if ($event->getAdapter() instanceof \Lti\Auth\LtiAdapter) {
            /** @var \Tk\Auth\Adapter\Ldap $adapter */
            $adapter = $event->getAdapter();
            $config = \App\Config::getInstance();

            // Find user data from ldap connection


            //$event->setResult(new \Tk\Auth\Result(\Tk\Auth\Result::SUCCESS, $user->getId()));


        }


    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLogin(AuthEvent $event)
    {
        $result = null;
        $formData = $event->all();

        vd('LTI onLogin');

        $institution = $this->getConfig()->getInstitution();
        if (!$institution) return;

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