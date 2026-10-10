<?php

namespace App\Models;

use CodeIgniter\Model;

class MPagos extends Model
{
    protected $table      = 'pagos_abonos';
    protected $primaryKey = 'id_pago';
    protected $allowedFields = [
        'id_cliente', 'id_venta', 'monto', 'metodo_pago', 'referencia_pago',
        'ruta_comprobante', 'id_usuario',
    ];

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Registra un abono. Si $idVenta viene definido, se aplica solo a esa
     * venta. Si viene vacío, se aplica automáticamente a la nota más antigua
     * pendiente del cliente (y si sobra, a la siguiente, etc.) - "abono al
     * saldo general" repartido por antigüedad.
     */
    public function registrarAbono($idCliente, $monto, $metodoPago, $referencia, $idUsuario, $idVenta = null)
    {
        $this->db->transStart();

        $montoRestante = (float) $monto;
        $idPagoPrincipal = null;

        if (!empty($idVenta)) {
            $ventas = $this->db->query(
                "SELECT id_venta, saldo_pendiente FROM ventas WHERE id_venta = ? FOR UPDATE",
                [$idVenta]
            )->getResult();
        } else {
            $ventas = $this->db->query(
                "SELECT id_venta, saldo_pendiente FROM ventas
                 WHERE id_cliente = ? AND estatus IN ('PENDIENTE','PARCIAL','VENCIDA') AND saldo_pendiente > 0
                 ORDER BY fecha_limite_pago ASC FOR UPDATE",
                [$idCliente]
            )->getResult();
        }

        foreach ($ventas as $venta) {
            if ($montoRestante <= 0) {
                break;
            }

            $aplicado = min($montoRestante, (float) $venta->saldo_pendiente);
            $nuevoSaldo = round($venta->saldo_pendiente - $aplicado, 2);
            $nuevoEstatus = $nuevoSaldo <= 0 ? 'PAGADA' : 'PARCIAL';

            $this->db->query(
                "UPDATE ventas SET saldo_pendiente = ?, estatus = ? WHERE id_venta = ?",
                [$nuevoSaldo, $nuevoEstatus, $venta->id_venta]
            );

            $this->insert([
                'id_cliente'      => $idCliente,
                'id_venta'        => $venta->id_venta,
                'monto'           => $aplicado,
                'metodo_pago'     => $metodoPago,
                'referencia_pago' => $referencia,
                'id_usuario'      => $idUsuario,
            ]);

            if (!$idPagoPrincipal) {
                $idPagoPrincipal = $this->db->insertID();
            }

            $montoRestante = round($montoRestante - $aplicado, 2);
        }

        // Si sobra dinero (pagó de más o no tenía deuda), se registra como
        // abono general sin venta ligada (a favor del cliente).
        if ($montoRestante > 0) {
            $this->insert([
                'id_cliente'      => $idCliente,
                'id_venta'        => null,
                'monto'           => $montoRestante,
                'metodo_pago'     => $metodoPago,
                'referencia_pago' => $referencia,
                'id_usuario'      => $idUsuario,
            ]);
            if (!$idPagoPrincipal) {
                $idPagoPrincipal = $this->db->insertID();
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [false, 'No se pudo registrar el abono, intenta de nuevo.', null];
        }

        return [true, 'Abono registrado correctamente.', $idPagoPrincipal];
    }

    public function getPorCliente($idCliente)
    {
        return $this->db->query("SELECT * FROM pagos_abonos WHERE id_cliente = ? ORDER BY fecha_pago DESC", [$idCliente])->getResult();
    }

    public function getListado()
    {
        $vsql = "SELECT p.*, c.nombre_completo AS cliente
                 FROM pagos_abonos p
                 INNER JOIN clientes c ON c.id_cliente = p.id_cliente
                 ORDER BY p.fecha_pago DESC LIMIT 300";
        return $this->db->query($vsql)->getResult();
    }

    public function guardarRutaComprobante($idPago, $ruta)
    {
        $this->update($idPago, ['ruta_comprobante' => $ruta]);
    }
}
