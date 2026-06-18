/**
 * Tournament management entry point
 * Initializes all tournament components
 */

(() => {
    const form = document.getElementById('generateRoundForm');
    const container = document.getElementById('roundProgram');
    const timerCard = document.querySelector('[data-timer-card]');

    if (!container || !timerCard) return;

    // Get data from DOM
    const initial = JSON.parse(container?.dataset.initial || 'null');
    const timerRoundId = timerCard?.dataset.roundId || null;
    const timerDuration = Number(timerCard?.dataset.roundDuration || 0);
    const timerAudioUrl = timerCard?.dataset.audioUrl || '';
    const timerAudioLabel = timerCard?.dataset.audioLabel || 'Aucun son sélectionné';

    const tournamentShowRoute = timerCard?.dataset.tournamentShowRoute || '';
    const matchScoreRoute = timerCard?.dataset.matchScoreRoute || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!window.TournamentMatches || !window.TournamentTimer || !window.TournamentRound || !window.TournamentUtils || !window.TournamentAudio) {
        return;
    }

    // Create managers
    const matchesManager = container && window.TournamentMatches.createManager(container, matchScoreRoute, csrfToken);
    const timerManager = timerCard && window.TournamentTimer.createManager(timerCard, timerDuration, timerRoundId);
    const roundManager = form && window.TournamentRound.createManager(form, tournamentShowRoute, csrfToken, matchesManager);

    // Set initial audio data
    if (timerManager && timerAudioUrl) {
        timerManager.setAudioUrl(timerAudioUrl);
        timerManager.setAudioLabel(timerAudioLabel);
        timerManager.render();
    }

    // Render initial round data
    if (matchesManager && initial) {
        matchesManager.render(initial);
    }
})();
