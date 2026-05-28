/**
 * Audio management for timer alarms
 */

window.TournamentAudio = (() => {
    let audioElement = null;
    let alarmTimeouts = [];

    return {
        triggerAudio: function(timerAudioUrl) {
            if (!timerAudioUrl) return;

            if (!audioElement) {
                audioElement = new Audio(timerAudioUrl);
                audioElement.preload = 'auto';
                audioElement.volume = 1;
            }

            audioElement.currentTime = 0;
            audioElement.play().catch(() => {});
        },

        triggerEndAlarm: function(timerAudioUrl, timerCard) {
            this.triggerAudio(timerAudioUrl);

            alarmTimeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
            alarmTimeouts = [];

            [1200, 2400].forEach((delay) => {
                const timeoutId = window.setTimeout(() => {
                    this.triggerAudio(timerAudioUrl);
                }, delay);
                alarmTimeouts.push(timeoutId);
            });

            if (navigator.vibrate) {
                navigator.vibrate([300, 150, 300]);
            }

            if (timerCard) {
                timerCard.classList.add('timer-card-alert');
                window.setTimeout(() => {
                    timerCard.classList.remove('timer-card-alert');
                }, 4500);
            }

            const alertBox = timerCard?.querySelector('[data-timer-alert]');
            if (alertBox) {
                alertBox.hidden = false;
                window.setTimeout(() => {
                    alertBox.hidden = true;
                }, 4500);
            }
        },

        clearTimeouts: function() {
            alarmTimeouts.forEach((timeoutId) => window.clearTimeout(timeoutId));
            alarmTimeouts = [];
        }
    };
})();
