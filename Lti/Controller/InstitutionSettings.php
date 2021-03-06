<?php
namespace Lti\Controller;

use Lti\Plugin;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class InstitutionSettings extends \Uni\Controller\AdminEditIface
{

    /**
     * @var \Uni\Db\Institution
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
        $this->setPageTitle('LTI Plugin - Institution Settings');

    }

    /**
     * @param Request $request
     * @return void
     * @throws Form\Exception
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->institution = $this->getConfig()->getInstitutionMapper()->find($request->get('zoneId'));
        $this->data = Plugin::getInstitutionData($this->institution);

        $this->setForm($this->getConfig()->createForm('formEdit'));
        $this->getForm()->setRenderer($this->getConfig()->createFormRenderer($this->getForm()));

        $this->getForm()->appendField(new Field\Checkbox(Plugin::LTI_ENABLE))->addCss('tk-input-toggle')->setLabel('Enable LTI')
            ->setTabGroup('LTI')->setCheckboxLabel('Enable the LTI launch URL for LMS systems.');

        $lurl = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html');
        if ($this->institution->domain)
            $lurl = \Tk\Uri::create('/lti/launch.html')->setHost($this->institution->domain);
        $lurl->setScheme('https')->toString();
        $this->getForm()->appendField(new Field\Html(Plugin::LTI_URL, $lurl))->setLabel('Launch Url');
        $this->institution->getData()->set(Plugin::LTI_URL, $lurl);

        $this->getForm()->appendField(new Field\Input(Plugin::LTI_KEY))->setLabel('LTI Key');
        $this->getForm()->appendField(new Field\Input(Plugin::LTI_SECRET))->setLabel('LTI Secret')
            ->setAttr('placeholder', 'Auto Generate');
        
        $this->getForm()->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

        $this->getForm()->load($this->data->toArray());
        $this->getForm()->execute();

    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
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

        if ($form->hasErrors()) {
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
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create());
        }
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
        $template->prependTemplate('panel', $this->getForm()->getRenderer()->show());

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
<div class="tk-panel" data-panel-title="LTI Settings" data-panel-icon="fa fa-cog" var="panel">
  <hr/>
  <p>Includes support for LTI 1.1 and the unofficial extensions to LTI 1.0, as well as the registration process and services of LTI 2.0.</p>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}