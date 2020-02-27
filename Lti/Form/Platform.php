<?php
namespace Lti\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new Platform::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 *
 * @author Mick Mifsud
 * @created 2020-02-27
 * @link http://tropotek.com.au/
 * @license Copyright 2020 Tropotek
 */
class Platform extends \Bs\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $this->appendField(new Field\Input('name'))->setLabel('Platform ID')
            ->setNotes('This will usually look something like \'http://example.com\'');
        $this->appendField(new Field\Input('clientId'))->setLabel('Client ID')
            ->setNotes('This is the id received in the \'aud\' during a launch');
        $this->appendField(new Field\Input('authLoginUrl'))
            ->setNotes('The platform\'s OIDC login endpoint');
        $this->appendField(new Field\Input('authTokenUrl'))
            ->setNotes('The platform\'s service authorization endpoint');
        $this->appendField(new Field\Input('keySetUrl'))
            ->setNotes('The platform\'s JWKS endpoint');
        $this->appendField(new Field\Input('deploymentId'))->setLabel('Deployment ID')
            ->setNotes('The deployment_id passed by the platform during launch');
        $this->appendField(new Field\Checkbox('active'))
            ->setNotes('Enable/Disable this LMS platform.');

        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));

    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load(\Lti\Db\PlatformMap::create()->unmapForm($this->getPlatform()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        \Lti\Db\PlatformMap::create()->mapForm($form->getValues(), $this->getPlatform());

        // Do Custom Validations
        $form->addFieldErrors($this->getPlatform()->validate());
        if ($form->hasErrors()) {
            return;
        }

        $isNew = (bool)$this->getPlatform()->getId();
        $this->getPlatform()->save();

        // Do Custom data saving

        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('platformId', $this->getPlatform()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Lti\Db\Platform
     */
    public function getPlatform()
    {
        return $this->getModel();
    }

    /**
     * @param \Lti\Db\Platform $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        return $this->setModel($platform);
    }

}