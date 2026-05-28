/**
 * Utility functions for tournaments
 */

window.TournamentUtils = {
    escapeHtml: function(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    },

    formatTime: function(totalSeconds) {
        const safeSeconds = Math.max(0, Math.floor(totalSeconds));
        const minutes = String(Math.floor(safeSeconds / 60)).padStart(2, '0');
        const seconds = String(safeSeconds % 60).padStart(2, '0');
        return `${minutes}:${seconds}`;
    },

    readTimerState: function(timerStorageKey) {
        if (!timerStorageKey) return null;
        try {
            const raw = localStorage.getItem(timerStorageKey);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    },

    writeTimerState: function(timerStorageKey, state) {
        if (!timerStorageKey) return;
        localStorage.setItem(timerStorageKey, JSON.stringify(state));
    },

    clearTimerState: function(timerStorageKey) {
        if (!timerStorageKey) return;
        localStorage.removeItem(timerStorageKey);
    }
};
