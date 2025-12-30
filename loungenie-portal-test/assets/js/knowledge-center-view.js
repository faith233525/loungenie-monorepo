/**
 * Knowledge Center View
 * Support: manage guides, Partners: view and watch guides
 */

(function () {
    'use strict';

    const isSupport = (document.body?.dataset?.role === 'support') || (window.lgpData?.isSupport ?? false);
    let videos = [];
    let companies = [];
    let currentVideoId = null;

    const knowledgeApiBases = ['/wp-json/lgp/v1/knowledge-center', '/wp-json/lgp/v1/help-guides'];

    const els = {
        grid: document.getElementById('lgp-help-guides-grid'),
        noVideos: document.getElementById('lgp-no-videos'),
        searchInput: document.getElementById('lgp-help-guides-search'),
        categoryFilter: document.getElementById('lgp-category-filter'),
        addVideoBtn: document.getElementById('lgp-add-video-btn'),
        videoModal: document.getElementById('lgp-video-modal'),
        playerModal: document.getElementById('lgp-player-modal'),
        videoForm: document.getElementById('lgp-video-form'),
        companyList: document.getElementById('lgp-company-list'),
        allCompaniesToggle: document.getElementById('lgp-all-companies'),
        fileInput: document.getElementById('lgp-video-file'),
    };

    // --- Core helpers ----------------------------------------------------
    async function apiFetch(path = '', options = {}) {
        const headers = new Headers(options.headers || {});
        const restNonce = window.wpApiSettings?.nonce || window.lgpData?.restNonce || '';

        if (restNonce && !headers.has('X-WP-Nonce')) {
            headers.set('X-WP-Nonce', restNonce);
        }

        const opts = { ...options, credentials: 'same-origin', headers };
        let lastResponse = null;

        for (const base of knowledgeApiBases) {
            const response = await fetch(`${base}${path}`, opts);
            if (response.ok) {
                return response;
            }
            lastResponse = response;
            if (response.status !== 404) {
                break;
            }
        }

        return lastResponse || new Response(null, { status: 500 });
    }

    // --- UI wiring -------------------------------------------------------
    function init() {
        bindEvents();
        loadVideos();
        if (isSupport) {
            loadCompanies();
        }
    }

    function bindEvents() {
        els.searchInput?.addEventListener('input', debounce(filterVideos, 250));
        els.categoryFilter?.addEventListener('change', filterVideos);
        els.addVideoBtn?.addEventListener('click', () => openVideoModal());
        els.videoForm?.addEventListener('submit', handleFormSubmit);
        els.allCompaniesToggle?.addEventListener('change', toggleCompanyList);

        // Close modals
        document.querySelectorAll('.lgp-modal-close').forEach(btn => {
            btn.addEventListener('click', () => closeModal(btn.closest('.lgp-modal-overlay') || btn.closest('.lgp-modal')));
        });

        els.videoModal?.addEventListener('click', (e) => {
            if (e.target === els.videoModal) {
                closeModal(els.videoModal);
            }
        });

        els.playerModal?.addEventListener('click', (e) => {
            if (e.target === els.playerModal) {
                closeModal(els.playerModal);
            }
        });
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // --- Data loading ----------------------------------------------------
    async function loadVideos(filters = {}) {
        try {
            const params = new URLSearchParams();
            if (filters.category) params.append('category', filters.category);
            if (filters.search) params.append('search', filters.search);
            const query = params.toString();

            const response = await apiFetch(query ? `/?${query}` : '', { method: 'GET' });
            if (!response.ok) throw new Error(`Failed to load videos (${response.status})`);

            videos = await response.json();
            renderVideos(videos);
        } catch (err) {
            console.error('Error loading videos:', err);
            showError('Failed to load knowledge guides');
        }
    }

    async function loadCompanies() {
        try {
            const response = await fetch('/wp-json/lgp/v1/companies', { credentials: 'same-origin' });
            if (response.ok) {
                companies = await response.json();
                renderCompanySelector();
            }
        } catch (err) {
            console.error('Error loading companies:', err);
        }
    }

    // --- Rendering -------------------------------------------------------
    function renderVideos(list) {
        if (!els.grid) return;

        els.grid.innerHTML = '';
        if (!list || list.length === 0) {
            els.grid.style.display = 'none';
            if (els.noVideos) {
                els.noVideos.style.display = 'block';
            }
            return;
        }

        els.grid.style.display = 'grid';
        if (els.noVideos) {
            els.noVideos.style.display = 'none';
        }

        list.forEach(video => {
            const card = createVideoCard(video);
            els.grid.appendChild(card);
        });
    }

    function createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'lgp-video-card';

        const duration = video.duration ? formatDuration(video.duration) : '';
        const category = video.category || 'general';

        card.innerHTML = `
            <div class="lgp-video-thumbnail" data-video-id="${video.id}">🎬</div>
            <div class="lgp-video-card-body">
                <h3 class="lgp-video-card-title">${escapeHtml(video.title)}</h3>
                <div class="lgp-video-card-meta">
                    <span class="lgp-badge badge-info">${escapeHtml(category)}</span>
                    ${duration ? `<span>${duration}</span>` : ''}
                </div>
                ${video.description ? `<p class="lgp-video-card-description">${escapeHtml(video.description)}</p>` : ''}
                <div class="lgp-video-card-actions">
                    <button class="lgp-btn lgp-btn-primary lgp-watch-btn" data-video-id="${video.id}">Watch</button>
                    ${isSupport ? `
                        <button class="lgp-btn lgp-btn-secondary lgp-edit-btn" data-video-id="${video.id}">Edit</button>
                        <button class="lgp-btn lgp-btn-danger lgp-delete-btn" data-video-id="${video.id}">Delete</button>
                    ` : ''}
                </div>
            </div>`;

        card.querySelector('.lgp-video-thumbnail')?.addEventListener('click', () => playVideo(video.id));
        card.querySelector('.lgp-watch-btn')?.addEventListener('click', () => playVideo(video.id));

        if (isSupport) {
            card.querySelector('.lgp-edit-btn')?.addEventListener('click', () => editVideo(video.id));
            card.querySelector('.lgp-delete-btn')?.addEventListener('click', () => deleteVideo(video.id));
        }

        return card;
    }

    function filterVideos() {
        const search = els.searchInput?.value?.toLowerCase() || '';
        const category = els.categoryFilter?.value || '';

        const filtered = videos.filter(video => {
            const matchesSearch = !search ||
                video.title?.toLowerCase().includes(search) ||
                video.description?.toLowerCase().includes(search);
            const matchesCategory = !category || video.category === category;
            return matchesSearch && matchesCategory;
        });

        renderVideos(filtered);
    }

    // --- Modal + form ----------------------------------------------------
    function openVideoModal(videoId = null) {
        if (!els.videoModal || !els.videoForm) return;

        currentVideoId = videoId;
        const modalTitle = document.getElementById('lgp-modal-title');

        if (videoId) {
            modalTitle.textContent = 'Edit Knowledge Guide';
            loadVideoData(videoId);
        } else {
            modalTitle.textContent = 'Add Knowledge Guide';
            els.videoForm.reset();
            document.getElementById('lgp-video-id').value = '';
            if (els.allCompaniesToggle) {
                els.allCompaniesToggle.checked = true;
            }
            toggleCompanyList();
        }

        renderCompanySelector();
        els.videoModal.classList.remove('hidden');
    }

    function loadVideoData(videoId) {
        const video = videos.find(v => v.id == videoId);
        if (!video) return;

        document.getElementById('lgp-video-id').value = video.id;
        document.getElementById('lgp-video-title').value = video.title;
        document.getElementById('lgp-video-description').value = video.description || '';
        document.getElementById('lgp-video-url').value = video.content_url || '';
        document.getElementById('lgp-video-category').value = video.category || 'general';
        document.getElementById('lgp-video-duration').value = video.duration || '';

        const targets = video.target_companies ? JSON.parse(video.target_companies) : [];
        const allCompanies = els.allCompaniesToggle;

        if (allCompanies) {
            allCompanies.checked = targets.length === 0;
        }

        if (els.companyList) {
            els.companyList.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = targets.includes(parseInt(cb.value));
            });
        }

        toggleCompanyList();
    }

    function renderCompanySelector() {
        if (!els.companyList || !companies.length) return;

        els.companyList.innerHTML = companies.map(company => `
            <label>
                <input type="checkbox" name="target_companies" value="${company.id}" />
                ${escapeHtml(company.name)}
            </label>`).join('');
    }

    function toggleCompanyList() {
        if (!els.companyList || !els.allCompaniesToggle) return;

        const showList = !els.allCompaniesToggle.checked;
        els.companyList.style.display = showList ? 'flex' : 'none';
        if (!showList) {
            els.companyList.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        const videoId = document.getElementById('lgp-video-id').value;
        const allCompanies = !!els.allCompaniesToggle?.checked;
        const fileInput = els.fileInput;

        const targetCompanies = allCompanies ? [] : Array.from(
            document.querySelectorAll('input[name="target_companies"]:checked')
        ).map(cb => parseInt(cb.value));

        let contentUrl = document.getElementById('lgp-video-url').value.trim();

        if (fileInput?.files?.[0]) {
            const uploadResult = await uploadVideoFile(fileInput.files[0]);
            if (!uploadResult?.url) {
                showError('Upload failed');
                return;
            }
            contentUrl = uploadResult.url;
        }

        if (!contentUrl) {
            showError('Please provide a video URL or upload a file');
            return;
        }

        const payload = {
            title: document.getElementById('lgp-video-title').value,
            description: document.getElementById('lgp-video-description').value,
            content_url: contentUrl,
            category: document.getElementById('lgp-video-category').value,
            duration: parseInt(document.getElementById('lgp-video-duration').value) || 0,
            target_companies: targetCompanies,
        };

        try {
            const url = videoId ? `/${videoId}` : '';
            const method = videoId ? 'PUT' : 'POST';
            const response = await apiFetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (!response.ok) throw new Error('Failed to save video');

            closeModal(els.videoModal);
            if (fileInput) fileInput.value = '';
            showSuccess(videoId ? 'Video updated successfully' : 'Video added successfully');
            loadVideos();
        } catch (err) {
            console.error('Error saving video:', err);
            showError('Failed to save video');
        }
    }

    async function uploadVideoFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        const response = await apiFetch('/upload', { method: 'POST', body: formData });
        if (!response.ok) {
            console.error('Upload failed', await response.text());
            return null;
        }
        return response.json();
    }

    // --- CRUD helpers ----------------------------------------------------
    function editVideo(videoId) {
        openVideoModal(videoId);
    }

    async function deleteVideo(videoId) {
        if (!confirm('Are you sure you want to delete this video?')) return;

        try {
            const response = await apiFetch(`/${videoId}`, { method: 'DELETE' });
            if (!response.ok) throw new Error('Failed to delete video');
            showSuccess('Video deleted successfully');
            loadVideos();
        } catch (err) {
            console.error('Error deleting video:', err);
            showError('Failed to delete video');
        }
    }

    // --- Player ---------------------------------------------------------
    function playVideo(videoId) {
        const video = videos.find(v => v.id == videoId);
        if (!video || !els.playerModal) return;

        const player = document.getElementById('lgp-video-player');
        const title = document.getElementById('lgp-player-title');
        const description = document.getElementById('lgp-video-description');

        if (title) title.textContent = video.title;
        if (description) description.textContent = video.description || '';
        if (player) player.innerHTML = embedVideo(video.content_url);

        els.playerModal.classList.remove('hidden');
    }

    function embedVideo(url) {
        if (!url) return '<p>Invalid video URL</p>';
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            const videoId = extractYouTubeId(url);
            return `<iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>`;
        }
        if (url.includes('vimeo.com')) {
            const videoId = url.split('/').pop();
            return `<iframe src="https://player.vimeo.com/video/${videoId}" allowfullscreen></iframe>`;
        }
        return `<video controls src="${url}" style="width:100%;height:auto;"></video>`;
    }

    function extractYouTubeId(url) {
        const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
        return url.match(regex)?.[1] || '';
    }

    // --- Utilities ------------------------------------------------------
    function formatDuration(seconds) {
        const mins = Math.floor(seconds / 60) || 0;
        const secs = Math.max(0, seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    function debounce(fn, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn.apply(null, args), wait);
        };
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function showSuccess(message) {
        alert(message);
    }

    function showError(message) {
        alert('Error: ' + message);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
