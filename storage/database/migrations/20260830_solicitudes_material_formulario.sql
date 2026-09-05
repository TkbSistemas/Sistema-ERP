ALTER TABLE solicitudes_material
    ADD COLUMN IF NOT EXISTS fecha_requerida DATE NULL AFTER fecha_solicitud;

ALTER TABLE solicitudes_material_detalles
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(255) NULL AFTER cantidad;

ALTER TABLE solicitudes_material_noregistrados
    ADD COLUMN IF NOT EXISTS cantidad DECIMAL(10,2) NOT NULL DEFAULT 1.00 AFTER unidad_medida;
