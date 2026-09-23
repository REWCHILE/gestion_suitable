<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_service.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'error' => 'No autorizado'], 401);
}

$user = current_user();
$db = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // 1. AI GENERATION
    case 'ai_generate':
        $provider = trim($_POST['provider'] ?? get_setting('active_ai_provider', 'groq'));
        $prompt = trim($_POST['prompt'] ?? '');
        if (!$prompt) {
            json_response(['success' => false, 'error' => 'El prompt no puede estar vacío']);
        }
        $res = AIService::generateCopy($provider, $prompt);
        json_response($res);
        break;

    // 2. TEST AI CONNECTION
    case 'test_ai_key':
        $provider = trim($_POST['provider'] ?? '');
        if (!$provider) {
            json_response(['success' => false, 'error' => 'Proveedor no especificado']);
        }
        $res = AIService::testConnection($provider);
        json_response($res);
        break;

    // 3. GET GROUP COUNT
    case 'get_group_count':
        $group_id = intval($_GET['group_id'] ?? 0);
        $count = 0;
        if ($group_id > 0) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
            $stmt->execute([$group_id]);
            $count = $stmt->fetchColumn();
        }
        json_response(['success' => true, 'count' => $count]);
        break;

    // 4. CREATE GROUP
    case 'create_group':
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#1E8888');

        if (!$name) {
            json_response(['success' => false, 'error' => 'El nombre del grupo es obligatorio']);
        }
        $stmt = $db->prepare("INSERT INTO contact_groups (name, description, color) VALUES (?, ?, ?)");
        $stmt->execute([$name, $desc, $color]);
        json_response(['success' => true, 'message' => 'Grupo creado exitosamente', 'id' => $db->lastInsertId()]);
        break;

    // 5. CREATE CLIENT
    case 'create_client':
        $empresa = trim($_POST['empresa'] ?? '');
        $contacto = trim($_POST['contacto_nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $comuna = trim($_POST['region_comuna'] ?? 'Santiago, RM');
        $equipo = intval($_POST['tamano_equipo'] ?? 15);
        $notas = trim($_POST['notas'] ?? '');

        if (!$empresa || !$contacto || !$email) {
            json_response(['success' => false, 'error' => 'Empresa, contacto y email son requeridos']);
        }

        $stmt = $db->prepare("
            INSERT INTO clients (empresa, contacto_nombre, email, telefono, cargo, region_comuna, tamano_equipo, notas, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'nuevo')
        ");
        $stmt->execute([$empresa, $contacto, $email, $telefono, $cargo, $comuna, $equipo, $notas]);
        json_response(['success' => true, 'message' => 'Clínica registrada en el pipeline exitosamente']);
        break;

    // 6. UPDATE CLIENT
    case 'update_client':
        $id = intval($_POST['id'] ?? 0);
        $empresa = trim($_POST['empresa'] ?? '');
        $contacto = trim($_POST['contacto_nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $estado = trim($_POST['estado'] ?? 'nuevo');
        $tallaje = !empty($_POST['fecha_tallaje']) ? $_POST['fecha_tallaje'] : null;
        $monto = !empty($_POST['monto_cotizacion']) ? floatval($_POST['monto_cotizacion']) : null;
        $equipo = intval($_POST['tamano_equipo'] ?? 15);
        $notas = trim($_POST['notas'] ?? '');

        if (!$id || !$empresa || !$contacto || !$email) {
            json_response(['success' => false, 'error' => 'Campos obligatorios incompletos']);
        }

        $stmt = $db->prepare("
            UPDATE clients SET 
                empresa = ?, contacto_nombre = ?, email = ?, telefono = ?,
                estado = ?, fecha_tallaje = ?, monto_cotizacion = ?,
                tamano_equipo = ?, notas = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$empresa, $contacto, $email, $telefono, $estado, $tallaje, $monto, $equipo, $notas, $id]);
        json_response(['success' => true, 'message' => 'Información de la institución actualizada']);
        break;

    // 7. DELETE CLIENT
    case 'delete_client':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            json_response(['success' => false, 'error' => 'ID inválido']);
        }
        $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['success' => true, 'message' => 'Clínica eliminada del pipeline']);
        break;

    // 8. UPDATE STATUS QUICKLY
    case 'update_status':
        $id = intval($_POST['client_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id || !$status) {
            json_response(['success' => false, 'error' => 'Parámetros incompletos']);
        }
        $stmt = $db->prepare("UPDATE clients SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $id]);
        json_response(['success' => true, 'message' => 'Estado comercial actualizado']);
        break;

    // 9. LAUNCH CAMPAIGN
    case 'launch_campaign':
        $name = trim($_POST['name'] ?? 'Campaña B2B');
        $subject = trim($_POST['subject'] ?? 'Propuesta Suitable');
        $target_type = $_POST['target_type'] ?? 'all';
        $group_id = intval($_POST['group_id'] ?? 0);
        $client_id = intval($_POST['client_id'] ?? 0);
        $template_id = intval($_POST['template_id'] ?? 2);
        $mode = $_POST['mode'] ?? 'simulacion';

        // Find recipient clients
        if ($target_type === 'single' && $client_id > 0) {
            $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $recipients = $stmt->fetchAll();
        } elseif ($target_type === 'group' && $group_id > 0) {
            $stmt = $db->prepare("
                SELECT c.* FROM clients c 
                INNER JOIN group_members gm ON c.id = gm.client_id 
                WHERE gm.group_id = ?
            ");
            $stmt->execute([$group_id]);
            $recipients = $stmt->fetchAll();
        } else {
            $recipients = $db->query("SELECT * FROM clients")->fetchAll();
        }

        $total_count = count($recipients);
        if ($total_count == 0) {
            json_response(['success' => false, 'error' => 'No se encontraron destinatarios para esta selección']);
        }

        // Create campaign record
        $stmt_c = $db->prepare("
            INSERT INTO campaigns (name, group_id, template_id, subject, status, sent_count, total_count, user_id)
            VALUES (?, ?, ?, ?, 'enviada', ?, ?, ?)
        ");
        $stmt_c->execute([$name, $group_id ?: null, $template_id, $subject, $total_count, $total_count, $user['id']]);
        $campaign_id = $db->lastInsertId();

        // Log each email and update client status
        $tpl_type = ($template_id == 2) ? 'plantilla_2' : 'plantilla_1';
        $new_status = ($template_id == 2) ? 'correo_2_enviado' : 'correo_1_enviado';

        $stmt_log = $db->prepare("
            INSERT INTO email_logs (client_id, campaign_id, user_id, template_id, recipient_email, subject, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_up_cli = $db->prepare("
            UPDATE clients SET 
                ultimo_envio_tipo = ?, ultimo_envio_fecha = CURRENT_TIMESTAMP,
                estado = CASE WHEN estado = 'nuevo' THEN ? ELSE estado END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $status_label = ($mode === 'brevo_api') ? 'enviado_brevo' : 'simulado';

        foreach ($recipients as $rec) {
            $stmt_log->execute([$rec['id'], $campaign_id, $user['id'], $template_id, $rec['email'], $subject, $status_label]);
            $stmt_up_cli->execute([$tpl_type, $new_status, $rec['id']]);
        }

        $msg = "¡Campaña '$name' orquestada exitosamente para $total_count clínicas con " . ($template_id == 2 ? 'Plantilla 2 B2B' : 'Plantilla 1') . "!";
        json_response(['success' => true, 'message' => $msg, 'campaign_id' => $campaign_id]);
        break;

    default:
        json_response(['success' => false, 'error' => 'Acción no reconocida']);
}
