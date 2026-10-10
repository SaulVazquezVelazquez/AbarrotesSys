<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Reportes</h1>
    <a href="<?= base_url('admin/reportes/exportarCartera') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Exportar cartera (CSV/Excel)</a>
</div>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Fiado este mes</div><div class="kpi-value">$<?= number_format($resumen['total_fiado_mes'], 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Cobrado este mes</div><div class="kpi-value">$<?= number_format($resumen['total_cobrado_mes'], 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Cartera total</div><div class="kpi-value">$<?= number_format($resumen['cartera_total'], 2) ?></div></div>
</div>

<div class="chart-card">
    <h2>Flujo de abonos registrados</h2>
    <canvas id="chartFlujo" height="80"></canvas>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const cobrado = <?= json_encode($flujo_caja) ?>;
new Chart(document.getElementById('chartFlujo'), {
    type: 'line',
    data: {
        labels: cobrado.map(c=>c.dia),
        datasets: [{ label:'Abonos registrados', data: cobrado.map(c => ({x:c.dia, y:parseFloat(c.total_cobrado)})), borderColor:'#2fb377', backgroundColor:'rgba(47,179,119,.12)', fill:true, tension:.3 }]
    },
    options: { scales:{ x:{type:'category', ticks:{color:'#9aa7b5'}, grid:{color:'rgba(255,255,255,.05)'}}, y:{ticks:{color:'#9aa7b5'},grid:{color:'rgba(255,255,255,.05)'}} }, plugins:{legend:{labels:{color:'#eef2f6'}}} }
});
</script>

<?= $this->include('admin/layout_footer') ?>
