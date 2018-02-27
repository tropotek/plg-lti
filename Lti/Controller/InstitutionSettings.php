<?php
namespace Lti\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Uni\Controller\Iface;
use Lti\Plugin;

/**
 * Class Contact
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class InstitutionSettings extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \App\Db\Institution
     */
    protected $institution = null;

    /**
     * @var \Tk\Db\Data|null
     */
    protected $data = null;


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('LDAP Plugin - Institution Settings');

    }

    /**
     * doDefault
     *
     * @param Request $request
     * @return void
     * @throws Form\Exception
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->institution = \App\Db\InstitutionMap::create()->find($request->get('zoneId'));
        $this->data = Plugin::getInstitutionData($this->institution);

        $this->form = \Uni\Config::createForm('formEdit');
        $this->form->setRenderer(\Uni\Config::createFormRenderer($this->form));

        $this->form->addField(new Field\Checkbox(Plugin::LTI_ENABLE))->addCss('tk-input-toggle')->setLabel('Enable LTI')->
            setTabGroup('LTI')->setNotes('Enable the LTI launch URL for LMS systems.');

        $lurl = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html');
        if ($this->institution->domain)
            $lurl = \Tk\Uri::create('/lti/launch.html')->setHost($this->institution->domain);
        $lurl->setScheme('https')->toString();
        $this->form->addField(new Field\Html(Plugin::LTI_URL, $lurl))->setLabel('Launch Url')->setTabGroup('LTI');
        $this->institution->getData()->set(Plugin::LTI_URL, $lurl);

        $this->form->addField(new Field\Input(Plugin::LTI_KEY))->setLabel('LTI Key')->setTabGroup('LTI');
        $this->form->addField(new Field\Input(Plugin::LTI_SECRET))->setLabel('LTI Secret')->setTabGroup('LTI')->
            setAttr('placeholder', 'Auto Generate');
        
        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', $this->getConfig()->getSession()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

    }

    /**
     * @param \Tk\Form $form
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function doSubmit($form)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        // validate LTI consumer key
        $lid = (int)$this->data->get(Plugin::LTI_CURRENT_ID);
        
        if ($form->getFieldValue(Plugin::LTI_ENABLE)) {
            if (!$form->getFieldValue(Plugin::LTI_KEY)) {
                $form->addFieldError(Plugin::LTI_KEY, 'Please enter a LTI Key');
            }
            if (Plugin::ltiKeyExists($form->getFieldValue(Plugin::LTI_KEY), $lid)) {
                $form->addFieldError(Plugin::LTI_KEY, 'This LTI key already exists for another Institution.');
            }

            if (!$form->getFieldValue(Plugin::LTI_SECRET) && $lid > 0) {
                //$form->addFieldError(Plugin::LTI_SECRET, 'Please enter a LTI secret code');
                $form->setFieldValue(Plugin::LTI_SECRET, hash('md5', time()));
            }
        }

        if ($this->form->hasErrors()) {
            return;
        }

        // unimelb_00002
        // 1f72a0bac401a3e375e737185817463c

        $consumer = Plugin::getLtiConsumer($this->institution);
        if ($this->data->get(Plugin::LTI_ENABLE)) {
            if (!$consumer) {
                $consumer = new \IMSGlobal\LTI\ToolProvider\ToolConsumer(null, Plugin::getLtiDataConnector());
            }
            $consumer->setKey($this->data->get(Plugin::LTI_KEY));
            if ($this->data->get(Plugin::LTI_SECRET)) {
                $consumer->secret = $this->data->get(Plugin::LTI_SECRET);
            }
            $consumer->enabled = true;
            $consumer->name = $this->institution->name;
            $consumer->save();

            $this->data->set(Plugin::LTI_CURRENT_KEY, $consumer->getKey());
            $this->data->set(Plugin::LTI_CURRENT_ID, $consumer->getRecordId());
            $this->data->set(Plugin::LTI_SECRET, $consumer->secret);
            $url = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html');
            if ($this->institution->domain)
                $url = \Tk\Uri::create('http://'.$this->institution->domain.'/lti/launch.html');
            $this->data->set(Plugin::LTI_URL, $url->setScheme('https')->toString());

        } else {
            if ($consumer) {
                $consumer->enabled = false;
                $consumer->save();
            }
        }

        $this->data->save();
        
        \Tk\Alert::addSuccess('LTI settings saved.');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            $this->getConfig()->getSession()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->redirect();
    }

    /**
     * show()
     *
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        
        // Render the form
        $template->insertTemplate($this->form->getId(), $this->form->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div var="content">

    <div class="panel panel-default">
      <div class="panel-heading"><i class="fa fa-cogs fa-fw"></i> Actions</div>
      <div class="panel-body " var="action-panel">
        <a href="javascript: window.history.back();" class="btn btn-default"><i class="fa fa-arrow-left"></i> <span>Back</span></a>
      </div>
    </div>
  
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cog"></i>
        LTI Settings
      </div>
      <div class="panel-body">
        <div var="formEdit"></div>
        <hr/>
        <p>Includes support for LTI 1.1 and the unofficial extensions to LTI 1.0, as well as the registration process and services of LTI 2.0.</p>
      </div>
    </div>
  
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}