<?php
require_once '../includes/functions.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);
$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$session = getSessionData($session_id);
$slides = getSlides($session_id);

if (!$session || $session['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสไลด์ - <?= htmlspecialchars($session['title']) ?> - Mentimeter Clone</title>
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
            <a class="nav-link" href="create_presentation.php"><i class="fas fa-presentation me-2"></i> New Presentation</a>
            <a class="nav-link active" href="sessions.php"><i class="fas fa-layer-group me-2"></i> My Sessions</a>
            <a class="nav-link" href="templates.php"><i class="fas fa-chart-bar me-2"></i> Templates</a>
            <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" onclick="window.location.href='sessions.php'">
                    <i class="fas fa-arrow-left me-2"></i>กลับ
                </button>
                <h4 class="mb-0"><?= htmlspecialchars($session['title']) ?></h4>
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

        <!-- Slide Editor -->
        <div class="container-fluid">
            <div class="row">
                <!-- Left Panel - Slide List -->
                <div class="col-lg-3">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-list me-2"></i>สไลด์</h5>
                            <button class="btn btn-sm btn-primary" onclick="addSlide()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="slideList">
                                <?php foreach ($slides as $index => $slide): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?= $index == 0 ? 'active' : '' ?>" 
                                    onclick="loadSlide(<?= $slide['id'] ?>)">
                                    <span>สไลด์ <?= $index + 1 ?>: <?= htmlspecialchars($slide['question'] ?: 'ไม่มีคำถาม') ?></span>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSlide(<?= $slide['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Center Panel - Slide Editor -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-edit me-2"></i>แก้ไขสไลด์</h5>
                        </div>
                        <div class="card-body">
                            <form id="editSlideForm">
                                <input type="hidden" id="slideId" name="slide_id">
                                <div class="mb-3">
                                    <label for="questionType" class="form-label">ประเภทคำถาม</label>
                                    <select class="form-select" id="questionType" name="question_type" onchange="loadQuestionType(this.value)">
                                        <option value="poll">Multiple Choice</option>
                                        <option value="wordcloud">Word Cloud</option>
                                        <option value="openended">Open Ended</option>
                                        <option value="scales">Scales</option>
                                        <option value="ranking">Ranking</option>
                                        <option value="pinimage">Pin on Image</option>
                                    </select>
                                </div>
                                <div id="questionContent">
                                    <!-- Dynamic question content loaded via JS -->
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>บันทึกสไลด์
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Preview -->
                <div class="col-lg-3">
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
                                            <p class="text-muted">เลือกสไลด์เพื่อดูตัวอย่าง</p>
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
            if (document.querySelector('#slideList li')) {
                loadSlide(document.querySelector('#slideList li').getAttribute('onclick').match(/\d+/)[0]);
            }
        });

        async function loadSlide(slideId) {
            try {
                const response = await fetch(`../api/get_slide.php?id=${slideId}`);
                const slide = await response.json();
                if (slide.success) {
                    document.getElementById('slideId').value = slideId;
                    document.getElementById('questionType').value = slide.data.type;
                    loadQuestionType(slide.data.type, slide.data);
                    updatePreview(slide.data);
                    document.querySelectorAll('#slideList li').forEach(li => li.classList.remove('active'));
                    document.querySelector(`#slideList li[onclick="loadSlide(${slideId})"]`).classList.add('active');
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการโหลดสไลด์');
            }
        }

        function loadQuestionType(type, data = {}) {
            const content = document.getElementById('questionContent');
            content.innerHTML = getQuestionTypeHTML(type, data);
        }

        function getQuestionTypeHTML(type, data) {
            switch(type) {
                case 'poll':
                    const options = data.options || ['', ''];
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   value="${data.question || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="options">
                                ${options.map((opt, i) => `
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="options[]" value="${opt}" 
                                               placeholder="ตัวเลือกที่ ${i + 1}" required>
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `).join('')}
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
                                   value="${data.question || ''}" placeholder="เช่น คำแรกที่นึกถึงเมื่อได้ยินคำว่า..." required>
                        </div>
                        <div class="mb-3">
                            <label for="maxWords" class="form-label">จำนวนคำสูงสุด</label>
                            <input type="number" class="form-control" id="maxWords" name="max_words" 
                                   value="${data.max_words || 3}" min="1" max="10">
                        </div>
                    `;
                case 'openended':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <textarea class="form-control" id="question" name="question" rows="3"
                                      placeholder="คำถามปลายเปิดของคุณ" required>${data.question || ''}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="charLimit" class="form-label">จำกัดจำนวนตัวอักษร</label>
                            <input type="number" class="form-control" id="charLimit" name="char_limit" 
                                   value="${data.char_limit || ''}" placeholder="เช่น 200 (ไม่ระบุ = ไม่จำกัด)">
                        </div>
                    `;
                case 'scales':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   value="${data.question || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label for="scaleMin" class="form-label">ค่าน้อยสุด</label>
                            <input type="number" class="form-control" id="scaleMin" name="scale_min" 
                                   value="${data.scale_min || 1}">
                        </div>
                        <div class="mb-3">
                            <label for="scaleMax" class="form-label">ค่ามากสุด</label>
                            <input type="number" class="form-control" id="scaleMax" name="scale_max" 
                                   value="${data.scale_max || 5}">
                        </div>
                    `;
                case 'ranking':
                    const rankingOptions = data.options || ['', ''];
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" 
                                   value="${data.question || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="rankingOptions">
                                ${rankingOptions.map((opt, i) => `
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="ranking_options[]" value="${opt}" 
                                               placeholder="ตัวเลือกที่ ${i + 1}" required>
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `).join('')}
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
                                   value="${data.question || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">อัปโหลดรูปภาพ</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            ${data.image_url ? `<img src="${data.image_url}" class="img-fluid mt-2" style="max-height: 200px;">` : ''}
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

        function updatePreview(data) {
            const title = '<?= htmlspecialchars($session['title']) ?>';
            const question = data.question || 'คำถามของคุณ';
            const type = data.type || document.getElementById('questionType').value;

            document.getElementById('mobilePreview').innerHTML = `
                <div class="p-3">
                    <div class="text-center mb-3">
                        <h6 class="mb-1">${title}</h6>
                        <small class="text-muted">mentimeter.com/12345</small>
                    </div>
                    <div class="question-preview">
                        <h5 class="mb-3">${question}</h5>
                        ${getPreviewContent(type, data)}
                    </div>
                </div>
            `;
        }

        function getPreviewContent(type, data) {
            switch(type) {
                case 'poll':
                    const options = data.options || ['ตัวเลือก 1', 'ตัวเลือก 2'];
                    return options.map(option => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${option}</div>`
                    ).join('');
                case 'wordcloud':
                    return '<div class="form-control">พิมพ์คำตอบของคุณ...</div>';
                case 'openended':
                    return '<textarea class="form-control" rows="3" placeholder="พิมพ์คำตอบของคุณ..."></textarea>';
                case 'scales':
                    return `<input type="range" class="form-range" min="${data.scale_min || 1}" max="${data.scale_max || 5}">`;
                case 'ranking':
                    const rankingOptions = data.options || ['ตัวเลือก 1', 'ตัวเลือก 2'];
                    return rankingOptions.map((option, index) => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${index + 1}. ${option}</div>`
                    ).join('');
                case 'pinimage':
                    return `<div class="text-center"><img src="${data.image_url || '../assets/images/placeholder.png'}" class="img-fluid" style="max-height: 150px;"><p>ตัวอย่างรูปภาพ</p></div>`;
                default:
                    return '<div class="text-muted">ตัวอย่างจะแสดงที่นี่</div>';
            }
        }

        async function addSlide() {
            try {
                const response = await fetch('../api/add_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: <?= $session_id ?>, type: 'poll' })
                });
                const result = await response.json();
                if (result.success) {
                    const slideList = document.getElementById('slideList');
                    const newSlide = document.createElement('li');
                    newSlide.className = 'list-group-item d-flex justify-content-between align-items-center';
                    newSlide.setAttribute('onclick', `loadSlide(${result.slide_id})`);
                    newSlide.innerHTML = `
                        <span>สไลด์ ${slideList.children.length + 1}: ไม่มีคำถาม</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSlide(${result.slide_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    slideList.appendChild(newSlide);
                    loadSlide(result.slide_id);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการเพิ่มสไลด์');
            }
        }

        async function deleteSlide(slideId) {
            if (confirm('ยืนยันการลบสไลด์นี้?')) {
                try {
                    const response = await fetch('../api/delete_slide.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ slide_id: slideId })
                    });
                    const result = await response.json();
                    if (result.success) {
                        document.querySelector(`#slideList li[onclick="loadSlide(${slideId})"]`).remove();
                        if (document.querySelector('#slideList li')) {
                            loadSlide(document.querySelector('#slideList li').getAttribute('onclick').match(/\d+/)[0]);
                        } else {
                            document.getElementById('questionContent').innerHTML = '';
                            document.getElementById('mobilePreview').innerHTML = `
                                <div class="text-center p-4">
                                    <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">ไม่มีสไลด์</p>
                                </div>
                            `;
                        }
                    }
                } catch (error) {
                    alert('เกิดข้อผิดพลาดในการลบสไลด์');
                }
            }
        }

        document.getElementById('editSlideForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('../api/update_slide.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert('บันทึกสไลด์เรียบร้อย');
                    const slideItem = document.querySelector(`#slideList li[onclick="loadSlide(${formData.get('slide_id')})"] span`);
                    slideItem.textContent = `สไลด์ ${Array.from(slideItem.parentElement.parentElement.children).indexOf(slideItem.parentElement) + 1}: ${formData.get('question') || 'ไม่มีคำถาม'}`;
                    updatePreview({ 
                        question: formData.get('question'), 
                        type: formData.get('question_type'),
                        options: formData.getAll('options[]') || formData.getAll('ranking_options[]'),
                        max_words: formData.get('max_words'),
                        char_limit: formData.get('char_limit'),
                        scale_min: formData.get('scale_min'),
                        scale_max: formData.get('scale_max'),
                        image_url: result.image_url
                    });
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการบันทึก');
            }
        });
    </script>
</body>
</html>