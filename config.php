<?php
$config = \App\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Lti\\', dirname(__FILE__));

$routes = $config->getRouteCollection();
if (!$routes) return;

$params = array();

// LTI launch
$routes->add('lti-launch', new \Tk\Routing\Route('/lti/launch.html', 'Lti\Controller\Launch::doLaunch', $params));
$routes->add('institution-lti-launch', new \Tk\Routing\Route('/lti/{instHash}/launch.html', 'Lti\Controller\Launch::doInsLaunch', $params));


$routes->add('lti-admin-settings', new \Tk\Routing\Route('/admin/ltiSettings.html', 'Lti\Controller\SystemSettings::doDefault'));

$routes->add('lti-admin-institution-settings', new \Tk\Routing\Route('/admin/ltiInstitutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault'));
$routes->add('lti-client-institution-settings', new \Tk\Routing\Route('/client/ltiInstitutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault'));
$routes->add('lti-staff-institution-settings', new \Tk\Routing\Route('/staff/ltiInstitutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault'));



