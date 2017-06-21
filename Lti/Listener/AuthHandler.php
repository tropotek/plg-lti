<?php
namespace Lti\Listener;

use Lti\Plugin;
use Tk\Event\Subscriber;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
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

    }

    /**
     * @param AuthEvent $event
     * @throws \Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        if ($event->get('isLti') === true) {
            //$event->setRedirect(Plugin::getPluginApi()->getLtiHome($event->get('user'), $event->get('course')));
            $event->setRedirect(null);
            \App\Factory::getSession()->set('auth.password.access', false);
            Plugin::getPluginApi()->getLtiHome($event->get('user'), $event->get('course'))->redirect();
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
            //AuthEvents::LOGIN => array('onLogin', 0),
            AuthEvents::LOGIN_SUCCESS => array('onLoginSuccess', 0),
            AuthEvents::LOGOUT => array('onLogout', 10)
        );
    }
    
    
}