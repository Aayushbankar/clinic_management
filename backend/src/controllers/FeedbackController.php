<?php
declare(strict_types=1);

final class FeedbackController
{
    public static function index(array $config): void
    {
        Auth::requireRoles(['admin', 'staff']);
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('
            SELECT
              f.feedback_id,
              f.patient_id,
              p.name AS patient_name,
              f.rating,
              f.comments,
              f.created_at
            FROM feedback f
            JOIN patients p ON p.patient_id = f.patient_id
            ORDER BY f.created_at DESC, f.feedback_id DESC
        ');
        $rows = $stmt->fetchAll();
        Response::ok(['items' => is_array($rows) ? $rows : []]);
    }

    public static function create(array $config): void
    {
        Auth::requireRoles(['patient']);
        $body = Request::json();
        $rating = $body['rating'] ?? null;
        $comments = $body['comments'] ?? null;

        if (!is_numeric($rating) || (int)$rating < 1 || (int)$rating > 5) {
            Response::error('Invalid rating (1-5)', 422);
        }
        $commentsVal = is_string($comments) ? trim($comments) : null;
        if ($commentsVal === '') $commentsVal = null;

        $pdo = Database::pdo($config);
        $me = PatientModel::findByUserId($pdo, (int)Auth::userId());
        if ($me === null) Response::error('Patient profile not found', 404);
        $patientId = (int)$me['patient_id'];

        $stmt = $pdo->prepare('
            INSERT INTO feedback (patient_id, rating, comments)
            VALUES (:patient_id, :rating, :comments)
        ');
        $stmt->execute([
            'patient_id' => $patientId,
            'rating' => (int)$rating,
            'comments' => $commentsVal,
        ]);

        Response::ok(['created' => true]);
    }
}

