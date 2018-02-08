
use kamailio;

-- Create Tables if not exists

-- http_origin

CREATE TABLE IF NOT EXISTS http_origins (
  id int(11) NOT NULL AUTO_INCREMENT,
  http_origin varchar(100) NOT NULL,
  active tinyint(4) NOT NULL DEFAULT '1',
  modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY http_origin_2 (http_origin),
  KEY http_origin (http_origin)
);

-- user_agents
CREATE TABLE IF NOT EXISTS user_agents (
  id int(11) NOT NULL AUTO_INCREMENT,
  key_name varchar(64) NOT NULL,
  key_type int(11) NOT NULL,
  value_type int(11) NOT NULL,
  key_value varchar(64) NOT NULL,
  expires int(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY IX_user_agents_distinct (key_name),
  KEY IX_key_name (key_name)
);


-- Domain
CREATE TABLE IF NOT EXISTS domain (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  domain varchar(64) NOT NULL,
  did varchar(64) DEFAULT NULL,
  last_modified datetime NOT NULL DEFAULT '2000-01-01 00:00:01',
  PRIMARY KEY (id),
  UNIQUE KEY domain_idx (domain)
);

-- Did_routing
CREATE TABLE IF NOT EXISTS did_routing (
  id int(11) NOT NULL AUTO_INCREMENT,
  did varchar(50) NOT NULL,
  destination_domain varchar(50) NOT NULL,
  active tinyint(4) NOT NULL DEFAULT '1',
  modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY IX_did_routing_distinct (did),
  KEY IX_did_routing_did (did),
  KEY IX_did_routing_active (active)
);

-- Address
CREATE TABLE IF NOT EXISTS address (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  grp int(11) unsigned NOT NULL DEFAULT '1',
  ip_addr varchar(50) NOT NULL,
  mask int(11) NOT NULL DEFAULT '32',
  port smallint(5) unsigned NOT NULL DEFAULT '0',
  tag varchar(64) DEFAULT NULL,
  PRIMARY KEY (id)
);

-- Domain Attributes
CREATE TABLE IF NOT EXISTS domain_attrs (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  did varchar(64) NOT NULL,
  name varchar(32) NOT NULL,
  type int(10) unsigned NOT NULL,
  value varchar(255) NOT NULL,
  last_modified datetime NOT NULL DEFAULT '2000-01-01 00:00:01',
  PRIMARY KEY (id),
  UNIQUE KEY domain_attrs_idx (did,name,value)
);



-- Create Stored Procedures
-- HTTP ORIGINS Insertion
DROP PROCEDURE IF EXISTS insert_http_origin;
DELIMITER //
CREATE PROCEDURE insert_http_origin(IN http_origin_value varchar(100))
   BEGIN   
                insert into http_origins (http_origin) values (http_origin_value); 
                select id,http_origin,active,modified from http_origins
                where id=(SELECT LAST_INSERT_ID()) ;  
   END //
DELIMITER ;


-- HTTP ORIGINS Updation
 
DROP PROCEDURE IF EXISTS update_http_origin; 
DELIMITER //
CREATE PROCEDURE update_http_origin(IN id_value int(11),IN http_origin_value varchar(100),IN active_value tinyint(4))
BEGIN   
DECLARE http_origin_exists int;
SELECT count(1) INTO http_origin_exists 
from http_origins
WHERE http_origin=http_origin_value;
IF (http_origin_exists=0) THEN
    UPDATE http_origins
    SET  http_origin=coalesce(http_origin_value,http_origin),active=coalesce(active_value,active)
    where id=id_value;
    
    SELECT id,http_origin,active,modified 
    from http_origins
    WHERE id=id_value;
END IF;
END //
DELIMITER ; 
 
 
-- USER AGENTS Insertion                                           

DROP PROCEDURE IF EXISTS insert_user_agent;
DELIMITER //
CREATE PROCEDURE insert_user_agent(IN key_name_value varchar(64))
   BEGIN   
        insert into user_agents (key_name,key_type,value_type,key_value,expires) values (key_name_value,0,1,1,0); 
        SELECT id, key_name AS user_agent
        FROM user_agents
        where id=(SELECT LAST_INSERT_ID()) ;  
   END //
DELIMITER ;


-- USER AGENTS Updation
 
DROP PROCEDURE IF EXISTS update_user_agent; 
DELIMITER //
CREATE PROCEDURE update_user_agent(IN id_value int(11),IN key_name_value varchar(64))
BEGIN   
DECLARE user_agent_exists int;
SELECT count(1) INTO user_agent_exists 
from user_agents
WHERE key_name=key_name_value;
IF (user_agent_exists=0) THEN
    UPDATE user_agents
    SET  key_name=key_name_value
    where id=id_value;
    
    SELECT id, key_name AS user_agent
    from user_agents
    WHERE id=id_value;
END IF;
END //
DELIMITER ; 




-- Domains Insertion

DROP PROCEDURE IF EXISTS insert_domain;
DELIMITER //
CREATE PROCEDURE insert_domain(IN domain_value varchar(64),IN did_value varchar(64))
   BEGIN   
        insert into domain (domain,did) values (domain_value,did_value); 
        SELECT id,domain,did,last_modified
        FROM domain
        where id=(SELECT LAST_INSERT_ID()) ;  
   END //
DELIMITER ;

-- Domains Updation
 
DROP PROCEDURE IF EXISTS update_domain; 
DELIMITER //
CREATE PROCEDURE update_domain(IN id_value int(11),IN domain_value varchar(64),IN did_value varchar(64))
BEGIN   
DECLARE domain_exists int;
SELECT count(1) INTO domain_exists 
from domain
WHERE domain=domain_value;
IF (domain_exists=0) THEN
    
    UPDATE domain
    SET  domain=coalesce(domain_value,domain), did=coalesce(did_value,did)
    where id=id_value;
    
    SELECT id,domain,did,last_modified
    FROM domain
    WHERE id=id_value;
END IF;
END //
DELIMITER ; 


-- did_routing Insertion

DROP PROCEDURE IF EXISTS insert_did;
DELIMITER //
CREATE PROCEDURE insert_did(IN did_value varchar(40),IN destination_domain_value varchar(40))
   BEGIN   
        insert into did_routing (did,destination_domain) values (did_value,destination_domain_value); 
        SELECT id, did, destination_domain, active, modified 
        FROM did_routing
        where id=(SELECT LAST_INSERT_ID()) ;  
   END //
DELIMITER ;

-- did_routing Updation
 
DROP PROCEDURE IF EXISTS update_did; 
DELIMITER //
CREATE PROCEDURE update_did(IN id_value int(11),IN did_value varchar(40),IN destination_domain_value varchar(40),IN active_value tinyint(4))
BEGIN   
DECLARE did_exists int;
SELECT count(1) INTO did_exists
from did_routing
WHERE did=did_value;
IF (did_exists=0) THEN
    update did_routing 
    SET did=coalesce(did_value,did),destination_domain=coalesce(destination_domain_value,destination_domain), active=coalesce(active_value,active)
    where id=id_value;
    
    SELECT id, did, destination_domain, active, modified 
    FROM did_routing
    where id=id_value;  
END IF;
END //
DELIMITER ; 




-- Address Insertion

DROP PROCEDURE IF EXISTS insert_address;
DELIMITER //
CREATE PROCEDURE insert_address(IN ip_addr_value varchar(50))
   BEGIN   
        DECLARE address_exists int;
        SELECT count(1) INTO address_exists
        from address
        WHERE ip_addr=ip_addr_value;
        IF (address_exists=0) THEN
            insert into address (ip_addr) values (ip_addr_value); 
            
            SELECT id, grp, ip_addr, mask, port, tag 
            FROM address
            WHERE id=(SELECT LAST_INSERT_ID()) ;  
        End IF;
   END //
DELIMITER ;


-- Address Updation
 
DROP PROCEDURE IF EXISTS update_address; 
DELIMITER // 
CREATE PROCEDURE update_address(IN id_value int(10),IN grp_value int(11),IN ip_addr_value varchar(50),IN mask_value int(11),IN port_value smallint(5),IN tag_value varchar(64))
BEGIN   
DECLARE address_exists int;
SELECT count(1) INTO address_exists
from address
WHERE ip_addr=ip_addr_value;
IF (address_exists=0) THEN
    update address 
    SET ip_addr=coalesce(ip_addr_value,ip_addr),grp=coalesce(grp_value,grp), mask=coalesce(mask_value,mask), port=coalesce(port_value,port), tag=coalesce(tag_value,tag)
    where id=id_value;
    
    SELECT id, grp, ip_addr, mask, port, tag 
    FROM address
    where id=id_value;  
END IF;
END //
DELIMITER ; 


-- Domain Attributes Insertion

DROP PROCEDURE IF EXISTS insert_domain_attribute;
DELIMITER //
CREATE PROCEDURE insert_domain_attribute(IN did_value varchar(64),IN name_value varchar(32),IN type_value int(10),IN value_field_value varchar(255))
   BEGIN   
        insert into domain_attrs (did,name,type,value) values (did_value,name_value,type_value,value_field_value); 
        SELECT id, did,name, type, value, last_modified 
        FROM domain_attrs
        WHERE id=(SELECT LAST_INSERT_ID()) ;  
   END //
DELIMITER ;


-- Domain Attributes Updation 
 
DROP PROCEDURE IF EXISTS update_domain_attribute; 
DELIMITER // 
CREATE PROCEDURE update_domain_attribute(IN id_value int(10),IN did_value varchar(64),IN name_value varchar(32),IN type_value int(10),IN value_field_value varchar(255))
BEGIN   
DECLARE address_attribute_exists int;
SELECT count(1) INTO address_attribute_exists
from domain_attrs
WHERE did=did_value and name=name_value and value= value_field_value;
IF (address_attribute_exists=0) THEN
    update domain_attrs 
    SET did=coalesce(did_value,did),name=coalesce(name_value,name), type=coalesce(type_value,type), value=coalesce(value_field_value,value)
    where id=id_value;
    
    SELECT id, did,name, type, value, last_modified 
    FROM domain_attrs
    where id=id_value;  
END IF;
END //
DELIMITER ; 