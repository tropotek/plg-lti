<?php
namespace Lti\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MenuHandler implements Subscriber
{
    use ConfigTrait;

    public function onInit(\Tk\Event\Event $event)
    {
        if (!$this->getSession()->get('isLti')) return;

        // Hide the staff/student subject menu
        /** @var \Bs\Controller\Iface $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);;
        if(method_exists($controller, 'getPage')) {
            $page = $event->get('page');

            // TODO: Better off to set a flag in the config or something so the menu can check id the dropdown should be shown
//            $nav = $page->getNavbar();
//            //if ($nav instanceof \App\Ui\Navbar\StaffMenu || $nav instanceof \App\Ui\Navbar\StudentMenu) {
//            if ($nav instanceof \App\Ui\Navbar\StudentMenu) {
//                $nav->showSubjectDropdown(false);
//            }
        }

    }

    public function onController($event)
    {
        if (!$this->getSession()->get('isLti')) return;

//        /** @var \Bs\Controller\Iface $controller */
//        $controller = $event->getControllerObject();
//        if(method_exists($controller, 'getPage')) {
//            $page = $controller->getPage();
//            $nav = $page->getNavbar();
//            vd($nav);
//            if ($nav instanceof \App\Ui\Navbar\StaffMenu) {
//                $nav->showSubjectDropdown(false);
//            }
//        }

    }


    public static function getSubscribedEvents()
    {
        return array(
            \Tk\PageEvents::PAGE_INIT => array('onInit', 0),
            KernelEvents::CONTROLLER => array('onController', 0)
        );
    }
    
    
}