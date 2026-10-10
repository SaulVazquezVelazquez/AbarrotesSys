<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Reportes</h1>
    <a href="<?= base_url('admin/reportes/exportarCartera') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-csv"></i> Exportar cartera (CSV/Excel)</a>
</div>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Fiado este mes</div><div class="kpi-value">$<?= number_format($resumen['total_fiado_mes'], 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Cobrado este mes</div><div class="kpi-value">$<?= number_format($resumen['total_cobrado_mes'], 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Cartera total</div><div class="kpi-value">$<?= number_format($resumen['cartera_total'], 2) ?></div></div>
    <div class="kpi-card <?= $resumen['porcentaje_morosidad'] > 20 ? 'alerta' : '' ?>"><div class="kpi-label">% Morosidad</div><div class="kpi-value"><?= esc($resumen['porcentaje_morosidad']) ?>%</div></div>
</div>

<div class="chart-card">
    <h2>Flujo real: cobrado vs. por cobrar (proyectado)</h2>
    <canvas id="chartFlujo" height="80"></canvas>
</div>

<div class="chart-card">
    <h2>Cartera por antigüedad</h2>
    <canvas id="chartAging" height="70"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const cobrado = <?= json_encode($flujo_caja) ?>;
const porCobrar = <?= json_encode($cartera_por_cobrar) ?>;
const aging = <?= json_encode($aging) ?>;

new Chart(document.getElementById('chartFlujo'), {
    type: 'line',
    data: {
        labels: [...new Set([...cobrado.map(c=>c.dia), ...porCobrar.map(p=>p.dia)])].sort(),
        datasets: [
            { label:'Cobrado (real)', data: cobrado.map(c => ({x:c.dia, y:parseFloat(c.total_cobrado)})), borderColor:'#2fb377', backgroundColor:'rgba(47,179,119,.12)', fill:true, tension:.3 },
            { label:'Por cobrar (proyectado)', data: porCobrar.map(p => ({x:p.dia, y:parseFloat(p.total_por_cobrar)})), borderColor:'#e0a83a', backgroundColor:'rgba(224,168,58,.12)', fill:true, tension:.3 }
        ]
    },
    options: { scales:{ x:{type:'category', ticks:{color:'#9aa7b5'}, grid:{color:'rgba(255,255,255,.05)'}}, y:{ticks:{color:'#9aa7b5'},grid:{color:'rgba(255,255,255,.05)'}} }, plugins:{legend:{labels:{color:'#eef2f6'}}} }
});

new Chart(document.getElementById('chartAging'), {
    type: 'bar',
    data: { labels: aging.map(a=>a.rango_atraso), datasets:[{ label:'Saldo', data: aging.map(a=>parseFloat(a.total_saldo)), backgroundColor:'#1f8a58' }] },
    options: { scales:{ x:{ticks:{color:'#9aa7b5'},grid:{display:false}}, y:{ticks:{color:'#9aa7b5'},grid:{color:'rgba(255,255,255,.05)'}} }, plugins:{legend:{display:false}} }
});
</script>

<?= $this->include('admin/layout_footer') ?>
