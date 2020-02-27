-- ---------------------------------
-- Install LTI SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
-- ---------------------------------

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS _lti2_consumer;
DROP TABLE IF EXISTS _lti2_tool_proxy;
DROP TABLE IF EXISTS _lti2_nonce;
DROP TABLE IF EXISTS _lti2_context;
DROP TABLE IF EXISTS _lti2_resource_link;
DROP TABLE IF EXISTS _lti2_user_result;
DROP TABLE IF EXISTS _lti2_share_key;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE IF NOT EXISTS `_lti_platform`
(
    `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `institution_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `name` VARCHAR(128) NOT NULL DEFAULT '',                -- This will usually look something like 'http://example.com' (aka platformId)
    `client_id` VARCHAR(255) NOT NULL DEFAULT '',           -- This is the id received in the 'aud' during a launch
    `auth_login_url` VARCHAR(255) NOT NULL DEFAULT '',      -- The platform's OIDC login endpoint
    `auth_token_url` VARCHAR(255) NOT NULL DEFAULT '',      -- The platform's service authorization endpoint
    `key_set_url` VARCHAR(255) NOT NULL DEFAULT '',         -- The platform's JWKS endpoint
    `deployment_id` VARCHAR(255) NOT NULL DEFAULT '',       -- The deployment_id passed by the platform during launch
    `active` BOOL NOT NULL DEFAULT 1,
    `modified` DATETIME NOT NULL,
    `created` DATETIME NOT NULL,
    KEY `institution_id` (`institution_id`),
    KEY `name` (`name`),
    UNIQUE KEY `institution_id_platform_id` (`institution_id`, name)
) ENGINE = InnoDB;






