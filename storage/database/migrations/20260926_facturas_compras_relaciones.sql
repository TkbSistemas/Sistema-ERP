-- Una factura de compra por orden y montos con precisión decimal.
ALTER TABLE facturas_compras
    MODIFY orden_id INT NOT NULL,
    MODIFY folio_fiscal VARCHAR(100) NOT NULL,
    MODIFY monto_total DECIMAL(14,2) NOT NULL,
    MODIFY fecha_emision DATE NOT NULL,
    ADD CONSTRAINT uq_facturas_compras_orden UNIQUE (orden_id),
    ADD CONSTRAINT uq_facturas_compras_folio UNIQUE (folio_fiscal),
    ADD CONSTRAINT facturas_compras_orden_fk
        FOREIGN KEY (orden_id) REFERENCES ordenes_compra (id)
        ON UPDATE CASCADE ON DELETE CASCADE;
