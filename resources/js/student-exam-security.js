class StudentExamSecurity {
    constructor(config) {
        this.config = config;
        this.csrfToken = config.csrfToken;
        this.currentQuestion = config.currentQuestion || null;
        this.pendingAnswers = new Map();
        this.lastViolationAt = new Map();
        this.lastActivityAt = Date.now();
        this.idleReported = false;
        this.submitting = false;
        this.warningOpen = false;
        this.devToolsOpen = false;
        this.examStarted = false;
        this.securityEnabled = false;
        this.fullscreenStartedAt = null;
        this.beforeUnloadKey = `cbt_exam_unload_${config.resultId}`;
        this.heartbeatTimer = null;
        this.autosaveTimer = null;
        this.idleTimer = null;
        this.devToolsTimer = null;
        this.countdownTimer = null;
        this.countdownStartedAt = Date.now();
        this.initialRemainingSeconds = Number(config.remainingSeconds ?? 0);
        this.autoSubmitSent = false;
    }

    init() {
        if (!this.config.active) {
            return;
        }

        console.log('[EXAM SECURITY] page loaded');
        this.bindStartFullscreen();
        this.bindAnswerButtons();
        this.initializeAnsweredState();
        this.renderTimer();
        document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => { this.submitting = true; }));
        this.bindSecurityEvents();
    }

    bindStartFullscreen() {
        const startButton = document.getElementById('startFullscreenExam');
        const overlay = document.getElementById('examStartOverlay');
        const examContent = document.getElementById('examContent');
        const error = document.getElementById('fullscreenStartError');

        if (!startButton || !overlay || !examContent) {
            this.startExam();
            return;
        }

        startButton.addEventListener('click', async () => {
            try {
                error?.classList.add('hidden');
                startButton.disabled = true;
                startButton.textContent = 'Membuka Fullscreen...';
                console.log('[EXAM SECURITY] fullscreen requested');

                await document.documentElement.requestFullscreen();

                console.log('[EXAM SECURITY] fullscreen success');
                overlay.classList.add('hidden');
                examContent.classList.remove('hidden');
                this.startExam();
            } catch (errorObject) {
                console.warn('[EXAM SECURITY] fullscreen failed', errorObject);
                error?.classList.remove('hidden');
                startButton.disabled = false;
                startButton.textContent = 'Mulai Ujian dalam Fullscreen';
            }
        });
    }

    startExam() {
        if (this.examStarted) {
            return;
        }

        this.examStarted = true;
        this.securityEnabled = true;
        this.fullscreenStartedAt = Date.now();
        this.lastActivityAt = Date.now();
        console.log('[EXAM SECURITY] examStarted=true');

        this.detectReloadReturn();
        this.startTimers();
        this.sendHeartbeat();
    }

    bindAnswerButtons() {
        // Single choice buttons (pilihan_ganda)
        document.querySelectorAll('[data-answer-button]').forEach((button) => {
            button.addEventListener('click', () => {
                const questionId = Number(button.dataset.questionId);
                const answer = button.dataset.answer;
                this.currentQuestion = questionId;
                this.selectAnswer(questionId, answer, button);
            });
        });

        // Dropdown selects
        document.querySelectorAll('[data-answer-dropdown]').forEach((select) => {
            select.addEventListener('change', () => {
                const questionId = Number(select.dataset.questionId);
                const answer = select.value;
                this.currentQuestion = questionId;
                this.selectDropdownAnswer(questionId, answer, select);
            });
        });

        // Multiple answer checkboxes
        document.querySelectorAll('[data-answer-checkbox]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const questionId = Number(checkbox.dataset.questionId);
                this.currentQuestion = questionId;
                this.collectAndSaveMultipleAnswers(questionId);
            });
        });

        // Checklist radio buttons
        document.querySelectorAll('[data-answer-checklist]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const questionId = Number(radio.dataset.questionId);
                this.currentQuestion = questionId;
                this.collectAndSaveChecklistAnswers(questionId);
            });
        });
    }

    bindSecurityEvents() {
        document.addEventListener('visibilitychange', () => {
            if (!this.canProcessSecurityEvent()) {
                return;
            }

            if (document.hidden) {
                this.reportViolation('tab_switch', 'Siswa meninggalkan tab ujian', {
                    visibility_state: document.visibilityState,
                    fullscreen: Boolean(document.fullscreenElement),
                });
            }
        });

        window.addEventListener('blur', () => {
            if (!this.canProcessSecurityEvent()) {
                return;
            }

            this.reportViolation('window_blur', 'Browser kehilangan fokus, kemungkinan siswa pindah aplikasi', {
                visibility_state: document.visibilityState,
                fullscreen: Boolean(document.fullscreenElement),
            });
        });

        window.addEventListener('focus', () => {
            this.markActivity();
            if (this.examStarted) {
                this.sendHeartbeat();
            }
        });

        document.addEventListener('fullscreenchange', () => {
            console.log('[EXAM SECURITY] fullscreenchange', document.fullscreenElement);
            if (!this.canProcessSecurityEvent() || this.submitting) {
                return;
            }

            if (!document.fullscreenElement) {
                console.log('[EXAM SECURITY] exit fullscreen detected');
                this.handleExitFullscreen();
            }
        });

        document.addEventListener('keydown', (event) => this.handleKeydown(event), true);

        document.addEventListener('contextmenu', (event) => {
            if (!this.canReportViolation()) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            this.reportViolation('right_click', 'Siswa mencoba klik kanan', { x: event.clientX, y: event.clientY });
        }, true);

        ['copy', 'paste', 'cut'].forEach((eventName) => {
            document.addEventListener(eventName, (event) => {
                if (!this.canReportViolation()) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                this.reportViolation('clipboard', 'Siswa mencoba copy/paste/cut', { action: eventName });
            }, true);
        });

        ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
            document.addEventListener(eventName, () => this.markActivity(), { passive: true });
        });

        window.addEventListener('beforeunload', (event) => {
            if (!this.examStarted || this.submitting) {
                return undefined;
            }

            sessionStorage.setItem(this.beforeUnloadKey, new Date().toISOString());
            event.preventDefault();
            event.returnValue = '';
            return '';
        });
    }

    canProcessSecurityEvent() {
        if (!this.canReportViolation()) {
            return false;
        }

        if (this.fullscreenStartedAt && Date.now() - this.fullscreenStartedAt < 1500) {
            return false;
        }

        return true;
    }

    canReportViolation() {
        return this.examStarted && this.securityEnabled;
    }

    handleKeydown(event) {
        this.markActivity();

        if (!this.canProcessSecurityEvent()) {
            return;
        }

        const key = event.key || '';
        const normalized = key.toLowerCase();
        const isCtrl = event.ctrlKey || event.metaKey;
        const forbiddenCtrl = ['c', 'v', 'x', 'a', 's', 'p', 'u', 'n', 't', 'w'].includes(normalized);
        const forbiddenInspector = isCtrl && event.shiftKey && ['i', 'j'].includes(normalized);
        const forbiddenFunction = normalized === 'f12' || normalized === 'printscreen';
        const escapeFullscreen = normalized === 'escape' && Boolean(document.fullscreenElement);

        if ((isCtrl && forbiddenCtrl) || forbiddenInspector || forbiddenFunction || escapeFullscreen) {
            event.preventDefault();
            event.stopPropagation();
            this.reportViolation('forbidden_shortcut', `Shortcut terlarang ditekan: ${this.shortcutLabel(event)}`, {
                key: this.shortcutLabel(event),
                fullscreen: Boolean(document.fullscreenElement),
                visibility_state: document.visibilityState,
            });
        }
    }

    shortcutLabel(event) {
        const parts = [];
        if (event.ctrlKey) parts.push('Ctrl');
        if (event.metaKey) parts.push('Meta');
        if (event.shiftKey) parts.push('Shift');
        if (event.altKey) parts.push('Alt');
        parts.push(event.key || event.code || 'Unknown');
        return parts.join('+');
    }

    async selectAnswer(questionId, answer, button) {
        this.pendingAnswers.set(questionId, answer);
        this.updateSelectedState(questionId, button);
        await this.flushAnswer(questionId, answer);
    }

    async selectDropdownAnswer(questionId, answer, selectElement) {
        this.pendingAnswers.set(questionId, answer);
        selectElement.closest('article')?.setAttribute('data-answered', 'true');
        await this.flushAnswer(questionId, answer);
    }

    async collectAndSaveMultipleAnswers(questionId) {
        const checkboxes = document.querySelectorAll(`[data-answer-checkbox][data-question-id="${questionId}"]`);
        const selected = [];
        checkboxes.forEach((cb) => {
            if (cb.checked) {
                selected.push(cb.value);
            }
        });
        
        // Update visual state
        checkboxes.forEach((cb) => {
            const label = cb.closest('label');
            if (cb.checked) {
                label?.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            } else {
                label?.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            }
        });

        const article = document.querySelector(`#question-${questionId}`);
        if (selected.length > 0) {
            article?.setAttribute('data-answered', 'true');
        } else {
            article?.removeAttribute('data-answered');
        }

        this.pendingAnswers.set(questionId, selected);
        await this.flushAnswer(questionId, selected);
    }

    async collectAndSaveChecklistAnswers(questionId) {
        const radios = document.querySelectorAll(`[data-answer-checklist][data-question-id="${questionId}"]`);
        const answers = {};
        radios.forEach((radio) => {
            if (radio.checked) {
                const index = radio.dataset.index;
                answers[index] = radio.value;
            }
        });

        // Update visual state
        radios.forEach((radio) => {
            const label = radio.closest('label');
            if (radio.checked) {
                label?.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            } else {
                label?.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            }
        });

        const article = document.querySelector(`#question-${questionId}`);
        if (Object.keys(answers).length > 0) {
            article?.setAttribute('data-answered', 'true');
        }

        // Convert to array format for backend
        const answerArray = Object.entries(answers).map(([index, value]) => ({ index: parseInt(index), value }));
        this.pendingAnswers.set(questionId, answerArray);
        await this.flushAnswer(questionId, answerArray);
    }

    updateSelectedState(questionId, selectedButton) {
        document.querySelectorAll(`[data-answer-button][data-question-id="${questionId}"]`).forEach((button) => {
            button.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            button.setAttribute('aria-pressed', 'false');
        });
        selectedButton.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
        selectedButton.setAttribute('aria-pressed', 'true');
        selectedButton.closest('article')?.setAttribute('data-answered', 'true');
    }

    async flushAnswer(questionId, answer) {
        try {
            const response = await fetch(this.config.answerUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({ question_id: questionId, jawaban: answer }),
            });
            if (response.status === 423) {
                const payload = await response.json().catch(() => null);
                this.handleAutoSubmit(payload?.message || 'Ujian sudah ditutup oleh sistem.');
                return;
            }
            if (response.ok) {
                this.pendingAnswers.delete(questionId);
            }
        } catch (error) {
            console.warn('Auto save jawaban tertunda.', error);
        }
    }

    flushPendingAnswers() {
        this.pendingAnswers.forEach((answer, questionId) => {
            this.flushAnswer(questionId, answer);
        });
    }

    async handleExitFullscreen() {
        const payload = await this.reportViolation('exit_fullscreen', 'Siswa keluar dari mode fullscreen', {
            visibility_state: document.visibilityState,
            fullscreen: false,
        }, false);

        if (payload?.action === 'auto_submit' || payload?.action === 'lock') {
            return;
        }

        this.showFullscreenWarning(payload?.violation_count || 1, payload?.max_violations || 3);
    }

    async reportViolation(type, message, meta = {}, showModal = true) {
        if (!this.canReportViolation()) {
            return null;
        }

        const now = Date.now();
        const throttleKey = `${type}:${meta.key || meta.action || ''}`;
        if (now - (this.lastViolationAt.get(throttleKey) || 0) < 2000) {
            return null;
        }
        this.lastViolationAt.set(throttleKey, now);

        try {
            const response = await fetch(this.config.violationUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    type,
                    message,
                    meta: {
                        ...meta,
                        timestamp: new Date().toISOString(),
                        current_question: this.currentQuestion,
                        remaining_time: this.remainingTime(),
                        user_agent: navigator.userAgent,
                    },
                }),
            });

            if (response.status === 423) {
                this.handleAutoSubmit('Ujian sudah ditutup oleh sistem.');
                return { action: 'auto_submit' };
            }

            const payload = await response.json();
            if (payload.action === 'auto_submit' || payload.action === 'lock') {
                this.handleAutoSubmit(payload.message || 'Ujian otomatis dikumpulkan karena melebihi batas pelanggaran.');
                return payload;
            }

            if (showModal) {
                this.showWarning(payload.violation_count, payload.max_violations, message, payload.action);
            }

            return payload;
        } catch (error) {
            console.warn('Gagal mengirim log pelanggaran anti-cheat.', error);
            return null;
        }
    }

    showWarning(count, max, message, action = 'warn') {
        const title = action === 'warn' && count >= 2 ? 'Peringatan Keras Anti-Cheat' : 'Peringatan Anti-Cheat';
        const html = `
            <p class="text-slate-700">${this.escapeHtml(message || 'Anda terdeteksi meninggalkan halaman ujian.')}</p>
            <p class="mt-3 font-bold text-red-600">Pelanggaran: ${count} / ${max}</p>
            <p class="mt-2 text-sm text-slate-500">Jika pelanggaran berulang, ujian akan otomatis dikumpulkan.</p>
        `;

        this.modal(title, html, 'Saya Mengerti');
    }

    showFullscreenWarning(count, max) {
        const backdrop = document.getElementById('fullscreenWarningBackdrop');
        backdrop?.classList.remove('hidden');
        this.warningOpen = true;

        const html = `
            <p class="text-slate-700">Ujian wajib dikerjakan dalam mode fullscreen. Aktivitas ini telah dicatat sebagai pelanggaran.</p>
            <p class="mt-3 font-bold text-red-600">Pelanggaran: ${count} / ${max}</p>
        `;

        if (window.Swal) {
            window.Swal.fire({
                title: 'Anda Keluar dari Fullscreen',
                html,
                icon: 'warning',
                confirmButtonText: 'Kembali ke Fullscreen',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                allowEscapeKey: false,
                preConfirm: async () => {
                    try {
                        await this.returnToFullscreen();
                    } catch (error) {
                        window.Swal.showValidationMessage('Gagal kembali ke fullscreen. Klik tombol lagi.');
                        return false;
                    }

                    return true;
                },
            }).then((result) => {
                if (result.isConfirmed && document.fullscreenElement) {
                    this.hideFullscreenWarning();
                }
            });
            return;
        }

        window.alert(`Anda Keluar dari Fullscreen\nUjian wajib dikerjakan dalam mode fullscreen. Aktivitas ini telah dicatat sebagai pelanggaran.\nPelanggaran: ${count} / ${max}`);
        this.returnToFullscreen().then(() => this.hideFullscreenWarning()).catch(() => window.alert('Gagal kembali ke fullscreen. Klik tombol lagi.'));
    }

    async returnToFullscreen() {
        await document.documentElement.requestFullscreen();
        this.fullscreenStartedAt = Date.now();
    }

    hideFullscreenWarning() {
        document.getElementById('fullscreenWarningBackdrop')?.classList.add('hidden');
        this.warningOpen = false;
    }

    showIdleModal() {
        this.modal('Peringatan Anti-Cheat', '<p>Anda tidak aktif. Lanjutkan ujian?</p>', 'Saya Mengerti');
    }

    handleAutoSubmit(message) {
        this.submitting = true;
        this.modal('Ujian Dikumpulkan Otomatis', `<p>${this.escapeHtml(message)}</p><p class="mt-2 text-sm text-slate-500">Anda telah melewati batas pelanggaran.</p>`, 'OK', () => {
            window.location.href = this.config.afterSubmitUrl;
        });
        setTimeout(() => {
            window.location.href = this.config.afterSubmitUrl;
        }, 2500);
    }

    modal(title, html, confirmText, onClose = null, allowOutsideClick = false) {
        if (window.Swal) {
            window.Swal.fire({
                title,
                html,
                icon: title.includes('Otomatis') ? 'error' : 'warning',
                confirmButtonText: confirmText,
                confirmButtonColor: '#2563eb',
                allowOutsideClick,
                allowEscapeKey: false,
            }).then(() => onClose && onClose());
            return;
        }

        window.alert(`${title}\n${html.replace(/<[^>]*>/g, '')}`);
        if (onClose) onClose();
    }

    startTimers() {
        if (this.heartbeatTimer || this.autosaveTimer || this.idleTimer || this.devToolsTimer) {
            return;
        }

        this.heartbeatTimer = window.setInterval(() => this.sendHeartbeat(), 10000);
        this.countdownTimer = window.setInterval(() => this.tickTimer(), 1000);
        this.autosaveTimer = window.setInterval(() => this.flushPendingAnswers(), 10000);
        this.idleTimer = window.setInterval(() => this.checkIdle(), 15000);
        this.devToolsTimer = window.setInterval(() => this.detectDevTools(), 3000);
    }

    async sendHeartbeat() {
        if (!this.examStarted) {
            return;
        }

        try {
            const response = await fetch(this.config.heartbeatUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    current_question: this.currentQuestion,
                    remaining_seconds: this.remainingTime(),
                    remaining_time: this.remainingTime(),
                    answered_questions: this.answeredCount(),
                    total_questions: Number(this.config.totalQuestions || 0),
                    fullscreen_status: Boolean(document.fullscreenElement),
                    visibility_state: document.visibilityState,
                }),
            });
            const payload = await response.json().catch(() => null);
            if (payload?.action === 'auto_submit') {
                this.handleAutoSubmit(payload.message || 'Waktu ujian habis.');
            }
        } catch (error) {
            this.reportViolation('connection_lost', 'Koneksi peserta terputus saat ujian berlangsung', {
                error: error.message,
                visibility_state: document.visibilityState,
            });
        }
    }

    checkIdle() {
        if (!this.canReportViolation()) {
            return;
        }

        if (Date.now() - this.lastActivityAt < this.config.idleTimeoutMs) {
            this.idleReported = false;
            return;
        }

        if (!this.idleReported) {
            this.idleReported = true;
            this.reportViolation('idle', 'Siswa tidak aktif saat ujian berlangsung', {
                idle_seconds: Math.round((Date.now() - this.lastActivityAt) / 1000),
            });
            this.showIdleModal();
        }
    }

    detectDevTools() {
        if (!this.canReportViolation()) {
            return;
        }

        const widthGap = Math.abs(window.outerWidth - window.innerWidth);
        const heightGap = Math.abs(window.outerHeight - window.innerHeight);
        const extremeResize = widthGap > 180 || heightGap > 180;
        const start = performance.now();
        for (let index = 0; index < 20000; index += 1) {
            Math.sqrt(index);
        }
        const timingGap = performance.now() - start > 120;

        if ((extremeResize || timingGap) && !this.devToolsOpen) {
            this.devToolsOpen = true;
            this.reportViolation('devtools', 'Indikasi Developer Tools terbuka', { width_gap: widthGap, height_gap: heightGap, timing_gap: timingGap });
        }

        if (!extremeResize && !timingGap) {
            this.devToolsOpen = false;
        }
    }

    detectReloadReturn() {
        const unloadAt = sessionStorage.getItem(this.beforeUnloadKey);
        if (unloadAt) {
            sessionStorage.removeItem(this.beforeUnloadKey);
            this.reportViolation('page_reload', 'Siswa me-refresh halaman ujian', { previous_unload_at: unloadAt });
        }
    }

    markActivity() {
        this.lastActivityAt = Date.now();
    }

    remainingTime() {
        const initial = Number(this.initialRemainingSeconds || 0);
        if (initial > 0) {
            const elapsed = Math.floor((Date.now() - this.countdownStartedAt) / 1000);
            return Math.max(0, initial - elapsed);
        }

        const end = Number(this.config.endsAtMs || 0);
        if (!end) {
            return null;
        }
        return Math.max(0, Math.floor((end - Date.now()) / 1000));
    }

    tickTimer() {
        this.renderTimer();
        if (this.remainingTime() <= 0) {
            this.submitBecauseTimeExpired();
        }
    }

    renderTimer() {
        const timer = document.getElementById('studentExamTimer');
        const value = document.getElementById('studentExamTimerValue');
        const warning = document.getElementById('studentExamTimerWarning');
        if (!timer || !value) return;

        const seconds = Math.max(0, Number(this.remainingTime() || 0));
        value.textContent = this.formatSeconds(seconds);
        timer.classList.remove('bg-blue-600', 'border-blue-200', 'bg-amber-500', 'border-amber-200', 'bg-red-600', 'border-red-200', 'animate-pulse');

        if (seconds <= 60) {
            timer.classList.add('bg-red-600', 'border-red-200', 'animate-pulse');
            if (warning) warning.textContent = 'Kurang dari 1 menit. Siapkan submit otomatis.';
        } else if (seconds <= 300) {
            timer.classList.add('bg-red-600', 'border-red-200');
            if (warning) warning.textContent = 'Kurang dari 5 menit. Segera selesaikan ujian.';
        } else if (seconds <= 600) {
            timer.classList.add('bg-amber-500', 'border-amber-200');
            if (warning) warning.textContent = 'Kurang dari 10 menit.';
        } else {
            timer.classList.add('bg-blue-600', 'border-blue-200');
            if (warning) warning.textContent = 'Timer sinkron dari server. Ujian otomatis submit saat waktu habis.';
        }
    }

    formatSeconds(seconds) {
        const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
        const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
        const s = Math.floor(seconds % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    async submitBecauseTimeExpired() {
        if (this.autoSubmitSent) return;
        this.autoSubmitSent = true;
        this.submitting = true;
        await this.flushPendingAnswers();

        try {
            const response = await fetch(this.config.submitUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({ auto_submit: true, submit_reason: 'Auto submit karena waktu habis' }),
            });
            const payload = await response.json().catch(() => null);
            window.location.href = payload?.redirect_url || this.config.afterSubmitUrl;
        } catch (error) {
            console.warn('Auto submit gagal, mengalihkan peserta.', error);
            window.location.href = this.config.afterSubmitUrl;
        }
    }

    initializeAnsweredState() {
        document.querySelectorAll('[data-answer-button][aria-pressed="true"]').forEach((button) => {
            button.closest('article')?.setAttribute('data-answered', 'true');
        });
    }

    answeredCount() {
        return document.querySelectorAll('article[data-answered="true"]').length;
    }

    headers() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}

window.StudentExamSecurity = StudentExamSecurity;

window.addEventListener('DOMContentLoaded', () => {
    if (window.studentExamSecurityConfig) {
        new StudentExamSecurity(window.studentExamSecurityConfig).init();
    }
});
