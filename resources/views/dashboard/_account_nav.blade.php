<style>
    .account-page-wrap {
        width: min(1050px, 100%);
        margin: 0 auto;
    }

    .account-side-menu {
        position: fixed;
        top: 6.4rem;
        left: max(1rem, calc((100vw - 1050px) / 2 - 240px));
        width: 210px;
        display: grid;
        gap: .65rem;
        z-index: 20;
    }

    .account-nav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem .95rem;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        color: var(--text);
        text-decoration: none;
        background: color-mix(in srgb, var(--card) 94%, var(--accent) 6%);
        font-weight: 800;
    }

    .account-nav-link span {
        color: var(--muted);
        font-size: .85rem;
        font-weight: 700;
    }

    .account-nav-link.is-active {
        border-color: color-mix(in srgb, var(--accent) 60%, var(--line) 40%);
        background: color-mix(in srgb, var(--card) 82%, var(--accent) 18%);
    }

    .account-avatar {
        width: 118px;
        height: 118px;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: linear-gradient(135deg, var(--accent), #60a5fa);
        display: grid;
        place-items: center;
        overflow: hidden;
        color: #06101f;
        font-size: 2rem;
        font-weight: 900;
        cursor: pointer;
        position: relative;
    }

    .account-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .account-avatar::after {
        content: "Modifier";
        position: absolute;
        inset: auto 0 0;
        padding: .35rem;
        background: rgba(6, 12, 24, .72);
        color: white;
        font-size: .72rem;
        font-weight: 800;
        text-align: center;
        opacity: 0;
        transition: opacity .15s ease;
    }

    .account-avatar:hover::after,
    .account-avatar:focus-within::after {
        opacity: 1;
    }

    .account-file-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .tournament-status-pill {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: .25rem .55rem;
        border-radius: 999px;
        border: 1px solid var(--line);
        color: var(--muted);
        font-size: .82rem;
        font-weight: 800;
    }

    @media (max-width: 1480px) {
        .account-side-menu {
            position: sticky;
            top: 1rem;
            width: auto;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 720px) {
        .account-side-menu {
            grid-template-columns: 1fr;
        }
    }
</style>

<aside class="account-side-menu" aria-label="Menu compte">
    <a class="account-nav-link {{ ($activeAccountMenu ?? '') === 'account' ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
        Compte
        <span>Profil</span>
    </a>
    <a class="account-nav-link {{ ($activeAccountMenu ?? '') === 'tournaments' ? 'is-active' : '' }}" href="{{ route('dashboard.tournaments') }}">
        Tournois
        <span>{{ $stats['my_tournaments'] ?? 0 }}</span>
    </a>
</aside>
