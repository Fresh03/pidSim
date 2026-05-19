<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PID Simulator — Sisteme Automate</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app-wrapper">
  
  <header class="app-header" id="app-header" style="display:none">
    <a href="#" class="logo">
      <div class="logo-icon">Σ</div>
      <span class="logo-text">PID<span>SIM</span></span>
    </a>
    <div class="header-right">
      <div class="user-badge">USER: <span id="user-display">—</span></div>
      <button class="btn btn-ghost btn-sm" onclick="logout()">⏻ Deconectare</button>
    </div>
  </header>

  <div id="auth-page">
    <div class="auth-container fade-in">
      <div class="auth-logo">
        <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:8px">
          <div class="logo-icon" style="width:48px;height:48px;font-size:22px">Σ</div>
          <h1>PID<span>SIM</span></h1>
        </div>
        <p>SIMULATOR SISTEME AUTOMATE · REGULATOR PID</p>
      </div>

      <div class="panel">
        <div class="auth-tabs">
          <div class="auth-tab active" data-tab="login-form">Autentificare</div>
          <div class="auth-tab" data-tab="register-form">Cont nou</div>
        </div>

        <form id="login-form" class="auth-form active">
          <div id="login-alert" class="alert alert-error" style="display:none"></div>
          <div class="form-group">
            <label class="form-label">Username / Email</label>
            <input id="login-username" class="form-control" type="text" placeholder="demo" required>
          </div>
          <div class="form-group">
            <label class="form-label">Parolă</label>
            <input id="login-password" class="form-control" type="password" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Conectare</button>
        </form>

        <form id="register-form" class="auth-form">
          <div id="register-alert" class="alert alert-error" style="display:none"></div>
          <div class="form-group">
            <label class="form-label">Username</label>
            <input id="reg-username" class="form-control" type="text" placeholder="ionescu_alex" minlength="3" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input id="reg-email" class="form-control" type="email" placeholder="alex@example.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Parolă (minim 6 caractere)</label>
            <input id="reg-password" class="form-control" type="password" placeholder="••••••••" minlength="6" required>
          </div>
          <button type="submit" class="btn btn-success btn-lg" style="width:100%">Creare cont</button>
        </form>
      </div>
    </div>
  </div>

  <div id="app-page" style="display:none">
    <div class="tab-bar">
      <button class="tab-btn active" data-tab="tab-sim">⚙ Simulare PID</button>
      <button class="tab-btn" data-tab="tab-history">🗄 Istoric</button>
      <button class="tab-btn" data-tab="tab-info">📖 Teorie</button>
    </div>

    <div class="tab-content active" id="tab-sim">
      <div class="app-main">
        <aside class="sidebar">
          <div class="sidebar-section">
            <div class="panel-header"><span class="panel-title">Tip Sistem</span></div>
            <div style="padding:16px">
              <div class="form-group">
                <label class="form-label">Ordin sistem</label>
                <select id="system-order" class="form-control">
                  <option value="1">Ordin 1 — Sistem standard</option>
                  <option value="2">Ordin 2 — Sistem standard</option>
                  <option value="custom">Personalizat — Funcție transfer completă</option>
                </select>
              </div>

              <div id="custom-tf-params" style="display:none; background:var(--bg-input); padding:10px; border:1px solid var(--border); border-radius:4px; margin-bottom:16px;">
                <label class="form-label" style="color:var(--accent-cyan); text-align:center; display:block; margin-bottom:12px;">
                  H(s) = (b₂·s² + b₁·s + b₀) / (a₂·s² + a₁·s + a₀)
                </label>
                <div style="display:flex; gap:8px; margin-bottom:8px">
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">b₂</label><input type="number" id="tf-b2" class="form-control" value="0" step="0.1"></div>
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">b₁</label><input type="number" id="tf-b1" class="form-control" value="0" step="0.1"></div>
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">b₀</label><input type="number" id="tf-b0" class="form-control" value="1" step="0.1"></div>
                </div>
                <hr style="border:none; border-top:1px solid var(--border); margin: 8px 0;">
                <div style="display:flex; gap:8px;">
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">a₂</label><input type="number" id="tf-a2" class="form-control" value="1" step="0.1"></div>
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">a₁</label><input type="number" id="tf-a1" class="form-control" value="2" step="0.1"></div>
                  <div class="form-group" style="flex:1; margin-bottom:0"><label class="form-label">a₀</label><input type="number" id="tf-a0" class="form-control" value="1" step="0.1"></div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" id="order1-label">τ — Constanta de timp [s]</label>
                <div class="pid-param-header">
                  <input type="range" id="tau" min="0.1" max="10" step="0.1" value="1.0">
                  <span class="pid-param-value" id="tau-val">1.00</span>
                </div>
              </div>

              <div id="order2-params" style="display:none">
                <div class="form-group">
                  <label class="form-label">τ₂ — Constanta de timp 2 [s]</label>
                  <div class="pid-param-header">
                    <input type="range" id="tau2" min="0.1" max="5" step="0.1" value="0.5">
                    <span class="pid-param-value" id="tau2-val">0.50</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">ζ — Coeficient amortizare</label>
                  <div class="pid-param-header">
                    <input type="range" id="zeta" min="0.1" max="2.0" step="0.05" value="0.7">
                    <span class="pid-param-value" id="zeta-val">0.70</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="sidebar-section">
            <div class="panel-header"><span class="panel-title">Parametri PID</span></div>
            <div style="padding:16px">
              <div class="pid-param">
                <div class="pid-param-header"><label class="pid-param-label"><span class="pid-param-name">Kp</span> Proporțional</label><span class="pid-param-value" id="kp-val">1.00</span></div>
                <input type="range" id="kp" min="0" max="20" step="0.1" value="1.0">
              </div>
              <div class="pid-param">
                <div class="pid-param-header"><label class="pid-param-label"><span class="pid-param-name">Ki</span> Integral</label><span class="pid-param-value" id="ki-val">0.10</span></div>
                <input type="range" id="ki" min="0" max="10" step="0.05" value="0.1">
              </div>
              <div class="pid-param">
                <div class="pid-param-header"><label class="pid-param-label"><span class="pid-param-name">Kd</span> Derivativ</label><span class="pid-param-value" id="kd-val">0.01</span></div>
                <input type="range" id="kd" min="0" max="5" step="0.01" value="0.01">
              </div>
            </div>
          </div>

          <div class="sidebar-section">
            <div class="panel-header"><span class="panel-title">Configurare simulare</span></div>
            <div style="padding:16px">
              <div class="form-group">
                <label class="form-label">Referință r(t) — Setpoint</label>
                <div class="pid-param-header">
                  <input type="range" id="setpoint" min="0.1" max="5" step="0.1" value="1.0">
                  <span class="pid-param-value" id="sp-val">1.00</span>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Durata simulare [s]</label>
                <div class="pid-param-header">
                  <input type="range" id="simtime" min="5" max="60" step="1" value="20">
                  <span class="pid-param-value" id="simtime-val">20.00</span>
                </div>
              </div>
              <div style="display:flex;gap:8px;margin-top:16px">
                <button id="run-btn" class="btn btn-primary" onclick="runSimulation(false)" style="flex:1">▶ RULEAZĂ SIMULARE</button>
              </div>
              <div style="margin-top:8px">
                <button class="btn btn-success" onclick="runSimulation(true)" style="width:100%">💾 RULEAZĂ ȘI SALVEAZĂ</button>
              </div>
            </div>
          </div>

          <div class="sidebar-section">
            <div class="panel-header"><span class="panel-title">Metrici Performanță</span></div>
            <div class="metrics-grid">
              <div class="metric-card"><div class="metric-label">Valoare staționar</div><div class="metric-value" id="m-steady">—</div></div>
              <div class="metric-card"><div class="metric-label">Supraunghi (Mp)</div><div class="metric-value" id="m-overshoot" style="color:var(--accent-red)">—</div></div>
              <div class="metric-card" style="grid-column: 1 / -1;"><div class="metric-label">Timp stabilizare (ts)</div><div class="metric-value" id="m-settle" style="color:var(--accent-cyan)">—</div></div>
            </div>
          </div>
        </aside>

        <div class="content-area">
          <div class="chart-container" style="padding-bottom:0">
            <div class="panel">
              <div class="panel-header"><span class="panel-title">Răspuns la treaptă — y(t)</span></div>
              <div class="panel-body" style="padding:12px"><div class="chart-wrapper"><canvas id="chart-response"></canvas></div></div>
            </div>
          </div>
          <div class="chart-container">
            <div class="panel">
              <div class="panel-header"><span class="panel-title">Semnal control u(t) și eroare e(t)</span></div>
              <div class="panel-body" style="padding:12px"><div class="chart-wrapper"><canvas id="chart-control"></canvas></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-content" id="tab-history" style="overflow-y:auto">
      <div style="padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-size:16px;color:var(--text-muted)">Istoricul simulărilor tale</h2>
          <button class="btn btn-ghost btn-sm" onclick="loadHistory()">↺ Actualizează</button>
        </div>
        <div class="panel">
          <table class="history-table">
            <thead>
              <tr>
                <th>#ID</th><th>Sistem</th><th>Kp / Ki / Kd</th><th>Setpoint</th><th>Val. staționar</th><th>Supraunghi</th><th>Data</th><th>Acțiuni</th>
              </tr>
            </thead>
            <tbody id="history-body"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="tab-content" id="tab-info" style="overflow-y:auto">
      <div style="padding:24px; max-width:1000px; margin: 0 auto;">
        <h2 style="font-size:24px; margin-bottom:6px; color:var(--accent-cyan); letter-spacing:0.05em;">📖 Ghid Teoretic — Simulator PID & Spațiul Stărilor</h2>
        <p style="color:var(--text-dim); font-size:14px; margin-bottom:24px; font-family:var(--font-mono)">Documentație tehnică adaptată structurii algoritmice a simulatorului curent.</p>
        
        <div class="panel" style="margin-bottom:20px;">
          <div class="panel-header"><span class="panel-title">1. Bucla de Control Automat (Closed-Loop)</span></div>
          <div class="panel-body">
            <p style="color:var(--text-muted); line-height:1.7;">Aplicația simulează un sistem de control cu reacție negativă unitară. Regulatorul compară ținta (Setpoint) cu valoarea reală a sistemului, calculând eroarea: e(t) = r(t) - y(t). Pe baza ei, se generează semnalul de control u(t).</p>
          </div>
        </div>

        <div class="panel" style="margin-bottom:20px;">
          <div class="panel-header"><span class="panel-title">2. Algoritmul PID și Structura de Ajustare</span></div>
          <div class="panel-body">
            <p style="color:var(--text-muted); line-height:1.7; margin-bottom:12px;">Ecuația matematică discretizată la fiecare $\Delta t = 0.01s$ combină trei termeni structurali:</p>
            <div style="background:var(--bg-input); padding:12px; border-radius:4px; font-family:var(--font-mono); font-size:14px; color:var(--accent-green); text-align:center;">u(t) = Kp · e(t) + Ki · ∫ e(t)dt + Kd · [de(t)/dt]</div>
            <p style="color:var(--text-muted); line-height:1.7; margin-top:12px;"><strong>Kp</strong> corectează eroarea curentă, <strong>Ki</strong> adună erorile din trecut eliminând eroarea staționară, iar <strong>Kd</strong> anticipează traiectoria viitoare funcționând ca o frână predictivă. Semnalul u(t) este limitat fizic în motor între <strong>-10 și +10</strong> (saturare actuator).</p>
          </div>
        </div>

        <div class="panel" style="margin-bottom:20px;">
          <div class="panel-header"><span class="panel-title">3. Modelele Matematice ale Sistemelor & Spațiul Stărilor</span></div>
          <div class="panel-body">
            <p style="color:var(--text-muted); line-height:1.7; margin-bottom:12px;">Opțiunea <strong>Personalizat</strong> utilizează o reprezentare matriceală în <strong>Spațiul Stărilor (State-Space Representation)</strong> în Formă Canonică de Comandă:</p>
            <div style="background:var(--bg-input); padding:12px; border-radius:4px; font-family:var(--font-mono); font-size:14px; text-align:center; margin-bottom:12px;">H(s) = (b₂·s² + b₁·s + b₀) / (a₂·s² + a₁·s + a₀)</div>
            <p style="color:var(--text-muted); line-height:1.7;">Această metodă evită derivarea directă a semnalului de control $u(t)$ în cod, integrând variabilele interne de stare $x_1$ și $x_2$ prin metoda Euler, asigurând o stabilitate numerică perfectă chiar și în prezența zerourilor la numărător.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="js/pid_engine.js"></script>
<script src="js/app.js"></script>
</body>
</html>