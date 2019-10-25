ALTER TABLE `images` DROP `extensions`
ALTER TABLE `versions` ADD `extensions` varchar(4096) NOT NULL;