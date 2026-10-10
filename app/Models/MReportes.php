<?php

namespace App\Models;

use CodeIgniter\Model;

class MReportes extends Model
{
    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';

    function __construct()
    {
        parent::__construct();
    }

    public function getResumenGeneral()
    {
        $totalFiadoMes = $this->db->query(
            "SELECT COALESCE(SUM(total),0) AS total FROM ventas WHERE fecha_venta >= date_trunc('month', CURRENT_DATE)"
        )->getRow();

        $totalCobradoMes = $this->db->query(
            "SELECT COALESCE(SUM(monto),0) AS total FROM pagos_abonos WHERE fecha_pago >= date_trunc('month', CURRENT_DATE)"
        )->getRow();

        $carteraTotal = $this->db->query(
            "SELECT COALESCE(SUM(saldo_pendiente),0) AS total FROM ventas WHERE estatus IN ('PENDIENTE','PARCIAL','VENCIDA')"
        )->getRow();

        $carteraVencida = $this->db->query(
            "SELECT COALESCE(SUM(saldo_pendiente),0) AS total FROM ventas WHERE estatus = 'VENCIDA'"
        )->getRow();

        $porcentajeMorosidad = $carteraTotal->total > 0
            ? round(($carteraVencida->total / $carteraTotal->total) * 100, 1)
            : 0;

        return [
            'total_fiado_mes'     => (float) $totalFiadoMes->total,
            'total_cobrado_mes'   => (float) $totalCobradoMes->total,
            'cartera_total'       => (float) $carteraTotal->total,
            'cartera_vencida'     => (float) $carteraVencida->total,
            'porcentaje_morosidad' => $porcentajeMorosidad,
        ];
    }

    public function getFlujoCaja($dias = 30)
    {
        return $this->db->query(
            "SELECT * FROM vista_flujo_caja WHERE dia >= CURRENT_DATE - ? ORDER BY dia ASC",
            [$dias]
        )->getResult();
    }

    public function getCarteraPorCobrar()
    {
        return $this->db->query("SELECT * FROM vista_cartera_por_cobrar")->getResult();
    }

    public function getResumenAging()
    {
        return $this->db->query(
            "SELECT rango_atraso, COUNT(*) AS total_notas, SUM(saldo_pendiente) AS total_saldo FROM vista_aging GROUP BY rango_atraso"
        )->getResult();
    }

    public function getAlertasOperativas(): array
    {
        $vencidas = $this->db->query(
            "SELECT COUNT(*) AS total, COALESCE(SUM(saldo_pendiente), 0) AS monto
             FROM ventas WHERE estatus = 'VENCIDA' AND saldo_pendiente > 0"
        )->getRowArray();
        $porVencer = $this->db->query(
            "SELECT COUNT(*) AS total, COALESCE(SUM(saldo_pendiente), 0) AS monto
             FROM ventas WHERE estatus IN ('PENDIENTE','PARCIAL') AND fecha_limite_pago BETWEEN CURRENT_DATE AND CURRENT_DATE + 7 AND saldo_pendiente > 0"
        )->getRowArray();
        $intenciones = $this->db->query(
            "SELECT COUNT(*) AS total FROM mensajes_cobranza WHERE tipo = 'INTENCION_PAGO' AND estatus = 'PENDIENTE'"
        )->getRowArray();

        return [
            'vencidas' => $vencidas,
            'por_vencer' => $porVencer,
            'intenciones_pago' => (int) $intenciones['total'],
        ];
    }

    // Devuelve filas listas para exportar a CSV (cartera detallada por cliente)
    public function getCarteraDetalladaParaExportar()
    {
        $vsql = "SELECT c.nombre_completo, c.telefono, s.saldo_actual, s.limite_credito,
                        s.credito_disponible, s.proxima_fecha_limite, s.semaforo
                 FROM vista_saldo_cliente s
                 INNER JOIN clientes c ON c.id_cliente = s.id_cliente
                 WHERE s.saldo_actual > 0
                 ORDER BY s.saldo_actual DESC";
        return $this->db->query($vsql)->getResult();
    }
}
