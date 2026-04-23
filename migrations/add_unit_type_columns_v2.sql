-- migrations/add_unit_type_columns_v2.sql
ALTER TABLE qat_types ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER name;
ALTER TABLE leftovers ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER weight_kg;
ALTER TABLE refunds ADD COLUMN unit_type ENUM('weight', 'units') DEFAULT 'weight' AFTER refund_type;
