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
class Launch extends \Bs\Controller\Iface
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

        try {
            $launch = LTI\LTI_Message_Launch::new(new \Lti\Database($this->institution))->validate();
            if ($launch->is_deep_link_launch()) {
                vd('TODO: Launch is a deep link???');
            }
            $this->onLaunch($launch);
        } catch (\Exception $e) {
            \Tk\Log::error($e->__toString());
            $this->message = $e->getMessage();
        }
    }


    /**
     * Insert code here to handle incoming connections - use the user,
     * context and resourceLink properties of the class instance
     * to access the current user, context and resource link.
     *
     * The onLaunch method may be used to:
     *
     *  - create the user account if it does not already exist (or update it if it does);
     *  - create any workspace required for the resource link if it does not already exist (or update it if it does);
     *  - establish a new session for the user (or otherwise log the user into the tool provider application);
     *  - keep a record of the return URL for the tool consumer (for example, in a session variable);
     *  - set the URL for the home page of the application so the user may be redirected to it.
     *
     * @param LTI\LTI_Message_Launch $launch
     * @throws \Exception
     */
    function onLaunch(LTI\LTI_Message_Launch $launch)
    {
        $ltiData = $launch->get_launch_data();
        if (empty($ltiData['nonce']) || empty($ltiData['https://purl.imsglobal.org/spec/lti/claim/version'])) {
            throw new \Tk\Exception('Invalid LTI data found!');
        }
        if (empty($ltiData['email'])) {
            throw new \Tk\Exception('User email not found! Please check your LMS configuration.');
        }

        // Save Lti Launch data
        $this->getConfig()->getSession()->set(Plugin::LTI_LAUNCH, $ltiData);
        $this->getConfig()->getSession()->set('isLti', true);
        $adapter = new \Lti\Auth\LtiAdapter($launch, $this->institution);

        $event = new \Tk\Event\AuthEvent($adapter);
        $this->getConfig()->getEventDispatcher()->dispatch(\Tk\Auth\AuthEvents::LOGIN, $event);
        $result = $event->getResult();

        if (!$result || !$result->isValid()) {
            if ($result) {
                throw new \Tk\Exception(implode("\n", $result->getMessages()));
            }
            throw new \Tk\Exception('Cannot connect to LTI interface, please contact your course coordinator.');
        }

        // Copy the event to avoid propagation issues
        $sEvent = new \Tk\Event\AuthEvent($adapter);
        $sEvent->replace($event->all());
        $sEvent->setResult($event->getResult());
        $sEvent->setRedirect($event->getRedirect());
        if (!$sEvent->getRedirect())
            $sEvent->setRedirect($adapter->getLoginProcessEvent()->getRedirect());
        $this->getConfig()->getEventDispatcher()->dispatch(\Tk\Auth\AuthEvents::LOGIN_SUCCESS, $sEvent);
        if ($sEvent->getRedirect())
            $sEvent->getRedirect()->redirect();

        \Tk\Log::warning('Remember to redirect to a valid LTI page.');
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