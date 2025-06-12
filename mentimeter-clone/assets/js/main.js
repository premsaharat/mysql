/**
 * main.js - Core JavaScript for Mentimeter Clone
 * Handles global UI interactions, AJAX requests, notifications, and utilities
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize application
function initializeApp() {
    initializeSidebar();
    initializeSearch();
    initializeNotifications();
    initializeCopyButtons();
    initializeFormValidation();
    initializeErrorHandling();
}

// Sidebar toggle for mobile
function initializeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggleButton = document.querySelector('.mobile-menu-toggle');
    const overlay = getOrCreateOverlay();

    if (toggleButton) {
        toggleButton.addEventListener('click', toggleSidebar);
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

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.style.display = 'none';
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
}

// Global search functionality
function initializeSearch() {
    const searchInput = document.querySelector('.search-box input');
    const searchResults = document.querySelector('.search-results') || createSearchResultsContainer();
    let searchTimeout;

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        clearTimeout(searchTimeout);
        if (searchTerm === '') {
            hideSearchResults();
            return;
        }
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            hideSearchResults();
        }
    });

    function createSearchResultsContainer() {
        const container = document.createElement('div');
        container.className = 'search-results';
        document.querySelector('.search-box').appendChild(container);
        return container;
    }

    async function performSearch(searchTerm) {
        searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> กำลังค้นหา...</div>';
        searchResults.style.display = 'block';

        try {
            const response = await fetch(`api/search.php?term=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();
            if (data.success) {
                displaySearchResults(data.results, searchTerm);
            } else {
                searchResults.innerHTML = `<div class="search-no-results">ไม่พบผลลัพธ์สำหรับ "${searchTerm}"</div>`;
            }
        } catch (error) {
            console.error('Search error:', error);
            searchResults.innerHTML = '<div class="search-no-results">เกิดข้อผิดพลาดในการค้นหา</div>';
        }
    }

    function displaySearchResults(results, searchTerm) {
        if (!results.length) {
            searchResults.innerHTML = `<div class="search-no-results">ไม่พบผลลัพธ์สำหรับ "${searchTerm}"</div>`;
            return;
        }

        let html = '<div class="search-results-header">ผลการค้นหา</div>';
        results.forEach(result => {
            html += `
                <div class="search-result-item" onclick="navigateToResult('${result.type}', '${result.id}')">
                    <i class="fas fa-${result.type === 'session' ? 'presentation' : 'question-circle'}"></i>
                    <div>
                        <div class="search-result-title">${highlightSearchTerm(result.title, searchTerm)}</div>
                        <div class="search-result-meta">${result.meta || ''}</div>
                    </div>
                </div>
            `;
        });
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

// Copy to clipboard functionality
function initializeCopyButtons() {
    document.querySelectorAll('.copy-code').forEach(button => {
        button.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (code) {
                navigator.clipboard.writeText(code).then(() => {
                    this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    showNotification('คัดลอกรหัสสำเร็จ!', 'success');
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    showNotification('ไม่สามารถคัดลอกรหัสได้', 'error');
                });
            } else {
                showNotification('ไม่มีรหัสให้คัดลอก', 'warning');
            }
        });
    });
}

// Form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                showNotification('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// Error handling for AJAX
function initializeErrorHandling() {
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        showNotification('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
    });
}

// Utility: Navigate to search result
function navigateToResult(type, id) {
    const routes = {
        session: `results.php?id=${id}`,
        question: `create_presentation.php?type=${id}`
    };
    if (routes[type]) {
        window.location.href = routes[type];
    }
}

// Utility: Make AJAX request
async function makeAjaxRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            body: options.body ? JSON.stringify(options.body) : null
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Request failed');
        }
        return data;
    } catch (error) {
        console.error('AJAX error:', error);
        showNotification(`เกิดข้อผิดพลาด: ${error.message}`, 'error');
        throw error;
    }
}

// Utility: Update badge count
function updateBadge(elementId, count) {
    const badge = document.getElementById(elementId);
    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('d-none', count === 0);
    }
}

// Utility: Format date to Thai format
function formatThaiDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('th-TH', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Expose global functions
window.showNotification = showNotification;
window.makeAjaxRequest = makeAjaxRequest;
window.updateBadge = updateBadge;
window.formatThaiDate = formatThaiDate;