/**
 * Match display and score management
 */

window.TournamentMatches = (() => {
    return {
        createManager: function(container, matchScoreRoute, csrfToken) {
            const renderMatches = (round) => {
                if (!round) {
                    container.innerHTML = '<p class="muted">Aucun tour genere pour le moment.</p>';
                    return;
                }

                const matchesHtml = round.matches.length
                    ? round.matches.map((match) => {
                        const teamAFirst = TournamentUtils.escapeHtml(match.team_a[0] ?? '-');
                        const teamASecond = TournamentUtils.escapeHtml(match.team_a[1] ?? '-');
                        const teamBFirst = TournamentUtils.escapeHtml(match.team_b[0] ?? '-');
                        const teamBSecond = TournamentUtils.escapeHtml(match.team_b[1] ?? '-');
                        const matchNumber = String(match.match_number).padStart(2, '0');

                        return `
                            <article class="match-table-item">
                                <h4 class="match-table-title">Terrain ${match.court_number} n°${matchNumber}</h4>
                                <form class="scoreForm" data-match-id="${match.id}">
                                    <table class="match-table-grid" role="presentation">
                                        <tbody>
                                            <tr>
                                                <td class="match-table-a">${teamAFirst}</td>
                                                <td class="match-table-vs" rowspan="2">vs</td>
                                                <td class="match-table-b">${teamBFirst}</td>
                                                <td class="match-table-score" rowspan="2">
                                                    <span class="match-table-score-wrap">
                                                        <input class="score-input" type="number" min="0" name="team_one_score" placeholder="A" value="${match.score ? match.score.team_one_score : ''}">
                                                        <input class="score-input" type="number" min="0" name="team_two_score" placeholder="B" value="${match.score ? match.score.team_two_score : ''}">
                                                        <button class="btn btn-primary" type="submit">OK</button>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="match-table-a">${teamASecond}</td>
                                                <td class="match-table-b">${teamBSecond}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </form>
                            </article>
                        `;
                    }).join('')
                    : '<p class="muted">Aucun match genere.</p>';

                const waitingHtml = round.waiting.length
                    ? round.waiting.map((player) => `<span class="tag">${TournamentUtils.escapeHtml(player.name)}</span>`).join('')
                    : '<p class="muted">Personne en attente.</p>';

                container.innerHTML = `
                    <div class="grid grid-2">
                        <article class="card">
                            <h3>Tour ${round.round_number}</h3>
                            <p class="match-status">Statut: ${TournamentUtils.escapeHtml(round.status)}${round.generated_at ? ' • Généré le ' + TournamentUtils.escapeHtml(round.generated_at) : ''}</p>
                        </article>
                        <article class="card">
                            <h3>En attente</h3>
                            <div class="list">${waitingHtml}</div>
                        </article>
                    </div>
                    <div style="margin-top:1rem;">
                        <h3>Matchs</h3>
                        <div class="match-flat-list">${matchesHtml}</div>
                    </div>
                `;
            };

            const handleScoreSubmit = async (event) => {
                const scoreForm = event.target.closest?.('.scoreForm');
                if (!scoreForm) return;
                event.preventDefault();

                const matchId = scoreForm.dataset.matchId;
                const response = await fetch(`${matchScoreRoute}/${matchId}/score`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(scoreForm),
                });

                if (!response.ok) {
                    alert('Impossible d\'enregistrer le score.');
                    return;
                }

                const data = await response.json();
                renderMatches(data.round);
                window.location.reload();
            };

            container.addEventListener('submit', handleScoreSubmit);

            return {
                render: renderMatches,
                cleanup: () => {
                    container.removeEventListener('submit', handleScoreSubmit);
                }
            };
        }
    };
})();
