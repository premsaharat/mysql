<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.txt');
ob_start();

require_once '../includes/functions.php';
requireLogin();

try {
    $user_id = $_SESSION['user_id'];
    $session_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$session_id) {
        throw new Exception("Invalid session ID");
    }

    $conn = getDBConnection();
    // Verify session belongs to user
    $stmt = $conn->prepare("SELECT sessionid, session_name, join_code, status FROM tbsession WHERE sessionid = ? AND created_by = ?");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$session) {
        throw new Exception("Session not found or unauthorized");
    }

    // Get total views
    $stmt = $conn->prepare("SELECT COUNT(*) as views FROM tbviews WHERE sessionid = ?");
    $stmt->execute([$session_id]);
    $views = $stmt->fetch(PDO::FETCH_ASSOC)['views'];

    // Get total responses
    $stmt = $conn->prepare("SELECT COUNT(*) as responses FROM tbresponse WHERE slideid IN (SELECT slideid FROM tbslide WHERE sessionid = ?)");
    $stmt->execute([$session_id]);
    $responses = $stmt->fetch(PDO::FETCH_ASSOC)['responses'];

} catch (Exception $e) {
    error_log("Results error: " . $e->getMessage());
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลลัพธ์ - <?= htmlspecialchars($session['session_name']) ?> - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/wordcloud2@1.2.2/wordcloud2.min.js"></script>
    <style>
        .results-container { padding: 20px; }
        .result-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .chart-container { position: relative; height: 400px; width: 100%; }
        .wordcloud-container { height: 400px; width: 100%; border: 1px solid #e9ecef; border-radius: 10px; }
        .last-updated { font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-poll"></i> Mentimeter</h3>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
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
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <h2>ผลลัพธ์: <?= htmlspecialchars($session['session_name']) ?></h2>
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> กลับไปที่ Dashboard</a>
            </div>
        </div>

        <div class="results-container">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $responses ?></div>
                    <div class="stat-label">การตอบสนอง</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $views ?></div>
                    <div class="stat-label">การดู</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= htmlspecialchars($session['join_code']) ?></div>
                    <div class="stat-label">รหัสเข้าร่วม</div>
                </div>
            </div>

            <!-- Results -->
            <div id="results-list"></div>
            <div class="last-updated">อัปเดตล่าสุด: <span id="last-updated-time"></span></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/wordcloud.js"></script>
    <script>
        const sessionId = <?= json_encode($session_id) ?>;
        let lastUpdate = new Date().toISOString();

        // Fetch initial results
        async function fetchResults() {
            try {
                const response = await fetch(`api/get_results.php?session_id=${sessionId}`);
                const data = await response.json();
                if (data.success) {
                    renderResults(data.results);
                    updateLastUpdated();
                } else {
                    console.error('Failed to fetch results:', data.error);
                }
            } catch (error) {
                console.error('Error fetching results:', error);
            }
        }

        // Render results
        function renderResults(results) {
            const resultsList = document.getElementById('results-list');
            resultsList.innerHTML = '';
            results.forEach((slide, index) => {
                const card = document.createElement('div');
                card.className = 'result-card';
                card.innerHTML = `
                    <h4>${slide.question}</h4>
                    <div id="chart-${slide.slideid}" class="${slide.type === 'wordcloud' ? 'wordcloud-container' : 'chart-container'}"></div>
                `;
                resultsList.appendChild(card);

                if (slide.type === 'poll') {
                    renderBarChart(`chart-${slide.slideid}`, slide.responses);
                } else if (slide.type === 'wordcloud') {
                    renderWordCloud(`chart-${slide.slideid}`, slide.responses);
                } else if (slide.type === 'openended') {
                    renderTextResponses(`chart-${slide.slideid}`, slide.responses);
                }
            });
        }

        // Render Bar Chart for Multiple Choice
        function renderBarChart(containerId, responses) {
            const ctx = document.getElementById(containerId).appendChild(document.createElement('canvas')).getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: responses.map(r => r.choice_text),
                    datasets: [{
                        label: 'จำนวนการตอบ',
                        data: responses.map(r => r.count),
                        backgroundColor: 'rgba(102, 126, 234, 0.5)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'จำนวนการตอบ' } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Render Text Responses for Open Ended
        function renderTextResponses(containerId, responses) {
            const container = document.getElementById(containerId);
            container.innerHTML = responses.map(r => `<p class="border-bottom py-2">${r.response_text}</p>`).join('');
        }

        // Update last updated time
        function updateLastUpdated() {
            const now = new Date();
            document.getElementById('last-updated-time').textContent = now.toLocaleString('th-TH');
            lastUpdate = now.toISOString();
        }

        // Poll for live updates
        async function pollLiveUpdates() {
            try {
                const response = await fetch(`api/live_update.php?session_id=${sessionId}&last_update=${encodeURIComponent(lastUpdate)}`);
                const data = await response.json();
                if (data.success && data.has_updates) {
                    fetchResults();
                }
            } catch (error) {
                console.error('Error polling live updates:', error);
            }
            setTimeout(pollLiveUpdates, 5000); // Poll every 5 seconds
        }

        // Initialize
        fetchResults();
        pollLiveUpdates();
    </script>
</body>
</html>
<?php ob_end_flush(); ?>