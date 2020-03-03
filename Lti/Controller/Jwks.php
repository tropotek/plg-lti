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
class Jwks extends \Bs\Controller\Iface
{

    /**
     * @var \Uni\Db\InstitutionIface
     */
    protected $institution = null;

    private $message = '';


    /**
     * @param Request $request
     * @param $instHash
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        try {
            \Tk\ResponseJson::createJson(Plugin::getJwks())->send();
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