<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Relay') }} | Fleet overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#F3F5FA;--side:#1A2342;--card:#fff;--ink:#131A2C;--muted:#636E88;--rule:#DFE4EE;--blue:#3355D1;--pale:#E3E9FB;--good:#16794A;--bad:#B3261E;--idle:#6B7385}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Onest",system-ui,sans-serif;font-size:15px;color:var(--ink);background:var(--bg);font-variant-numeric:tabular-nums}
        button,input,select{font:inherit;color:inherit}
        :focus-visible{outline:3px solid var(--blue);outline-offset:2px;border-radius:4px}
        .app{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
        .side{background:var(--side);color:#AEB8D6;padding:22px 14px}
        .brand{color:#fff;font-weight:700;font-size:1.3rem;padding:0 10px 18px}
        .side a{display:block;color:inherit;text-decoration:none;padding:10px 12px;border-radius:8px;font-weight:500}
        .side a[aria-current]{background:#fff;color:var(--side)}
        main{padding:26px 30px 48px;min-width:0}
        .head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:end;gap:16px;margin-bottom:22px}
        h1{margin:0;font-size:1.7rem} .sub{color:var(--muted);margin:4px 0 0}
        .seg{display:inline-flex;background:#fff;border:1px solid var(--rule);border-radius:10px;padding:3px}
        .seg button{border:0;background:none;padding:7px 14px;border-radius:7px;font-weight:600;color:var(--muted);cursor:pointer}
        .seg button[aria-pressed=true]{background:var(--side);color:#fff}
        .kpis{display:grid;grid-template-columns:repeat(4,1fr);background:#fff;border:1px solid var(--rule);border-radius:14px;margin-bottom:18px}
        .kpi{padding:18px 20px;border-right:1px solid var(--rule)} .kpi:last-child{border-right:0}
        .kpi span{color:var(--muted)} .kpi strong{display:block;font-size:1.8rem;margin:4px 0 2px}
        .up{color:var(--good)} .down{color:var(--bad)} .delta{font-size:.9rem;font-weight:600}
        .row{display:grid;grid-template-columns:1.7fr 1fr;gap:18px;margin-bottom:18px}
        .panel{background:#fff;border:1px solid var(--rule);border-radius:14px;padding:20px;min-width:0}
        .panel h2{margin:0;font-size:1.05rem} .panel p{color:var(--muted);margin:2px 0 14px}
        svg{width:100%;height:240px;display:block}
        .zones{list-style:none;margin:0;padding:0;display:grid;gap:12px}
        .zones li{display:grid;grid-template-columns:96px 1fr 52px;gap:10px;align-items:center}
        .track{height:14px;background:var(--pale);border-radius:0 4px 4px 0}.fill{height:100%;background:var(--blue);border-radius:0 4px 4px 0}
        .zones b{text-align:right}
        table{width:100%;border-collapse:collapse} th,td{text-align:left;padding:10px;border-bottom:1px solid var(--rule)}
        th{color:var(--muted);font-weight:600;font-size:.9rem} .num{text-align:right}
        .dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:7px;background:currentColor}
        .s-delivering{color:var(--blue)} .s-available{color:var(--good)} .s-delayed{color:var(--bad)} .s-offline{color:var(--idle)}
        .s-text{color:var(--ink)}
        @media (max-width:1000px){.row{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(2,1fr)}}
        @media (max-width:760px){.app{grid-template-columns:1fr}.side{display:none}main{padding:18px 14px}}
    </style>
</head>
<body>
<div class="app">
    <nav class="side" aria-label="Sections">
        <div class="brand">{{ config('app.name', 'Relay') }}</div>
        <a href="{{ route('dashboard') }}" aria-current="page">Overview</a>
        <a href="{{ route('api.orders.index') }}">Orders API</a>
        <a href="{{ route('api.couriers.index') }}">Couriers API</a>
    </nav>

    <main>
        <div class="head">
            <div>
                <h1>Fleet overview</h1>
                <p class="sub" id="period"></p>
            </div>
            <div class="seg" role="group" aria-label="Date range">
                @foreach ([7, 30, 90] as $days)
                    <button type="button" data-days="{{ $days }}" aria-pressed="{{ $days === 30 ? 'true' : 'false' }}">{{ $days }} days</button>
                @endforeach
            </div>
        </div>

        <section class="kpis" id="kpis" aria-label="Key figures"></section>

        <div class="row">
            <section class="panel">
                <h2>Revenue per day</h2>
                <p id="avg"></p>
                <svg id="chart" role="img" aria-label="Daily revenue for the selected period"></svg>
            </section>
            <section class="panel">
                <h2>Deliveries by zone</h2>
                <p>Completed orders in the selected period</p>
                <ul class="zones" id="zones"></ul>
            </section>
        </div>

        <section class="panel">
            <h2>Couriers</h2>
            <p>Sorted by completed deliveries</p>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Courier</th><th>Vehicle</th><th>Status</th><th class="num">Deliveries</th><th class="num">On time</th><th class="num">Rating</th></tr></thead>
                    <tbody id="couriers"><tr><td colspan="6">Loading couriers…</td></tr></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
    const STATS_URL = @json(route('api.stats'));
    const COURIERS_URL = @json(route('api.couriers.index'));
    const $ = id => document.getElementById(id);
    const fmt = n => Number(n).toLocaleString('en-US');
    const usd = cents => '$' + fmt(Math.round(cents / 100));
    const day = s => new Date(s + 'T00:00:00').toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function tile(label, value, now, before, lowerIsBetter = false, points = false) {
        if (before === null || before === 0 || now === null) {
            return `<div class="kpi"><span>${label}</span><strong>${value}</strong><span class="delta">No earlier data</span></div>`;
        }
        const diff = points ? now - before : (now - before) / before * 100;
        const good = lowerIsBetter ? diff < 0 : diff > 0;
        const text = (diff > 0 ? '+' : '−') + Math.abs(diff).toFixed(1) + (points ? ' pts' : '%');
        return `<div class="kpi"><span>${label}</span><strong>${value}</strong><span class="delta ${good ? 'up' : 'down'}">${diff > 0 ? '▲' : '▼'} ${text}</span></div>`;
    }

    function render(s) {
        const t = s.totals, p = s.previous;
        $('period').textContent = `${day(s.period.from)} to ${day(s.period.to)}, compared with the ${s.period.days} days before`;
        $('kpis').innerHTML =
            tile('Deliveries', fmt(t.deliveries), t.deliveries, p.deliveries) +
            tile('Revenue', usd(t.revenue_cents), t.revenue_cents, p.revenue_cents) +
            tile('On time', t.on_time_rate === null ? '—' : t.on_time_rate + '%', t.on_time_rate, p.on_time_rate, false, true) +
            tile('Average delivery', t.avg_delivery_minutes === null ? '—' : t.avg_delivery_minutes + ' min', t.avg_delivery_minutes, p.avg_delivery_minutes, true);
        $('avg').textContent = 'Average ' + usd(t.revenue_cents / s.period.days) + ' a day';
        drawChart(s.daily);
        const max = Math.max(1, ...s.zones.map(z => z.deliveries));
        $('zones').innerHTML = s.zones.length
            ? s.zones.map(z => `<li><span>${esc(z.zone)}</span><div class="track"><div class="fill" style="width:${z.deliveries / max * 100}%"></div></div><b>${fmt(z.deliveries)}</b></li>`).join('')
            : '<li>No deliveries in this period yet.</li>';
    }

    function drawChart(daily) {
        const svg = $('chart'), W = svg.clientWidth || 640, H = 240, m = {t: 10, r: 10, b: 24, l: 46};
        svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
        const top = Math.max(1000, Math.ceil(Math.max(...daily.map(d => d.revenue_cents)) / 100000) * 100000);
        const x = i => m.l + i / Math.max(1, daily.length - 1) * (W - m.l - m.r);
        const y = v => H - m.b - v / top * (H - m.t - m.b);
        let g = '';
        for (let k = 0; k <= 4; k++) {
            const v = top / 4 * k;
            g += `<line x1="${m.l}" x2="${W - m.r}" y1="${y(v)}" y2="${y(v)}" stroke="#DFE4EE"/><text x="${m.l - 8}" y="${y(v) + 4}" text-anchor="end" font-size="12" fill="#636E88">${usd(v)}</text>`;
        }
        const step = Math.ceil(daily.length / 6);
        daily.forEach((d, i) => { if (i % step === 0) g += `<text x="${x(i)}" y="${H - 6}" text-anchor="middle" font-size="12" fill="#636E88">${day(d.date)}</text>`; });
        const path = daily.map((d, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(d.revenue_cents).toFixed(1)).join('');
        svg.innerHTML = g + `<path d="${path}" fill="none" stroke="#3355D1" stroke-width="2" stroke-linejoin="round"/>`;
    }

    async function loadStats(days) {
        const res = await fetch(`${STATS_URL}?days=${days}`, {headers: {Accept: 'application/json'}});
        if (res.ok) render((await res.json()).data);
    }

    async function loadCouriers() {
        const res = await fetch(COURIERS_URL, {headers: {Accept: 'application/json'}});
        if (!res.ok) { $('couriers').innerHTML = '<tr><td colspan="6">Could not load couriers. Refresh the page to try again.</td></tr>'; return; }
        const list = (await res.json()).data;
        $('couriers').innerHTML = list.map(c => `<tr>
            <td><strong>${esc(c.name)}</strong></td><td>${esc(c.vehicle)}</td>
            <td><span class="s-${c.status}"><i class="dot"></i></span><span class="s-text">${esc(c.status_label)}</span></td>
            <td class="num">${fmt(c.deliveries ?? 0)}</td><td class="num">${c.on_time_rate ?? '—'}${c.on_time_rate === null ? '' : '%'}</td><td class="num">${c.rating.toFixed(1)}</td></tr>`).join('');
    }

    document.querySelectorAll('.seg button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.seg button').forEach(x => x.setAttribute('aria-pressed', x === b));
        loadStats(b.dataset.days);
    }));

    render(@json($initial));
    loadCouriers();
</script>
</body>
</html>
