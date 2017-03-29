<?php
namespace Lti;

use Tk\EventDispatcher\Dispatcher;


/**
 * Class Plugin
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \App\Plugin\Iface
{
    // Data labels
    const LTI_STUFF = 'inst.lti.setting';
    const LTI_ENABLE = 'inst.lti.enable';
    const LTI_KEY = 'inst.lti.key';
    const LTI_SECRET = 'inst.lti.secret';
    const LTI_URL = 'inst.lti.url';
    const LTI_CURRENT_KEY = 'inst.lti.currentKey';
    const LTI_CURRENT_ID = 'inst.lti.currentId';

    /**
     * @var string
     */
    public static $LTI_DB_PREFIX = '_';

    /**
     * @var \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector_pdo
     */
    public static $dataConnector = null;

    /**
     * @var \IMSGlobal\LTI\ToolProvider\ToolConsumer
     */
    public static $ltiConsumer= null;

    /**
     * @var \Tk\Db\Data
     */
    public static $institutionData = null;



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

    /**
     * @return \Tk\Db\Data
     */
    public static function getInstitutionData()
    {
        if (\Tk\Config::getInstance()->getUser() && !self::$institutionData) {
            $institution = \Tk\Config::getInstance()->getUser()->getInstitution();
            if ($institution)
                self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
        }
        return self::$institutionData;
    }

    /**
     *
     * @return \IMSGlobal\LTI\ToolProvider\ToolConsumer
     */
    public static function getLtiConsumer()
    {
        $data = self::getInstitutionData();
        $key = $data->get(self::LTI_CURRENT_KEY);
        if ($key === '') $key = null;
        if (!self::$ltiConsumer && $key) {
            self::$ltiConsumer = new \IMSGlobal\LTI\ToolProvider\ToolConsumer($key, self::getLtiDataConnector());
        }
        return self::$ltiConsumer;
    }


    /**
     * Check if the LTI key exists
     *
     * @param $consumer_key256
     * @return bool
     */
    public static function ltiKeyExists($consumer_key256, $ignoreId = 0)
    {
        $db = \App\Factory::getDb();
        $sql = sprintf('SELECT * FROM %s WHERE consumer_key256 = %s', $db->quoteParameter(self::$LTI_DB_PREFIX.'lti2_consumer'), $db->quote($consumer_key256));
        if ($ignoreId) {
            $sql .= sprintf(' AND consumer_pk != %s ', (int)$ignoreId);
        }
        return ($db->query($sql)->rowCount() > 0);
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @return bool
     */
    public static function isEnabled()
    {
        $db = \App\Factory::getDb();
        if(!$db->tableExists(self::$LTI_DB_PREFIX.'lti2_consumer')) {
            return false;
        }
        $data = self::getInstitutionData();
        if ($data && $data->has(self::LTI_ENABLE)) {
            return $data->get(self::LTI_ENABLE);
        }

        return false;
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
        $config = $this->getConfig();

        $this->getPluginFactory()->registerZonePlugin($this, \App\Plugin\Iface::ZONE_CLIENT);

        /** @var Dispatcher $dispatcher */
        $dispatcher = \Tk\Config::getInstance()->getEventDispatcher();
        /** @var \App\Db\Institution $institution */
        $institution = $config->getInstitution();
        if($institution && $this->isZonePluginEnabled(\App\Plugin\Iface::ZONE_CLIENT, $institution->getId())) {
            $dispatcher->addSubscriber(new \Lti\Listener\AuthHandler());
            $dispatcher->addSubscriber(new \Lti\Listener\MenuHandler());
        }

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
        $db = \App\Factory::getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('foreign_key'), $db->quote($this->getName().'%'));
        $db->query($sql);

        // Delete all LTI tables.
        $sql = sprintf("SHOW TABLES LIKE '%slti2\_%%' ", $db->escapeString(\Lti\Plugin::$LTI_DB_PREFIX));
        $result = $db->query($sql);
        foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $db->dropTable(current($row));
        }

        // Remove migration track
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Util\SqlMigrate::$DB_TABLE), $db->quoteParameter('path'), $db->quote('/plugin/' . $this->getName().'/%'));
        $db->query($sql);

    }

    /**
     * Get the course settings URL, if null then there is none
     *
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName)
    {
        switch ($zoneName) {
            case \App\Plugin\Iface::ZONE_CLIENT:
                return \Tk\Uri::create('/lti/institutionSettings.html');
        }
        return null;
    }

}