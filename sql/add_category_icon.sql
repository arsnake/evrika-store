-- Поле иконки для категорий (slug из библиотеки evrika/php/icons.php)
ALTER TABLE `oc_category`
  ADD COLUMN `icon` VARCHAR(50) NOT NULL DEFAULT '' AFTER `image`;
