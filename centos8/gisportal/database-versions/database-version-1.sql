CREATE DATABASE IF NOT EXISTS gisportal;
USE gisportal;
CREATE TABLE `database_version` (`id` int NOT NULL AUTO_INCREMENT, `version` int NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `afdelingen` (`id` int NOT NULL AUTO_INCREMENT, `naam` varchar(32) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `audit_trail` (`id` int NOT NULL AUTO_INCREMENT, `persoon` int NOT NULL, `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `query` varchar(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `geopackages` (`id` int NOT NULL AUTO_INCREMENT, `naam` varchar(64) NOT NULL, `afdeling` int NOT NULL, `onderwerp` int NOT NULL, `soort` char(6) NOT NULL,  `brongeopackage` varchar(255) NOT NULL, `indatalink` varchar(255) NOT NULL, `datalink` varchar(255) NOT NULL, `wms` char(1) NOT NULL, `wfs` char(1) NOT NULL, `wcs` char(1) NOT NULL, `wmts` char(1) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `onderwerpen` (`id` int NOT NULL AUTO_INCREMENT, `naam` varchar(64) NOT NULL, `afkorting` varchar(32) NOT NULL, `afdeling` int NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
CREATE TABLE `personen` (`id` int NOT NULL AUTO_INCREMENT, `naam` varchar(32) NOT NULL, `afdeling` int NOT NULL, `email` varchar(32) NOT NULL, `ad_account` varchar(32) NOT NULL, `password` varchar(255) NOT NULL, `admin` char(1) NOT NULL, `afd_admin` char(1) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `database_version` (`version`) VALUES (1);
INSERT INTO `afdelingen` (`naam`) VALUES ('SSC-Campus/RDG');
INSERT INTO `onderwerpen` (`naam`, `afkorting`, `afdeling`) VALUES ('SSC-Campus/RDG-test', 'RDG-test', 1);
INSERT INTO `personen` (`naam`, `afdeling`, `email`, `ad_account`, `password`, `admin`, `afd_admin`) VALUES ('Gisbeheer', 1, 'gisbeheer', 'gisbeheer', '452124959fc0305005b8b57fab667c1b', 'J', 'N');