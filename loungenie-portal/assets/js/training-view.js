/**
 * Training Videos View JavaScript
 * Handles video management for Support and viewing for Partners
 * 
 * @package LounGenie Portal
 */

(function () {
    'use strict';

    const isSupport = document.body.classList.contains('lgp-support');
    let videos = [];
    let companies = [];
    let currentVideoId = null;

    // DOM Elements
    const grid = document.getElementById('lgp-training-grid');
    const noVideos = document.getElementById('lgp-no-videos');
    const searchInput = document.getElementById('lgp-training-search');
    const categoryFilter = document.getElementById('lgp-category-filter');
    const addVideoBtn = document.getElementById('lgp-add-video-btn');
    const videoModal = document.getElementById('lgp-video-modal');
    const playerModal = document.getElementById('lgp-player-modal');
    const videoForm = document.getElementById('lgp-video-form');

    /**
     * Initialize
     */
    function init() {
        loadVideos();
        bindEvents();

        if (isSupport) {
            loadCompanies();
        }
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Search and filter
        if (searchInput) {
            searchInput.addEventListener('input', debounce(filterVideos, 300));
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterVideos);
        }

        // Add video button (support only)
        if (addVideoBtn) {
            addVideoBtn.addEventListener('click', () => openVideoModal());
        }

        // Form submission
        if (videoForm) {
            videoForm.addEventListener('submit', handleFormSubmit);
        }

        // Modal close buttons
        document.querySelectorAll('.lgp-modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.lgp-modal-overlay').classList.add('hidden');
            });
        });

        // All companies checkbox
        const allCompaniesCheck = document.getElementById('lgp-all-companies');
        if (allCompaniesCheck) {
            allCompaniesCheck.addEventListener('change', toggleCompanyList);
        }
    }

    /**
     * Load all videos from API
     */
    async function loadVideos(filters = {}) {
        try {
            let url = '/wp-json/lgp/v1/training-videos';
            const params = new URLSearchParams();

            if (filters.category) {
                params.append('category', filters.category);
            }
            if (filters.search) {
                params.append('search', filters.search);
            }

            if (params.toString()) {
                url += '?' + params.toString();
            }

            const response = await fetch(url, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to load videos');
            }

            videos = await response.json();
            renderVideos(videos);

        } catch (error) {
            console.error('Error loading videos:', error);
            showError('Failed to load training videos');
        }
    }

    /**
     * Load companies for target selection (support only)
     */
    async function loadCompanies() {
        try {
            const response = await fetch('/wp-json/lgp/v1/companies', {
                credentials: 'same-origin'
            });

            if (response.ok) {
                companies = await response.json();
            }
        } catch (error) {
            console.error('Error loading companies:', error);
        }
    }

    /**
     * Render videos grid
     */
    function renderVideos(videoList) {
        if (!grid) return;

        grid.innerHTML = '';

        if (videoList.length === 0) {
            grid.style.display = 'none';
            if (noVideos) {
                noVideos.style.display = 'block';
            }
            return;
        }

        grid.style.display = 'grid';
        if (noVideos) {
            noVideos.style.display = 'none';
        }

        videoList.forEach(video => {
            const card = createVideoCard(video);
            grid.appendChild(card);
        });
    }

    /**
     * Create video card element
     */
    function createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'lgp-video-card';

        const duration = video.duration ? formatDuration(video.duration) : '';
        const category = video.category || 'general';

        card.innerHTML = `
            <div class="lgp-video-thumbnail" data-video-id="${video.id}">
                🎬
            </div>
            <div class="lgp-video-card-body">
                <h3 class="lgp-video-card-title">${escapeHtml(video.title)}</h3>
                <div class="lgp-video-card-meta">
                    <span class="lgp-badge badge-info">${escapeHtml(category)}</span>
                    ${duration ? `<span>${duration}</span>` : ''}
                </div>
                ${video.description ? `<p class="lgp-video-card-description">${escapeHtml(video.description)}</p>` : ''}
                <div class="lgp-video-card-actions">
                    <button class="lgp-btn lgp-btn-primary lgp-watch-btn" data-video-id="${video.id}">
                        Watch Video
                    </button>
                    ${isSupport ? `
                        <button class="lgp-btn lgp-btn-secondary lgp-edit-btn" data-video-id="${video.id}">
                            Edit
                        </button>
                        <button class="lgp-btn lgp-btn-danger lgp-delete-btn" data-video-id="${video.id}">
                            Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `;

        // Bind events
        card.querySelector('.lgp-video-thumbnail').addEventListener('click', () => playVideo(video.id));
        card.querySelector('.lgp-watch-btn').addEventListener('click', () => playVideo(video.id));

        if (isSupport) {
            card.querySelector('.lgp-edit-btn').addEventListener('click', () => editVideo(video.id));
            card.querySelector('.lgp-delete-btn').addEventListener('click', () => deleteVideo(video.id));
        }

        return card;
    }

    /**
     * Filter videos based on search and category
     */
    function filterVideos() {
        const search = searchInput ? searchInput.value.toLowerCase() : '';
        const category = categoryFilter ? categoryFilter.value : '';

        const filtered = videos.filter(video => {
            const matchesSearch = !search ||
                video.title.toLowerCase().includes(search) ||
                (video.description && video.description.toLowerCase().includes(search));

            const matchesCategory = !category || video.category === category;

            return matchesSearch && matchesCategory;
        });

        renderVideos(filtered);
    }

    /**
     * Open video modal for add/edit
     */
    function openVideoModal(videoId = null) {
        if (!videoModal) return;

        currentVideoId = videoId;
        const modalTitle = document.getElementById('lgp-modal-title');

        if (videoId) {
            modalTitle.textContent = 'Edit Training Video';
            loadVideoData(videoId);
        } else {
            modalTitle.textContent = 'Add Training Video';
            videoForm.reset();
            document.getElementById('lgp-video-id').value = '';
            document.getElementById('lgp-all-companies').checked = true;
            toggleCompanyList();
        }

        videoModal.classList.remove('hidden');
        renderCompanySelector();
    }

    /**
     * Load video data for editing
     */
    async function loadVideoData(videoId) {
        const video = videos.find(v => v.id == videoId);
        if (!video) return;

        document.getElementById('lgp-video-id').value = video.id;
        document.getElementById('lgp-video-title').value = video.title;
        document.getElementById('lgp-video-description').value = video.description || '';
        document.getElementById('lgp-video-url').value = video.video_url;
        document.getElementById('lgp-video-category').value = video.category || 'general';
        document.getElementById('lgp-video-duration').value = video.duration || '';

        // Handle target companies
        const targets = video.target_companies ? JSON.parse(video.target_companies) : [];
        const allCompanies = document.getElementById('lgp-all-companies');

        if (targets.length === 0) {
            allCompanies.checked = true;
        } else {
            allCompanies.checked = false;
            // Check specific companies
            targets.forEach(companyId => {
                const checkbox = document.querySelector(`input[name="target_companies"][value="${companyId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        toggleCompanyList();
    }

    /**
     * Render company selector checkboxes
     */
    function renderCompanySelector() {
        const list = document.getElementById('lgp-company-list');
        if (!list || companies.length === 0) return;

        list.innerHTML = companies.map(company => `
            <label>
                <input type="checkbox" name="target_companies" value="${company.id}" />
                ${escapeHtml(company.name)}
            </label>
        `).join('');
    }

    /**
     * Toggle company list visibility
     */
    function toggleCompanyList() {
        const allCompanies = document.getElementById('lgp-all-companies');
        const list = document.getElementById('lgp-company-list');

        if (allCompanies && list) {
            list.style.display = allCompanies.checked ? 'none' : 'flex';

            // Uncheck all company checkboxes when "All Companies" is checked
            if (allCompanies.checked) {
                list.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            }
        }
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        const videoId = document.getElementById('lgp-video-id').value;
        const allCompanies = document.getElementById('lgp-all-companies').checked;

        const targetCompanies = allCompanies ? [] : Array.from(
            document.querySelectorAll('input[name="target_companies"]:checked')
        ).map(cb => parseInt(cb.value));

        const data = {
            title: document.getElementById('lgp-video-title').value,
            description: document.getElementById('lgp-video-description').value,
            video_url: document.getElementById('lgp-video-url').value,
            category: document.getElementById('lgp-video-category').value,
            duration: parseInt(document.getElementById('lgp-video-duration').value) || 0,
            target_companies: targetCompanies
        };

        try {
            const url = videoId
                ? `/wp-json/lgp/v1/training-videos/${videoId}`
                : '/wp-json/lgp/v1/training-videos';

            const method = videoId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Failed to save video');
            }

            videoModal.classList.add('hidden');
            showSuccess(videoId ? 'Video updated successfully' : 'Video added successfully');
            loadVideos();

        } catch (error) {
            console.error('Error saving video:', error);
            showError('Failed to save video');
        }
    }

    /**
     * Edit video
     */
    function editVideo(videoId) {
        openVideoModal(videoId);
    }

    /**
     * Delete video
     */
    async function deleteVideo(videoId) {
        if (!confirm('Are you sure you want to delete this video?')) {
            return;
        }

        try {
            const response = await fetch(`/wp-json/lgp/v1/training-videos/${videoId}`, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to delete video');
            }

            showSuccess('Video deleted successfully');
            loadVideos();

        } catch (error) {
            console.error('Error deleting video:', error);
            showError('Failed to delete video');
        }
    }

    /**
     * Play video in modal
     */
    async function playVideo(videoId) {
        const video = videos.find(v => v.id == videoId);
        if (!video) return;

        const player = document.getElementById('lgp-video-player');
        const title = document.getElementById('lgp-player-title');
        const description = document.getElementById('lgp-video-description');

        if (!player || !title || !description) return;

        title.textContent = video.title;
        description.textContent = video.description || '';

        // Embed video based on URL
        player.innerHTML = embedVideo(video.video_url);

        playerModal.classList.remove('hidden');
    }

    /**
     * Embed video from URL
     */
    function embedVideo(url) {
        // YouTube
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            const videoId = extractYouTubeId(url);
            return `<iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>`;
        }

        // Vimeo
        if (url.includes('vimeo.com')) {
            const videoId = url.split('/').pop();
            return `<iframe src="https://player.vimeo.com/video/${videoId}" allowfullscreen></iframe>`;
        }

        // Direct video
        return `<video controls src="${url}" style="width:100%;height:auto;"></video>`;
    }

    /**
     * Extract YouTube video ID from URL
     */
    function extractYouTubeId(url) {
        const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        const match = url.match(regex);
        return match ? match[1] : '';
    }

    /**
     * Format duration in seconds to MM:SS
     */
    function formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        // Simple alert for now - can be replaced with toast notification
        alert(message);
    }

    /**
     * Show error message
     */
    function showError(message) {
        alert('Error: ' + message);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
