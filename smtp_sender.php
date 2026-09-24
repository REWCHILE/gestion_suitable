<?php
/**
 * STEALTH SMTP SENDER - SUITABLE B2B
 * Ultra-clean RFC 5322/2822 socket client designed to emulate authentic corporate MUA
 * Strips all PHP signatures, custom headers, and bot fingerprints
 */

require_once __DIR__ . '/config.php';

class SmtpSender {

    /**
     * Send email via VPS Postfix / SMTP Relay
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, array $options = []): array {
        $host = $options['host'] ?? get_setting('smtp_host', '127.0.0.1');
        $port = intval($options['port'] ?? get_setting('smtp_port', '25'));
        $user = $options['user'] ?? get_setting('smtp_user', '');
        $pass = $options['pass'] ?? get_setting('smtp_pass', '');
        $secure = $options['secure'] ?? get_setting('smtp_secure', 'none'); // 'none', 'tls', 'ssl'
        $fromEmail = $options['from_email'] ?? get_setting('sender_email', 'ventas@suitable.cl');
        $fromName = $options['from_name'] ?? get_setting('sender_name', 'Suitable Uniformes Clínicos');
        $heloDomain = $options['helo_domain'] ?? get_setting('smtp_helo_domain', 'mail.suitable.cl');

        $protocol = '';
        if ($secure === 'ssl' || $port === 465) {
            $protocol = 'ssl://';
        }

        $connectionTarget = $protocol . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $timeout = 15;
        $socket = @stream_socket_client($connectionTarget, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return ['success' => false, 'error' => "No se pudo conectar al VPS ($connectionTarget): $errstr ($errno)"];
        }

        stream_set_timeout($socket, $timeout);

        // Read initial greeting
        $response = self::readResponse($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "Saludo SMTP no válido: $response"];
        }

        // Send EHLO
        self::sendCommand($socket, "EHLO $heloDomain");
        $ehloResponse = self::readResponse($socket);

        // Upgrade to STARTTLS if configured
        if (($secure === 'tls' || $port === 587) && strpos($ehloResponse, 'STARTTLS') !== false) {
            self::sendCommand($socket, "STARTTLS");
            $tlsResponse = self::readResponse($socket);
            if (substr($tlsResponse, 0, 3) === '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'error' => "Fallo al negociar STARTTLS con el servidor VPS"];
                }
                // Resend EHLO after TLS handshake
                self::sendCommand($socket, "EHLO $heloDomain");
                self::readResponse($socket);
            }
        }

        // Authenticate if credentials provided
        if (!empty($user) && !empty($pass)) {
            self::sendCommand($socket, "AUTH LOGIN");
            $authRes = self::readResponse($socket);
            if (substr($authRes, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'error' => "Servidor no soporta AUTH LOGIN: $authRes"];
            }

            self::sendCommand($socket, base64_encode($user));
            self::readResponse($socket);

            self::sendCommand($socket, base64_encode($pass));
            $passRes = self::readResponse($socket);
            if (substr($passRes, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'error' => "Credenciales SMTP inválidas para $user"];
            }
        }

        // MAIL FROM
        self::sendCommand($socket, "MAIL FROM:<$fromEmail>");
        $fromRes = self::readResponse($socket);
        if (substr($fromRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "MAIL FROM rechazado: $fromRes"];
        }

        // RCPT TO
        self::sendCommand($socket, "RCPT TO:<$toEmail>");
        $rcptRes = self::readResponse($socket);
        if (substr($rcptRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "Destinatario rechazado ($toEmail): $rcptRes"];
        }

        // DATA
        self::sendCommand($socket, "DATA");
        $dataRes = self::readResponse($socket);
        if (substr($dataRes, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'error' => "DATA rechazado: $dataRes"];
        }

        // BUILD STEALTH HEADERS (EMULATE LEGITIMATE CORPORATE MUA)
        $msgIdDomain = explode('@', $fromEmail)[1] ?? 'suitable.cl';
        $messageId = '<' . date('YmdHis') . '.' . bin2hex(random_bytes(6)) . '@' . $msgIdDomain . '>';
        $date = date('r');
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedToName = !empty($toName) ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '';

        $headers = [];
        $headers[] = "Date: $date";
        $headers[] = "From: $encodedFromName <$fromEmail>";
        $headers[] = "To: {$encodedToName}<$toEmail>";
        $headers[] = "Subject: $encodedSubject";
        $headers[] = "Message-ID: $messageId";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";

        // Raw body in base64 to avoid line break corruption
        $rawMessage = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";

        self::sendCommand($socket, $rawMessage);
        $finalRes = self::readResponse($socket);

        self::sendCommand($socket, "QUIT");
        fclose($socket);

        if (substr($finalRes, 0, 3) === '250') {
            return [
                'success' => true,
                'message_id' => $messageId,
                'response' => trim($finalRes)
            ];
        }

        return ['success' => false, 'error' => "Fallo en entrega final: $finalRes"];
    }

    /**
     * Test connection to VPS Postfix
     */
    public static function testConnection(array $params = []): array {
        $startTime = microtime(true);
        $host = $params['host'] ?? get_setting('smtp_host', '127.0.0.1');
        $port = intval($params['port'] ?? get_setting('smtp_port', '25'));
        $user = $params['user'] ?? get_setting('smtp_user', '');
        $pass = $params['pass'] ?? get_setting('smtp_pass', '');
        $secure = $params['secure'] ?? get_setting('smtp_secure', 'none');
        $heloDomain = $params['helo_domain'] ?? get_setting('smtp_helo_domain', 'mail.suitable.cl');

        $protocol = ($secure === 'ssl' || $port === 465) ? 'ssl://' : '';
        $target = $protocol . $host . ':' . $port;

        $ctx = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
        ]);

        $socket = @stream_socket_client($target, $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $ctx);
        if (!$socket) {
            return ['success' => false, 'message' => "Error de conexión con $target: $errstr ($errno)"];
        }

        stream_set_timeout($socket, 8);
        $banner = self::readResponse($socket);
        self::sendCommand($socket, "EHLO $heloDomain");
        $ehlo = self::readResponse($socket);

        self::sendCommand($socket, "QUIT");
        fclose($socket);

        $latency = round((microtime(true) - $startTime) * 1000);
        return [
            'success' => true,
            'message' => "Conectado exitosamente con Postfix en $target en {$latency}ms",
            'banner' => trim($banner),
            'latency_ms' => $latency
        ];
    }

    private static function sendCommand($socket, string $cmd): void {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function readResponse($socket): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}
