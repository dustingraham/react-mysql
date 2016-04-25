DROP TABLE IF EXISTS `abc`;
DROP TABLE IF EXISTS `simple_table`;
CREATE TABLE IF NOT EXISTS `simple_table` (
  `id`   INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(255)     NOT NULL
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8;

INSERT INTO `simple_table` (`id`, `name`) VALUES
  (1, 'a'),
  (2, 'b');

ALTER TABLE `simple_table`
ADD PRIMARY KEY (`id`);

ALTER TABLE `simple_table`
MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 3;
