<?php

$config = \App\Config::getInstance();
$routes = $config->getRouteCollection();
if (!$routes) return;

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Lti\\', dirname(__FILE__));



$params = array();


// LTI launch
$routes->add('lti-launch', new \Tk\Routing\Route('/lti/launch.html', 'Lti\Controller\Launch::doLaunch', $params));
$routes->add('institution-lti-launch', new \Tk\Routing\Route('/lti/{instHash}/launch.html', 'Lti\Controller\Launch::doInsLaunch', $params));


$params = array('role' => 'admin');
$routes->add('LTI Admin Settings', new \Tk\Routing\Route('/lti/adminSettings.html', 'Lti\Controller\SystemSettings::doDefault', $params));


$params = array('role' => array('admin', 'client'));
$routes->add('LTI Institution Settings', new \Tk\Routing\Route('/lti/institutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault', $params));



