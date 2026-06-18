/**
 * Round generation and management
 */

window.TournamentRound = (() => {
    return {
        createManager: function(form, tournamentShowRoute, csrfToken, matchesManager) {
            if (!form) {
                return {
                    cleanup: () => {},
                };
            }

            // Avoid registering multiple listeners on the same form.
            if (form.dataset.roundManagerBound === '1') {
                return {
                    cleanup: () => {},
                };
            }

            form.dataset.roundManagerBound = '1';

            const handleRoundSubmit = async (event) => {
                event.preventDefault();

                if (form.dataset.submitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';

                const submitButton = form.querySelector('button[type="submit"]');
                const originalLabel = submitButton?.textContent;

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Generation...';
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
                        let message = 'Impossible de générer le tour.';

                        try {
                            const errorData = await response.json();
                            const firstError = errorData.errors
                                ? Object.values(errorData.errors).flat()[0]
                                : null;

                            message = firstError || errorData.message || message;
                        } catch (error) {
                            // Keep the generic message when the server did not return JSON.
                        }

                        alert(message);
                        return;
                    }

                    const data = await response.json();
                    if (data.round?.id) {
                        window.location.href = `${tournamentShowRoute}?round=${data.round.id}`;
                        return;
                    }

                    matchesManager.render(data.round);
                } finally {
                    form.dataset.submitting = '0';

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalLabel || 'Generer le prochain tour';
                    }
                }
            };

            form.addEventListener('submit', handleRoundSubmit);

            return {
                cleanup: () => {
                    form.removeEventListener('submit', handleRoundSubmit);
                    delete form.dataset.roundManagerBound;
                    delete form.dataset.submitting;
                }
            };
        }
    };
})();
