<?php
namespace Lti\Controller;

use Lti\Plugin;
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
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function doDefault(Request $request)
    {

        /** @var \Lti\Plugin $plugin */
        $plugin = Plugin::getInstance();
        $this->data = $plugin->getData();

        if (!$this->data->has(Plugin::LTI_TOOL_KEY_PRIVATE) || !$this->data->has(Plugin::LTI_TOOL_KEY_PUBLIC)) {
            $keys = $plugin->generateKeys();
            $this->data->replace($keys);
            $this->data->save();

            \Tk\Alert::addSuccess('Successfully created LTI Certificate keys.');
            \Bs\Uri::create()->redirect();
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

        if ($this->data->has(Plugin::LTI_TOOL_KEY_PUBLIC)) {
            $html = sprintf('<p><h3>Public Key:</h3><textarea class="form-control" rows="18">%s</textarea></p>', $this->data->get(Plugin::LTI_TOOL_KEY_PUBLIC));
            $template->appendHtml('panel', $html);
        }

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