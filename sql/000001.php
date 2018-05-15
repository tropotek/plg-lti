<?php

$config = \Uni\Config::getInstance();
$db = $config->getDb();

$dataTable = \Tk\Db\Data::$DB_TABLE;

$sql = <<<SQL
-- Rename all data fields
UPDATE $dataTable SET `key` = 'lti.enable', `value` = 'lti.enable' WHERE `key` = 'inst.lti.enable';
UPDATE $dataTable SET `key` = 'lti.url'        WHERE `key` = 'inst.lti.url';
UPDATE $dataTable SET `key` = 'lti.key'        WHERE `key` = 'inst.lti.key';
UPDATE $dataTable SET `key` = 'lti.secret'     WHERE `key` = 'inst.lti.secret';
UPDATE $dataTable SET `key` = 'lti.currentKey' WHERE `key` = 'inst.lti.currentKey';
UPDATE $dataTable SET `key` = 'lti.currentId'  WHERE `key` = 'inst.lti.currentId';
SQL;

$db->query($sql);









