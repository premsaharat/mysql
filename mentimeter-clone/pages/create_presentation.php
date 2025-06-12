<?php
require_once '../includes/functions.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);
$templates = getTemplates();
$question_type = isset($_GET['type']) ? $_GET['type'] : 'poll';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Presentation ใหม่ - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/presentation.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-poll"></i> Mentimeter</h3>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a class="nav-link active" href="create_presentation.php"><i class="fas fa-presentation me-2"></i> New Presentation</a>
            <a class="nav-link" href="sessions.php"><i class="fas fa-layer-group me-2"></i> My Sessions</a>
            <a class="nav-link" href="templates.php"><i class="fas fa-chart-bar me-2"></i> Templates</a>
            <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" onclick="history.back()">
                    <i class="fas fa-arrow-left me-2"></i>กลับ
                </button>
                <h4 class="mb-0">สร้าง Presentation ใหม่</h4>
            </div>
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                </div>
            </div>
        </div>

        <!-- Create Presentation Form -->
        <div class="container-fluid">
            <div class="row">
                <!-- Left Panel - Form -->
                <div class="col-lg-8">
                    <form id="createPresentationForm" class="presentation-form">
                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">ชื่อ Presentation *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   placeholder="เช่น การสำรวจความพึงพอใจ" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description" class="form-label">คำอธิบาย</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="3" placeholder="อธิบายเกี่ยวกับ presentation นี้"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="template" class="form-label">Template</label>
                                            <select class="form-select" id="template" name="template_id">
                                                <option value="">เลือก Template (ไม่บังคับ)</option>
                                                <?php foreach ($templates as $template): ?>
                                                <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="category" class="form-label">หมวดหมู่</label>
                                            <select class="form-select" id="category" name="category">
                                                <option value="education">การศึกษา</option>
                                                <option value="business">ธุรกิจ</option>
                                                <option value="event">งานอีเวนต์</option>
                                                <option value="survey">แบบสำรวจ</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- First Slide -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-question-circle me-2"></i>สไลด์แรก</h5>
                                <div class="question-type-selector">
                                    <select class="form-select form-select-sm" id="questionType" name="question_type">
                                        <option value="poll" <?= $question_type == 'poll' ? 'selected' : '' ?>>Multiple Choice</option>
                                        <option value="wordcloud" <?= $question_type == 'wordcloud' ? 'selected' : '' ?>>Word Cloud</option>
                                        <option value="openended" <?= $question_type == 'openended' ? 'selected' : '' ?>>Open Ended</option>
                                        <option value="scales" <?= $question_type == 'scales' ? 'selected' : '' ?>>Scales</option>
                                        <option value="ranking" <?= $question_type == 'ranking' ? 'selected' : '' ?>>Ranking</option>
                                        <option value="pinimage" <?= $question_type == 'pinimage' ? 'selected' : '' ?>>Pin on Image</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="questionContent">
                                    <!-- Dynamic question content loaded via JS -->
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-cog me-2"></i>การตั้งค่า</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="allowAnonymous" name="allow_anonymous" checked>
                                            <label class="form-check-label" for="allowAnonymous">
                                                อนุญาตให้ตอบแบบไม่ระบุชื่อ
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="showResults" name="show_results" checked>
                                            <label class="form-check-label" for="showResults">
                                                แสดงผลลัพธ์แบบ Real-time
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="requireName" name="require_name">
                                            <label class="form-check-label" for="requireName">
                                                บังคับให้กรอกชื่อ
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="oneResponseOnly" name="one_response_only">
                                            <label class="form-check-label" for="oneResponseOnly">
                                                ตอบได้เพียงครั้งเดียว
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                <i class="fas fa-save me-2"></i>บันทึกร่าง
                            </button>
                            <div>
                                <button type="button" class="btn btn-success me-2" onclick="createAndPreview()">
                                    <i class="fas fa-eye me-2"></i>สร้างและดูตัวอย่าง
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>สร้าง Presentation
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Panel - Preview -->
                <div class="col-lg-4">
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5><i class="fas fa-mobile-alt me-2"></i>ตัวอย่างบนมือถือ</h5>
                        </div>
                        <div class="card-body">
                            <div class="mobile-preview">
                                <div class="mobile-frame">
                                    <div id="mobilePreview" class="mobile-content">
                                        <div class="text-center p-4">
                                            <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">กรอกข้อมูลเพื่อดูตัวอย่าง</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/presentation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadQuestionType('<?= $question_type ?>');
            updatePreview();
        });

        document.getElementById('questionType').addEventListener('change', function() {
            loadQuestionType(this.value);
            updatePreview();
        });

        document.getElementById('createPresentationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createPresentation();
        });

        document.querySelectorAll('input, textarea, select').forEach(function(element) {
            element.addEventListener('input', updatePreview);
        });

        function loadQuestionType(type) {
            const content = document.getElementById('questionContent');
            content.innerHTML = getQuestionTypeHTML(type);
        }

        function getQuestionTypeHTML(type) {
            switch(type) {
                case 'poll':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="options">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ 1" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ 2" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                                <i class="fas fa-plus me-1"></i>เพิ่มตัวเลือก
                            </button>
                        </div>
                    `;
                case 'wordcloud':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   placeholder="เช่น คำแรกที่นึกถึงเมื่อได้ยินคำว่า..." required>
                        </div>
                        <div class="mb-3">
                            <label for="maxWords" class="form-label">จำนวนคำสูงสุด</label>
                            <input type="number" class="form-control" id="maxWords" name="max_words" 
                                   value="3" min="1" max="10">
                        </div>
                    `;
                case 'openended':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <textarea class="form-control" id="question" name="question" rows="3"
                                      placeholder="คำถามปลายเปิดของคุณ" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="charLimit" class="form-label">จำกัดจำนวนตัวอักษร</label>
                            <input type="number" class="form-control" id="charLimit" name="char_limit" 
                                   placeholder="เช่น 200 (ไม่ระบุ = ไม่จำกัด)">
                        </div>
                    `;
                case 'scales':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label for="scaleMin" class="form-label">ค่าน้อยสุด</label>
                            <input type="number" class="form-control" id="scaleMin" name="scale_min" value="1">
                        </div>
                        <div class="mb-3">
                            <label for="scaleMax" class="form-label">ค่ามากสุด</label>
                            <input type="number" class="form-control" id="scaleMax" name="scale_max" value="5">
                        </div>
                    `;
                case 'ranking':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="rankingOptions">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="ranking_options[]" placeholder="ตัวเลือกที่ 1" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="ranking_options[]" placeholder="ตัวเลือกที่ 2" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRankingOption()">
                                <i class="fas fa-plus me-1"></i>เพิ่มตัวเลือก
                            </button>
                        </div>
                    `;
                case 'pinimage':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">อัปโหลดรูปภาพ</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    `;
                default:
                    return '<p class="text-muted">กำลังโหลด...</p>';
            }
        }

        function addOption() {
            const optionsContainer = document.getElementById('options');
            const optionCount = optionsContainer.children.length + 1;
            const newOption = document.createElement('div');
            newOption.className = 'input-group mb-2';
            newOption.innerHTML = `
                <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ ${optionCount}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            optionsContainer.appendChild(newOption);
        }

        function addRankingOption() {
            const optionsContainer = document.getElementById('rankingOptions');
            const optionCount = optionsContainer.children.length + 1;
            const newOption = document.createElement('div');
            newOption.className = 'input-group mb-2';
            newOption.innerHTML = `
                <input type="text" class="form-control" name="ranking_options[]" placeholder="ตัวเลือกที่ ${optionCount}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            optionsContainer.appendChild(newOption);
        }

        function removeOption(button) {
            const optionsContainer = button.parentElement.parentElement;
            if (optionsContainer.children.length > 2) {
                button.parentElement.remove();
            }
        }

        function updatePreview() {
            const title = document.getElementById('title').value || 'ชื่อ Presentation';
            const question = document.getElementById('question')?.value || 'คำถามของคุณ';
            const type = document.getElementById('questionType').value;

            document.getElementById('mobilePreview').innerHTML = `
                <div class="p-3">
                    <div class="text-center mb-3">
                        <h6 class="mb-1">${title}</h6>
                        <small class="text-muted">mentimeter.com/12345</small>
                    </div>
                    <div class="question-preview">
                        <h5 class="mb-3">${question}</h5>
                        ${getPreviewContent(type)}
                    </div>
                </div>
            `;
        }

        function getPreviewContent(type) {
            switch(type) {
                case 'poll':
                    const options = Array.from(document.querySelectorAll('input[name="options[]"]'))
                        .map(input => input.value || 'ตัวเลือก')
                        .slice(0, 3);
                    return options.map(option => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${option}</div>`
                    ).join('');
                case 'wordcloud':
                    return '<div class="form-control">พิมพ์คำตอบของคุณ...</div>';
                case 'openended':
                    return '<textarea class="form-control" rows="3" placeholder="พิมพ์คำตอบของคุณ..."></textarea>';
                case 'scales':
                    return '<input type="range" class="form-range" min="1" max="5">';
                case 'ranking':
                    const rankingOptions = Array.from(document.querySelectorAll('input[name="ranking_options[]"]'))
                        .map(input => input.value || 'ตัวเลือก')
                        .slice(0, 3);
                    return rankingOptions.map((option, index) => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${index + 1}. ${option}</div>`
                    ).join('');
                case 'pinimage':
                    return '<div class="text-center"><i class="fas fa-image fa-2x text-muted"></i><p>ตัวอย่างรูปภาพ</p></div>';
                default:
                    return '<div class="text-muted">ตัวอย่างจะแสดงที่นี่</div>';
            }
        }

        function saveDraft() {
            alert('บันทึกร่างเรียบร้อย');
            // Implement draft saving logic
        }

        function createAndPreview() {
            alert('สร้างและดูตัวอย่าง');
            // Implement preview logic
        }

        async function createPresentation() {
            const formData = new FormData(document.getElementById('createPresentationForm'));
            formData.append('user_id', '<?= $_SESSION['user_id'] ?>');

            try {
                const response = await fetch('../api/create_session.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('สร้าง Presentation เรียบร้อย');
                    window.location.href = `edit_slide.php?id=${result.session_id}`;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }
    </script>
</body>
</html>