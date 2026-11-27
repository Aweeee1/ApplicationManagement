
<?php
// Creates notifications for all active administrators.

function notify_admins(
    int $applicationId,
    string $type,
    string $title,
    string $message
): void {

    if (!in_array(
        $type,
        ['new_application', 'applicant_updated'],
        true
    )) {
        return;
    }

    try {

        $pdo = db();

        $stmt = $pdo->prepare("
            INSERT INTO notifications
                (user_id, application_id, type, title, message)

            SELECT
                id, ?, ?, ?, ?

            FROM users

            WHERE role = 'admin'
            AND status = 'active'
        ");

        $stmt->execute([
            $applicationId,
            $type,
            mb_substr($title, 0, 150),
            mb_substr($message, 0, 500)
        ]);

    } catch (Throwable $e) {

        // A notification failure must not prevent an application
        // from being successfully submitted or updated.
        error_log(
            'La Rose Noire notification error: ' . $e->getMessage()
        );

    }
}
