<?php
namespace Lti\Listener;

use Tk\Event\Subscriber;
use Lti\Plugin;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SetupHandler implements Subscriber
{

    /**
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     * @throws \Exception
     */
    public function onRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event)
    {
        $config = \Uni\Config::getInstance();
        $institution = $config->getInstitution();
        if($institution && Plugin::getInstance()->isZonePluginEnabled(Plugin::ZONE_INSTITUTION, $institution->getId())) {
            $dispatcher = $config->getEventDispatcher();
            $dispatcher->addSubscriber(new \Lti\Listener\AuthHandler());
            $dispatcher->addSubscriber(new \Lti\Listener\MenuHandler());
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
            KernelEvents::REQUEST => array('onRequest', -10)
        );
    }
    
    
}