<?php
namespace Lti\Listener;

use Tk\Event\Subscriber;

/**
 * Class StartupHandler
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MenuHandler implements Subscriber
{

    public function onInit(\Tk\Event\Event $event)
    {
        if (!\Lti\Provider::isLti()) return;

        // Hide the staff/student course menu
        // TODO: This may not be the best solution to limiting the staff and student to the one course???? Test to see the workflow
        /** @var \App\Controller\Iface $controller */
        $controller = $event->get('controller');
        if(method_exists($controller, 'getPage')) {
            $page = $event->get('page');
            $nav = $page->getNavbar();
            //if ($nav instanceof \App\Ui\Navbar\StaffMenu || $nav instanceof \App\Ui\Navbar\StudentMenu) {
            if ($nav instanceof \App\Ui\Navbar\StudentMenu) {
                $nav->showCourseDropdown(false);
            }
        }

    }

    public function onController(\Tk\Event\ControllerEvent $event)
    {
        if (!\Lti\Provider::isLti()) return;

//        /** @var \App\Controller\Iface $controller */
//        $controller = $event->getController();
//        if(method_exists($controller, 'getPage')) {
//            $page = $controller->getPage();
//            $nav = $page->getNavbar();
//            vd($nav);
//            if ($nav instanceof \App\Ui\Navbar\StaffMenu) {
//                $nav->showCourseDropdown(false);
//            }
//        }

    }


    public static function getSubscribedEvents()
    {
        return array(
            \App\AppEvents::PAGE_INIT => array('onInit', 0),
            \Tk\Kernel\KernelEvents::CONTROLLER => array('onController', 0)
        );
    }
    
    
}