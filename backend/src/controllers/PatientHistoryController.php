<?php
declare(strict_types=1);

final class PatientHistoryController
{
    public static function create(array $config): void
    {
        Auth::requireRoles(['doctor']);
        $body = Request::json();

        $patientId = (int)($body['patient_id'] ?? 0);
        $visitDate = (string)($body['visit_date'] ?? date('Y-m-d'));
        $notes = $body['notes'] ?? null;
        $diagnosis = $body['diagnosis'] ?? null;

        if ($patientId <= 0) Response::error('Invalid patient_id', 422);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $visitDate)) Response::error('Invalid visit_date', 422);
        $notesVal = is_string($notes) ? trim($notes) : null;
        if ($notesVal === '') $notesVal = null;
        $diagVal = is_string($diagnosis) ? trim($diagnosis) : null;
        if ($diagVal === '') $diagVal = null;

        $pdo = Database::pdo($config);
        $me = DoctorModel::findByUserId($pdo, (int)Auth::userId());
        if ($me === null) Response::error('Doctor profile not found', 404);
        $doctorId = (int)$me['doctor_id'];

        if (PatientModel::find($pdo, $patientId) === null) Response::error('Patient not found', 404);

        $stmt = $pdo->prepare('
            INSERT INTO patient_history (patient_id, doctor_id, visit_date, notes, diagnosis)
            VALUES (:patient_id, :doctor_id, :visit_date, :notes, :diagnosis)
        ');
        $stmt->execute([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'visit_date' => $visitDate,
            'notes' => $notesVal,
            'diagnosis' => $diagVal,
        ]);
        $id = (int)$pdo->lastInsertId();

        $row = $pdo->prepare('SELECT * FROM patient_history WHERE history_id = :id');
        $row->execute(['id' => $id]);
        Response::ok(['history' => $row->fetch()], ['created' => true]);
    }
}

