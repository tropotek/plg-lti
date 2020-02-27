<?php
namespace Lti\Controller\Platform;

use Bs\Controller\AdminEditIface;
use Dom\Template;
use Tk\Request;

/**
 * TODO: Add Route to routes.php:
 *      $routes->add('-lti-platform-edit', Route::create('/staff//lti/platformEdit.html', 'Lti\Controller\Platform\Edit::doDefault'));
 *
 * @author Mick Mifsud
 * @created 2020-02-27
 * @link http://tropotek.com.au/
 * @license Copyright 2020 Tropotek
 */
class Edit extends AdminEditIface
{

    /**
     * @var \Lti\Db\Platform
     */
    protected $platform = null;


    /**
     * Iface constructor.
     */
    public function __construct()
    {
        $this->setPageTitle('Platform Edit');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {
        $this->platform = new \Lti\Db\Platform();
        $this->platform->setInstitutionId($this->getConfig()->getInstitutionId());
        if ($request->get('platformId')) {
            $this->platform = \Lti\Db\PlatformMap::create()->find($request->get('platformId'));
        }

        $this->setForm(\Lti\Form\Platform::create()->setModel($this->platform));
        $this->initForm($request);
        $this->getForm()->execute();
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        // Render the form
        $template->appendTemplate('panel', $this->getForm()->show());

        return $template;
    }

    /**
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-title="Platform Edit" data-panel-icon="fa fa-book" var="panel"></div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

}