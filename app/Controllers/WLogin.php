<?php

namespace App\Controllers;

use App\Models\MUsuarios;

class WLogin extends BaseController
{
    protected MUsuarios $Modelo;

    function __construct()
    {
        $this->Modelo = new MUsuarios();
    }

    public function index()
    {
        return view('admin/login');
    }

    public function autenticar()
    {
        $usuario  = $this->request->getPost('usuario');
        $password = $this->request->getPost('password');

        $row = $this->Modelo->autenticar($usuario, $password);

        if ($row) {
            $this->session->set([
                'logueado'        => true,
                'id_usuario'      => $row->id_usuario,
                'usuario'         => $row->usuario,
                'nombre_completo' => $row->nombre_completo,
                'rol'             => $row->rol,
            ]);

            return $this->jsonResponse(true, 'Bienvenido de nuevo.');
        }

        return $this->jsonResponse(false, 'Usuario o contraseña incorrectos.');
    }

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to(base_url('admin/login'));
    }
}
