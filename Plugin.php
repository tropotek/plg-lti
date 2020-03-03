<?php
namespace Lti;


use Tk\EventDispatcher\EventDispatcher;
use Uni\Db\Institution;
use Uni\Db\InstitutionIface;
use \IMSGlobal\LTI;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Plugin extends \Tk\Plugin\Iface
{

    const ZONE_INSTITUTION          = 'institution';
    const ZONE_COURSE               = 'course';
    const ZONE_SUBJECT              = 'subject';

    // Plugin/Tool LTI Settings
    const LTI_TOOL_KEY_PUBLIC       = 'lti.cert.pub';
    const LTI_TOOL_KEY_PRIVATE      = 'lti.cert.prv';

    // Institution LTI Settings
    const LTI_ENABLE                = 'lti.enable';
    const LTI_LMS_PLATFORMID        = 'lti.lms.platform_id';        // This will usually look something like 'http://example.com'
    const LTI_LMS_CLIENTID          = 'lti.lms.client_id';          // This is the id received in the 'aud' during a launch
    const LTI_LMS_AUTHLOGINURL      = 'lti.lms.auth_login_url';     // The platform's OIDC login endpoint
    const LTI_LMS_AUTHTOKENURL      = 'lti.lms.auth_token_url';     // The platform's service authorization endpoint
    const LTI_LMS_KEYSETURL         = 'lti.lms.key_set_url';        // The platform's JWKS endpoint
    const LTI_LMS_DEPLOYMENTID      = 'lti.lms.deployment_id';      // The deployment_id passed by the platform during launch
    //const LTI_LMS_PRIVATEKEYFILE    = 'lti.lms.private_key_file';   // Relative path to the tool's private key

    const LTI_LAUNCH = 'lti_launch';
    //const LTI_SUBJECT_ID = 'custom_subjectid';


    /**
     * @var string
     */
    public static $LTI_DB_PREFIX = '_';

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
     * @param \Uni\Db\InstitutionIface $institution
     * @return \Tk\Db\Data
     * @throws \Exception
     */
    public function getInstitutionData($institution = null)
    {
        if (!$institution) $institution = \Uni\Config::getInstance()->getInstitution();
        $this->getConfig()->set('institution', $institution);
        return self::$institutionData = \Tk\Db\Data::create(self::getInstance()->getName() . '.institution', $institution->getId());
    }

    /**
     * Return true if the plugin is enabled for this institution
     *
     * @param \Uni\Db\InstitutionIface $institution
     * @return bool
     */
    public static function isEnabled($institution = null)
    {
        if (!$institution) $institution = \Uni\Config::getInstance()->getInstitution();
        $plugin = self::getInstance();
        try {
            $data = $plugin->getInstitutionData($institution);
            if ($data && $data->has(self::LTI_ENABLE)) {
                return true;
            }
        } catch (\Exception $e) { \Tk\Log::error($e->__toString()); }
        return false;
    }

    /**
     * @param string|\Tk\Uri $url
     * @param InstitutionIface $institution
     * @return \Tk\Uri
     */
    public static function createUrl($url, $institution = null)
    {
        if (!$institution) $institution = \Uni\Config::getInstance()->getInstitution();
        if ($url instanceof \Tk\Uri) {
            $url = $url->getRelativePath();
        }
        $url = \Tk\Uri::create('/lti/'.$institution->getHash().$url);
        if ($institution->getDomain())
            $url = \Tk\Uri::create('/lti'.$url)->setHost($institution->getDomain());

        $url->setScheme('https')->toString();
        return $url;
    }

    /**
     * @return array
     */
    public static function getJwks()
    {
        $data = self::getInstance()->getData();
        $kid = hash('sha256', $data->get(Plugin::LTI_TOOL_KEY_PUBLIC));
        $jwks = LTI\JWKS_Endpoint::new([
            $kid => $data->get(Plugin::LTI_TOOL_KEY_PRIVATE)
        ]);
        return $jwks->get_public_jwks();
    }

    /**
     * Generate an RSA public private key pair
     * returns array(
     *    'public'  => '...',
     *    'private' => '...'
     * )
     * @return array
     */
    public function generateKeys()
    {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => \OPENSSL_KEYTYPE_RSA,
        );
        // Create the private and public key
        $res = openssl_pkey_new($config);
        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);
        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];
        return array(Plugin::LTI_TOOL_KEY_PUBLIC => $pubKey, Plugin::LTI_TOOL_KEY_PRIVATE => $privKey);
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

        /** @var EventDispatcher $dispatcher */
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

        if ($this->getData()->has(Plugin::LTI_TOOL_KEY_PRIVATE) && $this->getData()->has(Plugin::LTI_TOOL_KEY_PUBLIC)) {
            return;
        }
        $keys = $this->generateKeys();
        $this->getData()->replace($keys);
        $this->getData()->save();

        if (!$db->hasTable('_lti_platform')) {
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
//        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Db\Data::$DB_TABLE), $db->quoteParameter('fkey'),
//            $db->quote($this->getName().'%'));
//        $db->query($sql);

        // Remove migration track
//        $sql = sprintf('DELETE FROM %s WHERE %s LIKE %s', $db->quoteParameter(\Tk\Util\SqlMigrate::$DB_TABLE), $db->quoteParameter('path'),
//            $db->quote('/plugin/' . $this->getName().'/%'));
//        $db->query($sql);

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

    /**
     * @return \Tk\Uri|null
     */
    public function getSettingsUrl()
    {
        return \Bs\Uri::createHomeUrl('/ltiSettings.html');
    }

}