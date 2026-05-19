class PIDEngine {
  constructor() {
    this.dt = 0.01; 
  }

  simulate(params) {
    const {
      Kp = 1.0, Ki = 0.1, Kd = 0.01,
      systemOrder = 1, tau = 1.0, tau2 = 0.5, zeta = 0.7,
      b2 = 0.0, b1 = 0.0, b0 = 1.0,
      a2 = 1.0, a1 = 2.0, a0 = 1.0,
      setpoint = 1.0, simTime = 20.0, K = 1.0
    } = params;

    const dt = this.dt;
    const steps = Math.floor(simTime / dt);
    const t = [], y = [], u = [], e = [], integral_arr = [];

    // x1, x2 sunt variabilele de stare interne ale sistemului (ex: pozitie, viteza)
    let x1 = 0.0, x2 = 0.0, integral = 0.0, e_prev = 0.0;
    let y_out = 0.0; // Ieșirea reală a sistemului

    for (let i = 0; i <= steps; i++) {
      const time = i * dt;
      const error = setpoint - y_out;

      integral += error * dt;
      const integralClamped = Math.max(-50, Math.min(50, integral));
      const derivative = i === 0 ? 0 : (error - e_prev) / dt;

      let controlSignal = Kp * error + Ki * integralClamped + Kd * derivative;
      controlSignal = Math.max(-10, Math.min(10, controlSignal)); // Saturare actuator

      if (systemOrder === '1' || systemOrder === 1) {
        // Sistem clasic ordin 1
        const dx1 = (K * controlSignal - x1) / tau;
        x1 += dx1 * dt;
        y_out = x1;
      } else if (systemOrder === '2' || systemOrder === 2) {
        // Sistem clasic ordin 2
        const wn = 1.0 / Math.max(tau2, 0.001);
        const dx1 = x2;
        const dx2 = (K * controlSignal - x1 - 2 * zeta * x2 / wn) * wn * wn;
        x1 += dx1 * dt;
        x2 += dx2 * dt;
        y_out = x1;
      } else if (systemOrder === 'custom' || systemOrder === 0) {
        // Funcție de Transfer (Reprezentare State-Space Controllable Canonical Form)
        if (a2 !== 0) {
          const alpha1 = a1 / a2;
          const alpha0 = a0 / a2;
          const beta2 = b2 / a2;
          const beta1 = b1 / a2;
          const beta0 = b0 / a2;

          const dx1 = x2;
          const dx2 = -alpha0 * x1 - alpha1 * x2 + controlSignal;
          
          x1 += dx1 * dt;
          x2 += dx2 * dt;

          y_out = (beta0 - beta2 * alpha0) * x1 + (beta1 - beta2 * alpha1) * x2 + beta2 * controlSignal;
        } else if (a1 !== 0) {
          // Sistem custom ordin 1: (b1*s + b0) / (a1*s + a0)
          const alpha0 = a0 / a1;
          const beta1 = b1 / a1;
          const beta0 = b0 / a1;

          const dx1 = -alpha0 * x1 + controlSignal;
          x1 += dx1 * dt;

          y_out = (beta0 - beta1 * alpha0) * x1 + beta1 * controlSignal;
        } else {
          // Gain pur dacă a2=0 și a1=0
          y_out = (b0 / Math.max(a0, 0.0001)) * controlSignal;
        }
      }

      if (i % 5 === 0) {
        t.push(parseFloat(time.toFixed(3)));
        y.push(parseFloat(y_out.toFixed(6)));
        u.push(parseFloat(controlSignal.toFixed(6)));
        e.push(parseFloat(error.toFixed(6)));
        integral_arr.push(parseFloat(integralClamped.toFixed(6)));
      }
      e_prev = error;
    }

    const metrics = this._computeMetrics(t, y, setpoint);
    return { t, y, u, e, integral: integral_arr, metrics };
  }

  _computeMetrics(t, y, setpoint) {
    if (!y.length) return {};
    const n = y.length;
    const finalVal = y[n - 1];
    const maxVal   = Math.max(...y);

    const overshoot = finalVal > 0.001 ? parseFloat(((maxVal - finalVal) / finalVal * 100).toFixed(2)) : 0;
    const band = 0.02 * Math.abs(setpoint);
    let settlingTime = null;
    
    for (let i = n - 1; i >= 0; i--) {
      if (Math.abs(y[i] - finalVal) > band) {
        settlingTime = i < n - 1 ? parseFloat(t[i + 1].toFixed(3)) : null;
        break;
      }
    }
    return { steadyState: parseFloat(finalVal.toFixed(4)), overshoot: parseFloat(Math.max(0, overshoot).toFixed(2)), settlingTime: settlingTime };
  }
}
const pidEngine = new PIDEngine();