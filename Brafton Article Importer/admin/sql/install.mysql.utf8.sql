CREATE TABLE IF NOT EXISTS `#__brafton_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) NOT NULL,
  `brafton_cat_id` int(10) NOT NULL,
   PRIMARY KEY  (`id`),
   FOREIGN KEY (`cat_id`) REFERENCES #__categories(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0;

CREATE TABLE IF NOT EXISTS `#__brafton_content` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `content_id` int(10) UNSIGNED NOT NULL,
  `brafton_content_id` int(10) NOT NULL,
   PRIMARY KEY  (`id`),
   FOREIGN KEY (`content_id`) REFERENCES #__content(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0;

CREATE TABLE IF NOT EXISTS `#__brafton_options` (
  `option` varchar(100) NOT NULL,
  `value` varchar(500) NOT NULL,
   PRIMARY KEY  (`option`)
) ENGINE=InnoDB AUTO_INCREMENT=0;

INSERT IGNORE INTO  `#__brafton_options` (`option`,`value`) VALUES ('api-key',  ''), ('base-url',  ''), ('author', ''), ('import-order', '');