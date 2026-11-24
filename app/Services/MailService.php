<?php

namespace App\Services;

use App\Core\Database;
use RuntimeException;

class MailService
{
    public const OFFICE_ADDRESS = '12 Adeola Odeku Street, Victoria Island, Lagos, Nigeria';

    public static function sendLoanPending(int $loanId): array
    {
        $loan = self::loanRecipient($loanId);
        if ($loan === null) {
            return self::recordFailure('', '', 'Financing enquiry received', 'loan_pending', '', 'The enquiry has no email address.');
        }

        $amount = '₦' . number_format((float)$loan['amount']);
        $body = '<p style="margin:0 0 16px;">Hello ' . e($loan['name']) . ',</p>'
            . '<p style="margin:0 0 16px;">Your financing enquiry has been sent. It is pending approval by CJ Travels customer care.</p>'
            . self::detailTable([
                'Reference' => (string)$loan['ref'],
                'Amount' => $amount,
                'Purpose' => (string)$loan['purpose'],
                'Repayment period' => (string)$loan['period'],
                'Status' => 'Pending approval',
            ])
            . '<p style="margin:16px 0 0;">We will email you again when customer care approves the flight loan.</p>';

        return self::deliver(
            (string)$loan['email'],
            (string)$loan['name'],
            'Your financing enquiry ' . $loan['ref'] . ' is pending approval',
            $body,
            self::plainFromHtml($body),
            'loan_pending',
            (string)$loan['ref']
        );
    }

    public static function sendLoanApproved(int $loanId): array
    {
        $loan = self::loanRecipient($loanId);
        if ($loan === null) {
            return self::recordFailure('', '', 'Flight loan approved', 'loan_approved', '', 'The enquiry has no email address.');
        }

        $amount = '₦' . number_format((float)$loan['amount']);
        $body = '<p style="margin:0 0 16px;">Hello ' . e($loan['name']) . ',</p>'
            . '<p style="margin:0 0 16px;">CJ Travels customer care has approved your flight loan.</p>'
            . self::detailTable([
                'Reference' => (string)$loan['ref'],
                'Amount' => $amount,
                'Purpose' => (string)$loan['purpose'],
                'Repayment period' => (string)$loan['period'],
                'Status' => 'Approved',
            ])
            . '<p style="margin:16px 0 0;">Our team will contact you about the next step for this loan.</p>';

        return self::deliver(
            (string)$loan['email'],
            (string)$loan['name'],
            'Your flight loan ' . $loan['ref'] . ' has been approved',
            $body,
            self::plainFromHtml($body),
            'loan_approved',
            (string)$loan['ref']
        );
    }

    public static function sendToCustomer(string $email, string $name, string $subject, string $message): array
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $subject = trim($subject);
        $message = trim($message);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Enter a valid recipient email address.'];
        }
        if ($subject === '' || $message === '') {
            return ['success' => false, 'error' => 'Subject and message are required.'];
        }

        $safeName = $name !== '' ? $name : 'there';
        $body = '<p style="margin:0 0 16px;">Hello ' . e($safeName) . ',</p>'
            . '<div style="margin:0;line-height:1.6;">' . nl2br(e($message)) . '</div>';

        return self::deliver($email, $name, $subject, $body, "Hello {$safeName},\n\n{$message}", 'admin', '');
    }

    public static function settings(): array
    {
        self::ensureMailSettings();
        $rows = Database::getInstance()->select(
            "SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'mail_%'"
        );
        $stored = [];
        foreach ($rows as $row) {
            $stored[(string)$row['setting_key']] = (string)$row['setting_value'];
        }

        return [
            'host' => $stored['mail_host'] ?? '',
            'port' => $stored['mail_port'] ?? '587',
            'encryption' => $stored['mail_encryption'] ?? 'tls',
            'username' => $stored['mail_username'] ?? '',
            'password' => $stored['mail_password'] ?? '',
            'from_email' => $stored['mail_from_email'] ?? 'info@cjtravelsowerri.com',
            'from_name' => $stored['mail_from_name'] ?? 'CJ Travels',
        ];
    }

    public static function settingsForForm(): array
    {
        $settings = self::settings();
        $settings['has_password'] = $settings['password'] !== '';
        unset($settings['password']);
        return $settings;
    }

    public static function saveSettings(array $input): array
    {
        self::ensureMailSettings();
        $current = self::settings();
        $fromEmail = strtolower(trim((string)($input['from_email'] ?? '')));
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Enter a valid From email address.'];
        }

        $encryption = strtolower(trim((string)($input['encryption'] ?? 'tls')));
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        $port = (int)($input['port'] ?? 587);
        if ($port < 1 || $port > 65535) {
            $port = 587;
        }

        $password = (string)($input['password'] ?? '');
        if ($password === '') {
            $password = $current['password'];
        }

        $values = [
            'mail_host' => trim((string)($input['host'] ?? '')),
            'mail_port' => (string)$port,
            'mail_encryption' => $encryption,
            'mail_username' => trim((string)($input['username'] ?? '')),
            'mail_password' => $password,
            'mail_from_email' => $fromEmail,
            'mail_from_name' => trim((string)($input['from_name'] ?? '')) ?: 'CJ Travels',
        ];

        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        foreach ($values as $key => $value) {
            $existing = $db->selectOne(
                'SELECT setting_key FROM site_settings WHERE setting_key = :key LIMIT 1',
                [':key' => $key]
            );
            if ($existing) {
                $db->update('site_settings', [
                    'setting_value' => $value,
                    'updated_at' => $now,
                ], 'setting_key = :key', [':key' => $key]);
            } else {
                $db->insert('site_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'updated_at' => $now,
                ]);
            }
        }

        return ['success' => true, 'message' => 'Email settings have been saved.'];
    }

    public static function recent(int $limit = 40): array
    {
        self::ensureLogTable();
        $limit = max(1, min(100, $limit));
        return Database::getInstance()->select(
            "SELECT id, recipient_email, recipient_name, subject, kind, related_ref, status, error_message, created_at
             FROM outbound_emails
             ORDER BY id DESC
             LIMIT {$limit}"
        );
    }

    public static function customers(): array
    {
        return Database::getInstance()->select(
            "SELECT id, name, email FROM users
             WHERE email IS NOT NULL AND TRIM(email) <> ''
             ORDER BY name ASC"
        );
    }

    private static function deliver(
        string $email,
        string $name,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $kind,
        string $relatedRef
    ): array {
        $settings = self::settings();
        $fromEmail = $settings['from_email'];
        $fromName = $settings['from_name'];
        $logo = self::logoBytes();
        $html = self::wrapHtml($htmlBody, $logo !== null);
        $mime = self::buildMime($html, $textBody, $logo);

        try {
            if ($settings['host'] !== '') {
                self::sendSmtp($settings, $fromEmail, $fromName, $email, $name, $subject, $mime);
            } else {
                self::sendPhpMail($fromEmail, $fromName, $email, $subject, $mime);
            }
            self::logEmail($email, $name, $subject, $htmlBody, $kind, $relatedRef, 'sent', '');
            return ['success' => true, 'message' => 'Email sent to ' . $email . '.'];
        } catch (\Throwable $e) {
            $error = trim($e->getMessage()) ?: 'The email could not be sent.';
            self::logEmail($email, $name, $subject, $htmlBody, $kind, $relatedRef, 'failed', $error);
            return ['success' => false, 'error' => $error];
        }
    }

    private static function loanRecipient(int $loanId): ?array
    {
        if ($loanId <= 0) {
            return null;
        }
        $row = Database::getInstance()->selectOne(
            'SELECT l.ref, l.amount, l.period, l.purpose, u.name, u.email
             FROM loan_enquiries l
             LEFT JOIN users u ON u.id = l.user_id
             WHERE l.id = :id
             LIMIT 1',
            [':id' => $loanId]
        );
        if (!$row || !filter_var((string)($row['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $row;
    }

    private static function detailTable(array $rows): string
    {
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:8px 0 0;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eadfca;width:160px;color:#6d6456;font-size:13px;">' . e((string)$label) . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eadfca;color:#0B1E3D;font-size:14px;font-weight:600;">' . e((string)$value) . '</td>'
                . '</tr>';
        }
        return $html . '</table>';
    }

    private static function wrapHtml(string $content, bool $hasLogo): string
    {
        $phone = '';
        try {
            $contact = RealtimeDataService::contactPhone();
            $phone = (string)($contact['display'] ?? '');
        } catch (\Throwable $e) {
            $phone = '';
        }

        $logo = $hasLogo
            ? '<img src="cid:cjlogo" alt="CJ Travels" height="46" style="display:block;border:0;height:46px;width:auto;" />'
            : '<div style="font-family:Georgia,serif;font-size:28px;color:#E0C58A;">CJ Travels</div>';

        $phoneLine = $phone !== '' ? ' · ' . e($phone) : '';

        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f6f1e6;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f1e6;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border:1px solid #eadfca;">'
            . '<tr><td style="background:#0B1E3D;padding:28px 32px;">' . $logo . '</td></tr>'
            . '<tr><td style="padding:32px;font-family:Georgia,\'Times New Roman\',serif;color:#1c2430;font-size:16px;line-height:1.6;">'
            . $content
            . '</td></tr>'
            . '<tr><td style="background:#0B1E3D;padding:24px 32px;font-family:Arial,sans-serif;color:#E0C58A;font-size:13px;line-height:1.6;">'
            . $logo
            . '<div style="margin-top:12px;color:#FAF6ED;">CJ Travels</div>'
            . '<div>' . e(self::OFFICE_ADDRESS) . '</div>'
            . '<div>info@cjtravelsowerri.com' . $phoneLine . '</div>'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private static function plainFromHtml(string $html): string
    {
        $text = preg_replace('/<tr>/', "\n", $html) ?? $html;
        $text = preg_replace('/<\/td>/', ' ', $text) ?? $text;
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $text))));
        return preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    }

    private static function logoBytes(): ?string
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo-white.png';
        if (!is_file($path)) {
            return null;
        }
        $bytes = file_get_contents($path);
        return $bytes === false ? null : $bytes;
    }

    private static function buildMime(string $html, string $text, ?string $logo): array
    {
        $related = 'rel_' . bin2hex(random_bytes(8));
        $alt = 'alt_' . bin2hex(random_bytes(8));
        $body = "--{$related}\r\n"
            . "Content-Type: multipart/alternative; boundary=\"{$alt}\"\r\n\r\n"
            . "--{$alt}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text)) . "\r\n"
            . "--{$alt}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$alt}--\r\n";

        if ($logo !== null) {
            $body .= "--{$related}\r\n"
                . "Content-Type: image/png; name=\"cj-travels-logo.png\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Content-ID: <cjlogo>\r\n"
                . "Content-Disposition: inline; filename=\"cj-travels-logo.png\"\r\n\r\n"
                . chunk_split(base64_encode($logo)) . "\r\n";
        }
        $body .= "--{$related}--\r\n";

        return [
            'headers' => "MIME-Version: 1.0\r\nContent-Type: multipart/related; boundary=\"{$related}\"",
            'body' => $body,
        ];
    }

    private static function sendPhpMail(string $fromEmail, string $fromName, string $to, string $subject, array $mime): void
    {
        $encodedSubject = self::encodeHeader($subject);
        $headers = 'From: ' . self::formatAddress($fromEmail, $fromName) . "\r\n" . $mime['headers'];
        $ok = @mail($to, $encodedSubject, $mime['body'], $headers);
        if (!$ok) {
            throw new RuntimeException('The server mail program is not set up. Add the mailbox host, port, username, and password in Email center.');
        }
    }

    private static function sendSmtp(array $settings, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, array $mime): void
    {
        $host = $settings['host'];
        $port = (int)$settings['port'];
        $encryption = $settings['encryption'];
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('Could not connect to the mail server: ' . ($errstr !== '' ? $errstr : 'connection failed'));
        }
        stream_set_timeout($socket, 20);

        try {
            self::smtpExpect($socket, [220], '');
            self::smtpExpect($socket, [250], 'EHLO cjtravels.local');
            if ($encryption === 'tls') {
                self::smtpExpect($socket, [220], 'STARTTLS');
                $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (!stream_socket_enable_crypto($socket, true, $crypto)) {
                    throw new RuntimeException('Could not start a secure connection to the mail server.');
                }
                self::smtpExpect($socket, [250], 'EHLO cjtravels.local');
            }
            if ($settings['username'] !== '') {
                self::smtpExpect($socket, [334], 'AUTH LOGIN');
                self::smtpExpect($socket, [334], base64_encode($settings['username']));
                self::smtpExpect($socket, [235], base64_encode($settings['password']));
            }
            self::smtpExpect($socket, [250], 'MAIL FROM:<' . $fromEmail . '>');
            self::smtpExpect($socket, [250, 251], 'RCPT TO:<' . $toEmail . '>');
            self::smtpExpect($socket, [354], 'DATA');

            $data = 'From: ' . self::formatAddress($fromEmail, $fromName) . "\r\n"
                . 'To: ' . self::formatAddress($toEmail, $toName) . "\r\n"
                . 'Subject: ' . self::encodeHeader($subject) . "\r\n"
                . $mime['headers'] . "\r\n\r\n"
                . $mime['body'];
            $data = preg_replace('/^\./m', '..', $data) ?? $data;
            self::smtpExpect($socket, [250], $data . "\r\n.");
            self::smtpExpect($socket, [221], 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private static function smtpExpect($socket, array $okCodes, string $command): void
    {
        if ($command !== '') {
            fwrite($socket, $command . "\r\n");
        }
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            throw new RuntimeException(trim($response) !== '' ? trim($response) : 'The mail server closed the connection.');
        }
    }

    private static function formatAddress(string $email, string $name): string
    {
        $name = trim(str_replace(["\r", "\n"], '', $name));
        if ($name === '') {
            return $email;
        }
        return self::encodeHeader($name) . ' <' . $email . '>';
    }

    private static function encodeHeader(string $value): string
    {
        $clean = trim(str_replace(["\r", "\n"], '', $value));
        if (preg_match('/[^\x20-\x7E]/', $clean)) {
            return '=?UTF-8?B?' . base64_encode($clean) . '?=';
        }
        return $clean;
    }

    private static function ensureMailSettings(): void
    {
        RealtimeDataService::ensureSiteSettings();
    }

    private static function ensureLogTable(): void
    {
        $db = Database::getInstance();
        if ($db->getDriver() !== 'mysql') {
            return;
        }
        $db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS outbound_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_email VARCHAR(190) NOT NULL,
                recipient_name VARCHAR(160) NULL,
                subject VARCHAR(190) NOT NULL,
                body MEDIUMTEXT NOT NULL,
                kind VARCHAR(40) NOT NULL,
                related_ref VARCHAR(40) NULL,
                status VARCHAR(20) NOT NULL,
                error_message VARCHAR(255) NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    private static function logEmail(
        string $email,
        string $name,
        string $subject,
        string $body,
        string $kind,
        string $relatedRef,
        string $status,
        string $error
    ): void {
        try {
            self::ensureLogTable();
            Database::getInstance()->insert('outbound_emails', [
                'recipient_email' => $email !== '' ? $email : 'unknown',
                'recipient_name' => $name !== '' ? $name : null,
                'subject' => mb_substr($subject, 0, 190),
                'body' => $body,
                'kind' => $kind,
                'related_ref' => $relatedRef !== '' ? $relatedRef : null,
                'status' => $status,
                'error_message' => $error !== '' ? mb_substr($error, 0, 255) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // A mail log failure must not hide the send result.
        }
    }

    private static function recordFailure(string $email, string $name, string $subject, string $kind, string $ref, string $error): array
    {
        self::logEmail($email, $name, $subject, '', $kind, $ref, 'failed', $error);
        return ['success' => false, 'error' => $error];
    }
}
