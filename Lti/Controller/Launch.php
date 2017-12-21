<?php
namespace Lti\Controller;

use Tk\Request;
use Dom\Template;
use Uni\Controller\Iface;
use Lti\Plugin;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Launch extends Iface
{

    /**
     * @var \App\Db\Institution
     */
    protected $institution = null;


    /**
     * @param Request $request
     */
    public function doLaunch(Request $request)
    {
        $this->institution = \App\Db\InstitutionMap::create()->findByDomain($request->getUri()->getHost());
        if ($this->institution) {
            $this->doInsLaunch($request, $this->institution->getHash());
        }
    }

    /**
     * @param Request $request
     * @return \Dom\Template|Template|string
     */
    public function doInsLaunch(Request $request, $instHash)
    {
        if (!$this->institution)
            $this->institution = \App\Db\InstitutionMap::create()->findByHash($instHash);

        if (!$this->institution) {
            throw new \Tk\NotFoundHttpException('Institution not found.');
        }

        if (!$request->has('lti_version') || !$request->has('ext_lms')) {
            return $this->show();
        }

        $msg = '';
        if(Plugin::getInstance()->isActive()) {
            $provider = new \Lti\Provider(Plugin::getLtiDataConnector(), $this->institution, $this->getConfig()->getEventDispatcher());
            $_POST['custom_tc_profile_url'] = '';   // Hack to speed up the launch as we do not need this url
            $provider->handleRequest();

            if ($provider->message) {
                $msg .= $provider->message . '<br/>';
            }
            if ($provider->reason) {
                $msg .= $provider->reason . '<br/>';
            }
            $this->getConfig()->set('lti.provider', $provider);
        } else {
            $msg = 'LTI is not enabled for this Institution';
        }

        $this->getTemplate()->insertHtml('message', trim($msg, '<br/>'));
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="content">
  <div class="container">
  
    <div class="alert alert-danger" var="row">
      <!-- button class="close noblock" data-dismiss="alert">&times;</button -->
      <h4><i choice="icon" var="icon"></i> <strong var="title">LTI Access Error</strong></h4>
      <span var="message">Sorry, there was an error connecting you to the application</span>
    </div>
        
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}