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
     * @var \Lti\Plugin
     */
    protected $plugin = null;

    /**
     * @var \Tk\Db\Data
     */
    protected $data = null;

    /**
     * @var null|\Lti\Table\Platform
     */
    protected $table = null;


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('LTI v1.3 Plugin - Institution Settings');
        $this->plugin = Plugin::getInstance();
    }

    /**
     * @return Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->institution = $this->getConfig()->getInstitutionMapper()->find($request->get('zoneId'));
        $this->data = $this->getPlugin()->getInstitutionData($this->institution);

        $this->setForm($this->getConfig()->createForm('formEdit'));
        $this->getForm()->setRenderer($this->getConfig()->createFormRenderer($this->getForm()));

        $this->getForm()->appendField(new Field\Checkbox(Plugin::LTI_ENABLE))->addCss('tk-input-toggle')->setLabel('Enable LTI')
            ->setCheckboxLabel('Enable the LTI launch URL for LMS systems.');

        $this->getForm()->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

        $this->getForm()->load($this->data->toArray());
        $this->getForm()->execute();


        $this->setTable(\Lti\Table\Platform::create());
        $this->getTable()->setEditUrl(\Uni\Uri::createHomeUrl('/lti/platformEdit.html'));
        $this->getTable()->init();

        $filter = array(
            'institutionId' => $this->institution->getId()
        );
        $this->getTable()->setList($this->getTable()->findList($filter));


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
        if ($form->hasErrors()) {
            return;
        }
        $this->data->save();
        
        \Tk\Alert::addSuccess('LTI Settings Saved.');
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
        // Render the form
        if ($this->getTable())
            $template->prependTemplate('table', $this->getTable()->show());

        $this->showRow('Tool URL', \Tk\Uri::create($this->getConfig()->getSiteUrl())->setScheme('https'));
        $this->showRow('LTI Version', 'LTI 1.3');
        if ($this->getPlugin()->getData()->get(Plugin::LTI_TOOL_KEY_PUBLIC))
            $this->showRow('Public Key', sprintf('<pre>%s</pre>', $this->getPlugin()->getData()->get(Plugin::LTI_TOOL_KEY_PUBLIC)), true);

        $this->showRow('Initiate login URL', Plugin::createUrl('/login', $this->institution));
        $this->showRow('Redirection URI(s)', Plugin::createUrl('/launch.html', $this->institution));
        $this->showRow('Public JWKS', \Tk\Uri::create('/lti/jwks.json')->setScheme('https'));
        $this->showRow('Canvas Config JSON', Plugin::createUrl('/canvas.json', $this->institution));

        return $template;
    }

    private $rowVar = 'row';
    protected function showRow($label, $data, $isHtml = false)
    {
        if (!$data) return;
        $row = $this->getTemplate()->getRepeat($this->rowVar);
        $row->insertText('label', $label.':');
        if ($isHtml) {
            $row->insertHtml('data', $data);
        } else {
            $row->insertText('data', $data);
        }
        $row->appendRepeat();
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div class="row">
  <div class="col-8 col-md-8">
    <div class="tk-panel" data-panel-title="LTI v1.3 Settings" data-panel-icon="fa fa-cog" var="panel"></div>
    <div class="tk-panel" data-panel-title="LTI Registered Learning Platforms" data-panel-icon="fa fa-institution" var="table"></div>
  </div>
  <div class="col-4 col-md-4">
    <div class="tk-panel" data-panel-title="LMS Settings" data-panel-icon="fa fa-cog" var="side-panel">
      
        <dl var="dl">
          <div repeat="row" var="row">
            <dt var="label"></dt>
            <dd var="data"></dd>
          </div>
        </dl>
    </div>
  </div>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }

    /**
     * @return \Lti\Table\Platform|null
     */
    public function getTable(): ?\Lti\Table\Platform
    {
        return $this->table;
    }

    /**
     * @param \Lti\Table\Platform|null $table
     * @return InstitutionSettings
     */
    public function setTable(?\Lti\Table\Platform $table): InstitutionSettings
    {
        $this->table = $table;
        return $this;
    }
}