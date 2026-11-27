
<?php
require __DIR__ . '/../auth/session.php';

require_admin();

$pdo = db();

$userId = (int)$_SESSION['user']['id'];

// Handle notification actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_ok()) {

        flash(
            'danger',
            'Your session expired. Please try again.'
        );

        redirect(url('pages/notifications.php'));

    }

    $action = $_POST['action'] ?? '';

    // Mark one notification as read.
    if ($action === 'mark_read') {

        $id = (int)($_POST['notification_id'] ?? 0);

        $stmt = $pdo->prepare("
            UPDATE notifications

            SET
                is_read = 1,
                read_at = NOW()

            WHERE id = ?
            AND user_id = ?
        ");

        $stmt->execute([$id, $userId]);

        $applicationId = (int)($_POST['application_id'] ?? 0);

        // Open the corresponding application.
        if ($applicationId > 0) {

            redirect(
                url('pages/applications.php?open=' . $applicationId)
            );

        }

        redirect(url('pages/notifications.php'));

    }

    // Mark all notifications as read.
    if ($action === 'mark_all') {

        $stmt = $pdo->prepare("
            UPDATE notifications

            SET
                is_read = 1,
                read_at = NOW()

            WHERE user_id = ?
            AND is_read = 0
        ");

        $stmt->execute([$userId]);

        flash(
            'success',
            'All notifications have been marked as read.'
        );

        redirect(url('pages/notifications.php'));

    }

}

// Retrieve administrator notifications.
$stmt = $pdo->prepare("
    SELECT
        n.*,
        a.applicant_name,
        a.position

    FROM notifications n

    LEFT JOIN applications a
        ON a.id = n.application_id

    WHERE n.user_id = ?

    ORDER BY
        n.created_at DESC,
        n.id DESC

    LIMIT 200
");

$stmt->execute([$userId]);

$notifications = $stmt->fetchAll();

// Calculate unread notifications.
$unread = 0;

foreach ($notifications as $notification) {

    if (!(int)$notification['is_read']) {
        $unread++;
    }

}

$title = 'Notifications';

include __DIR__ . '/../components/header.php';

?>

<!-- PAGE HEADER -->

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

    <div>

        <span class="eyebrow">
            Administration
        </span>

        <h1 class="h2 brand-title mb-1">
            <i class="bi bi-bell"></i>
            Notifications
        </h1>

        <p class="text-body-secondary mb-0">

            Stay updated on new applications and changes submitted by applicants.

        </p>

    </div>


    <?php if ($unread > 0): ?>

        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>">

            <input
                type="hidden"
                name="action"
                value="mark_all">

            <button
                type="submit"
                class="btn btn-outline-primary">

                <i class="bi bi-check2-all"></i>

                Mark all as read

            </button>

        </form>

    <?php endif; ?>

</div>


<!-- NOTIFICATION SUMMARY -->

<div class="row g-3 mb-4">

    <div class="col-md-6">

        <div class="card h-100">

            <div class="card-body d-flex align-items-center gap-3">

                <div class="fs-2 text-primary">
                    <i class="bi bi-bell"></i>
                </div>

                <div>

                    <div class="text-body-secondary small">
                        Recent notifications
                    </div>

                    <div class="h3 mb-0">
                        <?= count($notifications) ?>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-6">

        <div class="card h-100">

            <div class="card-body d-flex align-items-center gap-3">

                <div class="fs-2 text-danger">
                    <i class="bi bi-envelope-exclamation"></i>
                </div>

                <div>

                    <div class="text-body-secondary small">
                        Unread notifications
                    </div>

                    <div class="h3 mb-0">
                        <?= $unread ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- NOTIFICATION LIST -->

<div class="card">

    <div class="card-header d-flex align-items-center justify-content-between">

        <h2 class="h5 mb-0">
            Recent activity
        </h2>

        <span class="small text-body-secondary">
            Latest 200 notifications
        </span>

    </div>


    <div class="list-group list-group-flush">

        <?php foreach ($notifications as $n):

            $isUnread = !(int)$n['is_read'];

            $isNewApplication =
                $n['type'] === 'new_application';

            $icon = $isNewApplication
                ? 'person-plus'
                : 'person-lines-fill';

            $iconColor = $isNewApplication
                ? 'text-success'
                : 'text-primary';

            $typeLabel = $isNewApplication
                ? 'New Application'
                : 'Application Updated';

        ?>

            <div class="list-group-item p-3 <?= $isUnread ? 'bg-body-tertiary' : '' ?>">

                <div class="d-flex align-items-start gap-3">

                    <!-- ICON -->

                    <div class="fs-3 <?= $iconColor ?>">

                        <i
                            class="bi bi-<?= $icon ?>"
                            aria-hidden="true">
                        </i>

                    </div>


                    <!-- NOTIFICATION INFORMATION -->

                    <div class="flex-grow-1">

                        <div class="d-flex flex-wrap align-items-center gap-2">

                            <strong>
                                <?= e($n['title']) ?>
                            </strong>


                            <span class="badge <?= $isNewApplication
                                ? 'text-bg-success'
                                : 'text-bg-primary' ?>">

                                <?= e($typeLabel) ?>

                            </span>


                            <?php if ($isUnread): ?>

                                <span class="badge text-bg-danger">
                                    Unread
                                </span>

                            <?php else: ?>

                                <span class="badge text-bg-secondary">
                                    Read
                                </span>

                            <?php endif; ?>

                        </div>


                        <p class="mb-1 mt-2">

                            <?= e($n['message']) ?>

                        </p>


                        <?php if (!empty($n['applicant_name'])): ?>

                            <div class="small text-body-secondary">

                                <i class="bi bi-person"></i>

                                Applicant:
                                <?= e($n['applicant_name']) ?>

                            </div>

                            <div class="small text-body-secondary">

                                <i class="bi bi-briefcase"></i>

                                Position:
                                <?= e($n['position']) ?>

                            </div>

                        <?php endif; ?>


                        <small class="d-block text-body-secondary mt-2">

                            <i class="bi bi-clock"></i>

                            <?= e(date(
                                'F j, Y \a\t g:i A',
                                strtotime($n['created_at'])
                            )) ?>

                        </small>

                    </div>


                    <!-- ACTIONS -->

                    <div class="flex-shrink-0">

                        <?php if (!empty($n['application_id'])): ?>

                            <form method="post">

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= csrf() ?>">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_read">

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int)$n['id'] ?>">

                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int)$n['application_id'] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-primary">

                                    View application

                                    <i class="bi bi-arrow-right"></i>

                                </button>

                            </form>

                        <?php elseif ($isUnread): ?>

                            <form method="post">

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= csrf() ?>">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_read">

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int)$n['id'] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-secondary">

                                    Mark read

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>


        <!-- EMPTY NOTIFICATIONS -->

        <?php if (empty($notifications)): ?>

            <div class="text-center py-5 px-3">

                <i class="bi bi-bell-slash display-4 text-body-secondary"></i>

                <h2 class="h5 mt-3">
                    No notifications yet
                </h2>

                <p class="text-body-secondary mb-0">

                    New applications and applicant updates
                    will appear here.

                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include __DIR__ . '/../components/footer.php'; ?>
