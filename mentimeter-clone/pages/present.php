<?php
require_once '../includes/functions.php';
requireLogin();

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT s.*, u.username 
    FROM tbsession s 
    JOIN tbuser u ON s.created_by = u.userid 
    WHERE s.sessionid = ? AND s.created_by = ?
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $db->prepare("
    SELECT sl.*, sd.settings, GROUP_CONCAT(c.choice_text ORDER BY c.choice_order) as options
    FROM tbslide sl
    LEFT JOIN tbslidedisplay sd ON sl.slideid = sd.slideid
    LEFT JOIN tbchoice c ON sl.slideid = c.slideid
    WHERE sl.sessionid = ?
    GROUP BY sl.slideid
    ORDER BY sl.slide_order
");
$stmt->execute([$session_id]);
$slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($slides as &$slide) {
    $slide['options'] = $slide['options'] ? explode(',', $slide['options']) : [];
    $slide['settings'] = json_decode($slide['settings'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเสนอ - <?= htmlspecialchars($session['session_name']) ?> - Mentimeter Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/presentation.css" rel="stylesheet">
</head>
<body>
    <div class="presentation-container">
        <div class="slide-container" id="slideContainer">
            <div class="join-info">
                เข้าร่วมที่ mentimeter.com ด้วยรหัส: <?= htmlspecialchars($session['join_code']) ?>
            </div>
            <div class="slide-content" id="slideContent"></div>
        </div>
        <div class="control-bar">
            <button class="control-button" onclick="prevSlide()"><i class="fas fa-arrow-left"></i></button>
            <span class="control-button" id="slideCounter">1 / <?= count($slides) ?></span>
            <button class="control-button" onclick="nextSlide()"><i class="fas fa-arrow-right"></i></button>
            <button class="control-button" onclick="endPresentation()"><i class="fas fa-stop"></i></button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/presentation.js"></script>
    <script>
        const slides = <?= json_encode($slides) ?>;
        let currentSlideIndex = 0;

        function renderSlide(index) {
            const slide = slides[index];
            if (!slide) return;

            const slideContainer = document.getElementById('slideContainer');
            const slideContent = document.getElementById('slideContent');
            slideContainer.style.background = slide.background_image ? `url(${slide.background_image})` : slide.background_color;

            let content = `
                <h2 class="slide-title">${slide.slide_title}</h2>
                <div class="slide-question">${slide.question_text}</div>
            `;

            switch (slide.slide_type) {
                case 'poll':
                case 'ranking':
                    content += `<div class="slide-options">`;
                    slide.options.forEach(opt => {
                        content += `<button class="option-button">${opt}</button>`;
                    });
                    content += `</div>`;
                    break;
                case 'wordcloud':
                    content += `<div class="wordcloud-preview"><div class="wordcloud-word">ตัวอย่างคำ</div></div>`;
                    break;
                case 'openended':
                    content += `<div class="openended-preview">ตัวอย่างคำตอบ...</div>`;
                    break;
                case 'scales':
                    content += `<input type="range" class="form-range scales-preview" min="${slide.scale_min}" max="${slide.scale_max}">`;
                    break;
                case 'pinimage':
                    content += `<div class="pinimage-preview"><img src="${slide.background_image || '../assets/images/placeholder.png'}" alt="Slide Image"></div>`;
                    break;
            }

            slideContent.innerHTML = content;
            document.getElementById('slideCounter').textContent = `${index + 1} / ${slides.length}`;
        }

        function prevSlide() {
            if (currentSlideIndex > 0) {
                currentSlideIndex--;
                renderSlide(currentSlideIndex);
            }
        }

        function nextSlide() {
            if (currentSlideIndex < slides.length - 1) {
                currentSlideIndex++;
                renderSlide(currentSlideIndex);
            }
        }

        function endPresentation() {
            if (confirm('สิ้นสุดการนำเสนอ?')) {
                window.location.href = 'dashboard.php';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderSlide(currentSlideIndex);
        });
    </script>
</body>
</html>