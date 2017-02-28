<?php
namespace Lti;

use Tk\EventDispatcher\EventDispatcher;


/**
 * Class Plugin
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \App\Plugin\Iface
{

    /**
     * @var string
     */
    public static $LTI_DB_PREFIX = '_';

    /**
     * @var \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector_pdo
     */
    static $dataConnector = null;



    /**
     * A helper method to get the Plugin instance globally
     *
     * @return \App\Plugin\Iface
     */
    static function getInstance()
    {
        return \Tk\Config::getInstance()->getPluginFactory()->getPlugin('ems-lti');
    }

    /**
     * getRequest
     * @return \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector_pdo
     */
    public static function getLtiDataConnector()
    {
        if (!self::$dataConnector) {
            self::$dataConnector = \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector::getDataConnector(self::$LTI_DB_PREFIX, \App\Factory::getDb(), 'pdo');
        }
        return self::$dataConnector;
    }


    // ---- \Tk\Plugin\Iface Interface Methods ----
    
    
    /**
     * Init the plugin
     *
     * This is called when the session first registers the plugin to the queue
     * So it is the first called method after the constructor.....
     *
     */
    function doInit()
    {
        include dirname(__FILE__) . '/config.php';

        $this->getPluginFactory()->registerInstitutionPlugin($this);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = \Tk\Config::getInstance()->getEventDispatcher();
        //$dispatcher->addSubscriber(new \Ldap\Listener\ExampleHandler());

    }

    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     */
    function doActivate()
    {
        // Init Plugin Settings
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->set('plugin.title', 'EMS III LTI Plugin');
//        $data->set('plugin.email', 'null@unimelb.edu.au');
//        $data->save();

        $config = \Tk\Config::getInstance();
        $db = \App\Factory::getDb();
        $migrate = new \Tk\Util\SqlMigrate($db);
        $migrate->setTempPath($config->getTempPath());
        $migrate->migrate(dirname(__FILE__) . '/sql');

    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     *
     */
    function doDeactivate()
    {
        // Delete any setting in the DB
//        $data = \Tk\Db\Data::create($this->getName());
//        $data->clear();
//        $data->save();
        $config = \Tk\Config::getInstance();
        $db = \App\Factory::getDb();

        $sql = sprintf("SHOW TABLES LIKE '%slti2\_%' ", $db->escapeString(self::$LTI_DB_PREFIX));
        $result = $db->query($sql);

        foreach ($result as $row) {
            vd($result);
        }

    }

    /**
     * @return \Tk\Uri
     */
//    public function getSettingsUrl()
//    {
//        return \Tk\Uri::create('/lti/adminSettings.html');
//    }

    /**
     * Get the course settings URL, if null then there is none
     *
     * @return string|\Tk\Uri|null
     */
    public function getInstitutionSettingsUrl()
    {
        return \Tk\Uri::create('/lti/institutionSettings.html');
    }

}