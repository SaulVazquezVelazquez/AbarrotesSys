<?php

namespace App\Models;

use CodeIgniter\Model;

class MUsuarios extends Model
{
    protected $table      = 'usuarios';
    protected $primaryKey = 'id_usuario';
    protected $allowedFields = ['nombre_completo', 'usuario', 'password_hash', 'rol', 'activo'];

    function __construct()
    {
        parent::__construct();
    }

    public function getTodos()
    {
        return $this->db->query("SELECT id_usuario, nombre_completo, usuario, rol, activo, fecha_creacion FROM usuarios ORDER BY activo DESC, nombre_completo ASC")->getResult();
    }

    public function autenticar($usuario, $password)
    {
        $row = $this->db->query("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1", [$usuario])->getRow();

        if ($row && password_verify($password, $row->password_hash)) {
            return $row;
        }

        return null;
    }

    public function crearOActualizar($idUsuario, $nombreCompleto, $usuario, $rol, $activo, $password = null)
    {
        $data = [
            'nombre_completo' => $nombreCompleto,
            'usuario'         => $usuario,
            'rol'             => $rol,
            'activo'          => $activo,
        ];

        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($idUsuario) {
            $this->update($idUsuario, $data);
        } else {
            if (empty($password)) {
                return [false, 'La contraseña es obligatoria para un usuario nuevo.'];
            }
            $this->insert($data);
        }

        return [true, 'Usuario guardado correctamente.'];
    }
}
