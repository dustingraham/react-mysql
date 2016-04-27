SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `simple_table`;
CREATE TABLE IF NOT EXISTS `simple_table` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `simple_table` (`id`, `name`, `value`, `created_at`) VALUES
  (1, 'a', 2, '2016-04-12 05:23:31'),
  (2, 'b', 5, '0000-00-00 00:00:00'),
  (3, 'John''s Cafe', 54321, '2016-04-26 16:18:59');


ALTER TABLE `simple_table`
ADD PRIMARY KEY (`id`);


ALTER TABLE `simple_table`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
