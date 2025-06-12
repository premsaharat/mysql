// assets/js/presentation.js
function loadQuestionType(type, data = {}) {
    const content = document.getElementById('questionContent');
    switch(type) {
        case 'word_cloud':
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <input type="text" class="form-control" id="question" name="question_text" value="${data.question || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="max_words" class="form-label">จำนวนคำสูงสุด</label>
                    <input type="number" class="form-control" id="max_words" name="max_words" value="${data.settings?.max_words || 3}" min="1">
                </div>
            `;
            break;
        case 'multiple_choice':
        case 'poll':
            const options = data.options || ['', ''];
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <input type="text" class="form-control" id="question" name="question_text" value="${data.question || ''}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ตัวเลือก *</label>
                    <div id="options">
                        ${options.map((opt, i) => `
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="options[]" value="${opt}" required>
                                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">ลบ</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="addOption()">เพิ่มตัวเลือก</button>
                </div>
            `;
            break;
        case 'open_ended':
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <textarea class="form-control" id="question" name="question_text" required>${data.question || ''}</textarea>
                </div>
                <div class="mb-3">
                    <label for="char_limit" class="form-label">จำกัดตัวอักษร</label>
                    <input type="number" class="form-control" id="char_limit" name="char_limit" value="${data.settings?.char_limit || ''}" placeholder="เช่น 200">
                </div>
            `;
            break;
        case 'scales':
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <input type="text" class="form-control" id="question" name="question_text" value="${data.question || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="scale_min" class="form-label">ค่าน้อยสุด</label>
                    <input type="number" class="form-control" id="scale_min" name="scale_min" value="${data.settings?.scale_min || 1}">
                </div>
                <div class="mb-3">
                    <label for="scale_max" class="form-label">ค่ามากสุด</label>
                    <input type="number" class="form-control" id="scale_max" name="scale_max" value="${data.settings?.scale_max || 5}">
                </div>
            `;
            break;
        case 'ranking':
            const rankingOptions = data.options || ['', ''];
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <input type="text" class="form-control" id="question" name="question_text" value="${data.question || ''}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ตัวเลือก *</label>
                    <div id="ranking_options">
                        ${rankingOptions.map((opt, i) => `
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="ranking_options[]" value="${opt}" required>
                                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">ลบ</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="addRankingOption()">เพิ่มตัวเลือก</button>
                </div>
            `;
            break;
        case 'pin_on_image':
            content.innerHTML = `
                <div class="mb-3">
                    <label for="question" class="form-label">คำถาม *</label>
                    <input type="text" class="form-control" id="question" name="question_text" value="${data.question || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">อัปโหลดรูปภาพ</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    ${data.background_image ? `<img src="${data.background_image}" class="img-fluid mt-2" style="max-height: 200px;">` : ''}
                </div>`;
            break;
    }

function addOption() {
    const container = document.getElementById('options');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="options[]" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">ลบ</button>
    `;
    container.appendChild(div);
}

function addRankingOption() {
    const container = document.getElementById('ranking_options');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control" name="ranking_options[]" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">ลบ</button>
    `;
    container.appendChild(div);
}

function removeOption(button) {
    const container = button.parentElement;
    if (container.parentElement.children.length > 2) {
        container.remove();
    }
}

function updatePreview(data) {
    const title = '<?= htmlspecialchars($session['session_name'] ?? '') ?>';
    const question = data.question || 'คำถาม';
    const type = data.type || document.getElementById('questionType').value;

    document.getElementById('mobilePreview').innerHTML = `
        <div class="p-3">
            <div class="text-center mb-3">
                <h6 class="mb-1">${title}</h6>
                <small class="text-muted">mentimeter.com/join/${data.join_code || ''}</small>
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
        case 'word_cloud':
            return '<div class="form-control">พิมพ์คำตอบ...</div>';
        case 'multiple_choice':
        case 'poll':
            const options = data.options || ['ตัวเลือก 1', 'ตัวเลือก 2'];
            return options.map(opt => `<div class="btn btn-outline-primary btn-sm d-block mb-2">${opt}</div>`).join('');
        case 'open_ended':
            return '<textarea class="form-control" rows="3" placeholder="พิมพ์คำตอบ..."></textarea>';
        case 'scales':
            return `<input type="range" class="form-range" min="${data.settings?.scale_min || 1}" max="${data.settings?.scale_max || 5}">`;
        case 'ranking':
            const rankingOptions = data.options || ['ตัวเลือก 1', 'ตัวเลือก 2'];
            return rankingOptions.map((opt, index) => `<div class="btn btn-outline-primary btn-sm d-block mb-2">${index + 1}. ${opt}</div>`).join('');
        case 'pin_on_image':
            return `<div class="text-center"><img src="${data.background_image || '/assets/images/placeholder.png'}" class="img-fluid" style="max-height: 150px;"></div>`;
        default:
            return '<div class="text-muted">ตัวอย่าง</div>';
    }
}