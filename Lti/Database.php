<?php
namespace Lti;


use \IMSGlobal\LTI;
use Tk\Collection;
use Tk\ConfigTrait;
use Uni\Db\Institution;


class Database implements LTI\Database {

    use ConfigTrait;

    /**
     * Lti session ID
     */
    const SID = '_lti';

    /**
     * @var null|Collection
     */
    private $_ltiSession = null;

    /**
     * @var Institution
     */
    private $institution = null;


    /**
     * @param Institution $institution
     * @throws \Exception
     */
    public function __construct($institution)
    {
        $this->institution = $institution;
        $this->getLtiSession()->replace($this->makeConfig($institution));
    }

    /**
     * @param Institution $institution
     * @return array
     * @throws \Exception
     */
    protected function makeConfig($institution)
    {
        $json = '';
        $list = \Lti\Db\PlatformMap::create()->findFiltered(array(
            'institutionId' => $this->getInstitution()->getId()
        ));
        foreach ($list as $platform) {
            $json .= sprintf('
    "%s" : {
        "client_id" : "%s",
        "auth_login_url" : "%s",
        "auth_token_url" : "%s",
        "key_set_url" : "%s",
        "private_key_file" : "",
        "institutionId" : "%s",
        "deployment" : [
            "%s"
        ]
    },',
            $platform->getName(),
            $platform->getClientId(),
            $platform->getAuthLoginUrl(),
            $platform->getAuthTokenUrl(),
            $platform->getKeySetUrl(),
            $this->getInstitution()->getId(),
            $platform->getDeploymentId()
            );
        }
        $arr = json_decode(sprintf('{
%s
}',
            rtrim($json, ',')
        ), true);
        return $arr;
    }

    /**
     * @param string $iss Platform ID
     * @return bool|LTI\LTI_Registration
     */
    public function find_registration_by_issuer($iss)
    {
        if (!$this->getLtiSession()->has($iss)) {
            \Tk\Log::warning('Registration not found for: ' . $iss);
            return false;
        }
        $ses = new Collection($this->getLtiSession()->get($iss));
        $data = Plugin::getInstance()->getData();
        return LTI\LTI_Registration::new()
            ->set_auth_login_url($ses->get('auth_login_url'))
            ->set_auth_token_url($ses->get('auth_token_url'))
            ->set_auth_server($ses->get('auth_server'))
            ->set_client_id($ses->get('client_id'))
            ->set_key_set_url($ses->get('key_set_url'))
            ->set_kid($ses->get('kid'))
            ->set_issuer($iss)
            ->set_tool_private_key($data->get(Plugin::LTI_TOOL_KEY_PRIVATE));
    }

    /**
     * @param string $iss Platform ID
     * @param string $deploymentId
     * @return bool|LTI\LTI_Deployment
     */
    public function find_deployment($iss, $deploymentId)
    {
        $ses = $this->getLtiSession()->get($iss);
        if (!in_array($deploymentId, $ses['deployment'])) {
            \Tk\Log::warning('deployment not found for: ' . $iss . ' => ' . $deploymentId);
            return false;
        }
        return LTI\LTI_Deployment::new()->set_deployment_id($deploymentId);
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
     * @return Institution
     */
    public function getInstitution(): Institution
    {
        return $this->institution;
    }

}