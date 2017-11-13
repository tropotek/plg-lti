<?php
$config = \Tk\Config::getInstance();


// NOTE:
// You must manually include all required php files if you are not using composer to install the plugin.
// Alternatively be sure to use the plugin namespace for all classes such as Ems\MyClass


/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];

$params = array();


// LTI launch
$routes->add('lti-launch', new \Tk\Routing\Route('/lti/launch.html', 'Lti\Controller\Launch::doLaunch', $params));
$routes->add('institution-lti-launch', new \Tk\Routing\Route('/lti/{instHash}/launch.html', 'Lti\Controller\Launch::doInsLaunch', $params));


$params = array('role' => 'admin');
$routes->add('LTI Admin Settings', new \Tk\Routing\Route('/lti/adminSettings.html', 'Lti\Controller\SystemSettings::doDefault', $params));


$params = array('role' => array('admin', 'client'));
$routes->add('LTI Institution Settings', new \Tk\Routing\Route('/lti/institutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault', $params));



