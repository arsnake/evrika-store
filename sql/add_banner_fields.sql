-- Добавляем поля описания и даты к слайдам баннера
ALTER TABLE `oc_banner_image`
  ADD COLUMN `description` TEXT NOT NULL AFTER `link`,
  ADD COLUMN `slide_date`  VARCHAR(50) NOT NULL DEFAULT '' AFTER `description`;
