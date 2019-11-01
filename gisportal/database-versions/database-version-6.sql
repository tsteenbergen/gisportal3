CREATE TABLE `instellingen` (`id` int NOT NULL AUTO_INCREMENT, `label` varchar(64) NOT NULL, `variable` varchar(32) NOT NULL, `instelling` varchar(64) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
INSERT INTO `instellingen` (`label`, `variable`) VALUES ('Naam persistent storage','persistent_storage');
