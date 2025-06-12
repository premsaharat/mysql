<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

require_once '../includes/functions.php';

$join_code = isset($_GET['code']) ? sanitize($_GET['code']) : '';
$session = null;
$error = '';

if ($join_code) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT sessionid, session_name, join_code, status
            FROM tbsession
            WHERE join_code = ? AND status = 'active'
        ");
        $stmt->execute([$join_code]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $error = 'Session not found or not active';
        }
    } catch (Exception $e) {
        error_log("Join session error: " . $e->getMessage());
        $error = 'An error occurred while joining the session';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าร่วมโหวต - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/voting.css" rel="stylesheet">
</head>
<body>
    <div class="voting-container">
        <div class="voting-header">
            <h1><i class="fas fa-poll me-2"></i>Mentimeter</h1>
            <?php if ($session): ?>
                <div class="join-code"><?php echo htmlspecialchars($join_code); ?></div>
                <h3><?php echo htmlspecialchars($session['session_name']); ?></h3>
            <?php else: ?>
                <h3>เข้าร่วมโหวต</h3>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!$session): ?>
            <form id="joinForm" class="mb-4">
                <div class="mb-3">
                    <label for="joinCode" class="form-label">ป้อนรหัสเข้าร่วม</label>
                    <input type="text" class="form-control text-center" id="joinCode" name="join_code" value="<?php echo htmlspecialchars($join_code); ?>" maxlength="7" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>เข้าร่วม</button>
            </form>
        <?php else: ?>
            <div id="votingArea" data-session-id="<?php echo $session['sessionid']; ?>">
                <div class="question-card">
                    <div class="question-text">กำลังโหลดสไลด์...</div>
                    <div id="votingContent"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/voting.js"></script>
    <script>
        document.getElementById('joinForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            const joinCode = document.getElementById('joinCode').value.trim();
            window.location.href = `join.php?code=${encodeURIComponent(joinCode)}`;
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>