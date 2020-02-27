<?php
namespace Lti\Listener;

use Lti\Plugin;
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