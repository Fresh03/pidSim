<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
$userId = requireAuth();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true); if (!$data) $data = $_POST;
        $title        = substr(trim($data['title'] ?? 'Simulare nouă'), 0, 100);
        $systemOrder  = intval($data['system_order'] ?? 1);
        $kp           = floatval($data['kp'] ?? 1.0); $ki = floatval($data['ki'] ?? 0.1); $kd = floatval($data['kd'] ?? 0.01);
        $tau          = floatval($data['tau'] ?? 1.0); $tau2 = floatval($data['tau2'] ?? 0.5); $zeta = floatval($data['zeta'] ?? 0.7);
        $tf_b2        = floatval($data['tf_b2'] ?? 0.0); $tf_b1 = floatval($data['tf_b1'] ?? 0.0); $tf_b0 = floatval($data['tf_b0'] ?? 1.0);
        $tf_a2        = floatval($data['tf_a2'] ?? 1.0); $tf_a1 = floatval($data['tf_a1'] ?? 2.0); $tf_a0 = floatval($data['tf_a0'] ?? 1.0);
        $setpoint     = floatval($data['setpoint'] ?? 1.0); $simTime = floatval($data['sim_time'] ?? 20.0);
        $ssValue      = isset($data['steady_state_value']) ? floatval($data['steady_state_value']) : null;
        $overshoot    = isset($data['overshoot']) ? floatval($data['overshoot']) : null;
        $settlingTime = isset($data['settling_time']) ? floatval($data['settling_time']) : null;
        $simData      = $data['sim_data'] ?? []; 

        $db = getDB(); $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO simulations (user_id, title, system_order, kp, ki, kd, tau, tau2, zeta, tf_b2, tf_b1, tf_b0, tf_a2, tf_a1, tf_a0, setpoint, sim_time, steady_state_value, overshoot, settling_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $systemOrder, $kp, $ki, $kd, $tau, $tau2, $zeta, $tf_b2, $tf_b1, $tf_b0, $tf_a2, $tf_a1, $tf_a0, $setpoint, $simTime, $ssValue, $overshoot, $settlingTime]);
            $simId = $db->lastInsertId();
            if (!empty($simData) && is_array($simData)) {
                $insertStmt = $db->prepare("INSERT INTO simulation_data (simulation_id, time_point, output_value, control_signal, error_value) VALUES (?, ?, ?, ?, ?)");
                foreach (array_slice($simData, 0, 500) as $pt) { $insertStmt->execute([$simId, floatval($pt['t'] ?? 0), floatval($pt['y'] ?? 0), floatval($pt['u'] ?? 0), floatval($pt['e'] ?? 0)]); }
            }
            $db->commit(); jsonResponse(['success' => true, 'simulation_id' => $simId]);
        } catch (Exception $e) { $db->rollBack(); jsonResponse(['error' => 'Eroare la salvare: ' . $e->getMessage()], 500); }
        break;

    case 'list':
        $db = getDB();
        $stmt = $db->prepare("SELECT id, title, system_order, kp, ki, kd, tau, zeta, tf_b2, tf_b1, tf_b0, tf_a2, tf_a1, tf_a0, setpoint, sim_time, steady_state_value, overshoot, settling_time, created_at FROM simulations WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]); jsonResponse(['success' => true, 'simulations' => $stmt->fetchAll()]);
        break;

    case 'get':
        $simId = intval($_GET['id'] ?? 0); $db = getDB();
        $stmt = $db->prepare("SELECT * FROM simulations WHERE id = ? AND user_id = ?"); $stmt->execute([$simId, $userId]); $sim = $stmt->fetch();
        if (!$sim) jsonResponse(['error' => 'Simularea nu există.'], 404);
        jsonResponse(['success' => true, 'simulation' => $sim]);
        break;

    case 'delete':
        $simId = intval($_POST['id'] ?? 0); $db = getDB();
        $stmt = $db->prepare("DELETE FROM simulations WHERE id = ? AND user_id = ?"); $stmt->execute([$simId, $userId]);
        jsonResponse(['success' => true]);
        break;
}