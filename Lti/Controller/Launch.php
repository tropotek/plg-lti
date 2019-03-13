<?php
namespace Lti\Controller;

use Tk\Request;
use Dom\Template;
use Lti\Plugin;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Launch extends \Bs\Controller\Iface
{

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;


    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doLaunch(Request $request)
    {
        $this->institution = $this->getConfig()->getInstitutionMapper()->findByDomain($request->getUri()->getHost());
        if ($this->institution) {
            $this->doInsLaunch($request, $this->institution->getHash());
        }
    }

    /**
     * @param Request $request
     * @param $instHash
     * @return \Dom\Template|Template|string
     * @throws \Exception
     */
    public function doInsLaunch(Request $request, $instHash)
    {
        if (!$this->institution)
            $this->institution = $this->getConfig()->getInstitutionMapper()->findByHash($instHash);

        if (!$this->institution) {
            throw new \Tk\NotFoundHttpException('Institution not found.');
        }

        //if (!$request->has('lti_version') || !$request->has('ext_lms')) {     // Removed because Canvas does not have the ext_lms key
        if (!$request->has('lti_version')) {
            return $this->show();
        }

        $msg = '';
        if(Plugin::getInstance()->isActive()) {
            $provider = new \Lti\Provider(Plugin::getLtiDataConnector(), $this->institution, $this->getConfig()->getEventDispatcher());
            $_POST['custom_tc_profile_url'] = '';   // Hack to speed up the launch process as we do not need this url
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