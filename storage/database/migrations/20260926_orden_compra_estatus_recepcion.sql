ALTER TABLE ordenes_compra
    MODIFY estatus ENUM('Aprobada','Cancelada','Pendiente','Rechazada','Parcial','Completa','Recibida','Incompleta')
    NOT NULL DEFAULT 'Pendiente';
