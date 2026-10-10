<?php

namespace App\Controllers;

use App\Models\MProductos;

class AdminProductos extends BaseController
{
    protected MProductos $Modelo;

    function __construct()
    {
        $this->Modelo = new MProductos();
    }

    public function index()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $data['nombre_completo'] = $this->session->get('nombre_completo');
        $data['rol']             = $this->session->get('rol');
        $filtros = [
            'q' => $this->request->getGet('q'),
            'categoria' => $this->request->getGet('categoria'),
            'disponibilidad' => $this->request->getGet('disponibilidad'),
        ];
        $data['filtros'] = $filtros;
        $data['categorias'] = $this->Modelo->getCategorias();
        $data['productos']       = $this->Modelo->getTodos($filtros);
        $data['productosBajoMinimo'] = $this->Modelo->contarBajoMinimo();
        $data['imagenesPorProducto'] = $this->Modelo->getImagenesPorProducto(array_map(
            static fn($producto) => (int) $producto->id_producto,
            $data['productos']
        ));

        return view('admin/productos', $data);
    }

    public function plantilla()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }
        $csv = "nombre,descripcion,categoria,precio,stock,stock_minimo,codigo_barras,plu,departamento,unidad_base,unidades_por_empaque\r\n";
        $csv .= "Arroz 1 kg,Arroz blanco,cereales,28.50,25,5,7501000000012,,ABARROTES,PZA,12\r\n";
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename=plantilla_productos.csv')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    public function importar()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }
        $file = $this->request->getFile('archivo_productos');
        if (!$file || !$file->isValid()) {
            return redirect()->to(base_url('admin/productos'))->with('error', 'Selecciona un archivo CSV o Excel válido.');
        }
        $extension = strtolower($file->getClientExtension());
        try {
            $rows = $extension === 'xlsx' ? $this->leerXlsx($file->getTempName()) : ($extension === 'csv' ? $this->leerCsv($file->getTempName()) : null);
            if ($rows === null) {
                throw new \RuntimeException('Formato no válido. Usa .xlsx o .csv.');
            }
            if (count($rows) > 5000) {
                throw new \RuntimeException('El archivo supera el límite de 5,000 filas.');
            }
            $headers = array_map([$this, 'normalizarEncabezado'], array_shift($rows) ?? []);
            $required = ['nombre', 'precio', 'departamento', 'unidad_base'];
            if (array_diff($required, $headers)) {
                throw new \RuntimeException('Faltan encabezados obligatorios: nombre, precio, departamento y unidad_base. Descarga la plantilla para ver el formato.');
            }
            $db = db_connect();
            $inserted = 0; $skipped = 0; $errors = [];
            $db->transBegin();
            foreach ($rows as $i => $cells) {
                if (count(array_filter($cells, static fn($v) => trim((string) $v) !== '')) === 0) continue;
                $row = [];
                foreach ($headers as $col => $key) if ($key !== '') $row[$key] = trim((string) ($cells[$col] ?? ''));
                $name = $row['nombre'] ?? '';
                $price = str_replace(',', '.', $row['precio'] ?? '');
                if ($name === '' || !is_numeric($price) || (float) $price <= 0) {
                    $errors[] = 'Fila ' . ($i + 2) . ': nombre y precio mayor que cero son obligatorios.';
                    continue;
                }
                $department = strtoupper($row['departamento'] ?? 'ABARROTES');
                $unit = strtoupper($row['unidad_base'] ?? 'PZA');
                if (!in_array($department, ['ABARROTES', 'VERDULERIA'], true) || !in_array($unit, ['PZA', 'KG', 'G', 'L'], true)) {
                    $errors[] = 'Fila ' . ($i + 2) . ': departamento o unidad_base no reconocidos.';
                    continue;
                }
                $barcode = ($row['codigo_barras'] ?? '') ?: null;
                if ($barcode && $db->table('productos')->where('codigo_barras', $barcode)->countAllResults() > 0) { $skipped++; continue; }
                $plu = ($row['plu'] ?? '') ?: null;
                if ($plu && $db->table('productos')->where('plu', $plu)->countAllResults() > 0) { $skipped++; continue; }
                $stock = str_replace(',', '.', $row['stock'] ?? '0');
                $stockMinimo = str_replace(',', '.', $row['stock_minimo'] ?? '0');
                $pack = str_replace(',', '.', $row['unidades_por_empaque'] ?? '1');
                if (!is_numeric($stock) || (float) $stock < 0 || !is_numeric($stockMinimo) || (float) $stockMinimo < 0 || !is_numeric($pack) || (float) $pack <= 0) {
                    $errors[] = 'Fila ' . ($i + 2) . ': stock, stock_minimo o unidades_por_empaque no válido.';
                    continue;
                }
                $data = [
                    'nombre' => mb_substr($name, 0, 150), 'descripcion' => $row['descripcion'] ?? null,
                    'categoria' => $row['categoria'] ?? null, 'precio' => $price,
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                    'codigo_barras' => $barcode, 'plu' => $plu,
                    'departamento' => $department, 'unidad_base' => $unit,
                    'unidades_por_empaque' => $pack,
                    'activo' => 1,
                ];
                if (!$db->table('productos')->insert($data)) $errors[] = 'Fila ' . ($i + 2) . ': no se pudo guardar.';
                else $inserted++;
            }
            if ($db->transStatus() === false) throw new \RuntimeException('La base de datos rechazó la importación; no se guardaron cambios.');
            $db->transCommit();
            $message = "Importación terminada: {$inserted} agregados, {$skipped} omitidos por código repetido, " . count($errors) . ' con error.';
            if ($errors) $message .= ' ' . implode(' ', array_slice($errors, 0, 5));
            return redirect()->to(base_url('admin/productos'))->with($errors ? 'warning' : 'success', $message);
        } catch (\Throwable $e) {
            if (isset($db)) $db->transRollback();
            return redirect()->to(base_url('admin/productos'))->with('error', $e->getMessage());
        }
    }

    private function normalizarEncabezado(string $header): string
    {
        $header = mb_strtolower(trim($header));
        return str_replace([' ', '-', 'á', 'é', 'í', 'ó', 'ú'], ['', '_', 'a', 'e', 'i', 'o', 'u'], $header);
    }

    private function leerCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        $first = fgets($handle); rewind($handle);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) $rows[] = array_map(static fn($v) => preg_replace('/^\xEF\xBB\xBF/', '', (string) $v), $row);
        fclose($handle);
        return $rows;
    }

    private function leerXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) throw new \RuntimeException('El servidor requiere la extensión PHP zip para leer Excel .xlsx.');
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new \RuntimeException('No se pudo abrir el archivo Excel.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = simplexml_load_string($xml);
            foreach ($sx->si as $si) $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map('strval', iterator_to_array($si->r)));
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
        if (!$sheet) throw new \RuntimeException('El libro no contiene una primera hoja legible.');
        $sx = simplexml_load_string($sheet); $ns = $sx->getNamespaces(true); $main = $ns[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sx->registerXPathNamespace('x', $main); $rows = [];
        foreach ($sx->xpath('//x:sheetData/x:row') as $r) {
            $line = [];
            foreach ($r->c as $cell) {
                preg_match('/([A-Z]+)/', (string) $cell['r'], $m); $index = 0;
                foreach (str_split($m[1] ?? 'A') as $char) $index = $index * 26 + ord($char) - 64;
                $index--;
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 's') $value = $shared[(int) $value] ?? '';
                elseif ((string) $cell['t'] === 'inlineStr') $value = (string) $cell->is->t;
                $line[$index] = $value;
            }
            if ($line) { ksort($line); $rows[] = array_replace(array_fill(0, max(array_keys($line)) + 1, ''), $line); }
        }
        return $rows;
    }

    public function guardar()
    {
        if ($check = $this->requireAdmin(true)) {
            return $check;
        }

        $id = $this->request->getPost('id_producto');

        $data = [
            'nombre'      => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'categoria'   => $this->request->getPost('categoria'),
            'precio'      => $this->request->getPost('precio'),
            'stock'       => $this->request->getPost('stock') !== '' ? $this->request->getPost('stock') : null,
            'stock_minimo' => !in_array($this->request->getPost('stock_minimo'), ['', null], true) ? $this->request->getPost('stock_minimo') : 0,
            'codigo_barras' => $this->request->getPost('codigo_barras') ?: null,
            'plu' => $this->request->getPost('plu') ?: null,
            'departamento' => $this->request->getPost('departamento') ?: 'ABARROTES',
            'unidad_base' => $this->request->getPost('unidad_base') ?: 'PZA',
            'unidades_por_empaque' => $this->request->getPost('unidades_por_empaque') ?: 1,
        ];

        if (empty($data['nombre']) || $data['precio'] === '' || $data['precio'] === null) {
            return $this->jsonResponse(false, 'Nombre y precio son obligatorios.');
        }
        if (!is_numeric($data['stock_minimo']) || (float) $data['stock_minimo'] < 0 || ($data['stock'] !== null && (!is_numeric($data['stock']) || (float) $data['stock'] < 0))) {
            return $this->jsonResponse(false, 'El stock y el mínimo de stock deben ser números iguales o mayores que cero.');
        }

        $archivos = $this->request->getFileMultiple('imagenes') ?? [];
        $archivos = array_values(array_filter($archivos, static fn($archivo) => $archivo && $archivo->getError() !== UPLOAD_ERR_NO_FILE));

        foreach ($archivos as $archivo) {
            if (!$archivo->isValid()) {
                return $this->jsonResponse(false, 'No se pudo recibir una de las imágenes. Inténtalo de nuevo.');
            }
            $tipoMime = $archivo->getMimeType();
            if (!in_array($tipoMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                return $this->jsonResponse(false, 'Selecciona únicamente archivos de imagen válidos.');
            }
        }

        $eliminarImagenes = array_map('intval', $this->request->getPost('eliminar_imagenes') ?? []);

        if ($id) {
            $this->Modelo->update($id, $data);
            $seEliminaron = false;
            foreach ($eliminarImagenes as $idImagen) {
                $imagen = db_connect()->table('producto_imagenes')
                    ->where('id_imagen', $idImagen)->where('id_producto', $id)->get()->getRow();
                if (!$imagen) {
                    continue;
                }
                db_connect()->table('producto_imagenes')->where('id_imagen', $idImagen)->delete();
                $seEliminaron = true;
                $ruta = FCPATH . 'assets/img/' . ltrim($imagen->ruta_imagen, '/');
                if (str_starts_with($imagen->ruta_imagen, 'productos/') && is_file($ruta)) {
                    unlink($ruta);
                }
            }
            if ($seEliminaron) {
                $primeraImagen = db_connect()->table('producto_imagenes')->where('id_producto', $id)
                    ->orderBy('orden', 'ASC')->orderBy('id_imagen', 'ASC')->get()->getRow();
                $this->Modelo->update($id, ['ruta_imagen' => $primeraImagen->ruta_imagen ?? null]);
            }
        } else {
            $data['activo'] = 1;
            $id = $this->Modelo->insert($data, true);
        }

        foreach ($archivos as $archivo) {
            if ($archivo->hasMoved()) {
                continue;
            }
            $nombreNuevo = $archivo->getRandomName();
            $archivo->move(FCPATH . 'assets/img/productos', $nombreNuevo);
            $rutaImagen = 'productos/' . $nombreNuevo;
            $this->Modelo->agregarImagen((int) $id, $rutaImagen);

            // Conserva una imagen principal para compatibilidad con módulos existentes.
            if (empty($data['ruta_imagen'])) {
                $this->Modelo->update($id, ['ruta_imagen' => $rutaImagen]);
                $data['ruta_imagen'] = $rutaImagen;
            }
        }

        return $this->jsonResponse(true, 'Producto guardado correctamente.');
    }
}
