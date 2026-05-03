<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$staffRepo = new StaffRepository($pdo);
$today = date('Y-m-d');

// Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    foreach ($_POST['attendance'] as $staffId => $status) {
        $sql = "INSERT INTO staff_attendance (staff_id, work_date, status) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $pdo->prepare($sql)->execute([$staffId, $today, $status]);
    }
    $success = "تم حفظ التحضير لليوم بنجاح.";
}

$staff = $staffRepo->getWithCurrentWithdrawals($_SESSION['user_id'], $_SESSION['role']);

// Fetch today's attendance
$stmt = $pdo->prepare("SELECT staff_id, status FROM staff_attendance WHERE work_date = ?");
$stmt->execute([$today]);
$todayAttendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-clipboard-check me-2 text-success"></i> تحضير الموظفين - <?= $today ?></h3>
        <a href="staff_statements.php" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="fas fa-id-badge me-1"></i> كشوفات الحسابات
        </a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <form method="POST">
            <input type="hidden" name="mark_attendance" value="1">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">الموظف</th>
                            <th class="py-3">الصفة</th>
                            <th class="py-3 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): 
                            $status = $todayAttendance[$s['id']] ?? 'Present'; // Default to present for convenience
                        ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($s['role']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="p_<?= $s['id'] ?>" value="Present" <?= $status === 'Present' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success px-4" for="p_<?= $s['id'] ?>">حاضر</label>

                                        <input type="radio" class="btn-check" name="attendance[<?= $s['id'] ?>]" id="a_<?= $s['id'] ?>" value="Absent" <?= $status === 'Absent' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger px-4" for="a_<?= $s['id'] ?>">غائب</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light border-top text-end">
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                    <i class="fas fa-save me-2"></i> حفظ التحضير اليومي
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
