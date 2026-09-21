ALTER TABLE solicitudes_bajas
    MODIFY estatus ENUM('Pendiente','Aprobada','Rechazada','Cancelada','Auditoría')
    NOT NULL DEFAULT 'Pendiente';

ALTER TABLE solicitudes_bajas_detalles
    MODIFY cantidad DECIMAL(10,2) NOT NULL;

ALTER TABLE recepciones_almacen
    MODIFY estatus ENUM('Pendiente','Completa','Parcial','Auditoría')
    NULL DEFAULT 'Pendiente';
