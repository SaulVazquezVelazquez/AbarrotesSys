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
    <div class="kpi-card <?= $solicitudes_pendientes > 0 ? 'alerta' : '' ?>">
        <div class="kpi-label">Solicitudes por revisar</div>
        <div class="kpi-value"><?= esc($solicitudes_pendientes) ?></div>
    </div>
</div>

<section class="dashboard-actions">
    <a href="<?= base_url('admin/solicitudes') ?>" class="dashboard-action"><span class="dashboard-action-icon action-blue"><i class="fa-solid fa-inbox"></i></span><span><strong>Revisar solicitudes</strong><small><?= esc($solicitudes_pendientes) ?> pendiente(s)</small></span><i class="fa-solid fa-arrow-right action-arrow"></i></a>
    <a href="<?= base_url('admin/ventas') ?>" class="dashboard-action"><span class="dashboard-action-icon action-green"><i class="fa-solid fa-cash-register"></i></span><span><strong>Registrar una venta</strong><small>Ir al punto de venta</small></span><i class="fa-solid fa-arrow-right action-arrow"></i></a>
</section>

<div class="chart-card">
    <h2>Flujo de caja (últimos 30 días)</h2>
    <canvas id="chartFlujo" height="80"></canvas>
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
