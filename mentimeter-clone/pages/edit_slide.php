<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // สำหรับ debug
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');
ob_start();

if (!file_exists('../includes/functions.php')) {
    die("Error: functions.php not found at ../includes/functions.php");
}
require_once '../includes/functions.php';

if (!function_exists('getSessionData')) {
    error_log("Function getSessionData() is not defined");
    die("Error: getSessionData() function is not defined");
}

requireLogin();

try {
    $user = getUserData($_SESSION['user_id']);
    $session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($session_id <= 0) {
        throw new Exception('Invalid session ID');
    }

    $session = getSessionData($session_id);
    if (!$session) {
        error_log("Session not found: ID $session_id");
        header('Location: dashboard.php?error=session_not_found');
        exit;
    }
    if ($session['created_by'] != $_SESSION['user_id']) {
        header('Location: dashboard.php');
        error_log("Unauthorized access: User {$_SESSION['user_id']} to session $session_id");
        exit;
    }

    $slides = getSlides($session_id);
    if (empty($slides)) {
        error_log("No slides found for session $session_id");
    }
} catch (Exception $e) {
    error_log("Error in edit_slide.php: " . $e->getMessage());
    header('Location: dashboard.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสไลด์ - <?php echo htmlspecialchars($session['session_name']); ?> - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .slide-list { max-height: 600px; overflow-y: auto; }
        .list-group-item.active { background-color: #667eea; color: white; }
        .mobile-frame { border: 2px solid #dee2e6; border-radius: 20px; padding: 20px; background: #fff; max-width: 300px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .mobile-content { background: #f8f9fa; border-radius: 10px; min-height: 400px; padding: 15px; }
        .card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-poll"></i> Mentimeter</h3>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
            <a class="nav-link" href="create_presentation.php"><i class="fas fa-presentation me-2"></i> New Presentation</a>
            <a class="nav-link active" href="sessions.php"><i class="fas fa-layer-group me-2"></i> My Sessions</a>
            <a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary me-3" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left me-2"></i>กลับ
                </button>
                <h4 class="mb-0"><?php echo htmlspecialchars($session['session_name']); ?></h4>
            </div>
            <div class="user-profile d-flex align-items-center">
                <div class="user-avatar me-2"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-list me-2"></i>สไลด์</h5>
                            <button class="btn btn-sm btn-primary" onclick="addSlide()"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush slide-list" id="slideList">
                                <?php if (empty($slides)): ?>
                                    <li class="list-group-item text-muted">ไม่มีสไลด์</li>
                                <?php else: ?>
                                    <?php foreach ($slides as $index => $slide): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $index == 0 ? 'active' : ''; ?>" 
                                            onclick="loadSlide(<?php echo $slide['slideid']; ?>)">
                                            <span>สไลด์ <?php echo $index + 1; ?>: <?php echo htmlspecialchars($slide['question_text'] ?: 'ไม่มีคำถาม'); ?></span>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSlide(<?php echo $slide['slideid']; ?>, event)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-edit me-2"></i>แก้ไขสไลด์</h5>
                        </div>
                        <div class="card-body">
                            <form id="editSlideForm" enctype="multipart/form-data">
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
                                <div id="questionContent"></div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>บันทึกสไลด์</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="card sticky-top">
                        <div class="card-header">
                            <h5><i class="fas fa-mobile-alt me-2"></i>ตัวอย่างบนมือถือ</h5>
                        </div>
                        <div class="card-body">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // เก็บ state ของ slide ปัจจุบัน
        let currentSlideData = null;

        document.addEventListener('DOMContentLoaded', () => {
            const firstSlide = document.querySelector('#slideList li:not(.text-muted)');
            if (firstSlide) {
                const slideId = firstSlide.getAttribute('onclick').match(/\d+/)[0];
                loadSlide(slideId);
            }
        });

        async function loadSlide(slideId) {
            try {
                const response = await fetch(`../api/get_slide.php?id=${slideId}`);
                const text = await response.text();
                console.log('Raw slide response:', text); // Debug
                const slide = JSON.parse(text);
                if (slide.success) {
                    currentSlideData = slide.data; // เก็บข้อมูล slide
                    document.getElementById('slideId').value = slideId;
                    document.getElementById('questionType').value = slide.data.slide_type;
                    loadQuestionType(slide.data.slide_type, slide.data);
                    updatePreview(slide.data);
                    document.querySelectorAll('#slideList li').forEach(li => li.classList.remove('active'));
                    document.querySelector(`#slideList li[onclick="loadSlide(${slideId})"]`).classList.add('active');
                } else {
                    console.error('Slide load error:', slide.message);
                    alert('เกิดข้อผิดพลาด: ' + slide.message);
                }
            } catch (error) {
                console.error('Load slide error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดสไลด์: ' + error.message);
            }
        }

        function loadQuestionType(type, data = {}) {
            const content = document.getElementById('questionContent');
            content.innerHTML = getQuestionTypeHTML(type, data);
            document.querySelectorAll('input, textarea, select').forEach(elem => elem.addEventListener('input', () => updatePreview(data)));
        }

        function getQuestionTypeHTML(type, data) {
            switch (type) {
                case 'poll':
                case 'ranking':
                    const options = data.choices || [{choice_text: ''}, {choice_text: ''}];
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" value="${data.question_text || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ตัวเลือก *</label>
                            <div id="optionsContainer">
                                ${options.map((opt, i) => `
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="options[]" value="${opt.choice_text || ''}" placeholder="ตัวเลือกที่ ${i + 1}" required>
                                        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)"><i class="fas fa-trash"></i></button>
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
                            <input type="text" class="form-control" id="question" name="question" value="${data.question_text || ''}" placeholder="เช่น คำแรกที่นึกถึงเมื่อได้ยินคำว่า..." required>
                        </div>
                        <div class="mb-3">
                            <label for="maxWords" class="form-label">จำนวนคำสูงสุด</label>
                            <input type="number" class="form-control" id="maxWords" name="max_words" value="${data.settings?.max_words || 3}" min="1" max="10">
                        </div>
                    `;
                case 'openended':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <textarea class="form-control" id="question" name="question" rows="3" placeholder="คำถามปลายเปิดของคุณ" required>${data.question_text || ''}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="charLimit" class="form-label">จำกัดจำนวนตัวอักษร</label>
                            <input type="number" class="form-control" id="charLimit" name="char_limit" value="${data.settings?.char_limit || ''}" placeholder="เช่น 200">
                        </div>
                    `;
                case 'scales':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" value="${data.question_text || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scaleMin" class="form-label">ค่าน้อยสุด</label>
                                <input type="number" class="form-control" id="scaleMin" name="scale_min" value="${data.settings?.scale_min || 1}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scaleMax" class="form-label">ค่ามากสุด</label>
                                <input type="number" class="form-control" id="scaleMax" name="scale_max" value="${data.settings?.scale_max || 5}">
                            </div>
                        </div>
                    `;
                case 'pinimage':
                    return `
                        <div class="mb-3">
                            <label for="question" class="form-label">คำถาม *</label>
                            <input type="text" class="form-control" id="question" name="question" value="${data.question_text || ''}" placeholder="คำถามของคุณ" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">อัปโหลดรูปภาพ</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            ${data.background_image ? `<img src="${data.background_image}" class="img-fluid mt-2" style="max-height: 200px;">` : ''}
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
            updatePreview(currentSlideData || { slide_type: document.getElementById('questionType').value });
        }

        function removeOption(button) {
            const container = button.parentElement.parentElement;
            if (container.children.length > 2) {
                button.parentElement.remove();
                updatePreview(currentSlideData || { slide_type: document.getElementById('questionType').value });
            }
        }

        function updatePreview(data) {
            const title = '<?php echo htmlspecialchars($session['session_name']); ?>';
            const question = document.getElementById('question')?.value || data.question_text || 'คำถามของคุณ';
            const type = document.getElementById('questionType').value || data.slide_type;
            document.getElementById('mobilePreview').innerHTML = `
                <div class="p-3">
                    <div class="text-center mb-3">
                        <h6 class="mb-1">${title}</h6>
                        <small class="text-muted">mentimeter.com/<?php echo $session['join_code']; ?></small>
                    </div>
                    <div class="question-preview">
                        <h5 class="mb-3">${question}</h5>
                        ${getPreviewContent(type, data)}
                    </div>
                </div>
            `;
        }

        function getPreviewContent(type, data) {
            switch (type) {
                case 'poll':
                case 'ranking':
                    const options = data.choices?.map(c => c.choice_text) || Array.from(document.querySelectorAll('input[name="options[]"]'))
                        .map(input => input.value || 'ตัวเลือก').slice(0, 3);
                    return options.map((opt, i) => 
                        `<div class="btn btn-outline-primary btn-sm d-block mb-2">${type === 'ranking' ? `${i + 1}. ` : ''}${opt}</div>`
                    ).join('');
                case 'wordcloud':
                    return '<div class="form-control">พิมพ์คำตอบของคุณ...</div>';
                case 'openended':
                    return '<textarea class="form-control" rows="3" placeholder="พิมพ์คำตอบของคุณ..."></textarea>';
                case 'scales':
                    return `<input type="range" class="form-range" min="${data.settings?.scale_min || document.getElementById('scaleMin')?.value || 1}" max="${data.settings?.scale_max || document.getElementById('scaleMax')?.value || 5}">`;
                case 'pinimage':
                    return `<div class="text-center"><img src="${data.background_image || '../assets/images/placeholder.png'}" class="img-fluid" style="max-height: 150px;"><p>ตัวอย่างรูปภาพ</p></div>`;
                default:
                    return '<div class="text-muted">ตัวอย่างจะแสดงที่นี่</div>';
            }
        }

        async function addSlide() {
            try {
                const response = await fetch('../api/add_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: <?php echo $session_id; ?>, slide_type: 'poll' })
                });
                const text = await response.text();
                console.log('Raw add slide response:', text); // Debug
                const result = JSON.parse(text);
                if (result.success) {
                    const slideList = document.getElementById('slideList');
                    const newSlide = document.createElement('li');
                    newSlide.className = 'list-group-item d-flex justify-content-between align-items-center';
                    newSlide.setAttribute('onclick', `loadSlide(${result.slide_id})`);
                    newSlide.innerHTML = `
                        <span>สไลด์ ${slideList.children.length + 1}: ไม่มีคำถาม</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSlide(${result.slide_id}, event)">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    slideList.appendChild(newSlide);
                    loadSlide(result.slide_id);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                console.error('Add slide error:', error);
                alert('เกิดข้อผิดพลาดในการเพิ่มสไลด์: ' + error.message);
            }
        }

        async function deleteSlide(slideId, event) {
            event.stopPropagation();
            if (!confirm('ยืนยันการลบสไลด์นี้?')) return;
            try {
                const response = await fetch('../api/delete_slide.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slide_id: slideId })
                });
                const text = await response.text();
                console.log('Raw delete slide response:', text); // Debug
                const result = JSON.parse(text);
                if (result.success) {
                    document.querySelector(`#slideList li[onclick="loadSlide(${slideId})"]`).remove();
                    const firstSlide = document.querySelector('#slideList li:not(.text-muted)');
                    if (firstSlide) {
                        loadSlide(firstSlide.getAttribute('onclick').match(/\d+/)[0]);
                    } else {
                        document.getElementById('questionContent').innerHTML = '';
                        document.getElementById('mobilePreview').innerHTML = `
                            <div class="text-center p-4">
                                <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">ไม่มีสไลด์</p>
                            </div>
                        `;
                    }
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                console.error('Delete slide error:', error);
                alert('เกิดข้อผิดพลาดในการลบสไลด์: ' + error.message);
            }
        }

        document.getElementById('editSlideForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(document.getElementById('editSlideForm'));
            try {
                const response = await fetch('../api/update_slide.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                console.log('Raw update slide response:', text); // Debug
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const result = JSON.parse(text);
                if (result.success) {
                    alert('บันทึกสไลด์เรียบร้อย');
                    const slideItem = document.querySelector(`#slideList li[onclick="loadSlide(${formData.get('slide_id')})"] span`);
                    slideItem.textContent = `สไลด์ ${Array.from(slideItem.parentElement.parentElement.children).indexOf(slideItem.parentElement) + 1}: ${formData.get('question') || 'ไม่มีคำถาม'}`;
                    
                    // อัปเดต currentSlideData
                    currentSlideData = {
                        slide_type: formData.get('question_type'),
                        question_text: formData.get('question'),
                        choices: formData.getAll('options[]').map(text => ({ choice_text: text })),
                        settings: {
                            max_words: formData.get('max_words') ? parseInt(formData.get('max_words')) : undefined,
                            char_limit: formData.get('char_limit') ? parseInt(formData.get('char_limit')) : undefined,
                            scale_min: formData.get('scale_min') ? parseInt(formData.get('scale_min')) : undefined,
                            scale_max: formData.get('scale_max') ? parseInt(formData.get('scale_max')) : undefined
                        },
                        background_image: result.image_url || currentSlideData?.background_image
                    };
                    
                    updatePreview(currentSlideData);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                console.error('Update slide error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึก: ' + error.message);
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>