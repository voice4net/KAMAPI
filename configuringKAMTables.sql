USE kamailio;

-- Create Tables if not exists

-- http_origin

CREATE TABLE IF NOT EXISTS http_origins (
  id          INT(11)      NOT NULL AUTO_INCREMENT,
  http_origin VARCHAR(100) NOT NULL,
  active      TINYINT(4)   NOT NULL DEFAULT '1',
  modified    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY http_origin_2 (http_origin),
  KEY http_origin (http_origin)
);

-- user_agents
CREATE TABLE IF NOT EXISTS user_agents (
  id         INT(11)     NOT NULL AUTO_INCREMENT,
  key_name   VARCHAR(64) NOT NULL,
  key_type   INT(11)     NOT NULL,
  value_type INT(11)     NOT NULL,
  key_value  VARCHAR(64) NOT NULL,
  expires    INT(11)     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY IX_user_agents_distinct (key_name),
  KEY IX_key_name (key_name)
);

-- Domain
CREATE TABLE IF NOT EXISTS domain (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  domain        VARCHAR(64)      NOT NULL,
  did           VARCHAR(64)               DEFAULT NULL,
  last_modified DATETIME         NOT NULL DEFAULT '2000-01-01 00:00:01',
  PRIMARY KEY (id),
  UNIQUE KEY domain_idx (domain)
);

-- Did_routing
CREATE TABLE IF NOT EXISTS did_routing (
  id                 INT(11)     NOT NULL AUTO_INCREMENT,
  did                VARCHAR(50) NOT NULL,
  destination_domain VARCHAR(50) NOT NULL,
  active             TINYINT(4)  NOT NULL DEFAULT '1',
  modified           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY IX_did_routing_distinct (did),
  KEY IX_did_routing_did (did),
  KEY IX_did_routing_active (active)
);

-- Address
CREATE TABLE IF NOT EXISTS address (
  id      INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
  grp     INT(11) UNSIGNED     NOT NULL DEFAULT '1',
  ip_addr VARCHAR(50)          NOT NULL,
  mask    INT(11)              NOT NULL DEFAULT '32',
  port    SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
  tag     VARCHAR(64)                   DEFAULT NULL,
  PRIMARY KEY (id)
);

ALTER TABLE address ADD COLUMN description VARCHAR(100) NULL;

-- Domain Attributes
CREATE TABLE IF NOT EXISTS domain_attrs (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  did           VARCHAR(64)      NOT NULL,
  name          VARCHAR(32)      NOT NULL,
  type          INT(10) UNSIGNED NOT NULL,
  value         VARCHAR(255)     NOT NULL,
  last_modified DATETIME         NOT NULL DEFAULT '2000-01-01 00:00:01',
  PRIMARY KEY (id),
  UNIQUE KEY domain_attrs_idx (did, name, value)
);

-- Create Stored Procedures
-- HTTP ORIGINS Insertion
DROP PROCEDURE IF EXISTS insert_http_origin;
DELIMITER //
CREATE PROCEDURE insert_http_origin(IN http_origin_value VARCHAR(100))
  BEGIN
    INSERT INTO http_origins (http_origin) VALUES (http_origin_value);
    SELECT
      id,
      http_origin,
      active,
      modified
    FROM http_origins
    WHERE id = (SELECT LAST_INSERT_ID());
  END //
DELIMITER ;


-- HTTP ORIGINS Updation

DROP PROCEDURE IF EXISTS update_http_origin;
DELIMITER //
CREATE PROCEDURE update_http_origin(IN id_value INT(11), IN http_origin_value VARCHAR(100), IN active_value TINYINT(4))
  BEGIN
    DECLARE http_origin_exists INT;
    SELECT count(1)
    INTO http_origin_exists
    FROM http_origins
    WHERE http_origin = http_origin_value;
    IF (http_origin_exists = 0)
    THEN
      UPDATE http_origins
      SET http_origin = coalesce(http_origin_value, http_origin), active = coalesce(active_value, active)
      WHERE id = id_value;

      SELECT
        id,
        http_origin,
        active,
        modified
      FROM http_origins
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ;


-- USER AGENTS Insertion                                           

DROP PROCEDURE IF EXISTS insert_user_agent;
DELIMITER //
CREATE PROCEDURE insert_user_agent(IN key_name_value VARCHAR(64))
  BEGIN
    INSERT INTO user_agents (key_name, key_type, value_type, key_value, expires) VALUES (key_name_value, 0, 1, 1, 0);
    SELECT
      id,
      key_name AS user_agent
    FROM user_agents
    WHERE id = (SELECT LAST_INSERT_ID());
  END //
DELIMITER ;


-- USER AGENTS Updation

DROP PROCEDURE IF EXISTS update_user_agent;
DELIMITER //
CREATE PROCEDURE update_user_agent(IN id_value INT(11), IN key_name_value VARCHAR(64))
  BEGIN
    DECLARE user_agent_exists INT;
    SELECT count(1)
    INTO user_agent_exists
    FROM user_agents
    WHERE key_name = key_name_value;
    IF (user_agent_exists = 0)
    THEN
      UPDATE user_agents
      SET key_name = key_name_value
      WHERE id = id_value;

      SELECT
        id,
        key_name AS user_agent
      FROM user_agents
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ;




-- Domains Insertion

DROP PROCEDURE IF EXISTS insert_domain;
DELIMITER //
CREATE PROCEDURE insert_domain(IN domain_value VARCHAR(64), IN did_value VARCHAR(64))
  BEGIN
    INSERT INTO domain (domain, did) VALUES (domain_value, did_value);
    SELECT
      id,
      domain,
      did,
      last_modified
    FROM domain
    WHERE id = (SELECT LAST_INSERT_ID());
  END //
DELIMITER ;

-- Domains Updation

DROP PROCEDURE IF EXISTS update_domain;
DELIMITER //
CREATE PROCEDURE update_domain(IN id_value INT(11), IN domain_value VARCHAR(64), IN did_value VARCHAR(64))
  BEGIN
    DECLARE domain_exists INT;
    SELECT count(1)
    INTO domain_exists
    FROM domain
    WHERE domain = domain_value;
    IF (domain_exists = 0)
    THEN

      UPDATE domain
      SET domain = coalesce(domain_value, domain), did = coalesce(did_value, did)
      WHERE id = id_value;

      SELECT
        id,
        domain,
        did,
        last_modified
      FROM domain
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ;


-- did_routing Insertion

DROP PROCEDURE IF EXISTS insert_did;
DELIMITER //
CREATE PROCEDURE insert_did(IN did_value VARCHAR(40), IN destination_domain_value VARCHAR(40))
  BEGIN
    INSERT INTO did_routing (did, destination_domain) VALUES (did_value, destination_domain_value);
    SELECT
      id,
      did,
      destination_domain,
      active,
      modified
    FROM did_routing
    WHERE id = (SELECT LAST_INSERT_ID());
  END //
DELIMITER ;

-- did_routing Updation

DROP PROCEDURE IF EXISTS update_did;
DELIMITER //
CREATE PROCEDURE update_did(IN id_value     INT(11), IN did_value VARCHAR(40), IN destination_domain_value VARCHAR(40),
                            IN active_value TINYINT(4))
  BEGIN
    DECLARE did_exists INT;
    SELECT count(1)
    INTO did_exists
    FROM did_routing
    WHERE did = did_value;
    IF (did_exists = 0)
    THEN
      UPDATE did_routing
      SET did  = coalesce(did_value, did), destination_domain = coalesce(destination_domain_value, destination_domain),
        active = coalesce(active_value, active)
      WHERE id = id_value;

      SELECT
        id,
        did,
        destination_domain,
        active,
        modified
      FROM did_routing
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ;




-- Address Insertion

DROP PROCEDURE IF EXISTS insert_address;
DELIMITER //
CREATE PROCEDURE insert_address(IN ip_addr_value VARCHAR(50), IN description_value VARCHAR(100))
  BEGIN
    DECLARE address_exists INT;
    SELECT count(1)
    INTO address_exists
    FROM address
    WHERE ip_addr = ip_addr_value;
    IF (address_exists = 0)
    THEN
      INSERT INTO address (ip_addr,description) VALUES (ip_addr_value,description_value);

      SELECT
        id,
        grp,
        ip_addr,
        mask,
        port,
        tag,
        description
      FROM address
      WHERE id = (SELECT LAST_INSERT_ID());
    END IF;
  END //
DELIMITER ;



-- Address Updation

DROP PROCEDURE IF EXISTS update_address;
DELIMITER //
CREATE PROCEDURE update_address(IN id_value   INT(10), IN grp_value INT(11), IN ip_addr_value VARCHAR(50),
                                IN mask_value INT(11), IN port_value SMALLINT(5), IN tag_value VARCHAR(64),
                                IN description_value VARCHAR(100))
  BEGIN
    DECLARE address_exists INT;
    SELECT count(1)
    INTO address_exists
    FROM address
    WHERE ip_addr = ip_addr_value;
    IF (address_exists = 0)
    THEN
      UPDATE address
      SET ip_addr = coalesce(ip_addr_value, ip_addr), grp = coalesce(grp_value, grp), mask = coalesce(mask_value, mask),
        port = coalesce(port_value, port), tag = coalesce(tag_value, tag), description=coalesce(description_value, description)
      WHERE id = id_value;

      SELECT
        id,
        grp,
        ip_addr,
        mask,
        port,
        tag,
        description
      FROM address
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ;


-- Domain Attributes Insertion

DROP PROCEDURE IF EXISTS insert_domain_attribute;
DELIMITER //
CREATE PROCEDURE insert_domain_attribute(IN did_value         VARCHAR(64), IN name_value VARCHAR(32),
                                         IN type_value        INT(10), IN value_field_value VARCHAR(255))
  BEGIN
    INSERT INTO domain_attrs (did, name, type, value) VALUES (did_value, name_value, type_value, value_field_value);
    SELECT
      id,
      did,
      name,
      type,
      value,
      last_modified
    FROM domain_attrs
    WHERE id = (SELECT LAST_INSERT_ID());
  END //
DELIMITER ;


-- Domain Attributes Updation 

DROP PROCEDURE IF EXISTS update_domain_attribute;
DELIMITER //
CREATE PROCEDURE update_domain_attribute(IN id_value   INT(10), IN did_value VARCHAR(64), IN name_value VARCHAR(32),
                                         IN type_value INT(10), IN value_field_value VARCHAR(255))
  BEGIN
    DECLARE address_attribute_exists INT;
    SELECT count(1)
    INTO address_attribute_exists
    FROM domain_attrs
    WHERE did = did_value AND name = name_value AND value = value_field_value;
    IF (address_attribute_exists = 0)
    THEN
      UPDATE domain_attrs
      SET did = coalesce(did_value, did), name = coalesce(name_value, name), type = coalesce(type_value, type),
        value = coalesce(value_field_value, value)
      WHERE id = id_value;

      SELECT
        id,
        did,
        name,
        type,
        value,
        last_modified
      FROM domain_attrs
      WHERE id = id_value;
    END IF;
  END //
DELIMITER ; 