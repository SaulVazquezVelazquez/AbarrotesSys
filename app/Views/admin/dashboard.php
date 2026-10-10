<?= $this->include('admin/layout_header') ?>

<h1>Dashboard</h1>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Fiado este mes</div>
        <div class="kpi-value">$<?= number_format($resumen['total_fiado_mes'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Cobrado este mes</div>
        <div class="kpi-value">$<?= number_format($resumen['total_cobrado_mes'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Cartera total</div>
        <div class="kpi-value">$<?= number_format($resumen['cartera_total'], 2) ?></div>
    </div>
    <div class="kpi-card <?= $resumen['porcentaje_morosidad'] > 20 ? 'alerta' : '' ?>">
        <div class="kpi-label">% Morosidad</div>
        <div class="kpi-value"><?= esc($resumen['porcentaje_morosidad']) ?>%</div>
    </div>
    <div class="kpi-card <?= $solicitudes_pendientes > 0 ? 'alerta' : '' ?>">
        <div class="kpi-label">Solicitudes por revisar</div>
        <div class="kpi-value"><?= esc($solicitudes_pendientes) ?></div>
    </div>
</div>

<section class="dashboard-actions">
    <a href="<?= base_url('admin/solicitudes') ?>" class="dashboard-action"><span class="dashboard-action-icon action-blue"><i class="fa-solid fa-inbox"></i></span><span><strong>Revisar solicitudes</strong><small><?= esc($solicitudes_pendientes) ?> pendiente(s)</small></span><i class="fa-solid fa-arrow-right action-arrow"></i></a>
    <a href="<?= base_url('admin/cobranza') ?>" class="dashboard-action"><span class="dashboard-action-icon action-orange"><i class="fa-solid fa-phone-volume"></i></span><span><strong>Seguimiento de cobranza</strong><small><?= esc($alertas_operativas['vencidas']['total']) ?> cuenta(s) vencida(s)</small></span><i class="fa-solid fa-arrow-right action-arrow"></i></a>
    <a href="<?= base_url('admin/ventas') ?>" class="dashboard-action"><span class="dashboard-action-icon action-green"><i class="fa-solid fa-cash-register"></i></span><span><strong>Registrar una venta</strong><small>Ir al punto de venta</small></span><i class="fa-solid fa-arrow-right action-arrow"></i></a>
</section>

<div class="kpi-grid dashboard-alert-grid">
    <a class="kpi-card <?= $alertas_operativas['vencidas']['total'] > 0 ? 'alerta' : '' ?> kpi-link" href="<?= base_url('admin/cobranza') ?>"><div class="kpi-label">Cuentas vencidas</div><div class="kpi-value"><?= esc($alertas_operativas['vencidas']['total']) ?></div><small>$<?= number_format($alertas_operativas['vencidas']['monto'], 2) ?> por cobrar</small></a>
    <a class="kpi-card kpi-link" href="<?= base_url('admin/cobranza') ?>"><div class="kpi-label">Vencen en 7 días</div><div class="kpi-value"><?= esc($alertas_operativas['por_vencer']['total']) ?></div><small>$<?= number_format($alertas_operativas['por_vencer']['monto'], 2) ?> programados</small></a>
    <a class="kpi-card <?= $alertas_operativas['intenciones_pago'] > 0 ? 'alerta' : '' ?> kpi-link" href="<?= base_url('admin/cobranza') ?>#avisos-pago"><div class="kpi-label">Avisos de pago por confirmar</div><div class="kpi-value"><?= esc($alertas_operativas['intenciones_pago']) ?></div><small>Revisar y registrar abonos</small></a>
</div>

<div class="chart-card">
    <h2>Flujo de caja (últimos 30 días)</h2>
    <canvas id="chartFlujo" height="80"></canvas>
</div>

<?php
function dotSemaforo($semaforo) {
    $clase = 'dot-' . strtolower($semaforo ?: 'verde');
    return '<span class="semaforo-dot ' . $clase . '"></span>';
}
?>

<div class="chart-card">
    <h2>Clientes en semáforo amarillo/rojo</h2>
    <table class="admin-table">
        <thead><tr><th>Semáforo</th><th>Cliente</th><th>Teléfono</th><th>Saldo actual</th><th>Próxima fecha límite</th></tr></thead>
        <tbody>
            <?php foreach ($clientes_riesgo as $c): ?>
            <tr>
                <td><?= dotSemaforo($c->semaforo) ?><?= esc($c->semaforo) ?></td>
                <td><a class="row-link" href="<?= base_url('admin/clientes/' . $c->id_cliente) ?>"><?= esc($c->nombre_completo) ?></a></td>
                <td><?= esc($c->telefono) ?></td>
                <td>$<?= number_format($c->saldo_actual, 2) ?></td>
                <td><?= esc($c->proxima_fecha_limite) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($clientes_riesgo)): ?>
                <tr><td colspan="5">Todos tus clientes están al corriente. 🎉</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const flujoData = <?= json_encode($flujo_caja) ?>;
new Chart(document.getElementById('chartFlujo'), {
    type: 'line',
    data: {
        labels: flujoData.map(f => f.dia),
        datasets: [{ label:'Cobrado ($)', data: flujoData.map(f => parseFloat(f.total_cobrado)), borderColor:'#315cf5', backgroundColor:'rgba(49,92,245,.12)', fill:true, tension:.3 }]
    },
    options: {
        scales: { x:{ ticks:{color:'#718096'}, grid:{color:'rgba(35,55,90,.06)'} }, y:{ ticks:{color:'#718096'}, grid:{color:'rgba(35,55,90,.06)'} } },
        plugins: { legend:{ labels:{ color:'#354258' } } }
    }
});
</script>

<?= $this->include('admin/layout_footer') ?>
