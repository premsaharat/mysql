<?php
require_once '../includes/functions.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);
$question_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'poll';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Presentation ใหม่ - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .mobile-frame {
            border: 2px solid #dee2e6;
            border-radius: 20px;
            padding: 20px;
            background: #fff;
            max-width: 300px;
            margin: 0 auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .mobile-content {
            background: #f8f9fa;
            border-radius: 10px;
            min-height: 400px;
            padding: 15px;
        }
        .question-preview .btn {
            width: 100%;
            text-align: left;
        }
        .form-control, .form-select {
            border-radius: 10px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-poll"></i> Mentimeter</h3>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a class="nav-link active" href="#"><i class="fas fa-presentation me-2"></i> New Presentation</a>
            <a class="nav-link" href="sessions.php"><i class="fas fa-layer-group me-2"></i> My Sessions</a>
            <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left me-2"></i>กลับ
                </button>
                <h4 class="mb-0">สร้าง Presentation ใหม่</h4>
            </div>
            <div class="user-profile d-flex align-items-center">
                <div class="user-avatar me-2"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <form id="createPresentationForm" enctype="multipart/form-data">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">ชื่อ Presentation *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">คำอธิบาย</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="isPublic" name="is_public">
                                            <label class="form-check-label" for="isPublic">Public Presentation</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxParticipants" class="form-label">จำนวนผู้เข้าร่วมสูงสุด</label>
                                            <input type="number" class="form-control" id="maxParticipants" name="max_participants" value="1000" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-question-circle me-2"></i>สไลด์แรก</h5>
                                <select class="form-select w-auto" id="questionType" name="question_type">
                                    <option value="poll" <?php echo $question_type == 'poll' ? 'selected' : ''; ?>>Multiple Choice</option>
                                    <option value="wordcloud" <?php echo $question_type == 'wordcloud' ? 'selected' : ''; ?>>Word Cloud</option>
                                    <option value="openended" <?php echo $question_type == 'openended' ? 'selected' : ''; ?>>Open Ended</option>
                                    <option value="scales" <?php echo $question_type == 'scales' ? 'selected' : ''; ?>>Scales</option>
                                    <option value="ranking" <?php echo $question_type == 'ranking' ? 'selected' : ''; ?>>Ranking</option>
                                    <option value="pinimage" <?php echo $question_type == 'pinimage' ? 'selected' : ''; ?>>Pin on Image</option>
                                </select>
                            </div>
                            <div class="card-body" id="questionContent"></div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-cog me-2"></i>การตั้งค่า</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="allowAnonymous" name="allow_anonymous" checked>
                                            <label class="form-check-label" for="allowAnonymous">อนุญาตให้ตอบแบบไม่ระบุชื่อ</label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="showResults" name="show_results" checked>
                                            <label class="form-check-label" for="showResults">แสดงผลลัพธ์แบบ Real-time</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="requireName" name="require_name">
                                            <label class="form-check-label" for="requireName">บังคับให้กรอกชื่อ</label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="oneResponseOnly" name="one_response_only">
                                            <label class="form-check-label" for="oneResponseOnly">ตอบได้เพียงครั้งเดียว</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">บันทึกร่าง</button>
                            <button type="submit" class="btn btn-primary">สร้าง Presentation</button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5><i class="fas fa-mobile-alt me-2"></i>ตัวอย่างบนมือถือ</h5>
                        </div>
                        <div class="card-body">
                            <div class="mobile-frame">
                                <div id="mobilePreview" class="mobile-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadQuestionType('<?php echo $question_type; ?>');
            document.getElementById('questionType').addEventListener('change', () => loadQuestionType(document.getElementById('questionType').value));
            document.getElementById('createPresentationForm').addEventListener('submit', createPresentation);
            document.querySelectorAll('input, textarea, select').forEach(elem => elem.addEventListener('input', updatePreview));
            updatePreview();
        });

        function loadQuestionType(type) {
            const content = document.getElementById('questionContent');
            content.innerHTML = getQuestionTypeHTML(type);
        }

        function getQuestionTypeHTML(type) {
            switch (type) {
                case 'poll':
                case 'ranking':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="optionsContainer">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ 1" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="fas fa-trash"></i></button>
                                </div>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ 2" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="fas fa-trash"></i></button>
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
                            <input type="text" class="form-control" id="question" name="question" placeholder="เช่น คำแรกที่นึกถึงเมื่อได้ยินคำว่า..." required>
                        </div>
                        <div class="mb-3">
                            <label for="maxWords" class="form-label">จำนวนคำสูงสุด</label>
                            <input type="number" class="form-control" id="maxWords" name="max_words" value="3" min="1" max="10">
                        </div>
                    `;
                case 'openended':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <textarea class="form-control" id="question" name="question" rows="3" placeholder="คำถามปลายเปิดของคุณ" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="charLimit" class="form-label">จำกัดจำนวนตัวอักษร</label>
                            <input type="number" class="form-control" id="charLimit" name="char_limit" placeholder="เช่น 200">
                        </div>
                    `;
                case 'scales':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scaleMin" class="form-label">ค่าน้อยสุด</label>
                                <input type="number" class="form-control" id="scaleMin" name="scale_min" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scaleMax" class="form-label">ค่ามากสุด</label>
                                <input type="number" class="form-control" id="scaleMax" name="scale_max" value="5">
                            </div>
                        </div>
                    `;
                case 'pinimage':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" placeholder="คำถามของคุณ" required>
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
            const container = document.getElementById('optionsContainer');
            const count = container.children.length + 1;
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="text" class="form-control" name="options[]" placeholder="ตัวเลือกที่ ${count}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
            updatePreview();
        }

        function removeOption(button) {
            const container = button.parentElement.parentElement;
            if (container.children.length > 2) {
                button.parentElement.remove();
                updatePreview();
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
                        <small class="text-muted">mentimeter.com/${Math.floor(Math.random() * 100000)}</small>
                    </div>
                    <div class="question-preview">
                        <h5 class="mb-3">${question}</h5>
                        ${getPreviewContent(type)}
                    </div>
                </div>
            `;
        }

        function getPreviewContent(type) {
            switch (type) {
                case 'poll':
                case 'ranking':
                    const options = Array.from(document.querySelectorAll('input[name="options[]"]'))
                        .map(input => input.value || 'ตัวเลือก').slice(0, 3);
                    return options.map((opt, i) => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${type === 'ranking' ? `${i + 1}. ` : ''}${opt}</div>`
                    ).join('');
                case 'wordcloud':
                    return '<div class="form-control">พิมพ์คำตอบของคุณ...</div>';
                case 'openended':
                    return '<textarea class="form-control" rows="3" placeholder="พิมพ์คำตอบของคุณ..."></textarea>';
                case 'scales':
                    return `<input type="range" class="form-range" min="${document.getElementById('scaleMin')?.value || 1}" max="${document.getElementById('scaleMax')?.value || 5}">`;
                case 'pinimage':
                    return '<div class="text-center"><i class="fas fa-image fa-2x text-muted"></i><p>ตัวอย่างรูปภาพ</p></div>';
                default:
                    return '<div class="text-muted">ตัวอย่างจะแสดงที่นี่</div>';
            }
        }

        async function createPresentation(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('createPresentationForm'));
            try {
                const response = await fetch('../api/create_session.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                console.log('Raw response:', text); // Debug response
                const result = JSON.parse(text);
                if (result.success) {
                    alert('สร้าง Presentation เรียบร้อย');
                    window.location.href = `edit_slide.php?id=${result.session_id}`;
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
            }
        }

        async function saveDraft() {
            alert('บันทึกร่างเรียบร้อย (ยังไม่ implement การบันทึกร่าง)');
        }
    </script>
</body>
</html>