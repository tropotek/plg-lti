<?php
namespace Lti\Controller;

use Dom\Template;
use Lti\Plugin;
use Tk\Request;
use \IMSGlobal\LTI;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Login extends \Bs\Controller\Iface
{

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;


    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->institution = $this->getConfig()->getInstitutionMapper()->findByDomain($request->getTkUri()->getHost());
        if ($this->institution) {
            $this->doInsDefault($request, $this->institution->getHash());
        }
    }

    /**
     * @param Request $request
     * @param $instHash
     * @throws \Exception
     */
    public function doInsDefault(Request $request, $instHash)
    {
        if (!Plugin::getInstance()->isActive()) {
            throw new \Tk\NotFoundHttpException('Plugin not active.');
        }
        if (!$this->institution)
            $this->institution = $this->getConfig()->getInstitutionMapper()->findByHash($instHash);

        if (!$this->institution) {
            throw new \Tk\NotFoundHttpException('Institution not found.');
        }

        $msg = '';
        try {
            LTI\LTI_OIDC_Login::new(new \Lti\Database($this->institution))
                ->do_oidc_login_redirect(Plugin::createUrl('/launch.html', $this->institution)->toString())
                ->do_redirect();
        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
            $msg = $e->getMessage();
        }

        if ($msg)
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
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="content">
  <div class="container">
  
      <h4><i choice="icon" var="icon"></i> <strong var="title">LTI Login Error</strong></h4>
      <p><span var="message">Sorry, there was an error connecting you to the application</span></p>

  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}