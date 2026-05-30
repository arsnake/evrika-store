-- Миграция: добавить markup_percent в oc_customer_group
-- Запустить один раз в phpMyAdmin или MySQL CLI
ALTER TABLE oc_customer_group
    ADD COLUMN markup_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00
    COMMENT 'Наценка к базовой цене в %: цена = base × (1 + markup/100)';

-- Пример: установить наценку 100% для группы по умолчанию (group_id = 1)
-- UPDATE oc_customer_group SET markup_percent = 100.00 WHERE customer_group_id = 1;
