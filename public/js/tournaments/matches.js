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
                        const matchNumber = String(match.match_number).padStart(2, '0');
                        const compactDisplay = Boolean(match.team_a_display && match.team_b_display);

                        const teamARows = match.team_a ?? [];
                        const teamBRows = match.team_b ?? [];

                        const compactTeamA = compactDisplay ? match.team_a_display : null;
                        const compactTeamB = compactDisplay ? match.team_b_display : null;

                        const teamAFirst = TournamentUtils.escapeHtml(compactTeamA?.label ?? teamARows[0] ?? '-');
                        const teamBFirst = TournamentUtils.escapeHtml(compactTeamB?.label ?? teamBRows[0] ?? '-');
                        const teamASecond = TournamentUtils.escapeHtml(teamARows[1] ?? '');
                        const teamBSecond = TournamentUtils.escapeHtml(teamBRows[1] ?? '');
                        const teamATitle = compactTeamA?.title ? ` title="${TournamentUtils.escapeHtml(compactTeamA.title)}"` : '';
                        const teamBTitle = compactTeamB?.title ? ` title="${TournamentUtils.escapeHtml(compactTeamB.title)}"` : '';
                        const hasSecondRow = !compactDisplay && Boolean(teamARows[1] || teamBRows[1]);

                        return `
                            <article class="match-table-item">
                                <h4 class="match-table-title">Terrain ${match.court_number} n°${matchNumber}</h4>
                                <form class="scoreForm" data-match-id="${match.id}">
                                    <table class="match-table-grid" role="presentation">
                                        <tbody>
                                            <tr>
                                                <td class="match-table-a"${teamATitle}>${teamAFirst}</td>
                                                <td class="match-table-vs" rowspan="${hasSecondRow ? 2 : 1}">vs</td>
                                                <td class="match-table-b"${teamBTitle}>${teamBFirst}</td>
                                                <td class="match-table-score" rowspan="${hasSecondRow ? 2 : 1}">
                                                    <span class="match-table-score-wrap">
                                                        <input class="score-input" type="number" min="0" name="team_one_score" placeholder="A" value="${match.score ? match.score.team_one_score : ''}">
                                                        <input class="score-input" type="number" min="0" name="team_two_score" placeholder="B" value="${match.score ? match.score.team_two_score : ''}">
                                                        <button class="btn btn-primary" type="submit">OK</button>
                                                    </span>
                                                </td>
                                            </tr>
                                            ${hasSecondRow ? `
                                                <tr>
                                                    <td class="match-table-a">${teamASecond}</td>
                                                    <td class="match-table-b">${teamBSecond}</td>
                                                </tr>
                                            ` : ''}
                                        </tbody>
                                    </table>
                                </form>
                            </article>
                        `;
                    }).join('')
                    : '<p class="muted">Aucun match genere.</p>';

                const waitingHtml = round.waiting.length
                    ? round.waiting.map((player) => {
                        const title = player.title ? ` title="${TournamentUtils.escapeHtml(player.title)}"` : '';
                        return `<span class="tag"${title}>${TournamentUtils.escapeHtml(player.name)}</span>`;
                    }).join('')
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
