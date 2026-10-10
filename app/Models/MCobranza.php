<?php

namespace App\Models;

use CodeIgniter\Model;

class MCobranza extends Model
{
    protected $table = 'mensajes_cobranza';
    protected $primaryKey = 'id_mensaje';

    function __construct()
    {
        parent::__construct();
    }

    public function getAging($rango = null)
    {
        $vsql = "SELECT * FROM vista_aging WHERE 1=1 ";
        $params = [];
        if (!empty($rango)) {
            $vsql .= "AND rango_atraso = ? ";
            $params[] = $rango;
        }
        $vsql .= "ORDER BY dias_atraso DESC";

        return $this->db->query($vsql, $params)->getResult();
    }

    public function getResumenAging()
    {
        $vsql = "SELECT rango_atraso, COUNT(*) AS total_notas, SUM(saldo_pendiente) AS total_saldo
                 FROM vista_aging GROUP BY rango_atraso";
        return $this->db->query($vsql)->getResult();
    }

    // ---- Promesas de pago ----
    public function guardarPromesa($idCliente, $idVenta, $monto, $fecha, $notas)
    {
        $this->db->query(
            "INSERT INTO promesas_pago (id_cliente, id_venta, monto_prometido, fecha_prometida, notas)
             VALUES (?, ?, ?, ?, ?)",
            [$idCliente, $idVenta ?: null, $monto, $fecha, $notas]
        );
        return [true, 'Promesa de pago registrada.'];
    }

    public function getPromesas($estatus = null)
    {
        $vsql = "SELECT pr.*, c.nombre_completo, c.telefono
                 FROM promesas_pago pr
                 INNER JOIN clientes c ON c.id_cliente = pr.id_cliente
                 WHERE 1=1 ";
        $params = [];
        if (!empty($estatus)) {
            $vsql .= "AND pr.estatus = ? ";
            $params[] = $estatus;
        }
        $vsql .= "ORDER BY pr.fecha_prometida ASC";

        return $this->db->query($vsql, $params)->getResult();
    }

    public function getSeguimientosPendientes(): array
    {
        return $this->db->query(
            "SELECT sc.*, c.nombre_completo, c.telefono
             FROM seguimientos_cobranza sc
             INNER JOIN clientes c ON c.id_cliente = sc.id_cliente
             WHERE sc.proxima_fecha IS NOT NULL AND sc.proxima_fecha <= CURRENT_DATE + 7
             ORDER BY sc.proxima_fecha ASC, sc.fecha_creacion DESC LIMIT 100"
        )->getResult();
    }

    public function getIntencionesPago(): array
    {
        return $this->db->query(
            "SELECT m.*, c.nombre_completo, c.telefono
             FROM mensajes_cobranza m INNER JOIN clientes c ON c.id_cliente = m.id_cliente
             WHERE m.tipo = 'INTENCION_PAGO' AND m.estatus = 'PENDIENTE'
             ORDER BY m.fecha_programada ASC"
        )->getResult();
    }

    public function registrarSeguimiento(int $idCliente, ?int $idVenta, int $idUsuario, string $resultado, ?string $detalle, ?string $proximaFecha): void
    {
        $this->db->table('seguimientos_cobranza')->insert([
            'id_cliente' => $idCliente,
            'id_venta' => $idVenta,
            'id_usuario' => $idUsuario,
            'resultado' => $resultado,
            'detalle' => $detalle,
            'proxima_fecha' => $proximaFecha ?: null,
        ]);
    }

    public function actualizarPromesa($idPromesa, $estatus)
    {
        $this->db->query("UPDATE promesas_pago SET estatus = ? WHERE id_promesa = ?", [$estatus, $idPromesa]);
        return [true, 'Promesa actualizada.'];
    }

    // ---- Mensajes de cobranza ----
    public function getCola($estatus = null)
    {
        $vsql = "SELECT m.*, c.nombre_completo, c.telefono, pl.cuerpo AS plantilla_cuerpo
                 FROM mensajes_cobranza m
                 INNER JOIN clientes c ON c.id_cliente = m.id_cliente
                 LEFT JOIN plantillas_cobranza pl ON pl.clave = m.tipo
                 WHERE m.tipo <> 'INTENCION_PAGO' ";
        $params = [];
        if (!empty($estatus)) {
            $vsql .= "AND m.estatus = ? ";
            $params[] = $estatus;
        }
        $vsql .= "ORDER BY m.fecha_programada ASC";

        return $this->db->query($vsql, $params)->getResult();
    }

    public function getPendientesParaEnviar()
    {
        $vsql = "SELECT m.*, c.nombre_completo, c.telefono, pl.cuerpo AS plantilla_cuerpo
                 FROM mensajes_cobranza m
                 INNER JOIN clientes c ON c.id_cliente = m.id_cliente
                 LEFT JOIN plantillas_cobranza pl ON pl.clave = m.tipo
                 WHERE m.estatus = 'PENDIENTE' AND m.tipo <> 'INTENCION_PAGO' AND m.fecha_programada <= NOW()";
        return $this->db->query($vsql)->getResult();
    }

    public function enviarManual($idCliente, $texto)
    {
        $this->db->query(
            "INSERT INTO mensajes_cobranza (id_cliente, tipo, canal, texto_final, fecha_programada, estatus)
             VALUES (?, 'MANUAL', 'WHATSAPP', ?, NOW(), 'PENDIENTE')",
            [$idCliente, $texto]
        );
        return [true, 'Mensaje agregado a la cola de envío.'];
    }

    public function marcarEnviado($idMensaje)
    {
        $this->db->query("UPDATE mensajes_cobranza SET estatus = 'ENVIADO', fecha_envio = NOW() WHERE id_mensaje = ?", [$idMensaje]);
        return [true, 'Mensaje marcado como enviado.'];
    }
}
