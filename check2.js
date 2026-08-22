
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminApp', () => ({
                // ======================== STATE ========================
                sidebarOpen: false,
                darkMode: false,
                activeTab: 'dashboard',
                isLoading: true,
                isSaving: false,
                isDirty: false,
                health: {status:'checking', tables:{}},
                currentLang: 'bn',
                translations: {},

                // Admission filter
                admissionFilter: 'all',

                // Edit modal
                editModalOpen: false,
                editForm: { id: '', phone: '', address: '', course_name: '' },

                // Gallery
                galleryItems: [],
                attachmentModalOpen: false,
                currentGalleryItem: null,
                currentAttachments: [],
                eventMedia: [],
                pendingEventMedia: [],

                // Quotes
                quotes: [],

                // Content
                content: {
                    about_bn: '', about_en: '', about_status: true,
                    mission_bn: '', mission_en: '', vision_bn: '', vision_en: '', mv_status: true,
                    objectives_bn: '', objectives_en: '', objectives_status: true
                },
                faqs: [],

                // Social
                socialLinks: [],

                // Notice categories
                noticeCategories: [],

                // Popup
                popupImages: [],

                // Events/News
                eventType: 'event',
                activeSubTab: 'list',
                editorOpen: false,
                editingEvent: null,
                eventForm: {
                    id: null, type: 'event', date: '',
                    category_bn: '', category_en: '',
                    title_bn: '', title_en: '',
                    description_bn: '', description_en: '',
                    content_bn: '', content_en: '',
                    file_url: '',
                    showInMarquee: 0, status: 1
                },

                // Users
                users: [],
                addUserModalOpen: false,
                newUser: { username: '', full_name: '', email: '', password: '', role: 'editor' },

                // Main data
                data: {
                    site: {
                        font_size: '16', custom_font: '', logo: '', phone: '', email: '',
                        address: '', address_en: '',
                        master_admission: true, diploma_admission: true, nsda_admission: true,
                        marquee_speed: 30, popup_animation_delay: 3000,
                        popup_enabled: false, popup_images: []
                    },
                    scraper: { eligibility: { min_year: 2022, max_year: 2026, min_gpa: 2.00, gpa_operator: '>=', admission_active: true } },
                    messages: [], notices: [], events: [], news: [], teachers: [], courses: [], admissions: []
                },

                // ======================== COMPUTED ========================
                get totalPending() {
                    return this.data.admissions ? this.data.admissions.filter(a => a.status === 'pending').length : 0;
                },
                get filteredAdmissions() {
                    if (this.admissionFilter === 'all') return this.data.admissions || [];
                    return (this.data.admissions || []).filter(a => a.admission_type === this.admissionFilter);
                },

                // ======================== TRANSLATION ========================
                t(key) {
                    return this.translations[key] || key;
                },

                async loadTranslations(lang) {
                    try {
                        const res = await fetch(`../languages/${lang}.json?t=${Date.now()}`);
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        this.translations = await res.json();
                    } catch (e) {
                        console.warn('Translation fallback', e);
                        this.translations = {};
                    }
                },

                // ======================== INIT ========================
                init() {
                    // Dashboard is intentionally dark-only.
                    this.darkMode = true;
                    document.documentElement.classList.add('dark');

                    // Mark changes globally so only one contextual save action is shown.
                    document.addEventListener('input', (e) => { if (e.target && e.target.matches('input, textarea, select')) this.isDirty = true; }, true);
                    document.addEventListener('change', (e) => { if (e.target && e.target.matches('input, textarea, select')) this.isDirty = true; }, true);

                    // Language
                    const savedLang = localStorage.getItem('aspi_lang');
                    this.currentLang = ['bn','en'].includes(savedLang) ? savedLang : (navigator.language?.toLowerCase().startsWith('en') ? 'en' : 'bn');
                    document.documentElement.lang = this.currentLang;
                    this.loadTranslations(this.currentLang).then(() => { document.title = this.t('admin_title'); });

                    // Load all data
                    this.loadHealth();
                    this.loadData();
                    this.loadGallery();
                    this.loadQuotes();
                    this.loadContent();
                    this.loadFaqs();
                    this.loadSocialLinks();
                    this.loadNoticeCategories();
                    this.loadUsers();

                    // Auto-refresh
                    setInterval(() => {
                        this.loadData();
                        this.loadGallery();
                        this.loadQuotes();
                        this.loadContent();
                        this.loadFaqs();
                        this.loadSocialLinks();
                        this.loadNoticeCategories();
                        this.loadUsers();
                    }, 300000);
                },

                toggleTheme() {
                    this.darkMode = true;
                    document.documentElement.classList.add('dark');
                },

                switchLanguage(lang) {
                    if (lang === this.currentLang) return;
                    if (this.isDirty && !confirm(this.t('confirm_unsaved') || (this.currentLang === 'bn' ? 'অসংরক্ষিত পরিবর্তন আছে। ভাষা পরিবর্তন করলে এগুলো হারিয়ে যাবে। চালিয়ে যাবেন?' : 'You have unsaved changes. Switching language will discard them. Continue?'))) return;
                    this.currentLang = ['bn','en'].includes(lang) ? lang : 'bn';
                    document.documentElement.lang = this.currentLang;
                    localStorage.setItem('aspi_lang', this.currentLang);
                    this.loadTranslations(this.currentLang).then(() => { document.title = this.t('admin_title'); });
                    this.loadData();
                    this.loadGallery();
                    this.loadQuotes();
                    this.loadContent();
                    this.loadFaqs();
                    this.loadSocialLinks();
                    this.loadNoticeCategories();
                },

                logout() {
                    if (confirm(this.t('confirm_logout'))) {
                        window.location.href = 'logout.php';
                    }
                },

                // ======================== DATA LOADING ========================

                async loadHealth() {
                    try {
                        const res = await fetch('../api.php?action=dashboard_health&t=' + Date.now(), {cache:'no-store'});
                        this.health = await res.json();
                    } catch(e) { this.health = {status:'error',tables:{}}; }
                },

                async loadData() {
                    this.isLoading = true;
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?admin=1&lang=${lang}&t=${Date.now()}`, { cache: 'no-store' });
                        const json = await res.json();
                        if (!json.error) {
                            this.data = json;
                            // Ensure settings
                            if (this.data.site.master_admission === undefined) this.data.site.master_admission = true;
                            if (this.data.site.diploma_admission === undefined) this.data.site.diploma_admission = true;
                            if (this.data.site.nsda_admission === undefined) this.data.site.nsda_admission = true;
                            if (!this.data.site.address_en) this.data.site.address_en = '';
                            if (!this.data.site.marquee_speed) this.data.site.marquee_speed = 30;
                            if (!this.data.site.popup_animation_delay) this.data.site.popup_animation_delay = 3000;
                            if (this.data.site.popup_images) {
                                this.popupImages = typeof this.data.site.popup_images === 'string' ? JSON.parse(this.data.site.popup_images) : this.data.site.popup_images;
                            } else {
                                this.popupImages = [];
                            }
                            if (!this.data.scraper) this.data.scraper = { eligibility: {} };
                        }
                    } catch (e) { console.error('Data load error', e); }
                    this.isLoading = false;
                },

                async loadGallery() {
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?action=get_gallery_items&admin=1&lang=${lang}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.gallery) this.galleryItems = json.gallery;
                    } catch (e) { console.error('Gallery load error', e); }
                },

                async loadQuotes() {
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?action=get_quotes&admin=1&lang=${lang}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.quotes) this.quotes = json.quotes;
                    } catch (e) { console.error('Quotes load error', e); }
                },

                async loadContent() {
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?action=get_content&admin=1&lang=${lang}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.content) this.content = json.content;
                    } catch (e) { console.error('Content load error', e); }
                },

                async loadFaqs() {
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?action=get_faqs&admin=1&lang=${lang}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.faqs) this.faqs = json.faqs;
                    } catch (e) { console.error('FAQs load error', e); }
                },

                async loadSocialLinks() {
                    try {
                        const res = await fetch('../api.php?action=get_social_links&admin=1&t=' + Date.now());
                        const json = await res.json();
                        if (json.social_links) this.socialLinks = json.social_links;
                    } catch (e) { console.error('Social links load error', e); }
                },

                async loadNoticeCategories() {
                    try {
                        const lang = this.currentLang || 'bn';
                        const res = await fetch(`../api.php?action=get_notice_categories&admin=1&lang=${lang}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.categories) this.noticeCategories = json.categories;
                    } catch (e) { console.error('Notice categories load error', e); }
                },

                async loadUsers() {
                    try {
                        const res = await fetch('../api.php?action=get_users&t=' + Date.now());
                        const json = await res.json();
                        if (json.users) this.users = json.users;
                    } catch (e) { console.error('Users load error', e); }
                },

                // ======================== SAVE DATA ========================
                async saveData() {
                    if (this.isSaving) return;
                    this.isSaving = true;
                    this.isLoading = true;
                    try {
                        const payload = {
                            site: {
                                ...this.data.site,
                                popup_images: this.popupImages
                            },
                            scraper: this.data.scraper,
                            messages: this.data.messages,
                            notices: this.data.notices,
                            events: this.data.events,
                            news: this.data.news,
                            teachers: this.data.teachers,
                            courses: this.data.courses,
                            quotes: this.quotes,
                            social_links: this.socialLinks,
                            notice_categories: this.noticeCategories,
                            content: this.content,
                            faqs: this.faqs,
                            gallery: this.galleryItems
                        };
                        const res = await fetch('../api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.isDirty = false;
                            alert(this.t('save_success'));
                            this.loadData();
                            this.loadGallery();
                            this.loadQuotes();
                            this.loadContent();
                            this.loadFaqs();
                            this.loadSocialLinks();
                            this.loadNoticeCategories();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isSaving = false;
                    this.isLoading = false;
                },

                // ======================== ADMISSION SETTINGS ========================
                async saveAdmissionSettings() {
                    this.isLoading = true;
                    try {
                        const payload = {
                            master_admission: this.data.site.master_admission ? 1 : 0,
                            diploma_admission: this.data.site.diploma_admission ? 1 : 0,
                            nsda_admission: this.data.site.nsda_admission ? 1 : 0
                        };
                        const res = await fetch('../api.php?action=update_admission_settings', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.isDirty = false;
                            alert(this.t('settings_saved'));
                            this.loadData();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== POPUP MANAGEMENT ========================
                async uploadPopupImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const fd = new FormData();
                    fd.append('file', file);
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=upload', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.url) {
                            this.popupImages.push(result.url);
                            this.data.site.popup_images = this.popupImages;
                            await this.savePopupSettings();
                            alert(this.t('upload_success'));
                        } else {
                            alert(this.t('upload_failed'));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async removePopupImage(index) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.popupImages.splice(index, 1);
                    this.data.site.popup_images = this.popupImages;
                    await this.savePopupSettings();
                },

                async savePopupSettings() {
                    try {
                        const payload = {
                            popup_enabled: this.data.site.popup_enabled ? 1 : 0,
                            popup_images: this.popupImages
                        };
                        const res = await fetch('../api.php?action=update_settings', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const result = await res.json();
                        if (result.status !== 'success') {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                },

                // ======================== UPLOAD FILE ========================
                async uploadFile(event, objRef, propertyName) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.isLoading = true;
                    const fd = new FormData();
                    fd.append('file', file);
                    if (propertyName === 'custom_font') fd.append('upload_type', 'font');
                    try {
                        const res = await fetch('../api.php?action=upload', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.url) {
                            objRef[propertyName] = result.url;
                            this.isDirty = true;
                            alert(this.t('upload_success'));
                        } else {
                            alert(this.t('upload_failed') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== ADMISSIONS ACTIONS ========================
                async updateStatus(id, status) {
                    if (!confirm(this.t('confirm_status_change'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=update_admission_status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id, status, note: '' })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.isDirty = false;
                            alert(this.t('status_updated'));
                            this.loadData();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                editAdmission(app) {
                    this.editForm = { id: app.id, phone: app.phone, address: app.address, course_name: app.course_name };
                    this.editModalOpen = true;
                },

                async saveEdit() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=edit_admission', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.editForm)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.isDirty = false;
                            alert(this.t('save_success'));
                            this.editModalOpen = false;
                            this.loadData();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== GALLERY ========================
                openGalleryUpload() {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = async (e) => {
                        const file = e.target.files[0];
                        if (!file) return;
                        const title_bn = prompt(this.t('title_bn') + ':');
                        const title_en = prompt(this.t('title_en') + ':');
                        const fd = new FormData();
                        fd.append('file', file);
                        fd.append('title_bn', title_bn || '');
                        fd.append('title_en', title_en || '');
                        this.isLoading = true;
                        try {
                            const res = await fetch('../api.php?action=upload_gallery_item', { method: 'POST', body: fd });
                            const result = await res.json();
                            if (result.status === 'success') {
                                alert(this.t('upload_success'));
                                this.loadGallery();
                            } else {
                                alert(this.t('upload_failed') + ': ' + (result.error || ''));
                            }
                        } catch (e) { alert(this.t('network_error')); }
                        this.isLoading = false;
                    };
                    input.click();
                },

                async deleteGalleryItem(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=delete_gallery_item', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('delete_success'));
                            this.loadGallery();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async openAttachments(item) {
                    this.currentGalleryItem = item;
                    this.isLoading = true;
                    try {
                        const res = await fetch(`../api.php?action=get_gallery_attachments&id=${item.id}&t=${Date.now()}`);
                        const json = await res.json();
                        if (json.attachments) this.currentAttachments = json.attachments;
                        this.attachmentModalOpen = true;
                    } catch (e) { console.error(e); }
                    this.isLoading = false;
                },

                async uploadAttachment(event) {
                    const file = event.target.files[0];
                    if (!file || !this.currentGalleryItem) return;
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('gallery_id', this.currentGalleryItem.id);
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=upload_gallery_attachment', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('upload_success'));
                            this.openAttachments(this.currentGalleryItem);
                        } else {
                            alert(this.t('upload_failed') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async deleteAttachment(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=delete_gallery_attachment', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('delete_success'));
                            this.openAttachments(this.currentGalleryItem);
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== EVENTS & NEWS ========================
                assetUrl(path) {
                    if (!path) return '';
                    if (/^(https?:)?\/\//i.test(path) || path.startsWith('/')) return path;
                    return '../' + path.replace(/^\.\//, '');
                },

                async loadEventMedia(id) {
                    if (!id) { this.eventMedia = []; return; }
                    try {
                        const res = await fetch(`../api.php?action=get_event_media&id=${id}&t=${Date.now()}`);
                        const json = await res.json();
                        this.eventMedia = json.media || [];
                    } catch (e) { this.eventMedia = []; }
                },

                stageEventMedia(event) {
                    const files = Array.from(event.target.files || []);
                    this.pendingEventMedia = files.filter(file => /^(image\/(jpeg|png|gif|webp)|video\/(mp4|webm|ogg))$/i.test(file.type));
                    event.target.value = '';
                },

                async uploadPendingEventMedia(eventId) {
                    if (!eventId || !this.pendingEventMedia.length) return true;
                    const fd = new FormData();
                    fd.append('event_id', eventId);
                    this.pendingEventMedia.forEach(file => fd.append('files[]', file));
                    try {
                        const res = await fetch('../api.php?action=upload_event_media', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.status !== 'success') {
                            alert(this.t('upload_failed') + ': ' + (result.error || ''));
                            return false;
                        }
                        this.pendingEventMedia = [];
                        await this.loadEventMedia(eventId);
                        return true;
                    } catch (e) {
                        alert(this.t('network_error'));
                        return false;
                    }
                },

                async deleteEventMedia(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    try {
                        const res = await fetch('../api.php?action=delete_event_media', {
                            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') await this.loadEventMedia(this.eventForm.id);
                        else alert(this.t('save_error') + ': ' + (result.error || ''));
                    } catch (e) { alert(this.t('network_error')); }
                },

                openEventEditor(item = null) {
                    this.pendingEventMedia = [];
                    if (item) {
                        this.eventForm = { ...item };
                        this.editingEvent = true;
                        this.loadEventMedia(item.id);
                    } else {
                        this.eventForm = {
                            id: null,
                            type: this.eventType,
                            date: new Date().toISOString().split('T')[0],
                            category_bn: '', category_en: '',
                            title_bn: '', title_en: '',
                            description_bn: '', description_en: '',
                            content_bn: '', content_en: '',
                            file_url: '',
                            showInMarquee: 0,
                            status: 1
                        };
                        this.editingEvent = false;
                        this.eventMedia = [];
                    }
                    this.editorOpen = true;
                },

                editEvent(item) {
                    this.openEventEditor(item);
                },

                async saveEvent() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=save_event', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.eventForm)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.isDirty = false;
                            const savedId = result.id || this.eventForm.id;
                            if (savedId) {
                                this.eventForm.id = savedId;
                                await this.uploadPendingEventMedia(savedId);
                                await this.loadEventMedia(savedId);
                            }
                            alert(this.t('save_success'));
                            this.editorOpen = false;
                            this.loadData();
                        } else {
                            alert(this.t('save_error') + ': ' + result.error);
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async deleteEvent(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=delete_event', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('delete_success'));
                            this.loadData();
                        } else {
                            alert(this.t('save_error'));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async uploadFeaturedImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const fd = new FormData();
                    fd.append('file', file);
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=upload', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.url) {
                            this.eventForm.file_url = result.url;
                            this.isDirty = true;
                            alert(this.t('upload_success'));
                        } else {
                            alert(this.t('upload_failed'));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== NOTICES ========================
                addNotice() {
                    const today = new Date().toISOString().split('T')[0];
                    this.isDirty = true;
                    this.data.notices.unshift({
                        id: Date.now(),
                        date: today,
                        date_bn: '', date_en: '',
                        category_id: 0, sub_category_id: 0,
                        title_bn: '', title_en: '',
                        file_url: '',
                        isNew: true,
                        showInMarquee: false
                    });
                },

                deleteItem(type, index) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.data[type].splice(index, 1);
                    this.isDirty = true;
                },

                // ======================== NOTICE CATEGORIES ========================
                addNoticeCategory() {
                    this.isDirty = true;
                    this.noticeCategories.unshift({
                        id: Date.now(),
                        parent_id: 0,
                        name_bn: '', name_en: '',
                        slug: '',
                        status: 1
                    });
                },

                deleteNoticeCategory(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.noticeCategories = this.noticeCategories.filter(c => c.id !== id);
                    this.isDirty = true;
                },

                // ======================== QUOTES ========================
                addQuote() {
                    this.isDirty = true;
                    this.quotes.unshift({
                        id: Date.now(),
                        name_bn: '', name_en: '',
                        designation_bn: '', designation_en: '',
                        quote_bn: '', quote_en: '',
                        image_url: '',
                        status: true,
                        sort_order: 0
                    });
                },

                deleteQuote(index) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.quotes.splice(index, 1);
                    this.isDirty = true;
                },

                // ======================== SOCIAL LINKS ========================
                addSocialLink() {
                    this.isDirty = true;
                    this.socialLinks.unshift({
                        id: Date.now(),
                        platform_name: '', platform_name_bn: '',
                        icon_class: 'fa-brands fa-link',
                        icon_image: '',
                        url: '#',
                        color: '#000000',
                        sort_order: 0,
                        status: true
                    });
                },

                deleteSocialLink(index) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.socialLinks.splice(index, 1);
                    this.isDirty = true;
                },

                async uploadSocialIcon(event, link) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const fd = new FormData();
                    fd.append('file', file);
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=upload', { method: 'POST', body: fd });
                        const result = await res.json();
                        if (result.url) {
                            link.icon_image = result.url;
                            this.isDirty = true;
                            alert(this.t('upload_success'));
                        } else {
                            alert(this.t('upload_failed'));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== FAQ ========================
                addFaq() {
                    this.isDirty = true;
                    this.faqs.unshift({
                        id: Date.now(),
                        question_bn: '', question_en: '',
                        answer_bn: '', answer_en: '',
                        status: true,
                        sort_order: 0
                    });
                },

                deleteFaq(index) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.faqs.splice(index, 1);
                    this.isDirty = true;
                },

                // ======================== CLEANUP ========================
                async runCleanup(type) {
                    if (!confirm(this.t('confirm_cleanup'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=cleanup', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('cleanup_success'));
                            this.loadData();
                            this.loadGallery();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== USERS ========================
                openAddUser() {
                    this.newUser = { username: '', full_name: '', email: '', password: '', role: 'editor' };
                    this.addUserModalOpen = true;
                },

                async saveUser() {
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=add_user', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.newUser)
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            alert(this.t('user_added'));
                            this.addUserModalOpen = false;
                            this.loadUsers();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async toggleUserStatus(id) {
                    if (!confirm(this.t('confirm_status_change'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=toggle_user_status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.loadUsers();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                async deleteUser(id) {
                    if (!confirm(this.t('confirm_delete'))) return;
                    this.isLoading = true;
                    try {
                        const res = await fetch('../api.php?action=delete_user', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id })
                        });
                        const result = await res.json();
                        if (result.status === 'success') {
                            this.loadUsers();
                        } else {
                            alert(this.t('save_error') + ': ' + (result.error || ''));
                        }
                    } catch (e) { alert(this.t('network_error')); }
                    this.isLoading = false;
                },

                // ======================== TEACHERS, COURSES ADD ========================
                addTeacher() {
                    this.isDirty = true;
                    this.data.teachers.unshift({
                        id: Date.now(),
                        name_bn: '', name_en: '',
                        deg_bn: '', deg_en: '',
                        dept_bn: '', dept_en: '',
                        file_url: ''
                    });
                },

                addCourse() {
                    this.isDirty = true;
                    this.data.courses.unshift({
                        id: Date.now(),
                        type: 'diploma',
                        title_bn: '', title_en: '',
                        level_bn: '', level_en: ''
                    });
                }
            }));
        });
    