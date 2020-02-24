<?php
namespace Lti;


use \IMSGlobal\LTI;
use Tk\Collection;
use Tk\ConfigTrait;


class Database implements LTI\Database {

    use ConfigTrait;

    /**
     * Lti session ID
     */
    const SID = '_lti';

    /**
     * @var string
     */
    private $ltiPath = '';

    /**
     * @var null|Collection
     */
    private $_ltiSession = null;

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->ltiPath = $this->getConfig()->getDataPath().'/lti';

        if (!is_dir($this->getLtiPath())) {
            mkdir($this->getLtiPath(), 0777 ,true);
            mkdir($this->getConfigsPath(), 0777 ,true);
        }

        $regConfigs = array_diff(scandir($this->getConfigsPath()), array('..', '.', '.DS_Store'));
        foreach ($regConfigs as $key => $reg) {
            $regArr = json_decode(file_get_contents($this->getConfigsPath() . '/' . $reg), true);
            $this->getLtiSession()->replace($regArr);
            //$_SESSION['iss'] = array_merge($_SESSION['iss'], json_decode(file_get_contents(__DIR__ . "/configs/$reg"), true));
        }
    }

    /**
     * @param string $iss
     * @return bool|LTI\LTI_Registration
     */
    public function find_registration_by_issuer($iss)
    {
        if (!$this->getLtiSession()->has($iss)) {
            return false;
        }
        $ses = $this->getLtiSession()->get($iss);
        return LTI\LTI_Registration::new()
            ->set_auth_login_url($ses['auth_login_url'])
            ->set_auth_token_url($ses['auth_token_url'])
            ->set_auth_server($ses['auth_server'])
            ->set_client_id($ses['client_id'])
            ->set_key_set_url($ses['key_set_url'])
            ->set_kid($ses['kid'])
            ->set_issuer($iss)
            ->set_tool_private_key($this->privateKey($iss));
    }

    /**
     * @param string $iss
     * @param string $deploymentId
     * @return bool|LTI\LTI_Deployment
     */
    public function find_deployment($iss, $deploymentId)
    {
        $ses = $this->getLtiSession()->get($iss);
        if (!in_array($deploymentId, $ses['deployment'])) {
            return false;
        }
        return LTI\LTI_Deployment::new()->set_deployment_id($deploymentId);
    }

    /**
     * @param string $iss
     * @return false|string
     * @todo Check the path on this one
     */
    private function privateKey($iss)
    {
        $ses = $this->getLtiSession()->get($iss);

        $path = $this->getLtiPath().$ses['private_key_file'];
vd('PrivateKey Path', __DIR__ . $ses['private_key_file'], $path);

        return file_get_contents(__DIR__ . $ses['private_key_file']);
    }

    /**
     * @return mixed|Collection
     */
    public function getLtiSession()
    {
        if (!$this->_ltiSession) {
            $session = $this->getSession();
            $this->_ltiSession = new Collection();
            if ($session->has(self::SID)) {
                $this->_ltiSession = $session->get(self::SID);
            }
            $session->set(self::SID, $this->_ltiSession);
        }
        return $this->_ltiSession;
    }

    /**
     * @return string
     */
    private function getPrivateKeyPath()
    {
        return $this->getLtiPath() . '/key';
    }

    /**
     * @return string
     */
    private function getConfigsPath()
    {
        return $this->getLtiPath() . '/configs';
    }

    /**
     * @return string
     */
    private function getLtiPath()
    {
        return $this->ltiPath;
    }

}