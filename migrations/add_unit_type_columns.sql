-- migrations/add_unit_type_columns.sql
ALTER TABLE refunds ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER refund_type;
ALTER TABLE leftovers ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER weight_kg;
