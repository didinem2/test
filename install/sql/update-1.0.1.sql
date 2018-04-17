--
-- Structure de la table 'glpi_plugin_requester_tickets'
--
--
DROP TABLE IF EXISTS `glpi_plugin_requester_tickets`;
CREATE TABLE `glpi_plugin_requester_tickets` (
  `id` int(11) NOT NULL auto_increment, -- id
  `tickets_id` int(11) NOT NULL,
  `requester_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `tickets_id` (`tickets_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;