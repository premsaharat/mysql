let currentSlideId = null;

document.addEventListener('DOMContentLoaded', () => {
    const votingArea = document.getElementById('votingArea');
    if (votingArea) {
        const sessionId = votingArea.dataset.sessionId;
        loadCurrentSlide(sessionId);
        // Polling ทุก 5 วินาทีเพื่อเช็คสไลด์ใหม่
        setInterval(() => loadCurrentSlide(sessionId), 5000);
    }
});

async function loadCurrentSlide(sessionId) {
    try {
        const response = await fetch(`../api/get_current_slide.php?session_id=${sessionId}`);
        const text = await response.text();
        console.log('Raw slide response:', text);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const result = JSON.parse(text);
        if (result.success && result.data) {
            if (result.data.slideid !== currentSlideId) {
                currentSlideId = result.data.slideid;
                renderSlide(result.data);
            }
        } else {
            document.getElementById('votingContent').innerHTML = '<p class="text-muted">ไม่มีสไลด์ที่ใช้งานอยู่</p>';
            document.getElementById('questionText').textContent = 'รอสไลด์ถัดไป...';
        }
    } catch (error) {
        console.error('Load slide error:', error);
        showAlert('danger', 'เกิดข้อผิดพลาดในการโหลดสไลด์: ' + error.message);
    }
}

function renderSlide(slide) {
    const questionText = document.querySelector('.question-text');
    const votingContent = document.getElementById('votingContent');
    questionText.textContent = slide.question_text || 'ไม่มีคำถาม';
    votingContent.innerHTML = '';

    switch (slide.slide_type) {
        case 'poll':
        case 'ranking':
            slide.choices.forEach(choice => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = choice.choice_text;
                btn.dataset.choiceId = choice.choiceid;
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                });
                votingContent.appendChild(btn);
            });
            addSubmitButton(slide.slideid, slide.slide_type);
            break;
        case 'wordcloud':
            const wordInput = document.createElement('input');
            wordInput.type = 'text';
            wordInput.className = 'wordcloud-input';
            wordInput.placeholder = 'พิมพ์คำตอบของคุณ...';
            wordInput.maxLength = slide.settings?.max_words || 50;
            votingContent.appendChild(wordInput);
            addSubmitButton(slide.slideid, slide.slide_type);
            break;
        case 'openended':
            const textArea = document.createElement('textarea');
            textArea.className = 'openended-input';
            textArea.placeholder = 'พิมพ์คำตอบของคุณ...';
            textArea.rows = 4;
            textArea.maxLength = slide.settings?.char_limit || 200;
            votingContent.appendChild(textArea);
            addSubmitButton(slide.slideid, slide.slide_type);
            break;
        case 'scales':
            const range = document.createElement('input');
            range.type = 'range';
            range.className = 'scales-range';
            range.min = slide.settings?.scale_min || 1;
            range.max = slide.settings?.scale_max || 5;
            range.value = slide.settings?.scale_min || 1;
            votingContent.appendChild(range);
            const scaleLabel = document.createElement('div');
            scaleLabel.textContent = `ค่า: ${range.value}`;
            range.addEventListener('input', () => scaleLabel.textContent = `ค่า: ${range.value}`);
            votingContent.appendChild(scaleLabel);
            addSubmitButton(slide.slideid, slide.slide_type);
            break;
        case 'pinimage':
            const imgContainer = document.createElement('div');
            imgContainer.className = 'pinimage-container';
            const img = document.createElement('img');
            img.src = slide.background_image || '../assets/images/placeholder.png';
            img.className = 'pinimage-img';
            imgContainer.appendChild(img);
            imgContainer.addEventListener('click', (e) => {
                const marker = document.createElement('div');
                marker.className = 'pin-marker';
                marker.style.left = `${e.offsetX - 10}px`;
                marker.style.top = `${e.offsetY - 10}px`;
                imgContainer.appendChild(marker);
            });
            votingContent.appendChild(imgContainer);
            addSubmitButton(slide.slideid, slide.slide_type);
            break;
    }
}

function addSubmitButton(slideId, slideType) {
    const submitBtn = document.createElement('button');
    submitBtn.className = 'submit-btn';
    submitBtn.textContent = 'ส่งคำตอบ';
    submitBtn.addEventListener('click', () => submitVote(slideId, slideType));
    document.getElementById('votingContent').appendChild(submitBtn);
}

async function submitVote(slideId, slideType) {
    const submitBtn = document.querySelector('.submit-btn');
    submitBtn.disabled = true;
    try {
        let voteData = { slide_id: slideId };
        switch (slideType) {
            case 'poll':
            case 'ranking':
                const selected = document.querySelector('.option-btn.selected');
                if (!selected) throw new Error('กรุณาเลือกตัวเลือก');
                voteData.choice_id = selected.dataset.choiceId;
                break;
            case 'wordcloud':
                const word = document.querySelector('.wordcloud-input').value.trim();
                if (!word) throw new Error('กรุณาป้อนคำตอบ');
                voteData.response_text = word;
                break;
            case 'openended':
                const text = document.querySelector('.openended-input').value.trim();
                if (!text) throw new Error('กรุณาป้อนคำตอบ');
                voteData.response_text = text;
                break;
            case 'scales':
                voteData.response_value = document.querySelector('.scales-range').value;
                break;
            case 'pinimage':
                const markers = document.querySelectorAll('.pin-marker');
                voteData.coordinates = Array.from(markers).map(m => ({
                    x: parseInt(m.style.left),
                    y: parseInt(m.style.top)
                }));
                break;
        }

        const response = await fetch('../api/vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(voteData)
        });
        const text = await response.text();
        console.log('Raw vote response:', text);
        const result = JSON.parse(text);
        if (result.success) {
            showAlert('success', 'ส่งคำตอบเรียบร้อย!');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Vote error:', error);
        showAlert('danger', 'เกิดข้อผิดพลาด: ' + error.message);
    } finally {
        submitBtn.disabled = false;
    }
}

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.getElementById('votingArea').appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}