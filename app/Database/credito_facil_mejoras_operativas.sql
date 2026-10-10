-- Seguimiento de cobranza y consulta privada del estado de solicitudes.
ALTER TABLE solicitudes_compra
    ADD COLUMN IF NOT EXISTS token_seguimiento VARCHAR(64);

ALTER TABLE mensajes_cobranza
    ADD COLUMN IF NOT EXISTS monto_reportado NUMERIC(10,2);

UPDATE mensajes_cobranza
SET tipo = 'INTENCION_PAGO', canal = 'INTERNO',
    monto_reportado = COALESCE(
        monto_reportado,
        NULLIF(substring(texto_final FROM 'de \$\{?([0-9]+([.][0-9]+)?)'), '')::NUMERIC
    )
WHERE tipo = 'MANUAL'
  AND texto_final ILIKE 'Aviso interno: el cliente registró una intención de pago%';

CREATE UNIQUE INDEX IF NOT EXISTS idx_solicitudes_token_seguimiento
    ON solicitudes_compra(token_seguimiento)
    WHERE token_seguimiento IS NOT NULL;

CREATE TABLE IF NOT EXISTS seguimientos_cobranza (
    id_seguimiento   SERIAL PRIMARY KEY,
    id_cliente       INTEGER NOT NULL REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    id_venta         INTEGER REFERENCES ventas(id_venta),
    id_usuario       INTEGER REFERENCES usuarios(id_usuario),
    resultado        VARCHAR(30) NOT NULL,
    detalle          TEXT,
    proxima_fecha    DATE,
    fecha_creacion   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_seguimientos_cliente_fecha
    ON seguimientos_cobranza(id_cliente, fecha_creacion DESC);
CREATE INDEX IF NOT EXISTS idx_seguimientos_proxima_fecha
    ON seguimientos_cobranza(proxima_fecha) WHERE proxima_fecha IS NOT NULL;
