ALTER TABLE ordenes_compra
    ADD INDEX idx_ordenes_compra_almacen (id_almacen),
    ADD CONSTRAINT orden_compra_almacen_fk
        FOREIGN KEY (id_almacen) REFERENCES almacenes (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;
