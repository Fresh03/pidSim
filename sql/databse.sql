CREATE DATABASE IF NOT EXISTS pid_simulator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pid_simulator;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS simulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) DEFAULT 'Simulare nouă',
    system_order TINYINT NOT NULL COMMENT '0 = Custom, 1 = Ordin 1, 2 = Ordin 2',
    kp DECIMAL(10,4) NOT NULL,
    ki DECIMAL(10,4) NOT NULL,
    kd DECIMAL(10,4) NOT NULL,
    tau DECIMAL(10,4) DEFAULT 1.0,
    tau2 DECIMAL(10,4) DEFAULT 0.5,
    zeta DECIMAL(10,4) DEFAULT 0.7,
    tf_b2 DECIMAL(10,4) DEFAULT 0.0,
    tf_b1 DECIMAL(10,4) DEFAULT 0.0,
    tf_b0 DECIMAL(10,4) DEFAULT 1.0,
    tf_a2 DECIMAL(10,4) DEFAULT 1.0,
    tf_a1 DECIMAL(10,4) DEFAULT 2.0,
    tf_a0 DECIMAL(10,4) DEFAULT 1.0,
    setpoint DECIMAL(10,4) DEFAULT 1.0,
    sim_time DECIMAL(10,2) DEFAULT 20.0,
    steady_state_value DECIMAL(10,6) NULL,
    overshoot DECIMAL(10,4) NULL,
    settling_time DECIMAL(10,4) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS simulation_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    simulation_id INT NOT NULL,
    time_point DECIMAL(10,4) NOT NULL,
    output_value DECIMAL(15,8) NOT NULL,
    control_signal DECIMAL(15,8) NOT NULL,
    error_value DECIMAL(15,8) NOT NULL,
    FOREIGN KEY (simulation_id) REFERENCES simulations(id) ON DELETE CASCADE,
    INDEX idx_sim_time (simulation_id, time_point)
);

INSERT INTO users (username, email, password_hash) VALUES
('demo', 'demo@pid-sim.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');