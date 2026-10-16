<?php

namespace App\Controllers;

use App\Models\MProductos;

class AdminProductos extends BaseController
{
    protected MProductos $Modelo;
    private ?array $categoriasExistentesImportacion = null;

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
        if ($check = $this->requireAdmin(true)) return $check;

        $file = $this->request->getFile('archivo_productos');
        if (!$file || !$file->isValid()) return $this->jsonResponse(false, 'Selecciona un archivo Excel o CSV válido.');
        $extension = strtolower($file->getClientExtension());
        try {
            $rows = $extension === 'xlsx'
                ? $this->leerXlsx($file->getTempName())
                : ($extension === 'csv' ? $this->leerCsv($file->getTempName()) : null);
            if ($rows === null) throw new \RuntimeException('Formato no válido. Usa .xlsx o .csv.');
            if (count($rows) > 5001) throw new \RuntimeException('El archivo supera el límite de 5,000 productos.');

            $headers = array_map([$this, 'normalizarEncabezado'], array_shift($rows) ?? []);
            $required = ['nombre', 'precio', 'departamento', 'unidad_base'];
            if (array_diff($required, $headers)) {
                throw new \RuntimeException('Faltan encabezados obligatorios: nombre, precio, departamento y unidad_base. Descarga la plantilla para ver el formato.');
            }

            $db = db_connect();
            $filas = [];
            $codigosVistos = [];
            $plusVistos = [];
            foreach ($rows as $i => $cells) {
                if (count(array_filter($cells, static fn($v) => trim((string) $v) !== '')) === 0) continue;
                $raw = [];
                foreach ($headers as $col => $key) if ($key !== '') $raw[$key] = trim((string) ($cells[$col] ?? ''));
                [$data, $errores] = $this->validarFilaImportada($raw, $db, $codigosVistos, $plusVistos);
                $filas[] = ['numero' => $i + 2, 'datos' => $data, 'errores' => $errores];
            }
            if (!$filas) return $this->jsonResponse(false, 'El archivo no contiene productos para importar.');
            $conError = count(array_filter($filas, static fn($fila) => !empty($fila['errores'])));
            return $this->response->setJSON([
                'respuesta' => true,
                'mensaje' => $conError ? "Se encontraron {$conError} fila(s) con errores. Corrígelas y vuelve a guardar." : 'Archivo validado. Revisa los productos y guárdalos.',
                'filas' => $filas,
                'con_error' => $conError,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(false, $e->getMessage());
        }
    }

    public function guardarImportacion()
    {
        if ($check = $this->requireAdmin(true)) return $check;
        $productos = json_decode((string) $this->request->getPost('productos'), true);
        if (!is_array($productos) || !$productos || count($productos) > 5000) {
            return $this->jsonResponse(false, 'No hay productos válidos para guardar.');
        }

        $db = db_connect();
        $filas = [];
        $codigosVistos = [];
        $plusVistos = [];
        foreach ($productos as $i => $raw) {
            if (!is_array($raw)) $raw = [];
            [$data, $errores] = $this->validarFilaImportada($raw, $db, $codigosVistos, $plusVistos);
            $filas[] = ['numero' => $i + 2, 'datos' => $data, 'errores' => $errores];
        }
        $conError = count(array_filter($filas, static fn($fila) => !empty($fila['errores'])));
        if ($conError) {
            return $this->response->setJSON([
                'respuesta' => false,
                'mensaje' => "Aún hay {$conError} fila(s) por corregir. No se guardó ningún producto.",
                'filas' => $filas,
                'con_error' => $conError,
            ]);
        }

        $db->transBegin();
        try {
            foreach ($filas as $fila) {
                if (!$db->table('productos')->insert($fila['datos'])) {
                    throw new \RuntimeException('No se pudo guardar el producto de la fila ' . $fila['numero'] . '. No se guardó ningún producto.');
                }
            }
            if ($db->transStatus() === false) throw new \RuntimeException('La base de datos rechazó la importación. No se guardó ningún producto.');
            $db->transCommit();
            return $this->jsonResponse(true, count($filas) . ' producto(s) importado(s) correctamente.', ['total' => count($filas)]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->jsonResponse(false, $e->getMessage());
        }
    }

    private function validarFilaImportada(array $row, $db, array &$codigosVistos, array &$plusVistos): array
    {
        $name = trim((string) ($row['nombre'] ?? ''));
        $price = $this->normalizarPrecioImportado((string) ($row['precio'] ?? ''));
        $stock = str_replace(',', '.', trim((string) ($row['stock'] ?? '0')));
        $stockMinimo = str_replace(',', '.', trim((string) ($row['stock_minimo'] ?? '0')));
        $pack = str_replace(',', '.', trim((string) ($row['unidades_por_empaque'] ?? '1')));
        $barcode = trim((string) ($row['codigo_barras'] ?? ''));
        $plu = trim((string) ($row['plu'] ?? ''));
        $department = mb_strtoupper(trim((string) ($row['departamento'] ?? '')));
        $department = str_replace('Í', 'I', $department);
        $unit = strtoupper(trim((string) ($row['unidad_base'] ?? '')));
        $categoryInput = trim((string) ($row['categoria'] ?? ''));
        $category = $this->coincidenciaCategoria($categoryInput, $db);
        $errors = [];

        if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Nombre obligatorio (máximo 150 caracteres).';
        if ($price === null || $price <= 0) $errors[] = 'Precio debe ser mayor que cero. Usa un importe válido en pesos mexicanos.';
        if (!in_array($department, ['ABARROTES', 'VERDULERIA'], true)) $errors[] = 'Departamento debe ser ABARROTES o VERDULERIA.';
        if (!in_array($unit, ['PZA', 'KG', 'G', 'L'], true)) $errors[] = 'Unidad debe ser PZA, KG, G o L.';
        if (!is_numeric($stock) || (float) $stock < 0) $errors[] = 'Existencia debe ser igual o mayor que cero.';
        if (!is_numeric($stockMinimo) || (float) $stockMinimo < 0) $errors[] = 'Stock mínimo debe ser igual o mayor que cero.';
        if (!is_numeric($pack) || (float) $pack <= 0) $errors[] = 'Unidades por empaque debe ser mayor que cero.';
        if (strlen($barcode) > 32) $errors[] = 'Código de barras supera 32 caracteres.';
        if (strlen($plu) > 8) $errors[] = 'PLU supera 8 caracteres.';
        if ($barcode !== '') {
            if (isset($codigosVistos[$barcode])) $errors[] = 'Código de barras repetido dentro del archivo.';
            elseif ($db->table('productos')->where('codigo_barras', $barcode)->countAllResults() > 0) $errors[] = 'Código de barras ya registrado.';
            $codigosVistos[$barcode] = true;
        }
        if ($plu !== '') {
            if (isset($plusVistos[$plu])) $errors[] = 'PLU repetido dentro del archivo.';
            elseif ($db->table('productos')->where('plu', $plu)->countAllResults() > 0) $errors[] = 'PLU ya registrado.';
            $plusVistos[$plu] = true;
        }

        return [[
            'nombre' => mb_substr($name, 0, 150),
            'descripcion' => trim((string) ($row['descripcion'] ?? '')) ?: null,
            'categoria' => $category ?: null,
            'precio' => $price ?? 0,
            'stock' => is_numeric($stock) ? (float) $stock : 0,
            'stock_minimo' => is_numeric($stockMinimo) ? (float) $stockMinimo : 0,
            'codigo_barras' => $barcode ?: null,
            'plu' => $plu ?: null,
            'departamento' => $department,
            'unidad_base' => $unit,
            'unidades_por_empaque' => is_numeric($pack) ? (float) $pack : 1,
            'activo' => 1,
        ], array_values(array_unique($errors))];
    }

    private function normalizarPrecioImportado(string $valor): ?float
    {
        $valor = trim(str_replace(["\u{00A0}", "\u{202F}"], ' ', $valor));
        if ($valor === '') return null;

        $valor = preg_replace('/(?:MXN|MX\$|M\.?N\.?|PESOS?)/iu', '', $valor) ?? '';
        $valor = str_replace(['$', ' ', "\t", "\r", "\n"], '', $valor);
        if ($valor === '' || !preg_match('/^[+-]?[0-9.,]+$/', $valor)) return null;

        $signo = '';
        if ($valor[0] === '-' || $valor[0] === '+') {
            $signo = $valor[0] === '-' ? '-' : '';
            $valor = substr($valor, 1);
        }
        if ($valor === '' || !preg_match('/\d/', $valor)) return null;

        $ultimaComa = strrpos($valor, ',');
        $ultimoPunto = strrpos($valor, '.');
        if ($ultimaComa !== false && $ultimoPunto !== false) {
            $decimal = $ultimaComa > $ultimoPunto ? ',' : '.';
            $miles = $decimal === ',' ? '.' : ',';
            $valor = str_replace($miles, '', $valor);
            $valor = str_replace($decimal, '.', $valor);
        } elseif ($ultimaComa !== false || $ultimoPunto !== false) {
            $separador = $ultimaComa !== false ? ',' : '.';
            $partes = explode($separador, $valor);
            $decimales = strlen((string) end($partes));
            $gruposDeMiles = count($partes) > 1 && strlen($partes[0]) >= 1 && strlen($partes[0]) <= 3;
            foreach (array_slice($partes, 1) as $grupo) $gruposDeMiles = $gruposDeMiles && strlen($grupo) === 3;
            if ($gruposDeMiles && $decimales === 3) {
                $valor = implode('', $partes);
            } elseif (count($partes) > 2) {
                $fraccion = array_pop($partes);
                $valor = implode('', $partes) . '.' . $fraccion;
            } else {
                $valor = str_replace($separador, '.', $valor);
            }
        }

        if (!is_numeric($valor)) return null;
        return round((float) ($signo . $valor), 2);
    }

    private function coincidenciaCategoria(string $categoria, $db): string
    {
        if ($categoria === '') return '';
        if ($this->categoriasExistentesImportacion === null) {
            $this->categoriasExistentesImportacion = array_values(array_filter(array_map(
                static fn($fila) => trim((string) $fila['categoria']),
                $db->query('SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND trim(categoria) <> \'\'')->getResultArray()
            ), static fn($valor) => $valor !== ''));
        }

        $buscada = mb_strtolower($categoria, 'UTF-8');
        foreach ($this->categoriasExistentesImportacion as $existente) {
            if (mb_strtolower($existente, 'UTF-8') === $buscada) return $existente;
        }
        return $categoria;
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
