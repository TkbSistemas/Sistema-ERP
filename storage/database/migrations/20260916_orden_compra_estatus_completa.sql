ALTER TABLE ordenes_compra
    MODIFY COLUMN estatus ENUM(
        'Aprobada',
        'Cancelada',
        'Pendiente',
        'Rechazada',
        'Parcial',
        'Completa'
    ) NOT NULL DEFAULT 'Pendiente';
