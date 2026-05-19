const App = { user: null, currentSim: null, chart: null, chart2: null, simRunning: false };
const $ = id => document.getElementById(id);
const qs = sel => document.querySelector(sel);
const qsa = sel => document.querySelectorAll(sel);

document.addEventListener('DOMContentLoaded', async () => {
  initSliders(); initTabs(); initAuthForms(); initSystemOrderToggle();
  await checkSession();
});

async function checkSession() {
  try {
    const res = await fetch('php/auth.php?action=check');
    const data = await res.json();
    if (data.logged_in) { App.user = { id: data.user_id, username: data.username }; showApp(); } 
    else { showAuth(); }
  } catch { showAuth(); }
}

function showAuth() {
  $('auth-page').classList.add('active'); $('app-page').classList.remove('active');
  $('app-page').style.display = 'none'; $('auth-page').style.display = '';
}

function showApp() {
  $('auth-page').style.display = 'none'; $('app-page').style.display = 'flex';
  $('app-page').classList.add('active'); $('app-header').style.display = '';
  $('user-display').textContent = App.user.username.toUpperCase();
  loadHistory();
  setTimeout(() => runSimulation(false), 300);
}

function initAuthForms() {
  qsa('.auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      qsa('.auth-tab').forEach(t => t.classList.remove('active'));
      qsa('.auth-form').forEach(f => f.classList.remove('active'));
      tab.classList.add('active'); $(tab.dataset.tab).classList.add('active');
      qsa('.alert').forEach(a => a.style.display = 'none');
    });
  });

  $('login-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]'); btn.disabled = true;
    const body = new FormData(); body.append('action', 'login'); body.append('username', $('login-username').value); body.append('password', $('login-password').value);
    try {
      const res = await fetch('php/auth.php', { method: 'POST', body }); const data = await res.json();
      if (data.success) { App.user = { id: data.user_id, username: data.username }; showApp(); } else { $('login-alert').style.display='flex'; $('login-alert').textContent=data.error; }
    } catch { $('login-alert').style.display='flex'; $('login-alert').textContent='Eroare'; }
    btn.disabled = false;
  });

  $('register-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]'); btn.disabled = true;
    const body = new FormData(); body.append('action', 'register'); body.append('username', $('reg-username').value); body.append('email', $('reg-email').value); body.append('password', $('reg-password').value);
    try {
      const res = await fetch('php/auth.php', { method: 'POST', body }); const data = await res.json();
      if (data.success) { App.user = { id: data.user_id, username: data.username }; showApp(); } else { $('register-alert').style.display='flex'; $('register-alert').textContent=data.error; }
    } catch { $('register-alert').style.display='flex'; $('register-alert').textContent='Eroare'; }
    btn.disabled = false;
  });
}

async function logout() { await fetch('php/auth.php?action=logout'); App.user = null; showAuth(); }

function initSliders() {
  const sliders = ['kp', 'ki', 'kd', 'tau', 'zeta', 'setpoint', 'simtime'];
  sliders.forEach((id) => {
    const slider = $(id), disp = $(id + '-val') || $(id === 'setpoint' ? 'sp-val' : null);
    if (slider && disp) {
      disp.textContent = parseFloat(slider.value).toFixed(2);
      slider.addEventListener('input', () => { disp.textContent = parseFloat(slider.value).toFixed(2); });
    }
  });
}

function initSystemOrderToggle() {
  const sel = $('system-order');
  if (!sel) return;
  sel.addEventListener('change', () => {
    const val = sel.value;
    $('order1-label').parentElement.style.display = (val === '1') ? '' : 'none';
    $('order2-params').style.display = (val === '2') ? '' : 'none';
    $('custom-tf-params').style.display = (val === 'custom') ? '' : 'none';
  });
}

function getParams() {
  return {
    Kp: parseFloat($('kp').value), Ki: parseFloat($('ki').value), Kd: parseFloat($('kd').value),
    systemOrder: $('system-order').value, 
    tau: parseFloat($('tau').value), tau2: parseFloat($('tau2') ? $('tau2').value : 0.5), zeta: parseFloat($('zeta').value),
    b2: parseFloat($('tf-b2') ? $('tf-b2').value : 0), b1: parseFloat($('tf-b1') ? $('tf-b1').value : 0), b0: parseFloat($('tf-b0') ? $('tf-b0').value : 1),
    a2: parseFloat($('tf-a2') ? $('tf-a2').value : 1), a1: parseFloat($('tf-a1') ? $('tf-a1').value : 2), a0: parseFloat($('tf-a0') ? $('tf-a0').value : 1),
    setpoint: parseFloat($('setpoint').value), simTime: parseFloat($('simtime').value), K: 1.0,
  };
}

function runSimulation(save = false) {
  if (App.simRunning) return;
  App.simRunning = true;
  setTimeout(() => {
    const params = getParams();
    const result = pidEngine.simulate(params);
    App.currentSim = { params, result };
    renderCharts(result, params.setpoint); updateMetrics(result.metrics);
    App.simRunning = false;
    if (save) saveSimulation(params, result);
  }, 50);
}

function renderCharts(result, setpoint) {
  const ctx1 = $('chart-response').getContext('2d'), ctx2 = $('chart-control').getContext('2d');
  if (App.chart) App.chart.destroy(); if (App.chart2) App.chart2.destroy();
  const spLine = result.t.map(() => setpoint);
  
  App.chart = new Chart(ctx1, {
    type: 'line', data: { labels: result.t, datasets: [
      { label: 'y(t) - Răspuns sistem', data: result.y, borderColor: '#00d4ff', borderWidth: 2, pointRadius: 0, tension: 0.3 },
      { label: `r(t) = ${setpoint}`, data: spLine, borderColor: '#ffb800', borderWidth: 1, borderDash: [6, 4], pointRadius: 0 }
    ]}, options: { responsive: true, maintainAspectRatio: false, animation: { duration: 400 }, plugins: { legend: { labels: { color: '#5a7a96', font: { size: 11 } } } }, scales: { x: { ticks: { color: '#2a4a64' } }, y: { ticks: { color: '#2a4a64' } } } }
  });

  App.chart2 = new Chart(ctx2, {
    type: 'line', data: { labels: result.t, datasets: [
      { label: 'u(t) - Semnal', data: result.u, borderColor: '#00ff9d', borderWidth: 1.5, pointRadius: 0, tension: 0.2 },
      { label: 'e(t) - Eroare', data: result.e, borderColor: '#ff3d5a', borderWidth: 1.5, pointRadius: 0, tension: 0.2 },
    ]}, options: { responsive: true, maintainAspectRatio: false, animation: { duration: 400 }, plugins: { legend: { labels: { color: '#5a7a96', font: { size: 11 } } } }, scales: { x: { ticks: { color: '#2a4a64' } }, y: { ticks: { color: '#2a4a64' } } } }
  });
}

function updateMetrics(m) {
  if (!m) return;
  $('m-steady').textContent = m.steadyState != null ? m.steadyState : '—';
  $('m-overshoot').textContent = m.overshoot != null ? m.overshoot + '%' : '—';
  $('m-settle').textContent = m.settlingTime != null ? m.settlingTime + ' s' : '—';
}

async function saveSimulation(params, result) {
  if (!App.user) return;
  const step = Math.ceil(result.t.length / 200);
  const simData = result.t.filter((_, i) => i % step === 0).map((time, i) => { const idx = i * step; return { t: time, y: result.y[idx] ?? 0, u: result.u[idx] ?? 0, e: result.e[idx] ?? 0 }; });

  const payload = {
    title: `Sistem ${params.systemOrder} | Kp=${params.Kp} Ki=${params.Ki}`,
    system_order: params.systemOrder === 'custom' ? 0 : params.systemOrder,
    kp: params.Kp, ki: params.Ki, kd: params.Kd, tau: params.tau, tau2: params.tau2, zeta: params.zeta,
    tf_b2: params.b2, tf_b1: params.b1, tf_b0: params.b0, tf_a2: params.a2, tf_a1: params.a1, tf_a0: params.a0,
    setpoint: params.setpoint, sim_time: params.simTime, steady_state_value: result.metrics.steadyState, overshoot: result.metrics.overshoot, settling_time: result.metrics.settlingTime, sim_data: simData,
  };

  try {
    await fetch('php/simulation.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    loadHistory(); alert("Salvat cu succes!");
  } catch { alert("Eroare la salvare"); }
}

async function loadHistory() {
  const tbody = $('history-body'); if (!tbody) return;
  try {
    const res = await fetch('php/simulation.php?action=list'); const data = await res.json();
    if (!data.success || !data.simulations.length) { tbody.innerHTML = '<tr><td colspan="8">Fără simulări.</td></tr>'; return; }
    tbody.innerHTML = data.simulations.map(s => `<tr><td>#${s.id}</td><td><span class="badge badge-1">${s.system_order == 0 ? 'Custom' : 'Ord. ' + s.system_order}</span></td><td class="mono">${s.kp} / ${s.ki} / ${s.kd}</td><td class="mono">${s.setpoint}</td><td class="mono">${s.steady_state_value ?? '—'}</td><td class="mono">${s.overshoot != null ? s.overshoot + '%' : '—'}</td><td class="mono">${new Date(s.created_at).toLocaleDateString()}</td><td><button class="btn btn-ghost btn-sm" onclick="loadSimulation(${s.id})">↩</button> <button class="btn btn-danger btn-sm" onclick="deleteSimulation(${s.id})">✕</button></td></tr>`).join('');
  } catch { tbody.innerHTML = '<tr><td colspan="8">Eroare.</td></tr>'; }
}

async function loadSimulation(id) {
  try {
    const res = await fetch(`php/simulation.php?action=get&id=${id}`); const data = await res.json();
    if (!data.success) return;
    const s = data.simulation;
    ['kp','ki','kd','tau','setpoint','simtime','zeta'].forEach(k => { if($(k)) { $(k).value = s[k] || $(k).value; if($(k+'-val')) $(k+'-val').textContent = parseFloat($(k).value).toFixed(2); }});
    $('system-order').value = s.system_order == 0 ? 'custom' : s.system_order;
    
    if (s.system_order == 0) {
      $('tf-b2').value = s.tf_b2; $('tf-b1').value = s.tf_b1; $('tf-b0').value = s.tf_b0;
      $('tf-a2').value = s.tf_a2; $('tf-a1').value = s.tf_a1; $('tf-a0').value = s.tf_a0;
    }
    $('system-order').dispatchEvent(new Event('change'));
    runSimulation(false); qsa('.tab-btn')[0].click();
  } catch { alert("Eroare incarcare"); }
}

async function deleteSimulation(id) {
  if (!confirm(`Ștergi simularea #${id}?`)) return;
  const body = new FormData(); body.append('action', 'delete'); body.append('id', id);
  await fetch('php/simulation.php', { method: 'POST', body }); loadHistory();
}

function initTabs() { qsa('.tab-btn').forEach(btn => { btn.addEventListener('click', () => { const target = btn.dataset.tab; qsa('.tab-btn').forEach(b => b.classList.remove('active')); qsa('.tab-content').forEach(c => c.classList.remove('active')); btn.classList.add('active'); $(target).classList.add('active'); if (target === 'tab-history') loadHistory(); }); }); }