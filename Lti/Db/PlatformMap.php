<?php
namespace Lti\Db;

use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;
use Bs\Db\Mapper;
use Tk\Db\Filter;

/**
 * @author Mick Mifsud
 * @created 2020-02-27
 * @link http://tropotek.com.au/
 * @license Copyright 2020 Tropotek
 */
class PlatformMap extends Mapper
{

    /**
     * @param \Tk\Db\Pdo|null $db
     * @throws \Exception
     */
    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->setMarkDeleted();
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) { 
            $this->setTable('_lti_platform');

            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addPropertyMap(new Db\Integer('id'), 'key');
            $this->dbMap->addPropertyMap(new Db\Integer('institutionId', 'institution_id'));
            $this->dbMap->addPropertyMap(new Db\Text('name'));
            $this->dbMap->addPropertyMap(new Db\Text('clientId', 'client_id'));
            $this->dbMap->addPropertyMap(new Db\Text('authLoginUrl', 'auth_login_url'));
            $this->dbMap->addPropertyMap(new Db\Text('authTokenUrl', 'auth_token_url'));
            $this->dbMap->addPropertyMap(new Db\Text('keySetUrl', 'key_set_url'));
            $this->dbMap->addPropertyMap(new Db\Text('deploymentId', 'deployment_id'));
            $this->dbMap->addPropertyMap(new Db\Boolean('active'));

        }
        return $this->dbMap;
    }

    /**
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addPropertyMap(new Form\Integer('id'), 'key');
            $this->formMap->addPropertyMap(new Form\Integer('institutionId'));
            $this->formMap->addPropertyMap(new Form\Text('name'));
            $this->formMap->addPropertyMap(new Form\Text('clientId'));
            $this->formMap->addPropertyMap(new Form\Text('authLoginUrl'));
            $this->formMap->addPropertyMap(new Form\Text('authTokenUrl'));
            $this->formMap->addPropertyMap(new Form\Text('keySetUrl'));
            $this->formMap->addPropertyMap(new Form\Text('deploymentId'));
            $this->formMap->addPropertyMap(new Form\Boolean('active'));

        }
        return $this->formMap;
    }

    /**
     * @param array|Filter $filter
     * @param Tool $tool
     * @return ArrayObject|Platform[]
     * @throws \Exception
     */
    public function findFiltered($filter, $tool = null)
    {
        return $this->selectFromFilter($this->makeQuery(\Tk\Db\Filter::create($filter)), $tool);
    }

    /**
     * @param Filter $filter
     * @return Filter
     */
    public function makeQuery(Filter $filter)
    {
        $filter->appendFrom('%s a', $this->quoteParameter($this->getTable()));

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) $filter->appendWhere('(%s) AND ', substr($w, 0, -3));
        }

        if (!empty($filter['id'])) {
            $w = $this->makeMultiQuery($filter['id'], 'a.id');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        if (!empty($filter['institutionId'])) {
            $filter->appendWhere('a.institution_id = %s AND ', (int)$filter['institutionId']);
        }
        if (!empty($filter['name'])) {
            $filter->appendWhere('a.name = %s AND ', $this->quote($filter['name']));
        }
        if (!empty($filter['clientId'])) {
            $filter->appendWhere('a.client_id = %s AND ', $this->quote($filter['clientId']));
        }
        if (!empty($filter['authLoginUrl'])) {
            $filter->appendWhere('a.auth_login_url = %s AND ', $this->quote($filter['authLoginUrl']));
        }
        if (!empty($filter['authTokenUrl'])) {
            $filter->appendWhere('a.auth_token_url = %s AND ', $this->quote($filter['authTokenUrl']));
        }
        if (!empty($filter['keySetUrl'])) {
            $filter->appendWhere('a.key_set_url = %s AND ', $this->quote($filter['keySetUrl']));
        }
        if (!empty($filter['deploymentId'])) {
            $filter->appendWhere('a.deployment_id = %s AND ', $this->quote($filter['deploymentId']));
        }
        if (!empty($filter['active'])) {
            $filter->appendWhere('a.active = %s AND ', (int)$filter['active']);
        }

        if (!empty($filter['exclude'])) {
            $w = $this->makeMultiQuery($filter['exclude'], 'a.id', 'AND', '!=');
            if ($w) $filter->appendWhere('(%s) AND ', $w);
        }

        return $filter;
    }

}