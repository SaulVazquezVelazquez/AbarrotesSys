<?php

namespace App\Models;

use CodeIgniter\Model;

class MClientes extends Model
{
    protected $table      = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $allowedFields = [
        'nombre_completo', 'telefono', 'direccion', 'foto', 'identificacion',
        'pin_hash', 'limite_credito', 'plazo_dias', 'estatus', 'motivo_bloqueo', 'notas',
    ];

    function __construct()
    {
        parent::__construct();
    }

    public function buscar($termino, array $filtros = [])
    {
        return $this->consultaListado($filtros, $termino);
    }

    public function getTodosConSaldo(array $filtros = [])
    {
        return $this->consultaListado($filtros);
    }

    private function consultaListado(array $filtros = [], ?string $termino = null)
    {
        $builder = $this->db->table('vista_saldo_cliente s')
            ->select('s.*, c.foto')
            ->join('clientes c', 'c.id_cliente = s.id_cliente')
            ->orderBy('s.nombre_completo', 'ASC');
        if ($termino !== null && trim($termino) !== '') {
            $builder->limit(100);
            $builder->groupStart()
                ->like('s.nombre_completo', trim($termino), 'both', null, true)
                ->orLike('s.telefono', trim($termino), 'both', null, true)
                ->groupEnd();
        }
        if (in_array($filtros['semaforo'] ?? '', ['VERDE', 'AMARILLO', 'ROJO'], true)) {
            $builder->where('s.semaforo', $filtros['semaforo']);
        }
        if (in_array($filtros['estatus'] ?? '', ['ACTIVO', 'BLOQUEADO'], true)) {
            $builder->where('s.estatus', $filtros['estatus']);
        }
        if (($filtros['deuda'] ?? '') === 'si') {
            $builder->where('s.saldo_actual >', 0);
        } elseif (($filtros['deuda'] ?? '') === 'no') {
            $builder->where('s.saldo_actual', 0);
        }
        return $builder->get()->getResult();
    }

    public function getPorTelefono($telefono)
    {
        return $this->db->query("SELECT * FROM clientes WHERE telefono = ? LIMIT 1", [$telefono])->getRow();
    }

    public function getSaldo($idCliente)
    {
        return $this->db->query("SELECT s.*, c.foto FROM vista_saldo_cliente s INNER JOIN clientes c ON c.id_cliente = s.id_cliente WHERE s.id_cliente = ?", [$idCliente])->getRow();
    }

    // Verifica si una nueva compra de $monto cabe dentro del crédito disponible
    public function validarLimite($idCliente, $monto)
    {
        $saldo = $this->getSaldo($idCliente);
        if (! $saldo) {
            return [false, 'Cliente no encontrado.'];
        }
        if ($saldo->estatus === 'BLOQUEADO') {
            return [false, 'Este cliente está bloqueado y no puede recibir más crédito.'];
        }
        if ($monto > $saldo->credito_disponible) {
            return [false, 'La venta excede el crédito disponible (disponible: $' . number_format($saldo->credito_disponible, 2) . ').'];
        }
        return [true, 'Crédito disponible suficiente.', $saldo->credito_disponible];
    }

    public function bloquear($idCliente, $motivo)
    {
        $this->update($idCliente, ['estatus' => 'BLOQUEADO', 'motivo_bloqueo' => $motivo]);
        return [true, 'Cliente bloqueado correctamente.'];
    }

    public function desbloquear($idCliente)
    {
        $this->update($idCliente, ['estatus' => 'ACTIVO', 'motivo_bloqueo' => null]);
        return [true, 'Cliente desbloqueado correctamente.'];
    }

    public function verificarPin($idCliente, $pin)
    {
        $cliente = $this->find($idCliente);
        if (! $cliente || empty($cliente['pin_hash'])) {
            return false;
        }
        return password_verify((string) $pin, $cliente['pin_hash']);
    }

    public function establecerPin($idCliente, $pin)
    {
        $this->update($idCliente, ['pin_hash' => password_hash($pin, PASSWORD_DEFAULT)]);
    }

    public function getHistorialCompras($idCliente)
    {
        $vsql = "SELECT v.id_venta, v.fecha_venta, v.total, v.saldo_pendiente, v.estatus,
                        v.fecha_limite_pago,
                        v.pickup_at, v.pedido_estatus, 'CREDITO' AS tipo, u.nombre_completo AS atendio
                 FROM ventas v LEFT JOIN usuarios u ON u.id_usuario = v.id_usuario WHERE v.id_cliente = ?
                 UNION ALL
                 SELECT pv.id_venta, pv.fecha_venta, pv.total, 0::NUMERIC AS saldo_pendiente, 'PAGADA' AS estatus,
                        NULL::DATE AS fecha_limite_pago,
                        NULL::TIMESTAMP AS pickup_at, NULL::VARCHAR AS pedido_estatus, 'MOSTRADOR' AS tipo,
                        u.nombre_completo AS atendio
                 FROM pos_ventas pv LEFT JOIN usuarios u ON u.id_usuario = pv.id_usuario
                 WHERE pv.id_cliente = ? AND pv.metodo_pago <> 'CREDITO'
                 ORDER BY fecha_venta DESC";
        return $this->db->query($vsql, [$idCliente, $idCliente])->getResult();
    }

    public function getHistorialPagos($idCliente)
    {
        $vsql = "SELECT p.* FROM pagos_abonos p WHERE p.id_cliente = ? ORDER BY p.fecha_pago DESC";
        return $this->db->query($vsql, [$idCliente])->getResult();
    }

    public function getContactos($idCliente)
    {
        return $this->db->query("SELECT * FROM contactos_referencia WHERE id_cliente = ?", [$idCliente])->getResult();
    }

    public function agregarContacto($idCliente, $nombre, $telefono, $relacion)
    {
        $this->db->query(
            "INSERT INTO contactos_referencia (id_cliente, nombre, telefono, relacion) VALUES (?, ?, ?, ?)",
            [$idCliente, $nombre, $telefono, $relacion]
        );
        return [true, 'Contacto de referencia agregado.'];
    }

    public function getScoring($idCliente)
    {
        return $this->db->query("SELECT * FROM vista_scoring_cliente WHERE id_cliente = ?", [$idCliente])->getRow();
    }

    public function getTimeline(int $idCliente): array
    {
        $sql = "SELECT * FROM (
                    SELECT 'VENTA' AS tipo, 'Compra a crédito' AS titulo,
                           'Venta #' || v.id_venta || ' · ' || v.estatus AS detalle,
                           v.fecha_venta AS fecha, v.total AS monto, v.estatus
                    FROM ventas v WHERE v.id_cliente = ?
                    UNION ALL
                    SELECT 'PAGO', 'Abono recibido', p.metodo_pago || COALESCE(' · ' || NULLIF(p.referencia_pago, ''), ''),
                           p.fecha_pago, p.monto, 'REGISTRADO'
                    FROM pagos_abonos p WHERE p.id_cliente = ?
                    UNION ALL
                    SELECT 'PROMESA', 'Promesa de pago', 'Prometió pagar el ' || to_char(pr.fecha_prometida, 'DD/MM/YYYY'),
                           pr.fecha_creacion, pr.monto_prometido, pr.estatus
                    FROM promesas_pago pr WHERE pr.id_cliente = ?
                    UNION ALL
                    SELECT 'GESTION', 'Gestión de cobranza', sc.resultado || COALESCE(' · ' || NULLIF(sc.detalle, ''), ''),
                           sc.fecha_creacion, NULL::NUMERIC, COALESCE(sc.resultado, 'REGISTRADA')
                    FROM seguimientos_cobranza sc WHERE sc.id_cliente = ?
                    UNION ALL
                    SELECT 'SOLICITUD', 'Solicitud de compra', 'Solicitud #' || so.id_solicitud || ' · ' || so.estatus,
                           so.fecha_creacion, so.total_estimado, so.estatus
                    FROM solicitudes_compra so WHERE so.id_cliente = ?
                ) actividad ORDER BY fecha DESC LIMIT 100";
        return $this->db->query($sql, [$idCliente, $idCliente, $idCliente, $idCliente, $idCliente])->getResult();
    }
}
