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
        this.beforeUnloadKey = `cbt_exam_unload_${config.resultId}`;
        this.heartbeatTimer = null;
        this.autosaveTimer = null;
        this.idleTimer = null;
        this.devToolsTimer = null;
    }

    init() {
        if (!this.config.active) {
            return;
        }

        this.bindAnswerButtons();
        document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => { this.submitting = true; }));
        this.bindSecurityEvents();
        this.detectReloadReturn();
        this.startTimers();
        this.sendHeartbeat();
    }

    bindAnswerButtons() {
        document.querySelectorAll('[data-answer-button]').forEach((button) => {
            button.addEventListener('click', () => {
                const questionId = Number(button.dataset.questionId);
                const answer = button.dataset.answer;
                this.currentQuestion = questionId;
                this.selectAnswer(questionId, answer, button);
            });
        });
    }

    bindSecurityEvents() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.reportViolation('tab_switch', 'Siswa meninggalkan tab ujian', {
                    visibility_state: document.visibilityState,
                    fullscreen: Boolean(document.fullscreenElement),
                });
            }
        });

        window.addEventListener('blur', () => {
            this.reportViolation('window_blur', 'Browser kehilangan fokus, kemungkinan siswa pindah aplikasi', {
                visibility_state: document.visibilityState,
                fullscreen: Boolean(document.fullscreenElement),
            });
        });

        window.addEventListener('focus', () => {
            this.markActivity();
            this.sendHeartbeat();
        });

        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement && !this.submitting) {
                this.reportViolation('exit_fullscreen', 'Siswa keluar dari mode fullscreen', {
                    visibility_state: document.visibilityState,
                    fullscreen: false,
                });
            }
        });

        document.addEventListener('keydown', (event) => this.handleKeydown(event), true);

        document.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.reportViolation('right_click', 'Siswa mencoba klik kanan', { x: event.clientX, y: event.clientY });
        }, true);

        ['copy', 'paste', 'cut'].forEach((eventName) => {
            document.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.reportViolation('clipboard', 'Siswa mencoba copy/paste/cut', { action: eventName });
            }, true);
        });

        ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach((eventName) => {
            document.addEventListener(eventName, () => this.markActivity(), { passive: true });
        });

        window.addEventListener('beforeunload', (event) => {
            if (this.submitting) {
                return undefined;
            }
            sessionStorage.setItem(this.beforeUnloadKey, new Date().toISOString());
            event.preventDefault();
            event.returnValue = '';
            return '';
        });
    }

    handleKeydown(event) {
        this.markActivity();

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

    updateSelectedState(questionId, selectedButton) {
        document.querySelectorAll(`[data-answer-button][data-question-id="${questionId}"]`).forEach((button) => {
            button.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
            button.setAttribute('aria-pressed', 'false');
        });
        selectedButton.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-900', 'ring-2', 'ring-blue-200');
        selectedButton.setAttribute('aria-pressed', 'true');
    }

    async flushAnswer(questionId, answer) {
        try {
            const response = await fetch(this.config.answerUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({ question_id: questionId, jawaban: answer }),
            });
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

    async reportViolation(type, message, meta = {}) {
        const now = Date.now();
        const throttleKey = `${type}:${meta.key || meta.action || ''}`;
        if (now - (this.lastViolationAt.get(throttleKey) || 0) < 2000) {
            return;
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
                return;
            }

            const payload = await response.json();
            if (payload.action === 'auto_submit' || payload.action === 'lock') {
                this.handleAutoSubmit(payload.message || 'Ujian otomatis dikumpulkan karena melebihi batas pelanggaran.');
                return;
            }

            this.showWarning(payload.violation_count, payload.max_violations, message, payload.action);
        } catch (error) {
            console.warn('Gagal mengirim log pelanggaran anti-cheat.', error);
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

    modal(title, html, confirmText, onClose = null) {
        if (window.Swal) {
            window.Swal.fire({
                title,
                html,
                icon: title.includes('Otomatis') ? 'error' : 'warning',
                confirmButtonText: confirmText,
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
            }).then(() => onClose && onClose());
            return;
        }

        window.alert(`${title}\n${html.replace(/<[^>]*>/g, '')}`);
        if (onClose) onClose();
    }

    startTimers() {
        this.heartbeatTimer = window.setInterval(() => this.sendHeartbeat(), 10000);
        this.autosaveTimer = window.setInterval(() => this.flushPendingAnswers(), 10000);
        this.idleTimer = window.setInterval(() => this.checkIdle(), 15000);
        this.devToolsTimer = window.setInterval(() => this.detectDevTools(), 3000);
    }

    async sendHeartbeat() {
        try {
            await fetch(this.config.heartbeatUrl, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    current_question: this.currentQuestion,
                    remaining_time: this.remainingTime(),
                    fullscreen_status: Boolean(document.fullscreenElement),
                    visibility_state: document.visibilityState,
                }),
            });
        } catch (error) {
            this.reportViolation('connection_lost', 'Koneksi peserta terputus saat ujian berlangsung', {
                error: error.message,
                visibility_state: document.visibilityState,
            });
        }
    }

    checkIdle() {
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
        const end = Number(this.config.endsAtMs || 0);
        if (!end) {
            return null;
        }
        return Math.max(0, Math.floor((end - Date.now()) / 1000));
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
