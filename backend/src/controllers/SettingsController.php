<?php
declare(strict_types=1);

final class SettingsController
{
    public static function clinic(array $config): void
    {
        Auth::requireRoles(['admin', 'staff', 'doctor', 'patient']);
        $pdo = Database::pdo($config);
        $row = $pdo->query('SELECT setting_id, clinic_name, address, contact, email, created_at, updated_at FROM clinic_settings ORDER BY setting_id ASC LIMIT 1')->fetch();
        Response::ok(['clinic' => is_array($row) ? $row : null]);
    }

    public static function updateClinic(array $config): void
    {
        Auth::requireRoles(['admin']);
        $body = Request::json();
        $clinicName = trim((string)($body['clinic_name'] ?? ''));
        $address = $body['address'] ?? null;
        $contact = $body['contact'] ?? null;
        $email = $body['email'] ?? null;

        if ($clinicName === '' || strlen($clinicName) > 190) Response::error('Invalid clinic_name', 422);
        $addressVal = is_string($address) ? trim($address) : null;
        if ($addressVal === '') $addressVal = null;
        $contactVal = is_string($contact) ? trim($contact) : null;
        if ($contactVal === '') $contactVal = null;
        $emailVal = is_string($email) ? trim($email) : null;
        if ($emailVal === '') $emailVal = null;
        if ($emailVal !== null && !filter_var($emailVal, FILTER_VALIDATE_EMAIL)) Response::error('Invalid email', 422);

        $pdo = Database::pdo($config);
        $existing = $pdo->query('SELECT setting_id FROM clinic_settings ORDER BY setting_id ASC LIMIT 1')->fetch();
        if (is_array($existing) && isset($existing['setting_id'])) {
            $stmt = $pdo->prepare('
                UPDATE clinic_settings
                SET clinic_name = :clinic_name, address = :address, contact = :contact, email = :email
                WHERE setting_id = :id
            ');
            $stmt->execute([
                'id' => (int)$existing['setting_id'],
                'clinic_name' => $clinicName,
                'address' => $addressVal,
                'contact' => $contactVal,
                'email' => $emailVal,
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO clinic_settings (clinic_name, address, contact, email)
                VALUES (:clinic_name, :address, :contact, :email)
            ');
            $stmt->execute([
                'clinic_name' => $clinicName,
                'address' => $addressVal,
                'contact' => $contactVal,
                'email' => $emailVal,
            ]);
        }

        self::clinic($config);
    }
}

