/**
 * Timer logic for tournament rounds
 */

window.TournamentTimer = (() => {
    let timerHandle = null;

    return {
        createManager: function(timerCard, timerDuration, timerRoundId) {
            const timerDisplay = timerCard?.querySelector('[data-timer-display]');
            const timerState = timerCard?.querySelector('[data-timer-state]');
            const timerStartButton = timerCard?.querySelector('[data-timer-start]');
            const timerPauseButton = timerCard?.querySelector('[data-timer-pause]');
            const timerResetButton = timerCard?.querySelector('[data-timer-reset]');
            const timerPlayButton = timerCard?.querySelector('[data-timer-play]');
            const timerAudioName = timerCard?.querySelector('[data-timer-audio-name]');

            let timerAudioUrl = timerCard?.dataset.audioUrl || '';
            let timerAudioLabel = timerCard?.dataset.audioLabel || 'Aucun son selectionne';
            const timerStorageKey = timerRoundId ? `tournabad-timer:${timerRoundId}` : null;

            const defaultState = () => ({
                status: timerRoundId ? 'idle' : 'empty',
                durationSeconds: timerDuration,
                remainingSeconds: timerDuration,
                startedAt: null,
                finishedAt: null,
                notified: false,
            });

            const getState = () => TournamentUtils.readTimerState(timerStorageKey) || defaultState();

            const getRemainingSeconds = (state) => {
                if (!state || !timerRoundId) return 0;
                if (state.status === 'paused' || state.status === 'completed' || state.status === 'idle') {
                    return Math.max(0, Math.floor(state.remainingSeconds ?? state.durationSeconds ?? 0));
                }
                if (state.status !== 'running' || !state.startedAt) {
                    return Math.max(0, Math.floor(state.remainingSeconds ?? state.durationSeconds ?? 0));
                }
                const startedAt = new Date(state.startedAt).getTime();
                const elapsedSeconds = Math.floor((Date.now() - startedAt) / 1000);
                return Math.max(0, Math.floor((state.durationSeconds ?? 0) - elapsedSeconds));
            };

            const setButtons = (state) => {
                const hasRound = Boolean(timerRoundId);
                const running = state.status === 'running';
                const paused = state.status === 'paused';
                const completed = state.status === 'completed';

                if (timerStartButton) {
                    timerStartButton.disabled = !hasRound || running;
                    timerStartButton.setAttribute('aria-label', paused ? 'Reprendre le chrono' : 'Demarrer le chrono');
                }
                if (timerPauseButton) {
                    timerPauseButton.disabled = !running;
                    timerPauseButton.setAttribute('aria-label', 'Mettre le chrono en pause');
                }
                if (timerResetButton) {
                    timerResetButton.disabled = !hasRound;
                    timerResetButton.setAttribute('aria-label', completed ? 'Recommencer le chrono' : 'Stop et recommencer le chrono');
                }
                if (timerPlayButton) {
                    timerPlayButton.disabled = !timerAudioUrl;
                    timerPlayButton.setAttribute('aria-label', 'Jouer le son');
                }
                if (timerState) {
                    timerState.textContent = !hasRound ? 'Aucun tour' : completed ? 'Termine' : running ? 'En cours' : paused ? 'En pause' : 'Pret';
                }
            };

            const render = () => {
                if (!timerCard || !timerDisplay) return;
                const state = getState();
                if (!timerRoundId) {
                    timerDisplay.textContent = '--:--';
                    if (timerAudioName) timerAudioName.textContent = timerAudioLabel;
                    setButtons(state);
                    return;
                }
                timerDisplay.textContent = TournamentUtils.formatTime(getRemainingSeconds(state));
                if (timerAudioName) timerAudioName.textContent = timerAudioLabel;
                setButtons(state);
            };

            const stopInterval = () => {
                if (timerHandle) {
                    window.clearInterval(timerHandle);
                    timerHandle = null;
                }
            };

            const finishTimer = () => {
                const state = getState();
                if (state.status === 'completed') return;
                stopInterval();
                const completedState = {
                    ...state,
                    status: 'completed',
                    remainingSeconds: 0,
                    finishedAt: new Date().toISOString(),
                    notified: true,
                };
                TournamentUtils.writeTimerState(timerStorageKey, completedState);
                render();
                TournamentAudio.triggerEndAlarm(timerAudioUrl, timerCard);
            };

            const tick = () => {
                const state = getState();
                if (state.status !== 'running') {
                    stopInterval();
                    render();
                    return;
                }
                const remainingSeconds = getRemainingSeconds(state);
                if (remainingSeconds <= 0) {
                    finishTimer();
                    return;
                }
                TournamentUtils.writeTimerState(timerStorageKey, {
                    ...state,
                    remainingSeconds,
                });
                render();
            };

            const start = () => {
                if (!timerRoundId) return;
                const state = getState();
                const durationSeconds = timerDuration;
                const remainingSeconds = state.status === 'paused'
                    ? Math.max(0, Math.floor(state.remainingSeconds ?? durationSeconds))
                    : durationSeconds;
                const elapsedSeconds = durationSeconds - remainingSeconds;
                const nextState = {
                    ...state,
                    status: 'running',
                    durationSeconds,
                    startedAt: new Date(Date.now() - (elapsedSeconds * 1000)).toISOString(),
                    remainingSeconds,
                    notified: false,
                };
                TournamentUtils.writeTimerState(timerStorageKey, nextState);
                stopInterval();
                tick();
                timerHandle = window.setInterval(tick, 1000);
            };

            const pause = () => {
                const state = getState();
                if (state.status !== 'running') return;
                stopInterval();
                const remainingSeconds = getRemainingSeconds(state);
                TournamentUtils.writeTimerState(timerStorageKey, {
                    ...state,
                    status: 'paused',
                    remainingSeconds,
                });
                render();
            };

            const reset = () => {
                if (!timerRoundId) return;
                stopInterval();
                TournamentUtils.clearTimerState(timerStorageKey);
                render();
            };

            // Initialize
            const initialState = getState();
            if (initialState.status === 'running') {
                timerHandle = window.setInterval(tick, 1000);
                tick();
            }
            render();

            timerStartButton?.addEventListener('click', start);
            timerPauseButton?.addEventListener('click', pause);
            timerResetButton?.addEventListener('click', reset);
            timerPlayButton?.addEventListener('click', () => TournamentAudio.triggerAudio(timerAudioUrl));

            return {
                setAudioUrl: (url) => { timerAudioUrl = url; },
                setAudioLabel: (label) => { timerAudioLabel = label; },
                render: render,
                cleanup: () => {
                    stopInterval();
                    TournamentAudio.clearTimeouts();
                }
            };
        }
    };
})();
