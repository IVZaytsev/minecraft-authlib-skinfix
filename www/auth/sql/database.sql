DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
	`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
	`username` varchar(16) NOT NULL DEFAULT '',
	`password` varchar(32) NOT NULL DEFAULT '',
	`uuid` char(32) NOT NULL DEFAULT '',
	`accessToken` char(32) NOT NULL DEFAULT '',
	`serverID` varchar(42) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Добавляем тестовые данные в `players`
-- TaoGunner:password
-- Notch:apple
--
-- INSERT INTO `players` (`username`, `password`, `uuid`) VALUES('TaoGunner', '5f4dcc3b5aa765d61d8327deb882cf99', '313a7d63a4326905cbace067a7c84a71');
-- INSERT INTO `players` (`username`, `password`, `uuid`) VALUES('Notch', '1f3870be274f6c49b3e31a0c6728957f', 'a80ec078ca3f08d5bf0f3016f8c062a3');