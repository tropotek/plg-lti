<?php

namespace Lti\Controller;

use Dom\Template;
use IMSGlobal\LTI;
use Lti\Plugin;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Canvas extends \Bs\Controller\Iface
{

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;

    private $message = '';

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



        // Sourced From: https://canvas.instructure.com/doc/api/file.lti_dev_key_config.html
        try {
            $data = Plugin::getInstance()->getData();
            $canvas = array(
                'title' => $this->getConfig()->get('site.title'),
                'description' => strip_tags($this->institution->getDescription()),
                'privacy_level' => 'public',
                'oidc_initiation_url' => Plugin::createUrl('/login.html', $this->institution)->toString(),
                'target_link_uri' => Plugin::createUrl('/launch.html', $this->institution)->toString(),
                'scopes' => array(
                    'https => //purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                    'https => //purl.imsglobal.org/spec/lti-ags/scope/result.readonly'
                ),
                'extensions' => array(
                    array(
                        'domain' => \Tk\Uri::create()->getHost(),
                        'tool_id' => $this->getConfig()->get('site.short.title'),
                        'platform' => 'canvas.instructure.com',
                        'settings' => array(
                            'text' => 'Launch ' . $this->getConfig()->get('site.short.title'),
                            'icon_url' => \Tk\Uri::create('/html/app/img/favicon.png')->toString(),
                            'selection_height' => 800,
                            'selection_width' => 800,
                            'placements' => array(
                                array(
                                    'text' => 'User Navigation Placement',
                                    'enabled' => true,
                                    'icon_url' => \Tk\Uri::create('/html/app/img/favicon.png')->toString(),
                                    'placement' => 'user_navigation',
                                    'message_type' => 'LtiResourceLinkRequest',
                                    'target_link_uri' => Plugin::createUrl('/launch.html', $this->institution)->toString(),
                                    'canvas_icon_class' => 'icon-lti'
//                                    ,'custom_fields' => array(
//                                        'foo' => '$Canvas.user.id'
//                                    )
                                )
//                                ,array(
//                                    'text' => 'Editor Button Placement',
//                                    'enabled' => true,
//                                    'icon_url' => \Tk\Uri::create('/html/app/img/favicon.png')->toString(),
//                                    'placement' => 'editor_button',
//                                    'message_type' => 'LtiDeepLinkingRequest',
//                                    'target_link_uri' => Plugin::createUrl('/launch.html', $this->institution)->toString(),
//                                    'selection_height' => 500,
//                                    'selection_width' => 500
//                                )
                            )
                        )
                    )
                ),
                "public_jwk" => current(current(Plugin::getJwks()))
//                ,'custom_fields' => array(
//                    'subjectId' => '22'
//                )
            );

            \Tk\ResponseJson::createJson($canvas)->send();
            exit();
        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
            $this->message = $e->getMessage();
        }
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        if ($this->message)
            $template->insertHtml('message', trim($this->message, '<br/>'));

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
  
      <h4><i choice="icon" var="icon"></i> <strong var="title">LTI Launch Error</strong></h4>
      <p><span var="message">Sorry, there was an error connecting you to the application</span></p>
        
  </div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}