<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.txt');
ob_start();

require_once '../includes/functions.php';
requireLogin();

try {
    $user = getUserData($_SESSION['user_id']);
    $sessions = getUserSessions($_SESSION['user_id']);
    $templates = function_exists('getAllTemplates') ? getAllTemplates() : [];
    // Debug: Log sessions data
    error_log("Sessions data: " . print_r($sessions, true));
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $sessions = [];
    $user = ['name' => 'Guest', 'email' => ''];
    $templates = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-poll"></i> Mentimeter</h3>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a class="nav-link" href="create_presentation.php"><i class="fas fa-presentation me-2"></i> New Presentation</a>
            <a class="nav-link" href="sessions.php"><i class="fas fa-layer-group me-2"></i> Sessions</a>
            <a class="nav-link" href="templates.php"><i class="fas fa-chart-bar me-2"></i> Templates</a>
            <a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-2"></i> Analytics</a>
            <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
            <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="form-control" placeholder="ค้นหา sessions, templates...">
            </div>
            <div class="user-profile d-flex align-items-center">
                <div class="user-avatar me-2">
                    <?= strtoupper(substr($user['name'] ?? 'G', 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($user['name'] ?? 'Guest') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                </div>
            </div>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>สวัสดี, <?= htmlspecialchars($user['name'] ?? 'Guest') ?>!</h1>
            <p class="mb-0">ยินดีต้อนรับสู่ Mentimeter Clone - สร้างการมีส่วนร่วมที่น่าประทับใจ</p>
            <div class="action-buttons d-flex flex-wrap gap-3">
                <button class="btn btn-new-menti" onclick="location.href='create_presentation.php'">
                    <i class="fas fa-plus me-2"></i>สร้าง Presentation ใหม่
                </button>
                <button class="btn btn-new-menti" onclick="location.href='templates.php'">
                    <i class="fas fa-layer-group me-2"></i>เลือก Template
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($sessions) ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= array_sum(array_column($sessions, 'responses')) ?: 0 ?></div>
                <div class="stat-label">Total Responses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count(array_filter($sessions, fn($s) => $s['status'] === 'active')) ?></div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>

        <!-- Question Types -->
        <div class="recent-section">
            <h2><i class="fas fa-question-circle me-2"></i>ประเภทคำถาม</h2>
            <div class="feature-grid">
                <div class="feature-card" onclick="createQuestion('wordcloud')">
                    <div class="feature-icon icon-wordcloud">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h5>Word Cloud</h5>
                    <p class="text-muted">สร้างเมฆคำจากคำตอบของผู้เข้าร่วม</p>
                </div>
                <div class="feature-card" onclick="createQuestion('poll')">
                    <div class="feature-icon icon-poll">
                        <i class="fas fa-poll"></i>
                    </div>
                    <h5>Multiple Choice</h5>
                    <p class="text-muted">คำถามแบบเลือกตอบ</p>
                </div>
                <div class="feature-card" onclick="createQuestion('openended')">
                    <div class="feature-icon icon-openended">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <h5>Open Ended</h5>
                    <p class="text-muted">คำถามปลายเปิดให้พิมพ์ตอบ</p>
                </div>
                <div class="feature-card" onclick="createQuestion('scales')">
                    <div class="feature-icon icon-scales">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h5>Scales</h5>
                    <p class="text-muted">คำถามแบบมาตรวัด</p>
                </div>
                <div class="feature-card" onclick="createQuestion('ranking')">
                    <div class="feature-icon icon-ranking">
                        <i class="fas fa-sort-numeric-down"></i>
                    </div>
                    <h5>Ranking</h5>
                    <p class="text-muted">คำถามแบบจัดอันดับ</p>
                </div>
                <div class="feature-card" onclick="createQuestion('pinimage')">
                    <div class="feature-icon icon-pinimage">
                        <i class="fas fa-map-pin"></i>
                    </div>
                    <h5>Pin on Image</h5>
                    <p class="text-muted">ปักหมุดบนรูปภาพ</p>
                </div>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div class="recent-section">
            <h2><i class="fas fa-history me-2"></i>Sessions ล่าสุด</h2>
            <?php if (empty($sessions)): ?>
                <div class="alert alert-info">ยังไม่มี Sessions คลิก "สร้าง Presentation ใหม่" เพื่อเริ่มต้น!</div>
            <?php else: ?>
                <?php foreach (array_slice($sessions, 0, 5) as $session): ?>
                <div class="session-card" data-session-id="<?= $session['id'] ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="session-title"><?= htmlspecialchars($session['title'] ?? 'Untitled') ?></div>
                            <div class="session-meta">
                                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($session['created_at'])) ?>
                                <span class="ms-3 response-count"><i class="fas fa-users me-1"></i><?= $session['responses'] ?? 0 ?> การตอบสนอง</span>
                                <span class="ms-3 badge bg-<?= $session['status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars($session['status']) ?>
                                </span>
                                <span class="ms-3 join-code">
                                    <i class="fas fa-key me-1"></i>Code: <?= htmlspecialchars($session['join_code'] ?? 'N/A') ?>
                                    <button class="btn btn-sm btn-outline-primary ms-2 copy-code" data-code="<?= htmlspecialchars($session['join_code'] ?? '') ?>">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="edit_slide.php?id=<?= $session['id'] ?>">แก้ไข</a></li>
                                <li><a class="dropdown-item" href="view-results.php?id=<?= $session['id'] ?>">ดูผลลัพธ์</a></li>
                                <li><a class="dropdown-item" href="join.php?code=<?= urlencode($session['join_code'] ?? '') ?>">เข้าร่วม</a></li>
                                <li><a class="dropdown-item text-danger" href="delete-session.php?id=<?= $session['id'] ?>">ลบ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="text-center mt-3">
                    <a href="sessions.php" class="btn btn-outline-primary">ดู Sessions ทั้งหมด</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Copy join code to clipboard
        document.querySelectorAll('.copy-code').forEach(button => {
            button.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                if (code) {
                    navigator.clipboard.writeText(code).then(() => {
                        this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy:', err);
                    });
                } else {
                    alert('No join code available.');
                }
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>