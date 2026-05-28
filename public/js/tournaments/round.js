/**
 * Round generation and management
 */

window.TournamentRound = (() => {
    return {
        createManager: function(form, tournamentShowRoute, csrfToken, matchesManager) {
            const handleRoundSubmit = async (event) => {
                event.preventDefault();

                if (!form) return;

                if (form.dataset.submitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';

                const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                }
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: new FormData(form),
                    });

                    if (!response.ok) {
                        alert('Impossible de generer le tour.');
                        return;
                    }

                    const data = await response.json();
                    if (data.round?.id) {
                        window.location.href = `${tournamentShowRoute}?round=${data.round.id}`;
                        return;
                    }

                    matchesManager.render(data.round);
                } finally {
                    delete form.dataset.submitting;
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            };

            form?.addEventListener('submit', handleRoundSubmit);

            return {
                cleanup: () => {
                    form?.removeEventListener('submit', handleRoundSubmit);
                }
            };
        }
    };
})();
