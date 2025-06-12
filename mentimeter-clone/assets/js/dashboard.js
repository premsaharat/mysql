// Dashboard JavaScript - Mentimeter Clone
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
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (searchTerm === '') {
            hideSearchResults();
            return;
        }

        // Debounce search
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            hideSearchResults();
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
        // Show loading
        searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        searchResults.style.display = 'block';

        // Search in sessions
        const sessionCards = document.querySelectorAll('.session-card');
        const featureCards = document.querySelectorAll('.feature-card');
        const results = [];

        // Search sessions
        sessionCards.forEach(card => {
            const title = card.querySelector('.session-title')?.textContent.toLowerCase();
            if (title && title.includes(searchTerm)) {
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
                    <div class="search-result-item" onclick="scrollToElement(this, '${result.element.id || ''}')">
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
    
    // Mobile menu toggle button
    if (!document.querySelector('.mobile-menu-toggle')) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'mobile-menu-toggle d-md-none';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.onclick = toggleSidebar;
        document.querySelector('.top-navbar').prepend(toggleButton);
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('.mobile-menu-toggle')) {
            closeSidebar();
        }
    });

    // Handle window resize
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
    // Add hover effects and animations
    const sessionCards = document.querySelectorAll('.session-card');
    
    sessionCards.forEach(card => {
        // Add click-to-expand functionality
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on dropdown
            if (e.target.closest('.dropdown')) return;
            
            expandSessionCard(card);
        });

        // Add status update functionality
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
    // Remove any existing expanded cards
    document.querySelectorAll('.session-card.expanded').forEach(c => {
        if (c !== card) c.classList.remove('expanded');
    });
    
    card.classList.toggle('expanded');
    
    if (card.classList.contains('expanded')) {
        loadSessionDetails(card);
    }
}

function loadSessionDetails(card) {
    // Check if details already loaded
    let detailsContainer = card.querySelector('.session-details');
    if (detailsContainer) return;
    
    // Create details container
    detailsContainer = document.createElement('div');
    detailsContainer.className = 'session-details';
    detailsContainer.innerHTML = `
        <div class="session-details-loading">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
        </div>
    `;
    
    card.appendChild(detailsContainer);
    
    // Simulate loading session details
    setTimeout(() => {
        detailsContainer.innerHTML = `
            <div class="session-stats">
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span>Views: 245</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>Duration: 15 min</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-question"></i>
                    <span>Questions: 5</span>
                </div>
            </div>
            <div class="session-actions">
                <button class="btn btn-sm btn-primary" onclick="presentSession(this)">
                    <i class="fas fa-play"></i> Present
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="duplicateSession(this)">
                    <i class="fas fa-copy"></i> Duplicate
                </button>
            </div>
        `;
    }, 500);
}

function toggleSessionStatus(card) {
    const badge = card.querySelector('.badge');
    const currentStatus = badge.textContent.trim();
    
    if (currentStatus === 'active') {
        badge.textContent = 'inactive';
        badge.className = 'badge bg-secondary';
        showNotification('Session deactivated', 'info');
    } else {
        badge.textContent = 'active';
        badge.className = 'badge bg-success';
        showNotification('Session activated', 'success');
    }
    
    // Here you would typically make an API call to update the status
    // updateSessionStatus(sessionId, newStatus);
}

// Stats refresh functionality
function initializeStatsRefresh() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            refreshStats();
        });
    });
    
    // Auto-refresh stats every 30 seconds
    setInterval(refreshStats, 30000);
}

function refreshStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach(stat => {
        stat.classList.add('updating');
        const currentValue = parseInt(stat.textContent);
        
        // Simulate stats update with animation
        animateNumber(stat, currentValue, currentValue + Math.floor(Math.random() * 3));
    });
    
    setTimeout(() => {
        statNumbers.forEach(stat => stat.classList.remove('updating'));
    }, 1000);
}

function animateNumber(element, start, end) {
    const duration = 800;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// Question type creation functionality
function createQuestion(type) {
    // Add loading state
    const clickedCard = event.target.closest('.feature-card');
    if (clickedCard) {
        clickedCard.classList.add('loading');
        clickedCard.innerHTML += '<div class="card-loading"><i class="fas fa-spinner fa-spin"></i></div>';
    }
    
    // Store selected question type in session storage
    try {
        sessionStorage.setItem('selectedQuestionType', type);
        sessionStorage.setItem('dashboardReturn', window.location.href);
    } catch (e) {
        console.warn('SessionStorage not available, using URL parameters');
    }
    
    // Navigate to create question page
    window.location.href = `create-question.php?type=${type}`;
}

// Utility functions
function presentSession(button) {
    const card = button.closest('.session-card');
    const sessionTitle = card.querySelector('.session-title').textContent;
    
    if (confirm(`เริ่มการนำเสนอ "${sessionTitle}" หรือไม่?`)) {
        // Add loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังเริ่ม...';
        button.disabled = true;
        
        setTimeout(() => {
            window.open(`present.php?session_id=${getSessionId(card)}`, '_blank');
            button.innerHTML = '<i class="fas fa-play"></i> Present';
            button.disabled = false;
        }, 1000);
    }
}

function duplicateSession(button) {
    const card = button.closest('.session-card');
    const sessionTitle = card.querySelector('.session-title').textContent;
    
    if (confirm(`คัดลอก "${sessionTitle}" หรือไม่?`)) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังคัดลอก...';
        button.disabled = true;
        
        // Simulate duplication
        setTimeout(() => {
            showNotification('Session duplicated successfully!', 'success');
            button.innerHTML = '<i class="fas fa-copy"></i> Duplicate';
            button.disabled = false;
            
            // Refresh the page to show the new session
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }, 1000);
    }
}

function getSessionId(card) {
    // Extract session ID from card (this would depend on your HTML structure)
    const editLink = card.querySelector('a[href*="edit-session.php"]');
    if (editLink) {
        const url = new URL(editLink.href);
        return url.searchParams.get('id');
    }
    return null;
}

// Notification system
function initializeNotifications() {
    // Create notification container if it doesn't exist
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
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Highlight feature card (for search results)
function highlightFeatureCard(searchItem, title) {
    const featureCards = document.querySelectorAll('.feature-card');
    
    // Remove previous highlights
    featureCards.forEach(card => card.classList.remove('highlighted'));
    
    // Find and highlight the matching card
    featureCards.forEach(card => {
        const cardTitle = card.querySelector('h5').textContent;
        if (cardTitle === title) {
            card.classList.add('highlighted');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove highlight after 3 seconds
            setTimeout(() => {
                card.classList.remove('highlighted');
            }, 3000);
        }
    });
    
    // Hide search results
    document.querySelector('.search-results').style.display = 'none';
}

// Scroll to element (for search results)
function scrollToElement(searchItem, elementId) {
    if (elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    // Hide search results
    document.querySelector('.search-results').style.display = 'none';
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close search results
    if (e.key === 'Escape') {
        const searchResults = document.querySelector('.search-results');
        if (searchResults && searchResults.style.display === 'block') {
            searchResults.style.display = 'none';
        }
    }
});

// Export functions for global access
window.createQuestion = createQuestion;
window.toggleSidebar = toggleSidebar;
window.presentSession = presentSession;
window.duplicateSession = duplicateSession;
window.highlightFeatureCard = highlightFeatureCard;
window.scrollToElement = scrollToElement;