<?php
$config = \Tk\Config::getInstance();

// NOTE:
// You must manually include all required php files if you are not using composer to install the plugin.
// Alternatively be sure to use the plugin namespace for all classes such as \sample\Ems\MyClass


/** @var \Tk\Routing\RouteCollection $routes */
$routes = $config['site.routes'];

$params = array('access' => \App\Db\User::ROLE_ADMIN);
$routes->add('LTI Admin Settings', new \Tk\Routing\Route('/lti/adminSettings.html', 'Lti\Controller\SystemSettings::doDefault', $params));


$params = array('access' => \App\Db\User::ROLE_CLIENT);
$routes->add('LTI Institution Settings', new \Tk\Routing\Route('/lti/institutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault', $params));



