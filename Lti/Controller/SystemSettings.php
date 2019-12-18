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
class SystemSettings extends \Uni\Controller\AdminEditIface
{

    /**
     * @var \Tk\Db\Data|null
     */
    protected $data = null;


    /**
     * SystemSettings constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('LTI Plugin Settings');

        /** @var \Lti\Plugin $plugin */
        $plugin = Plugin::getInstance();
        $this->data = \Tk\Db\Data::create($plugin->getName());
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->setForm($this->getConfig()->createForm('formEdit'));
        $this->getForm()->setRenderer($this->getConfig()->createFormRenderer($this->getForm()));

        $this->getForm()->appendField(new Field\Input('plugin.title'))->setLabel('Site Title')->setRequired(true);
        $this->getForm()->appendField(new Field\Input('plugin.email'))->setLabel('Site Email')->setRequired(true);
        $this->getForm()->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->getForm()->appendField(new Event\LinkButton('cancel', $this->getBackUrl()));

        $this->getForm()->load($this->data->toArray());
        $this->getForm()->execute();
    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->data->replace($values);
        
        if (empty($values['plugin.title']) || strlen($values['plugin.title']) < 3) {
            $form->addFieldError('plugin.title', 'Please enter your name');
        }
        if (empty($values['plugin.email']) || !filter_var($values['plugin.email'], \FILTER_VALIDATE_EMAIL)) {
            $form->addFieldError('plugin.email', 'Please enter a valid email address');
        }
        
        if ($form->hasErrors()) {
            return;
        }
        
        $this->data->save();
        
        \Tk\Alert::addSuccess('Site settings saved.');
        $event->setRedirect(\Tk\Uri::create());
        if ($form->getTriggeredEvent()->getName() == 'update') {
            $event->setRedirect($this->getConfig()->getBackUrl());
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
        $template->appendTemplate('panel', $this->getForm()->getRenderer()->show());

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
<div class="tk-panel" data-panel-title="LTI Settings" data-panel-icon="fa fa-cog" var="panel"></div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}