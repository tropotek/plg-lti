<?php

use Tk\Routing\Route;

$config = \App\Config::getInstance();

/** @var \Composer\Autoload\ClassLoader $composer */
$composer = $config->getComposer();
if ($composer)
    $composer->add('Lti\\', dirname(__FILE__));

$routes = $config->getRouteCollection();

$params = array();

// LTI launch

$routes->add('lti-login', Route::create('/lti/login.html', 'Lti\Controller\Login::doDefault'));
$routes->add('lti-ins-login', Route::create('/lti/{instHash}/login.html', 'Lti\Controller\Login::doInsDefault'));
$routes->add('lti-launch', Route::create('/lti/launch.html', 'Lti\Controller\Launch::doDefault'));
$routes->add('lti-ins-launch', Route::create('/lti/{instHash}/launch.html', 'Lti\Controller\Launch::doInsDefault'));

$routes->add('lti-canvas', Route::create('/lti/canvas.json', 'Lti\Controller\Canvas::doDefault'));
$routes->add('lti-ins-canvas', Route::create('/lti/{instHash}/canvas.json', 'Lti\Controller\Canvas::doInsDefault'));
$routes->add('lti-jwks', Route::create('/lti/jwks.json', 'Lti\Controller\Jwks::doDefault'));



$routes->add('lti-admin-settings', Route::create('/admin/ltiSettings.html', 'Lti\Controller\SystemSettings::doDefault'));
$routes->add('lti-client-ins-settings', Route::create('/client/ltiInstitutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault'));
$routes->add('lti-staff-ins-settings', Route::create('/staff/ltiInstitutionSettings.html', 'Lti\Controller\InstitutionSettings::doDefault'));

$routes->add('lti-client-ins-platform-edit', Route::create('/client/lti/platformEdit.html', 'Lti\Controller\Platform\Edit::doDefault'));
$routes->add('lti-staff-ins-platform-edit', Route::create('/staff/lti/platformEdit.html', 'Lti\Controller\Platform\Edit::doDefault'));




