<?php

namespace App\Models;

use CodeIgniter\Model;

class MProductos extends Model
{
    protected $table      = 'productos';
    protected $primaryKey = 'id_producto';
    protected $allowedFields = ['nombre', 'descripcion', 'ruta_imagen', 'categoria', 'precio', 'stock', 'stock_minimo', 'activo', 'codigo_barras', 'plu', 'departamento', 'unidad_base', 'unidades_por_empaque'];

    function __construct()
    {
        parent::__construct();
    }

    public function getActivos()
    {
        return $this->db->query("SELECT * FROM productos WHERE activo = 1 ORDER BY categoria ASC, nombre ASC")->getResult();
    }

    public function getTodos(array $filtros = [])
    {
        $builder = $this->db->table('productos')->orderBy('activo', 'DESC')->orderBy('nombre', 'ASC');
        if (!empty($filtros['q'])) {
            $builder->groupStart()->like('nombre', trim($filtros['q']), 'both', null, true)->orLike('descripcion', trim($filtros['q']), 'both', null, true)->groupEnd();
        }
        if (!empty($filtros['categoria'])) {
            $builder->where('categoria', $filtros['categoria']);
        }
        if (($filtros['disponibilidad'] ?? '') === 'disponible') {
            $builder->groupStart()->where('stock IS NULL', null, false)->orWhere('stock >', 0)->groupEnd();
        } elseif (($filtros['disponibilidad'] ?? '') === 'agotado') {
            $builder->where('stock', 0);
        } elseif (($filtros['disponibilidad'] ?? '') === 'bajo') {
            $builder->where('stock IS NOT NULL', null, false)->where('stock <= stock_minimo', null, false);
        }
        return $builder->get()->getResult();
    }

    public function getCategorias(): array
    {
        return $this->db->table('productos')->distinct()->select('categoria')
            ->where('categoria IS NOT NULL', null, false)->where('categoria !=', '')
            ->orderBy('categoria', 'ASC')->get()->getResult();
    }

    public function contarBajoMinimo(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) AS total FROM productos WHERE activo = 1 AND stock IS NOT NULL AND stock <= stock_minimo')->getRow()->total;
    }

    public function getImagenesPorProducto(array $idsProductos): array
    {
        if (empty($idsProductos)) {
            return [];
        }

        $filas = $this->db->table('producto_imagenes')
            ->whereIn('id_producto', $idsProductos)
            ->orderBy('orden', 'ASC')
            ->orderBy('id_imagen', 'ASC')
            ->get()->getResult();

        $imagenes = [];
        foreach ($filas as $imagen) {
            $imagenes[$imagen->id_producto][] = $imagen;
        }

        return $imagenes;
    }

    public function agregarImagen(int $idProducto, string $ruta): void
    {
        $this->db->table('producto_imagenes')->insert([
            'id_producto' => $idProducto,
            'ruta_imagen' => $ruta,
            'orden'       => 0,
        ]);
    }

    public function descontarStock($idProducto, $cantidad)
    {
        $this->db->query(
            "UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND stock IS NOT NULL",
            [$cantidad, $idProducto]
        );
    }
}
