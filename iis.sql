-- Adminer 3.1.0 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- Cannot load from mysql.proc. The table is probably corrupted
-- Cannot load from mysql.proc. The table is probably corrupted
DROP TABLE IF EXISTS `project_variants`;
CREATE TABLE `project_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project` int(11) NOT NULL,
  `title` text COLLATE utf8_czech_ci NOT NULL,
  `description` text COLLATE utf8_czech_ci NOT NULL,
  `teams_allowed` tinyint(1) NOT NULL COMMENT 'is this a team variant?',
  `max_teams` smallint(3) unsigned DEFAULT NULL,
  `max_members` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project` (`project`),
  CONSTRAINT `project_variants_ibfk_1` FOREIGN KEY (`project`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `project_variants` (`id`, `project`, `title`, `description`, `teams_allowed`, `max_teams`, `max_members`) VALUES
(14,	10,	'',	'',	0,	NULL,	NULL),
(16,	11,	'Varianta 1',	'Popis první varianty',	0,	NULL,	2),
(17,	11,	'Varianta 2',	'Popis druhé varianty',	0,	NULL,	1),
(18,	11,	'Varianta 3',	'Popis třetí varianty',	0,	NULL,	1),
(24,	8,	'',	'',	1,	NULL,	2),
(25,	12,	'Varianta 1',	'',	1,	1,	2),
(26,	12,	'Varianta 2',	'',	1,	NULL,	3);

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` int(10) unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci,
  `signup_from` datetime DEFAULT NULL,
  `signup_until` datetime DEFAULT NULL,
  `submit_from` datetime DEFAULT NULL,
  `submit_until` datetime DEFAULT NULL,
  `submit_files` tinyint(1) NOT NULL DEFAULT '0',
  `min_points` smallint(4) DEFAULT NULL,
  `max_points` smallint(4) NOT NULL,
  `variants` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`subject`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `projects` (`id`, `subject`, `title`, `text`, `signup_from`, `signup_until`, `submit_from`, `submit_until`, `submit_files`, `min_points`, `max_points`, `variants`) VALUES
(8,	6,	'Týmový projekt bez variant',	'Aby se student mohl přihlásit na tento projekt, musí být členem týmu. Za celý tým se pak přihlašuje pouze vedoucí.',	NULL,	NULL,	NULL,	NULL,	1,	5,	14,	0),
(10,	6,	'Projekt bez týmů a variant',	'Tento projekt nemá žádné varianty a přihlašování i odevzdávání má časově neomezené. Uplatní se pouze jediné omezení, které nedovolí studentovi odhlásit se, jestliže už byl projekt ohodnocen.',	NULL,	NULL,	NULL,	NULL,	0,	0,	60,	0),
(11,	6,	'Projekt s několika variantami bez týmů',	'Tento projekt má několik netýmových variant s různými kapacitami.',	NULL,	NULL,	NULL,	NULL,	0,	0,	2,	1),
(12,	6,	'Týmový projekt s variantami',	'Tento projekt má několik týmových variant s různými omezeními na počty týmů a jejich velikosti.',	NULL,	NULL,	NULL,	NULL,	0,	0,	10,	1);

DROP TABLE IF EXISTS `students_in_subjects`;
CREATE TABLE `students_in_subjects` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  `id` int(10) unsigned NOT NULL,
  UNIQUE KEY `login` (`login`,`id`),
  KEY `id` (`id`),
  CONSTRAINT `students_in_subjects_ibfk_1` FOREIGN KEY (`login`) REFERENCES `users` (`login`),
  CONSTRAINT `students_in_subjects_ibfk_2` FOREIGN KEY (`id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `students_in_subjects` (`login`, `id`) VALUES
('xnovak01',	5),
('xsabat01',	5),
('xstrat06',	5),
('xtycka01',	5),
('xzelap01',	5),
('xnovak01',	6),
('xsabat01',	6),
('xstrat06',	6),
('xtycka01',	6),
('xnovak01',	7),
('xsabat01',	7),
('xstrat06',	7),
('xnovak01',	8),
('xsabat01',	8),
('xstrat06',	8),
('xnovak01',	9),
('xsabat01',	9),
('xstrat06',	9),
('xnovak01',	10),
('xsabat01',	10),
('xstrat06',	10),
('xtycka01',	10),
('xzelap01',	10),
('xsabat01',	11),
('xstrat06',	12),
('xstrat06',	14),
('xsabat01',	16),
('xstrat06',	19),
('xsabat01',	21),
('xstrat06',	21),
('xsabat01',	27),
('xsabat01',	29),
('xstrat06',	30),
('xsabat01',	31),
('xstrat06',	31),
('xsabat01',	32),
('xstrat06',	32);

DROP TABLE IF EXISTS `students_in_teams`;
CREATE TABLE `students_in_teams` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  `team` bigint(20) NOT NULL,
  `rate` float DEFAULT NULL COMMENT 'amount of points the student gained for completing the project',
  UNIQUE KEY `login` (`login`,`team`),
  KEY `team` (`team`),
  CONSTRAINT `students_in_teams_ibfk_1` FOREIGN KEY (`login`) REFERENCES `users` (`login`),
  CONSTRAINT `students_in_teams_ibfk_2` FOREIGN KEY (`team`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `students_in_teams` (`login`, `team`, `rate`) VALUES
('xnovak01',	42,	3.33),
('xsabat01',	42,	14),
('xsabat01',	45,	5.5);

DROP TABLE IF EXISTS `students_in_variants`;
CREATE TABLE `students_in_variants` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  `variant` bigint(20) unsigned NOT NULL,
  `rated` datetime DEFAULT NULL,
  `rated_by` varchar(8) COLLATE utf8_czech_ci DEFAULT NULL,
  `rate` float unsigned DEFAULT NULL,
  UNIQUE KEY `login` (`login`,`variant`),
  KEY `variant` (`variant`),
  KEY `rated_by` (`rated_by`),
  CONSTRAINT `students_in_variants_ibfk_1` FOREIGN KEY (`login`) REFERENCES `users` (`login`),
  CONSTRAINT `students_in_variants_ibfk_2` FOREIGN KEY (`variant`) REFERENCES `project_variants` (`id`),
  CONSTRAINT `students_in_variants_ibfk_3` FOREIGN KEY (`rated_by`) REFERENCES `users` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `students_in_variants` (`login`, `variant`, `rated`, `rated_by`, `rate`) VALUES
('xnovak01',	16,	NULL,	NULL,	NULL),
('xsabat01',	14,	'2010-12-05 17:30:28',	'ivyucu01',	50),
('xsabat01',	18,	'2010-12-05 17:12:58',	'ivyucu01',	0.25),
('xstrat06',	14,	'2010-12-05 21:55:54',	'ivyucu01',	60),
('xtycka01',	16,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) COLLATE utf8_czech_ci NOT NULL COMMENT 'zkratka predmetu',
  `name` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'nazev predmetu',
  `year` year(4) NOT NULL COMMENT 'akademicky rok',
  `semester` enum('S','W') COLLATE utf8_czech_ci NOT NULL COMMENT 'summer/winter leto/zima',
  `credits` int(3) unsigned NOT NULL COMMENT 'pocet kreditu',
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `year` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `subjects` (`id`, `code`, `name`, `year`, `semester`, `credits`) VALUES
(5,	'IMP',	'Mikroprocesorové a vestavěné systémy',	'2010',	'W',	6),
(6,	'IIS',	'Informační systémy',	'2010',	'W',	4),
(7,	'IMS',	'Modelování a simulace',	'2010',	'W',	5),
(8,	'IPZ',	'Periferní zařízení',	'2010',	'W',	4),
(9,	'ISA',	'Síťové aplikace a správa sítí',	'2010',	'W',	5),
(10,	'IBP',	'Bakalářská práce',	'2010',	'S',	6),
(11,	'IAS',	'Asemblery',	'2008',	'W',	6),
(12,	'IDA',	'Diskrétní matematika',	'2008',	'W',	8),
(13,	'ITO',	'Teorie obvodů',	'2008',	'W',	6),
(14,	'IUS',	'Úvod do softwarového inženýrství',	'2008',	'W',	5),
(15,	'IZP',	'Základy programování',	'2008',	'W',	7),
(16,	'IFY',	'Fyzika',	'2008',	'S',	5),
(17,	'IMA',	'Matematická analýza',	'2008',	'S',	5),
(18,	'INC',	'Návrh číslicových systémů',	'2008',	'S',	5),
(19,	'IOS',	'Operační systémy',	'2008',	'S',	5),
(20,	'IPR',	'Prvky počítačů',	'2008',	'S',	6),
(21,	'IAL',	'Algoritmy',	'2009',	'W',	5),
(22,	'IFJ',	'Formální jazyky a překladače',	'2009',	'W',	5),
(23,	'INM',	'Numerická matematika a pravděpodobnost',	'2009',	'W',	5),
(24,	'INP',	'Návrh počítačových systémů',	'2009',	'W',	5),
(25,	'ISS',	'Signály a systémy',	'2009',	'W',	6),
(26,	'IDS',	'Databázové systémy',	'2009',	'S',	5),
(27,	'IPK',	'Počítačové komunikace a sítě',	'2009',	'S',	5),
(28,	'IPP',	'Principy programovacích jazyků a OOP',	'2009',	'S',	5),
(29,	'IZG',	'Základy počítačové grafiky',	'2009',	'S',	6),
(30,	'IZU',	'Základy umělé inteligence',	'2009',	'S',	4),
(31,	'ISP',	'Semestrální projekt',	'2010',	'W',	2),
(32,	'ITU',	'Tvorba uživatelských rozhraní',	'2010',	'W',	4);

DROP TABLE IF EXISTS `teachers_in_subjects`;
CREATE TABLE `teachers_in_subjects` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  `id` int(10) unsigned NOT NULL,
  UNIQUE KEY `login` (`login`,`id`),
  KEY `id` (`id`),
  CONSTRAINT `teachers_in_subjects_ibfk_1` FOREIGN KEY (`login`) REFERENCES `users` (`login`),
  CONSTRAINT `teachers_in_subjects_ibfk_2` FOREIGN KEY (`id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `teachers_in_subjects` (`login`, `id`) VALUES
('igaran01',	5),
('ivyucu01',	5),
('iasist01',	6),
('igaran01',	6),
('ivyucu01',	6),
('igaran01',	7),
('ivyucu01',	7),
('igaran01',	8),
('ivyucu01',	8),
('igaran01',	9),
('ivyucu01',	9),
('igaran01',	10),
('ivyucu01',	10),
('igaran01',	31),
('ivyucu01',	31),
('igaran01',	32),
('ivyucu01',	32);

DROP TABLE IF EXISTS `team_requests`;
CREATE TABLE `team_requests` (
  `team` bigint(20) NOT NULL,
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  UNIQUE KEY `team` (`team`,`login`),
  KEY `login` (`login`),
  CONSTRAINT `team_requests_ibfk_1` FOREIGN KEY (`team`) REFERENCES `teams` (`id`),
  CONSTRAINT `team_requests_ibfk_2` FOREIGN KEY (`login`) REFERENCES `users` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `team_requests` (`team`, `login`) VALUES
(42,	'xtycka01');

DROP TABLE IF EXISTS `teams`;
CREATE TABLE `teams` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `project` int(11) NOT NULL,
  `variant` bigint(20) unsigned DEFAULT NULL COMMENT 'project variant',
  `leader` varchar(8) COLLATE utf8_czech_ci NOT NULL COMMENT 'leader''s login',
  `rated` datetime DEFAULT NULL COMMENT 'time the project has been rated by a teacher',
  `rated_by` varchar(8) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leader` (`leader`),
  KEY `variant` (`variant`),
  KEY `project` (`project`),
  KEY `rated_by` (`rated_by`),
  CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`leader`) REFERENCES `users` (`login`),
  CONSTRAINT `teams_ibfk_3` FOREIGN KEY (`rated_by`) REFERENCES `users` (`login`),
  CONSTRAINT `teams_ibfk_4` FOREIGN KEY (`variant`) REFERENCES `project_variants` (`id`),
  CONSTRAINT `teams_ibfk_5` FOREIGN KEY (`project`) REFERENCES `projects` (`id`),
  CONSTRAINT `teams_ibfk_6` FOREIGN KEY (`rated_by`) REFERENCES `users` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `teams` (`id`, `project`, `variant`, `leader`, `rated`, `rated_by`) VALUES
(42,	8,	24,	'xsabat01',	'2010-12-05 02:31:15',	'iasist01'),
(45,	12,	25,	'xsabat01',	'2010-12-05 17:10:39',	'ivyucu01');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL COMMENT 'xnovak00',
  `password` varchar(40) COLLATE utf8_czech_ci NOT NULL COMMENT 'sha1 hash',
  `name_prefix` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'titul pred jmenem',
  `name_suffix` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'titul za jmenem',
  `name` varchar(20) COLLATE utf8_czech_ci NOT NULL COMMENT 'krestni jmeno',
  `surname` varchar(20) COLLATE utf8_czech_ci NOT NULL COMMENT 'prijmeni',
  PRIMARY KEY (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='uzivatele systemu, studenti i vyucujici';

INSERT INTO `users` (`login`, `password`, `name_prefix`, `name_suffix`, `name`, `surname`) VALUES
('admin',	'ecda1ddb64ac9e6f442a196910fa3dc0b8eb1929',	'Ing.',	'Ph.D',	'Radek',	'Administrátor'),
('iasist01',	'a5a89deb6c5eb85dbb1e20671627d569f756b946',	'Ing.',	NULL,	'Michal',	'Asistent'),
('igaran01',	'2d49c9a2c9538f5a2a4194468b756661e3326bfb',	'Doc. Ing.',	'Ph.D',	'Jan',	'Garant'),
('ivyucu01',	'55c4b247bc46b91a6b74609426da86a50da4430b',	'Ing.',	'Ph.D',	'Petr',	'Učitel'),
('xnovak01',	'e281b10d8938749b1176f93826118fedbd6745cf',	NULL,	NULL,	'Ondřej',	'Novák'),
('xsabat01',	'be7fdbba892265622687424066e8813597de2db3',	NULL,	NULL,	'David',	'Šabata'),
('xstrat06',	'3d6e3d337ccdc5acaf223083c59689c0f231e581',	NULL,	NULL,	'Lenka',	'Stratilová'),
('xtycka01',	'b4c0fa22b1b5fcbac6fc147ae9f1f7da724d299a',	NULL,	NULL,	'Roman',	'Tyčka'),
('xzelap01',	'03fb16157ecca93c5018f529e1dad60a4fd51141',	NULL,	NULL,	'Petr',	'Žela');

DROP TABLE IF EXISTS `users_acl`;
CREATE TABLE `users_acl` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` tinyint(4) unsigned NOT NULL,
  `privilege_id` tinyint(4) unsigned NOT NULL,
  `resource_id` tinyint(4) unsigned NOT NULL,
  `allowed` enum('Y','N') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `privilege_id` (`privilege_id`),
  KEY `resource_id` (`resource_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_acl_ibfk_2` FOREIGN KEY (`privilege_id`) REFERENCES `users_privileges` (`id`),
  CONSTRAINT `users_acl_ibfk_3` FOREIGN KEY (`resource_id`) REFERENCES `users_resources` (`id`),
  CONSTRAINT `users_acl_ibfk_5` FOREIGN KEY (`role_id`) REFERENCES `users_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `users_acl` (`id`, `role_id`, `privilege_id`, `resource_id`, `allowed`) VALUES
(1,	2,	3,	1,	'Y'),
(2,	4,	2,	1,	'Y'),
(3,	6,	1,	1,	'Y'),
(5,	2,	3,	4,	'Y'),
(6,	3,	3,	3,	'Y'),
(7,	8,	3,	2,	'Y');

DROP TABLE IF EXISTS `users_in_roles`;
CREATE TABLE `users_in_roles` (
  `login` varchar(8) COLLATE utf8_czech_ci NOT NULL,
  `role_id` tinyint(4) unsigned NOT NULL,
  KEY `role_id` (`role_id`),
  KEY `login` (`login`),
  CONSTRAINT `users_in_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `users_roles` (`id`),
  CONSTRAINT `users_in_roles_ibfk_4` FOREIGN KEY (`login`) REFERENCES `users` (`login`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `users_in_roles` (`login`, `role_id`) VALUES
('xsabat01',	2),
('ivyucu01',	6),
('iasist01',	2),
('iasist01',	4),
('igaran01',	7),
('xzelap01',	2),
('xtycka01',	2),
('admin',	8),
('xnovak01',	2),
('xstrat06',	2);

DROP TABLE IF EXISTS `users_privileges`;
CREATE TABLE `users_privileges` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='akce, napr. prihlasit, vytvorit,.. kombinuji se s resources';

INSERT INTO `users_privileges` (`id`, `name`) VALUES
(1,	'Vytvoření a úprava'),
(2,	'Hodnocení'),
(3,	'Přihlášení');

DROP TABLE IF EXISTS `users_resources`;
CREATE TABLE `users_resources` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='predmety opravneni, napr. predmety, projekty';

INSERT INTO `users_resources` (`id`, `name`) VALUES
(1,	'Projekt'),
(2,	'Administrace'),
(3,	'Učitelská sekce'),
(4,	'Studentská sekce');

DROP TABLE IF EXISTS `users_roles`;
CREATE TABLE `users_roles` (
  `id` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` tinyint(4) unsigned DEFAULT NULL,
  `name` varchar(64) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `users_roles_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `users_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `users_roles` (`id`, `parent_id`, `name`) VALUES
(2,	NULL,	'Student'),
(3,	NULL,	'Zaměstnanec'),
(4,	3,	'Asistent'),
(6,	4,	'Vyučující'),
(7,	6,	'Garant'),
(8,	NULL,	'Administrátor');

-- 2011-02-21 15:03:41
