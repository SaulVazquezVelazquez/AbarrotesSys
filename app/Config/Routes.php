<?php

namespace Config;

$routes = Services::routes();

if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Catalogo');
$routes->setDefaultMethod('index');
$routes->setAutoRoute(false);
$routes->setTranslateURIDashes(false);
$routes->set404Override();

/*
* --------------------------------------------------------------------
* CATÁLOGO PÚBLICO (portada: cualquier visitante puede ver productos y
* enviar una solicitud de compra, sin necesidad de iniciar sesión)
* --------------------------------------------------------------------
*/
$routes->get('/', 'Catalogo::index');
$routes->get('healthz', 'Health::index');
$routes->post('catalogo/solicitar', 'Catalogo::solicitar');
$routes->post('catalogo/estado', 'Catalogo::estadoSolicitud');

/*
* --------------------------------------------------------------------
* PORTAL DEL CLIENTE YA REGISTRADO (consulta de saldo, acceso por PIN)
* --------------------------------------------------------------------
*/
$routes->get('portal', 'Portal::index');
$routes->post('portal/entrar', 'Portal::entrar');
$routes->get('portal/cuenta', 'Portal::cuenta');
$routes->get('portal/logout', 'Portal::logout');
$routes->get('portal/comprobante/(:num)', 'Portal::descargarComprobante/$1');

/*
* --------------------------------------------------------------------
* PANEL ADMINISTRATIVO (protegido por sesión + roles)
* --------------------------------------------------------------------
*/
$routes->group('admin', function ($routes) {
    $routes->get('login', 'WLogin::index');
    $routes->post('login/autenticar', 'WLogin::autenticar');
    $routes->get('logout', 'WLogin::logout');

    $routes->get('/', 'AdminDashboard::index');
    $routes->get('dashboard', 'AdminDashboard::index');

    // Punto de venta de Tienda y Abarrotes
    $routes->get('pos', 'AdminPOS::index');
    $routes->get('pos/buscar', 'AdminPOS::buscar');
    $routes->post('pos/producto-rapido', 'AdminPOS::productoRapido');
    $routes->post('pos/cobrar', 'AdminPOS::cobrar');
    $routes->get('pos/ticket/(:num)', 'AdminPOS::ticket/$1');
    $routes->post('pos/pausar', 'AdminPOS::pausar');
    $routes->get('pos/esperas', 'AdminPOS::esperas');
    $routes->post('pos/esperas/(:num)/recuperar', 'AdminPOS::recuperar/$1');
    $routes->post('pos/esperas/(:num)/eliminar', 'AdminPOS::eliminarEspera/$1');
    $routes->post('pos/merma', 'AdminPOS::merma');
    $routes->post('pos/entrada', 'AdminPOS::entrada');
    $routes->post('pos/corte', 'AdminPOS::corte');
    $routes->post('pos/cliente', 'AdminPOS::cliente');
    $routes->post('pos/abono', 'AdminPOS::abono');

    // Solicitudes de compra (catálogo público)
    $routes->get('solicitudes', 'AdminSolicitudes::index');
    $routes->get('solicitudes/(:num)', 'AdminSolicitudes::detalle/$1');
    $routes->post('solicitudes/(:num)/aprobar', 'AdminSolicitudes::aprobar/$1');
    $routes->post('solicitudes/(:num)/rechazar', 'AdminSolicitudes::rechazar/$1');

    // Clientes
    $routes->get('clientes', 'AdminClientes::index');
    $routes->get('clientes/buscarJson', 'AdminClientes::buscarJson');
    $routes->get('clientes/(:num)/foto', 'AdminClientes::foto/$1');
    $routes->get('clientes/(:num)', 'AdminClientes::ficha/$1');
    $routes->post('clientes/guardar', 'AdminClientes::guardar');
    $routes->post('clientes/(:num)/bloquear', 'AdminClientes::bloquear/$1');
    $routes->post('clientes/(:num)/desbloquear', 'AdminClientes::desbloquear/$1');

    // Productos
    $routes->get('productos', 'AdminProductos::index');
    $routes->post('productos/guardar', 'AdminProductos::guardar');
    $routes->get('productos/plantilla', 'AdminProductos::plantilla');
    $routes->post('productos/importar', 'AdminProductos::importar');
    $routes->post('productos/importar/guardar', 'AdminProductos::guardarImportacion');

    // Ventas (POS a crédito)
    $routes->get('ventas', 'AdminVentas::index');
    $routes->get('ventas/validarLimite', 'AdminVentas::validarLimite');
    $routes->post('ventas/guardar', 'AdminVentas::guardar');
    $routes->post('ventas/pedido/(:num)/estado', 'AdminVentas::actualizarPedido/$1');
    $routes->get('ventas/(:num)', 'AdminVentas::detalle/$1');
    $routes->get('ventas/(:num)/ticket', 'AdminVentas::ticket/$1');
    $routes->post('ventas/(:num)/cancelar', 'AdminVentas::cancelar/$1');

    // Pagos / abonos
    $routes->get('pagos', 'AdminPagos::index');
    $routes->post('pagos/guardar', 'AdminPagos::guardar');
    $routes->get('pagos/(:num)/comprobante', 'AdminPagos::comprobante/$1');

    // Reportes
    $routes->get('reportes', 'AdminReportes::index');
    $routes->get('reportes/exportarCartera', 'AdminReportes::exportarCarteraCsv');

    // Usuarios y roles (solo DUENO)
    $routes->get('usuarios', 'AdminUsuarios::index');
    $routes->post('usuarios/guardar', 'AdminUsuarios::guardar');

    // Auditoría
    $routes->get('auditoria', 'AdminAuditoria::index');
});
