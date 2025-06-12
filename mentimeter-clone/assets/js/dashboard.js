document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

// Initialize dashboard functionality
function initializeDashboard() {
    initializeSearch();
    initializeSidebar();
    initializeSessionCards();
    initializeStatsRefresh();
    initializeNotifications();
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-box input');
    const searchResults = createSearchResultsContainer();
    let searchTimeout;

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm === '') {
            hideSearchResults();
            resetSessionDisplay();
            return;
        }

        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            hideSearchResults();
            resetSessionDisplay();
        }
    });

    function createSearchResultsContainer() {
        let container = document.querySelector('.search-results');
        if (!container) {
            container = document.createElement('div');
            container.className = 'search-results';
            document.querySelector('.search-box').appendChild(container);
        }
        return container;
    }

    function performSearch(searchTerm) {
        searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        searchResults.style.display = 'block';

        const sessionCards = document.querySelectorAll('.session-card');
        const featureCards = document.querySelectorAll('.feature-card');
        const results = [];

        // Hide all sessions
        sessionCards.forEach(card => card.style.display = 'none');

        // Search sessions
        sessionCards.forEach(card => {
            const title = card.querySelector('.session-title')?.textContent.toLowerCase();
            const joinCode = card.querySelector('.join-code')?.textContent.toLowerCase();
            if ((title && title.includes(searchTerm)) || (joinCode && joinCode.includes(searchTerm))) {
                card.style.display = '';
                results.push({
                    type: 'session',
                    title: card.querySelector('.session-title').textContent,
                    meta: card.querySelector('.session-meta').textContent,
                    element: card
                });
            }
        });

        // Search question types
        featureCards.forEach(card => {
            const title = card.querySelector('h5')?.textContent.toLowerCase();
            const description = card.querySelector('p')?.textContent.toLowerCase();
            if ((title && title.includes(searchTerm)) || (description && description.includes(searchTerm))) {
                results.push({
                    type: 'feature',
                    title: card.querySelector('h5').textContent,
                    description: card.querySelector('p').textContent,
                    element: card
                });
            }
        });

        displaySearchResults(results, searchTerm);
    }

    function displaySearchResults(results, searchTerm) {
        if (results.length === 0) {
            searchResults.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-search"></i>
                    <p>ไม่พบผลลัพธ์สำหรับ "${searchTerm}"</p>
                </div>
            `;
            return;
        }

        let html = '<div class="search-results-header">ผลการค้นหา</div>';
        
        const sessionResults = results.filter(r => r.type === 'session');
        const featureResults = results.filter(r => r.type === 'feature');

        if (sessionResults.length > 0) {
            html += '<div class="search-category">Sessions</div>';
            sessionResults.forEach(result => {
                html += `
                    <div class="search-result-item" onclick="scrollToSession(this, '${result.element.dataset.sessionId || ''}')">
                        <i class="fas fa-presentation"></i>
                        <div>
                            <div class="search-result-title">${highlightSearchTerm(result.title, searchTerm)}</div>
                            <div class="search-result-meta">${result.meta}</div>
                        </div>
                    </div>
                `;
            });
        }

        if (featureResults.length > 0) {
            html += '<div class="search-category">ประเภทคำถาม</div>';
            featureResults.forEach(result => {
                html += `
                    <div class="search-result-item" onclick="highlightFeatureCard(this, '${result.title}')">
                        <i class="fas fa-question-circle"></i>
                        <div>
                            <div class="search-result-title">${highlightSearchTerm(result.title, searchTerm)}</div>
                            <div class="search-result-description">${highlightSearchTerm(result.description, searchTerm)}</div>
                        </div>
                    </div>
                `;
            });
        }

        searchResults.innerHTML = html;
    }

    function resetSessionDisplay() {
        document.querySelectorAll('.session-card').forEach(card => {
            card.style.display = '';
        });
    }

    function highlightSearchTerm(text, searchTerm) {
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function hideSearchResults() {
        searchResults.style.display = 'none';
    }
}

// Sidebar functionality
function initializeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!document.querySelector('.mobile-menu-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-menu-toggle d-md-none';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.onclick = toggleSidebar;
        document.querySelector('.top-navbar').prepend(toggleButton);
    }

    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('.mobile-menu-toggle')) {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            closeSidebar();
        }
    });
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = getOrCreateOverlay();
    
    sidebar.classList.toggle('show');
    
    if (sidebar.classList.contains('show')) {
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    } else {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.remove('show');
    if (overlay) {
        overlay.style.display = 'none';
    }
    document.body.style.overflow = '';
}

function getOrCreateOverlay() {
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = closeSidebar;
        document.body.appendChild(overlay);
    }
    return overlay;
}

// Session cards functionality
function initializeSessionCards() {
    const sessionCards = document.querySelectorAll('.session-card');
    
    sessionCards.forEach(card => {
        card.dataset.sessionId = card.querySelector('a[href*="edit_slide.php"]')?.href.match(/id=(\d+)/)?.[1] || card.dataset.sessionId;
        
        card.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown') || e.target.closest('.copy-code') || e.target.closest('a')) return;
            expandSessionCard(card);
        });

        const statusBadge = card.querySelector('.badge');
        if (statusBadge) {
            statusBadge.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleSessionStatus(card);
            });
        }
    });
}

function expandSessionCard(card) {
    document.querySelectorAll('.session-card.expanded').forEach(c => {
        if (c !== card) c.classList.remove('expanded');
    });
    
    card.classList.toggle('expanded');
    
    if (card.classList.contains('expanded')) {
        loadSessionDetails(card);
    }
}

function loadSessionDetails(card) {
    let detailsContainer = card.querySelector('.session-details');
    if (detailsContainer) return;
    
    detailsContainer = document.createElement('div');
    detailsContainer.className = 'session-details';
    detailsContainer.innerHTML = `
        <div class="session-details-loading">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
        </div>
    `;
    
    card.appendChild(detailsContainer);
    
    const sessionId = card.dataset.sessionId;
    const joinCode = card.querySelector('.join-code')?.textContent.match(/Code: (\w+)/)?.[1] || '';
    if (sessionId) {
        fetch(`api/get_session_details.php?id=${sessionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    detailsContainer.innerHTML = `
                        <div class="session-stats">
                            <div class="stat-item">
                                <i class="fas fa-eye"></i>
                                <span>Views: ${data.views || 0}</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>Duration: ${data.duration || 'N/A'}</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-question"></i>
                                <span>Questions: ${data.question_count || 0}</span>
                            </div>
                        </div>
                        <div class="session-actions">
                            <button class="btn btn-sm btn-primary" onclick="presentSession(this)">
                                <i class="fas fa-play"></i> Present
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="duplicateSession(this)">
                                <i class="fas fa-copy"></i> Duplicate
                            </button>
                            <a class="btn btn-sm btn-outline-primary" href="join.php?code=${encodeURIComponent(joinCode)}">
                                <i class="fas fa-sign-in-alt"></i> Join
                            </a>
                        </div>
                    `;
                } else {
                    detailsContainer.innerHTML = '<div class="error">ไม่สามารถโหลดข้อมูลได้</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching session details:', error);
                detailsContainer.innerHTML = '<div class="error">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            });
    } else {
        detailsContainer.innerHTML = '<div class="error">ไม่พบ Session ID</div>';
    }
}

function toggleSessionStatus(card) {
    const badge = card.querySelector('.badge');
    const sessionId = card.dataset.sessionId;
    const currentStatus = badge.textContent.trim();
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

    fetch('api/update_session_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sessionId, status: newStatus })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                badge.textContent = newStatus;
                badge.className = `badge bg-${newStatus === 'active' ? 'success' : 'secondary'}`;
                showNotification(`Session ${newStatus === 'active' ? 'activated' : 'deactivated'}`, 'success');
            } else {
                showNotification('Failed to update session status', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating session status:', error);
            showNotification('Error updating session status', 'error');
        });
}

// Stats refresh functionality
function initializeStatsRefresh() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('click', refreshStats);
    });
    
    setInterval(refreshStats, 30000);
}

function refreshStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    fetch('api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statNumbers[0].textContent = data.total_sessions || 0;
                statNumbers[1].textContent = data.total_responses || 0;
                statNumbers[2].textContent = data.active_sessions || 0;
                
                statNumbers.forEach(stat => {
                    stat.classList.add('updating');
                    setTimeout(() => stat.classList.remove('updating'), 1000);
                });
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

// Question type creation functionality
function createQuestion(type) {
    const clickedCard = event.target.closest('.feature-card');
    if (clickedCard) {
        clickedCard.classList.add('loading');
        clickedCard.innerHTML += '<div class="card-loading"><i class="fas fa-spinner fa-spin"></i></div>';
    }
    
    try {
        sessionStorage.setItem('selectedQuestionType', type);
        sessionStorage.setItem('dashboardReturn', window.location.href);
    } catch (e) {
        console.warn('SessionStorage not available, using URL parameters');
    }
    
    setTimeout(() => {
        window.location.href = `create_presentation.php?type=${type}`;
        if (clickedCard) {
            clickedCard.classList.remove('loading');
            clickedCard.querySelector('.card-loading')?.remove();
        }
    }, 500);
}

// Utility functions
function presentSession(button) {
    const card = button.closest('.session-card');
    const sessionTitle = card.querySelector('.session-title').textContent;
    const sessionId = card.dataset.sessionId;
    
    if (confirm(`เริ่มการนำเสนอ "${sessionTitle}" หรือไม่?`)) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังเริ่ม...';
        button.disabled = true;
        
        setTimeout(() => {
            window.open(`present.php?session_id=${sessionId}`, '_blank');
            button.innerHTML = '<i class="fas fa-play"></i> Present';
            button.disabled = false;
        }, 1000);
    }
}

function duplicateSession(button) {
    const card = button.closest('.session-card');
    const sessionTitle = card.querySelector('.session-title').textContent;
    const sessionId = card.dataset.sessionId;
    
    if (confirm(`คัดลอก "${sessionTitle}" หรือไม่?`)) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังคัดลอก...';
        button.disabled = true;
        
        fetch('api/duplicate_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sessionId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Session duplicated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification('Failed to duplicate session', 'error');
                }
                button.innerHTML = '<i class="fas fa-copy"></i> Duplicate';
                button.disabled = false;
            })
            .catch(error => {
                console.error('Error duplicating session:', error);
                showNotification('Error duplicating session', 'error');
                button.innerHTML = '<i class="fas fa-copy"></i> Duplicate';
                button.disabled = false;
            });
    }
}

function getSessionId(card) {
    return card.dataset.sessionId;
}

// Notification system
function initializeNotifications() {
    if (!document.querySelector('.notification-container')) {
        const container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'info') {
    const container = document.querySelector('.notification-container');
    const notification = document.createElement('div');
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle'
    };
    
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="${icons[type]}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function highlightFeatureCard(searchItem, title) {
    const featureCards = document.querySelectorAll('.feature-card');
    
    featureCards.forEach(card => card.classList.remove('highlighted'));
    
    featureCards.forEach(card => {
        const cardTitle = card.querySelector('h5').textContent;
        if (cardTitle === title) {
            card.classList.add('highlighted');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => card.classList.remove('highlighted'), 3000);
        }
    });
    
    document.querySelector('.search-results').style.display = 'none';
}

function scrollToSession(searchItem, sessionId) {
    if (sessionId) {
        const element = document.querySelector(`.session-card[data-session-id="${sessionId}"]`);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    document.querySelector('.search-results').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    if (e.key === 'Escape') {
        const searchResults = document.querySelector('.search-results');
        if (searchResults && searchResults.style.display === 'block') {
            searchResults.style.display = 'none';
            resetSessionDisplay();
        }
    }
});

window.createQuestion = createQuestion;
window.toggleSidebar = toggleSidebar;
window.presentSession = presentSession;
window.duplicateSession = duplicateSession;
window.highlightFeatureCard = highlightFeatureCard;
window.scrollToSession = scrollToSession;