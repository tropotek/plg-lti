<?php
namespace Lti\Table;

use Tk\Form\Field;
use Tk\Table\Cell;

/**
 * Example:
 * <code>
 *   $table = new Platform::create();
 *   $table->init();
 *   $list = ObjectMap::getObjectListing();
 *   $table->setList($list);
 *   $tableTemplate = $table->show();
 *   $template->appendTemplate($tableTemplate);
 * </code>
 *
 * @author Mick Mifsud
 * @created 2020-02-27
 * @link http://tropotek.com.au/
 * @license Copyright 2020 Tropotek
 */
class Platform extends \Bs\TableIface
{

    /**
     * @return $this
     * @throws \Exception
     */
    public function init()
    {

        $this->appendCell(new Cell\Checkbox('id'));
        $this->appendCell(new Cell\Text('name'))->setLabel('Platform ID')->setUrl($this->getEditUrl());
        //$this->appendCell(new Cell\Text('clientId'));
        //$this->appendCell(new Cell\Text('authLoginUrl'));
        //$this->appendCell(new Cell\Text('authTokenUrl'));
        //$this->appendCell(new Cell\Text('keySetUrl'));
        //$this->appendCell(new Cell\Text('deploymentId'));
        $this->appendCell(new Cell\Boolean('active'));
        $this->appendCell(new Cell\Date('modified'));
        $this->appendCell(new Cell\Date('created'));

        // Filters
        //$this->appendFilter(new Field\Input('keywords'))->setAttr('placeholder', 'Search');

        // Actions
        $this->appendAction(\Tk\Table\Action\Link::createLink('New Platform', \Bs\Uri::createHomeUrl('/lti/platformEdit.html'), 'fa fa-plus'));
        //$this->appendAction(\Tk\Table\Action\ColumnSelect::create()->setUnselected(array('modified', 'created')));
        $this->appendAction(\Tk\Table\Action\Delete::create());
        //$this->appendAction(\Tk\Table\Action\Csv::create());

        // load table
        //$this->setList($this->findList());

        return $this;
    }

    /**
     * @param array $filter
     * @param null|\Tk\Db\Tool $tool
     * @return \Tk\Db\Map\ArrayObject|\Lti\Db\Platform[]
     * @throws \Exception
     */
    public function findList($filter = array(), $tool = null)
    {
        if (!$tool) $tool = $this->getTool();
        $filter = array_merge($this->getFilterValues(), $filter);
        $list = \Lti\Db\PlatformMap::create()->findFiltered($filter, $tool);
        return $list;
    }

}