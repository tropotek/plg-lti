<?php
namespace Lti;

use Tk\Event\Dispatcher;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \Tk\Plugin\Iface
{

    const ZONE_INSTITUTION      = 'institution';
    const ZONE_SUBJECT_PROFILE  = 'profile';
    const ZONE_SUBJECT          = 'subject';

    // Data labels
    const LTI_STUFF         = 'lti.setting';
    const LTI_ENABLE        = 'lti.enable';
    const LTI_KEY           = 'lti.key';
    const LTI_SECRET        = 'lti.secret';
    const LTI_URL           = 'lti.url';
    const LTI_CURRENT_KEY   = 'lti.currentKey';
    const LTI_CURRENT_ID    = 'lti.currentId';

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
     * @return Plugin|\Tk\Plugin\Iface
     */
    public static function getInstance()
    {
        return \App\Config::getInstance()->getPluginFactory()->getPlugin('plg-lti');
    }

    /**
     * getRequest
     * @return \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector_pdo
     */
    public static function getLtiDataConnector()
    {
        if (!self::$dataConnector) {
            self::$dataConnector = \IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector::getDataConnector(self::$LTI_DB_PREFIX,
                \Uni\Config::getInstance()->getDb(), 'pdo');
        }
        return self::$dataConnector;
    }

    /**
     * @param \Uni\Db\InstitutionIface $institution
     * @return \Tk\Db\Data
     * @throws \Exception
     */
    public static function getInstitutionData($institution)
    {
        \Uni\Config::getInstance()->set('institution', $institution);
        return self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
    }

    /**
     * @param \Uni\Db\InstitutionIface $institution
     * @return \IMSGlobal\LTI\ToolProvider\ToolConsumer
     * @throws \Exception
     */
    public static function getLtiConsumer($institution)
    {
        $data = self::getInstitutionData($institution);
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
     * @param int $ignoreId
     * @return bool
     * @throws \Tk\Db\Exception
     */
    public static function ltiKeyExists($consumer_key256, $ignoreId = 0)
    {
        $db = \Uni\Config::getInstance()->getDb();
        $sql = sprintf('SELECT * FROM %s WHERE consumer_key256 = %s',
            $db->quoteParameter(self::$LTI_DB_PREFIX.'lti2_consumer'), $db->quote($consumer_key256));
        if ($ignoreId) {
            $sql .= sprintf(' AND consumer_pk != %s ', (int)$ignoreId);
        }
        return ($db->query($sql)->rowCount() > 0);
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @param \Uni\Db\InstitutionIface $institution
     * @return bool
     * @throws \Exception
     */
    public static function isEnabled($institution)
    {
        $db = \Uni\Config::getInstance()->getDb();
        if(!$db->hasTable(self::$LTI_DB_PREFIX.'lti2_consumer')) {
            return false;
        }
        $data = self::getInstitutionData($institution);
        if ($data && $data->has(self::LTI_ENABLE)) {
            return true;
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
        $this->getPluginFactory()->registerZonePlugin($this, self::ZONE_INSTITUTION);
        /** @var Dispatcher $dispatcher */
        $dispatcher = $config->getEventDispatcher();
        $dispatcher->addSubscriber(new \Lti\Listener\SetupHandler());

    }

    /**
     * Activate the plugin, essentially
     * installing any DB and settings required to run
     * Will only be called when activating the plugin in the
     * plugin control panel
     *
     * @throws \Exception
     */
    function doActivate()
    {
        // Init Plugin Settings
        $config = \Tk\Config::getInstance();
        $db = $this->getConfig()->getDb();

        if (!$db->hasTable('_lti2_consumer')) {
            $migrate = new \Tk\Util\SqlMigrate($db);
            $migrate->setTempPath($config->getTempPath());
            $migrate->migrate(dirname(__FILE__) . '/sql');
        }
    }

    /**
     * Deactivate the plugin removing any DB data and settings
     * Will only be called when deactivating the plugin in the
     * plugin control panel
     *
     * @throws \Exception
     */
    function doDeactivate()
    {
        $db = $this->getConfig()->getDb();

        // Clear the data table of all plugin data
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
            $db->quote($this->getName().'%'));
        $db->query($sql);

        // Delete all LTI tables.
        $sql = sprintf("SHOW TABLES LIKE '%slti2\_%%' ", $db->escapeString(Plugin::$LTI_DB_PREFIX));
        $result = $db->query($sql);
        foreach ($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $db->dropTable(current($row));
        }

        // Remove migration track
        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Util\SqlMigrate::$DB_TABLE), $db->quoteParameter('path'),
            $db->quote('/plugin/' . $this->getName().'/%'));
        $db->query($sql);

    }

    /**
     * Get the settings URL, if null then there is none
     *
     * @param string $zoneName
     * @param string $zoneId
     * @return string|\Tk\Uri|null
     */
    public function getZoneSettingsUrl($zoneName, $zoneId)
    {
        switch ($zoneName) {
            case self::ZONE_INSTITUTION:
                return \Bs\Uri::createHomeUrl('/ltiInstitutionSettings.html');
        }
        return null;
    }

}