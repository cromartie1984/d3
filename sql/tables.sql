delimiter $$

CREATE TABLE `d3_profiles` (
  `battle_net_id` varchar(200) NOT NULL,
  `profile_json` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_updated` datetime NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`battle_net_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Diablo 3 character profiles imported from battle.net.'$$

delimiter $$

CREATE TABLE `battlenet_api_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `battle_net_id` varchar(45) NOT NULL,
  `url` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `date_number` varchar(45) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Daily log of request made to Battle.net web API.'$$

delimiter $$

CREATE TABLE `d3_items` (
  `hash` varchar(255) NOT NULL,
  `id` varchar(255) NOT NULL,
  `name` varchar(45) NOT NULL,
  `item_type` varchar(45) NOT NULL,
  `json` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_updated` datetime NOT NULL,
  `date_added` datetime NOT NULL,
  `d3_itemscol` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `SORTING` (`name`,`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Diablo 3 items'$$
