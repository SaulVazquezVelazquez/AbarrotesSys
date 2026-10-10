<?php

namespace App\Controllers;

use App\Models\MUsuarios;

class AdminUsuarios extends BaseController
{
    protected MUsuarios $Modelo;

    function __construct()
    {
        $this->Modelo = new MUsuarios();
    }

    public function index()
    {
        if ($check = $this->requireAdmin(false, ['DUENO'])) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $data['usuarios']        = $this->Modelo->getTodos();

        return view('admin/usuarios', $data);
    }

    public function guardar()
    {
        if ($check = $this->requireAdmin(true, ['DUENO'])) {
            return $check;
        }

        $id       = $this->request->getPost('id_usuario');
        $nombre   = $this->request->getPost('nombre_completo');
        $usuario  = $this->request->getPost('usuario');
        $rol      = $this->request->getPost('rol') ?: 'CAJERO';
        $activo   = $this->request->getPost('activo') ? 1 : 0;
        $password = $this->request->getPost('password');

        if (empty($nombre) || empty($usuario)) {
            return $this->jsonResponse(false, 'Nombre y usuario son obligatorios.');
        }

        $resultado = $this->Modelo->crearOActualizar($id ?: null, $nombre, $usuario, $rol, $activo, $password ?: null);
        $this->registrarAuditoria('GUARDAR_USUARIO', 'usuarios', $id, $usuario);

        return $this->jsonResponse($resultado[0], $resultado[1]);
    }
}
