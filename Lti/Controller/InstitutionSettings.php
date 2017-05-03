<?php
namespace Lti\Controller;


use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use \App\Controller\Iface;

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
        parent::__construct();
        $this->setPageTitle('LDAP Plugin - Institution Settings');

        /** @var \Lti\Plugin $plugin */
        $plugin = \Lti\Plugin::getInstance();
        $this->institution = $this->getUser()->getInstitution();
        $this->data = \Lti\Plugin::getInstitutionData();

    }

    /**
     * doDefault
     *
     * @param Request $request
     * @return \App\Page\Iface
     */
    public function doDefault(Request $request)
    {
        $this->form = \App\Factory::createForm('formEdit');
        $this->form->setParam('renderer', \App\Factory::createFormRenderer($this->form));

        $this->form->addField(new Field\Checkbox(\Lti\Plugin::LTI_ENABLE))->setLabel('Enable LTI')->setTabGroup('LTI')->setNotes('Enable the LTI launch URL for LMS systems.');
        $lurl = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html')->toString();
        if ($this->institution->domain)
            $lurl = \Tk\Uri::create('/lti/launch.html')->setHost($this->institution->domain)->toString();
        $this->form->addField(new Field\Html(\Lti\Plugin::LTI_URL, $lurl))->setLabel('Launch Url')->setTabGroup('LTI');
        $this->institution->getData()->set(\Lti\Plugin::LTI_URL, $lurl);
        $this->form->addField(new Field\Input(\Lti\Plugin::LTI_KEY))->setLabel('LTI Key')->setTabGroup('LTI');
        $this->form->addField(new Field\Input(\Lti\Plugin::LTI_SECRET))->setLabel('LTI Secret')->setTabGroup('LTI')->setAttr('placeholder', 'Auto Generate');
        
        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', \App\Factory::getCrumbs()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

        return $this->show();
    }

    /**
     * doSubmit()
     *
     * @param Form $form
     */
    public function doSubmit($form)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        // validate LTI consumer key
        $lid = (int)$this->data->get(\Lti\Plugin::LTI_CURRENT_ID);
        
        if ($form->getFieldValue(\Lti\Plugin::LTI_ENABLE)) {
            if (!$form->getFieldValue(\Lti\Plugin::LTI_KEY)) {
                $form->addFieldError(\Lti\Plugin::LTI_KEY, 'Please enter a LTI Key');
            }
            if (!$form->getFieldValue(\Lti\Plugin::LTI_SECRET) && $lid > 0) {
                $form->addFieldError(\Lti\Plugin::LTI_SECRET, 'Please enter a LTI secret code');
            }
            if (\Lti\Plugin::ltiKeyExists($form->getFieldValue(\Lti\Plugin::LTI_KEY), $lid)) {
                $form->addFieldError(\Lti\Plugin::LTI_KEY, 'This LTI key already exists for another Institution.');
            }
        }

        if ($this->form->hasErrors()) {
            return;
        }

        // unimelb_00002
        // 1f72a0bac401a3e375e737185817463c

        $consumer = \Lti\Plugin::getLtiConsumer();
        if ($this->data->get(\Lti\Plugin::LTI_ENABLE)) {
            if (!$consumer) {
                $consumer = new \IMSGlobal\LTI\ToolProvider\ToolConsumer(null, \Lti\Plugin::getLtiDataConnector());
            }
            $consumer->setKey($this->data->get(\Lti\Plugin::LTI_KEY));
            if ($this->data->get(\Lti\Plugin::LTI_SECRET)) {
                $consumer->secret = $this->data->get(\Lti\Plugin::LTI_SECRET);
            }
            $consumer->enabled = true;
            $consumer->name = $this->institution->name;
            $consumer->save();

            $this->data->set(\Lti\Plugin::LTI_CURRENT_KEY, $consumer->getKey());
            $this->data->set(\Lti\Plugin::LTI_CURRENT_ID, $consumer->getRecordId());
            $this->data->set(\Lti\Plugin::LTI_SECRET, $consumer->secret);
            $url = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html')->toString();
            if ($this->institution->domain)
                $url = \Tk\Uri::create('http://'.$this->institution->domain.'/lti/launch.html')->toString();
            $this->data->set(\Lti\Plugin::LTI_URL, $url);

        } else {
            if ($consumer) {
                $consumer->enabled = false;
                $consumer->save();
            }
        }

        $this->data->save();
        
        \Tk\Alert::addSuccess('LTI settings saved.');
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
        }
        \Tk\Uri::create()->redirect();
    }

    /**
     * show()
     *
     * @return \App\Page\Iface
     */
    public function show()
    {
        $template = $this->getTemplate();
        
        // Render the form
        $template->insertTemplate($this->form->getId(), $this->form->getParam('renderer')->show()->getTemplate());

        $formId = $this->form->getId();

        $js = <<<JS
jQuery(function($) {

  function toggleFields(checkbox) {
    var name = checkbox.get(0).name;
    var parent = checkbox.closest('.tab-pane, .tk-form-fields');
    var list = parent.find('input, textarea, select').not('input[name="'+name+'"]');
    
    if (!list.length) return;
    if (checkbox.prop('checked')) {
      list.removeAttr('disabled', 'disabled').removeClass('disabled');
    } else {
      list.attr('disabled', 'disabled').addClass('disabled');
    }
  }
  
  $('#$formId').find('input[name$=".enable"]').not('input[type="hidden"]').change(function(e) {
    toggleFields($(this));
  }).each(function (i) {
    toggleFields($(this));
  });
   
});
JS;
        $template->appendJs($js);


        return $this->getPage()->setPageContent($template);
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div class="row" var="content">

  <div class="col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cogs fa-fw"></i> Actions
      </div>
      <div class="panel-body ">
        <div class="row">
          <div class="col-lg-12">
            <a href="javascript: window.history.back();" class="btn btn-default"><i class="fa fa-arrow-left"></i> <span>Back</span></a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cog"></i>
        LTI Settings
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <div var="formEdit"></div>
      
            <hr/>
            <p>Includes support for LTI 1.1 and the unofficial extensions to LTI 1.0, as well as the registration process and services of LTI 2.0.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}