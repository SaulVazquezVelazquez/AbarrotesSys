-- Consulta privada del estado de solicitudes pickup.
ALTER TABLE solicitudes_compra
    ADD COLUMN IF NOT EXISTS token_seguimiento VARCHAR(64);

CREATE UNIQUE INDEX IF NOT EXISTS idx_solicitudes_token_seguimiento
    ON solicitudes_compra(token_seguimiento)
    WHERE token_seguimiento IS NOT NULL;
