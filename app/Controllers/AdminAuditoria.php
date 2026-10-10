<?php

namespace App\Controllers;

class AdminAuditoria extends BaseController
{
    public function index()
    {
        if ($check = $this->requireAdmin(false, ['DUENO', 'ADMIN'])) {
            return $check;
        }

        $vsql = "SELECT a.*, u.nombre_completo AS usuario
                 FROM auditoria a
                 LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
                 ORDER BY a.fecha_creacion DESC LIMIT 300";

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['registros']       = $this->db->query($vsql)->getResult();

        return view('admin/auditoria', $data);
    }
}
