<div style="display:flex; align-items:start; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem;">
    <div>
        <h3 style="margin-bottom:.35rem;">Tours</h3>
        <p class="muted" style="margin:0;">Anciens tours et tour courant</p>
    </div>

    @if ($canReturnToCurrentRound)
        <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Revenir au tour en cours</a>
    @endif
</div>

<div class="rounds-scroll">
    <ol class="mini-ranking-list">
        @forelse ($roundMenu as $roundItem)
            <li class="mini-ranking-item {{ $roundItem['is_selected'] ? 'round-item-selected' : '' }} {{ $roundItem['is_current'] ? 'round-item-current' : '' }}" style="grid-template-columns: 26px minmax(0, 1fr) auto;">
                <span class="mini-ranking-pos">{{ $roundItem['number'] }}</span>
                <a class="mini-ranking-name" href="{{ route('tournaments.show', ['tournament' => $tournament, 'round' => $roundItem['id']]) }}" style="text-decoration:none; color:inherit;">
                    Tour {{ $roundItem['number'] }}
                    @if ($roundItem['is_current'])
                        <span class="badge" style="margin-left:.35rem;">en cours</span>
                    @endif
                </a>
                <span class="round-status round-status-{{ $roundItem['status_key'] }}">{{ $roundItem['status_label'] }}</span>
            </li>
        @empty
            <li class="mini-ranking-item">
                <span class="mini-ranking-pos">-</span>
                <span class="mini-ranking-name">Aucun tour</span>
                <span class="mini-ranking-points">-</span>
            </li>
        @endforelse
    </ol>
</div>
