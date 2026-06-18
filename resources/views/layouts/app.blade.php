<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'TournaBad' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #eef2f7;
            --bg-2: #e6edf6;
            --paper: rgba(255, 255, 255, 0.82);
            --paper-strong: #ffffff;
            --ink: #142033;
            --muted: #5f6b85;
            --brand: #0f766e;
            --brand-2: #2563eb;
            --brand-soft: rgba(15, 118, 110, 0.12);
            --accent: #f59e0b;
            --success: #16a34a;
            --danger: #d92d20;
            --line: rgba(20, 32, 51, 0.10);
            --shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-soft: 0 10px 20px rgba(15, 23, 42, 0.05);
        }

        html[data-theme="dark"] {
            color-scheme: dark;
            --bg: #08111d;
            --bg-2: #0d1726;
            --paper: rgba(13, 22, 34, 0.88);
            --paper-strong: #101b2b;
            --ink: #e6eef8;
            --muted: #9ab0c7;
            --brand: #2dd4bf;
            --brand-2: #60a5fa;
            --brand-soft: rgba(45, 212, 191, 0.12);
            --accent: #fbbf24;
            --success: #4ade80;
            --danger: #f87171;
            --line: rgba(148, 163, 184, 0.18);
            --shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            --shadow-soft: 0 10px 20px rgba(0, 0, 0, 0.18);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 10% 10%, rgba(15, 118, 110, 0.16) 0%, transparent 30%),
                radial-gradient(circle at 90% 0%, rgba(37, 99, 235, 0.12) 0%, transparent 28%),
                linear-gradient(180deg, var(--bg-2) 0%, var(--bg) 100%);
            min-height: 100vh;
        }

        .container {
            width: min(1050px, 94%);
            margin: 0 auto;
        }

        .topbar {
            border-bottom: 1px solid var(--line);
            background: color-mix(in srgb, var(--paper-strong) 82%, transparent 18%);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .topbar-inner {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            font-weight: 900;
            letter-spacing: 0.04em;
            color: var(--ink);
            text-decoration: none;
            font-size: 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }

        .brand::before {
            content: "";
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent), var(--brand));
            box-shadow: 0 0 0 4px var(--brand-soft);
        }

        .nav {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .mobile-menu-toggle.btn {
            display: none;
            flex: 0 0 auto;
        }

        .hamburger-lines {
            display: grid;
            gap: 0.24rem;
            width: 1.15rem;
        }

        .hamburger-lines span {
            display: block;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
        }

        .btn, .btn-link {
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            background: transparent;
            color: var(--ink);
        }

        .btn:hover, .btn-link:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            box-shadow: var(--shadow-soft);
        }

        .btn-outline {
            border-color: var(--line);
            background: color-mix(in srgb, var(--paper-strong) 88%, transparent 12%);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .main {
            padding: 2rem 0 3rem;
        }

        .site-footer {
            margin-top: 1.5rem;
            padding: 1.2rem 0 2rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .site-footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            border-top: 1px solid var(--line);
            padding-top: 1rem;
        }

        .site-footer-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .site-footer a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 600;
        }

        .site-footer a:hover {
            color: var(--ink);
        }

        .card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 1.25rem;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(8px);
        }

        .mode-card {
            display: block;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .mode-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .mode-card-active {
            box-shadow: var(--shadow);
        }

        .list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--paper-strong) 88%, transparent 12%);
            color: var(--ink);
            font-size: 0.9rem;
        }

        .tournament-actions {
            position: relative;
            margin-left: auto;
            z-index: 60;
        }

        .tournament-header-card {
            position: relative;
            z-index: 70;
        }

        .tournament-actions-desktop {
            display: flex;
            gap: 0.7rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .tournament-actions-mobile {
            display: none;
            position: relative;
        }

        .tournament-actions-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.55rem);
            right: 0;
            width: min(260px, 88vw);
            padding: 0.75rem;
            gap: 0.6rem;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: color-mix(in srgb, var(--paper-strong) 94%, transparent 6%);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
            z-index: 80;
        }

        .tournament-actions-menu.is-open {
            display: grid;
        }

        .tournament-actions-menu .btn {
            width: 100%;
            min-height: 2.8rem;
        }

        .match-card {
            margin-bottom: 0.8rem;
            background: var(--paper-strong);
        }

        .match-badge {
            background: color-mix(in srgb, var(--paper-strong) 82%, var(--brand) 18%);
            color: var(--ink);
            border: 1px solid var(--line);
            cursor: default;
        }

        .match-team {
            background: color-mix(in srgb, var(--paper-strong) 90%, transparent 10%);
        }

        .match-team ul {
            margin: 0.5rem 0 0 1.1rem;
            padding: 0;
            color: var(--ink);
        }

        .match-team li {
            color: var(--ink);
        }

        .match-status {
            color: var(--muted);
        }

        .score-input {
            max-width: 170px;
        }

        .match-flat-list {
            margin-top: 0.75rem;
            overflow-x: auto;
            padding-bottom: 0.2rem;
        }

        .match-table-item {
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--line);
        }

        .match-table-item:last-child {
            border-bottom: 0;
        }

        .match-table-title {
            margin: 0 0 0.35rem;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .match-table-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            min-width: 640px;
        }

        .match-table-grid td {
            padding: 0.08rem 0.2rem 0.08rem 0;
            color: var(--ink);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .match-table-a,
        .match-table-b {
            width: 39%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .match-table-a {
            text-align: right;
            padding-right: 0.45rem;
        }

        .match-table-b {
            text-align: left;
            padding-left: 0.45rem;
        }

        .match-table-vs {
            width: 6%;
            text-align: center;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .match-table-score {
            width: 16%;
            text-align: right;
            padding-right: 0;
        }

        .match-table-score-wrap {
            display: inline-flex;
            gap: 0.4rem;
            align-items: center;
        }

        .match-table-score-wrap .score-input {
            max-width: 64px;
            margin-top: 0;
            padding: 0.4rem 0.45rem;
        }

        .score-input::-webkit-outer-spin-button,
        .score-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .score-input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .match-table-score-wrap .btn {
            padding: 0.4rem 0.62rem;
            border-radius: 9px;
        }

        .match-flat-item {
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--line);
        }

        .match-flat-item:last-child {
            border-bottom: 0;
        }

        .match-flat-title {
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .match-flat-type {
            font-size: 0.82rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .match-flat-line {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr) 34px minmax(0, 1fr) auto;
            gap: 0.55rem;
            align-items: center;
            min-height: 28px;
        }

        .match-flat-line-sub {
            margin-top: 0.08rem;
        }

        .match-flat-player {
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.94rem;
        }

        .match-flat-player-right {
            text-align: left;
        }

        .match-flat-vs {
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            text-align: center;
        }

        .match-flat-score {
            display: flex;
            gap: 0.45rem;
            align-items: center;
            justify-content: flex-end;
        }

        .match-flat-score .score-input {
            max-width: 70px;
            margin-top: 0;
            padding: 0.45rem 0.5rem;
        }

        .match-flat-score .btn {
            padding: 0.45rem 0.7rem;
            border-radius: 10px;
        }

        .match-flat-empty {
            color: transparent;
            user-select: none;
        }

        @media (max-width: 880px) {
            .match-flat-list {
                overflow-x: visible;
            }

            .match-table-grid {
                min-width: 0;
                width: 100%;
                table-layout: fixed;
            }

            .match-table-a,
            .match-table-b {
                width: 31%;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
                line-height: 1.25;
            }

            .match-table-a {
                text-align: right;
                padding-right: 0.35rem;
            }

            .match-table-b {
                text-align: left;
                padding-left: 0.35rem;
            }

            .match-table-vs {
                width: 8%;
                font-size: 0.76rem;
            }

            .match-table-score {
                width: 30%;
            }

            .match-table-score-wrap {
                gap: 0.28rem;
                justify-content: flex-end;
            }

            .match-table-score-wrap .score-input {
                width: 2.15rem;
                max-width: 2.15rem;
                padding: 0.42rem 0.2rem;
                text-align: center;
            }

            .match-table-score-wrap .btn {
                padding: 0.45rem 0.58rem;
            }

            .match-flat-line {
                grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
                row-gap: 0.35rem;
            }

            .match-flat-title {
                grid-column: 1 / -1;
            }

            .match-flat-score {
                grid-column: 1 / -1;
                justify-content: flex-start;
            }

            .match-flat-empty {
                display: none;
            }

            .match-flat-type {
                grid-column: 1 / -1;
            }
        }

        .hero {
            background: linear-gradient(130deg, #0f766e 0%, #2563eb 100%);
            color: #fff;
            border-radius: 22px;
            padding: 2rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto -60px -80px auto;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            filter: blur(4px);
        }

        .grid {
            display: grid;
            gap: 1rem;
        }

        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

        h1, h2, h3 { margin-top: 0; }
        p { color: var(--muted); line-height: 1.5; }

        .alert {
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
            margin-bottom: 1rem;
            border: 1px solid color-mix(in srgb, var(--danger) 28%, var(--line) 72%);
            background: color-mix(in srgb, var(--danger) 12%, var(--paper-strong) 88%);
            color: var(--danger);
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 0.65rem 0.8rem;
            margin-top: 0.35rem;
            background: color-mix(in srgb, var(--paper-strong) 85%, transparent 15%);
            color: var(--ink);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        label {
            display: block;
            font-weight: 600;
            margin-top: 0.7rem;
            color: var(--ink);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            border-bottom: 1px solid var(--line);
            padding: 0.75rem;
            text-align: left;
        }

        .table th {
            color: var(--muted);
            font-size: 0.92rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .responsive-card-list {
            display: none;
        }

        .points-label-short {
            display: none;
        }

        .tournament-list-card {
            display: grid;
            gap: 0.85rem;
            padding: 0.95rem;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: color-mix(in srgb, var(--paper-strong) 92%, transparent 8%);
        }

        .tournament-list-card + .tournament-list-card {
            margin-top: 0.75rem;
        }

        .tournament-list-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .muted { color: var(--muted); }

        .theme-toggle {
            border-color: var(--line);
            background: color-mix(in srgb, var(--paper-strong) 88%, transparent 12%);
        }

        .btn-icon {
            padding: 0;
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 14px;
            box-shadow: var(--shadow-soft);
        }

        .theme-toggle-icon {
            font-size: 1.1rem;
            line-height: 1;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: var(--brand-soft);
            color: var(--brand);
            border: 1px solid var(--line);
        }

        .section-title {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .mini-ranking-floating {
            position: fixed;
            right: 2rem;
            top: 108px;
            width: 300px;
            z-index: 8;
        }

        .rounds-floating {
            position: fixed;
            left: 2rem;
            top: 392px;
            width: 300px;
            z-index: 8;
            height: calc(100vh - 408px);
        }

        .rounds-inline {
            display: none;
        }

        .timer-floating {
            position: fixed;
            left: 2rem;
            top: 108px;
            width: 300px;
            z-index: 9;
        }

        .timer-floating .card {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .timer-display {
            font-size: 2.8rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            color: var(--brand-2);
            text-align: center;
            padding: 0.15rem 0 0.35rem;
        }

        .timer-meta {
            margin-top: 0.45rem;
            color: var(--muted);
            font-size: 0.92rem;
            text-align: center;
        }

        .timer-actions {
            margin-top: 0.9rem;
            display: flex;
            gap: 0.65rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .timer-icon-btn {
            width: 2.8rem;
            height: 2.8rem;
            padding: 0;
            border-radius: 14px;
            flex: 0 0 auto;
            line-height: 1;
            box-shadow: var(--shadow-soft);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .timer-icon {
            width: 1.08rem;
            height: 1.08rem;
            fill: currentColor;
            display: block;
        }

        .timer-audio-name {
            font-size: 0.9rem;
            flex-basis: 100%;
            margin-top: 0.15rem;
            text-align: center;
        }

        .timer-end-alert {
            margin-top: 0.9rem;
            width: 100%;
            padding: 0.65rem 0.9rem;
            border-radius: 14px;
            text-align: center;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, var(--danger), color-mix(in srgb, var(--danger) 60%, #000 40%));
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger) 18%, transparent 82%);
            animation: timer-alert-pulse 0.9s ease-in-out infinite;
        }

        .timer-card-alert {
            border-color: color-mix(in srgb, var(--danger) 50%, var(--line) 50%);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--danger) 24%, transparent 76%), var(--shadow-soft);
        }

        @keyframes timer-alert-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .mini-ranking-floating .card {
            max-height: calc(100vh - 132px);
            overflow: auto;
        }

        .rounds-floating .card {
            height: 100%;
            max-height: none;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .rounds-scroll {
            min-height: 0;
            overflow: auto;
            padding-right: 0.2rem;
        }

        .mini-ranking-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 0.55rem;
        }

        .mini-ranking-item {
            display: grid;
            grid-template-columns: 26px minmax(0, 1fr) auto;
            gap: 0.5rem;
            align-items: center;
            padding: 0.42rem 0.5rem;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: color-mix(in srgb, var(--paper-strong) 90%, transparent 10%);
        }

        .mini-ranking-pos {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            font-weight: 800;
            color: var(--brand);
            background: var(--brand-soft);
        }

        .mini-ranking-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--ink);
            font-weight: 600;
        }

        .mini-ranking-points {
            color: var(--ink);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .round-item-current {
            border-color: color-mix(in srgb, var(--brand) 45%, var(--line) 55%);
            background: color-mix(in srgb, var(--brand-soft) 52%, var(--paper-strong) 48%);
        }

        .round-item-selected {
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--accent) 55%, transparent 45%);
        }

        .round-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: capitalize;
            font-variant-numeric: tabular-nums;
        }

        .round-status-generated {
            color: var(--brand-2);
            background: color-mix(in srgb, var(--brand-2) 12%, transparent 88%);
        }

        .round-status-no-score {
            color: var(--danger);
            background: color-mix(in srgb, var(--danger) 12%, transparent 88%);
        }

        .round-status-started {
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 12%, transparent 88%);
        }

        .round-status-completed {
            color: var(--success);
            background: color-mix(in srgb, var(--success) 12%, transparent 88%);
        }

        .final-podium {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .final-podium-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 1.2rem;
            text-align: center;
            background: color-mix(in srgb, var(--paper-strong) 94%, transparent 6%);
            box-shadow: var(--shadow-soft);
        }

        .final-podium-first {
            transform: translateY(-10px);
            background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 12%, var(--paper-strong) 88%), var(--paper-strong));
        }

        .final-rank {
            width: 2.6rem;
            height: 2.6rem;
            border-radius: 999px;
            margin: 0 auto 0.9rem;
            display: grid;
            place-items: center;
            font-weight: 900;
            color: var(--ink);
            background: var(--brand-soft);
        }

        .final-podium-first .final-rank {
            background: var(--accent);
            color: #fff;
        }

        .final-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.65rem;
        }

        .final-list-item {
            display: grid;
            grid-template-columns: 2.2rem minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.8rem 0.9rem;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: color-mix(in srgb, var(--paper-strong) 92%, transparent 8%);
        }

        .final-list-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 999px;
            font-weight: 800;
            color: var(--brand);
            background: var(--brand-soft);
        }

        .final-list-main {
            display: grid;
            gap: 0.15rem;
        }

        .final-list-points {
            font-weight: 800;
            white-space: nowrap;
            color: var(--ink);
        }

        .final-summary-grid {
            display: grid;
            gap: 0.55rem;
            margin-top: 0.85rem;
        }

        .final-summary-grid p {
            margin: 0;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--line);
        }

        .final-summary-grid p:last-child {
            border-bottom: 0;
        }

        @media (max-width: 768px) {
            .final-podium {
                grid-template-columns: 1fr;
            }

            .final-podium-first {
                transform: none;
            }

            .final-podium-place-1 {
                order: 1;
            }

            .final-podium-place-2 {
                order: 2;
            }

            .final-podium-place-3 {
                order: 3;
            }

            .final-list-item {
                grid-template-columns: 2.2rem minmax(0, 1fr);
            }

            .final-list-points {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            .hero {
                padding: 1.2rem;
            }

            .topbar-inner {
                height: auto;
                padding: 0.8rem 0;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
            }

            .brand {
                min-width: 0;
            }

            .mobile-menu-toggle.btn {
                display: inline-flex;
            }

            .nav {
                grid-column: 1 / -1;
                display: none;
                position: absolute;
                top: calc(100% + 0.55rem);
                right: 3%;
                width: min(260px, 94vw);
                padding: 0.75rem;
                align-items: stretch;
                gap: 0.6rem;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: color-mix(in srgb, var(--paper-strong) 94%, transparent 6%);
                box-shadow: var(--shadow);
                backdrop-filter: blur(14px);
                z-index: 30;
            }

            .nav.is-open {
                display: grid;
            }

            .nav .btn,
            .nav form,
            .nav form .btn {
                width: 100%;
            }

            .nav .btn {
                min-height: 2.8rem;
            }

            .responsive-table-desktop {
                display: none;
            }

            .responsive-card-list {
                display: block;
            }

            .points-table {
                table-layout: fixed;
                font-size: 0.84rem;
            }

            .points-table th,
            .points-table td {
                padding: 0.58rem 0.34rem;
            }

            .points-table th:first-child,
            .points-table td:first-child {
                width: 34%;
                padding-left: 0;
            }

            .points-table th:not(:first-child),
            .points-table td:not(:first-child) {
                width: 16.5%;
                text-align: center;
            }

            .points-table td:first-child {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .points-table .badge {
                display: none;
            }

            .points-label-full {
                display: none;
            }

            .points-label-short {
                display: inline;
            }
        }

        @media (max-width: 900px) {
            .tournament-header-main {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: start !important;
            }

            .tournament-actions-desktop,
            .tournament-summary-cards,
            .tournament-team-config {
                display: none;
            }

            .tournament-actions-mobile {
                display: block;
            }
        }

        @media (max-width: 1400px) {
            .timer-floating {
                position: static;
                width: auto;
                margin-bottom: 1rem;
            }

            .mini-ranking-floating {
                display: none;
            }

            .rounds-floating {
                display: none;
            }

            .rounds-inline {
                display: block;
            }
        }
    </style>
    <script>
        (() => {
            const stored = localStorage.getItem('tournabad-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored || (prefersDark ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="{{ route('home') }}">TournaBad</a>

        <button class="btn btn-outline btn-icon mobile-menu-toggle" type="button" id="mobileMenuToggle" aria-controls="siteNav" aria-expanded="false" aria-label="Ouvrir le menu">
            <span class="hamburger-lines" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>

        <nav class="nav" id="siteNav">
            @auth
                <a class="btn btn-outline" href="{{ route('home') }}">Accueil</a>
                <a class="btn btn-outline" href="{{ route('tournaments.index') }}">Tournois</a>
                <a class="btn btn-outline" href="{{ route('dashboard') }}">Compte</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Déconnexion</button>
                </form>
                <button class="btn theme-toggle btn-icon" type="button" id="themeToggle" aria-label="Basculer le thème" title="Basculer le thème">
                    <span class="theme-toggle-icon" data-theme-icon="moon" aria-hidden="true">🌙</span>
                    <span class="theme-toggle-icon" data-theme-icon="sun" aria-hidden="true" hidden>☀</span>
                    <span class="sr-only" data-theme-label>Mode sombre</span>
                </button>
            @else
                <a class="btn btn-outline" href="{{ route('login') }}">Connexion</a>
                <a class="btn btn-primary" href="{{ route('register') }}">Créer un compte</a>
                <button class="btn theme-toggle btn-icon" type="button" id="themeToggle" aria-label="Basculer le thème" title="Basculer le thème">
                    <span class="theme-toggle-icon" data-theme-icon="moon" aria-hidden="true">🌙</span>
                    <span class="theme-toggle-icon" data-theme-icon="sun" aria-hidden="true" hidden>☀</span>
                    <span class="sr-only" data-theme-label>Mode sombre</span>
                </button>
            @endauth
        </nav>
    </div>
</header>

<main class="main container">
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="container site-footer-inner">
        <div>
            <strong>TournaBad</strong>
            <span> - {{ date('Y') }} - Tous droits réservés.</span>
        </div>
        <div class="site-footer-links">
            <a href="{{ route('legal.notices') }}">Mentions légales</a>
            <span aria-hidden="true">|</span>
            <a href="{{ route('legal.privacy') }}">Confidentialité</a>
        </div>
    </div>
</footer>

@stack('scripts')

<script>
    (() => {
        const button = document.getElementById('mobileMenuToggle');
        const nav = document.getElementById('siteNav');
        if (!button || !nav) return;

        const closeMenu = () => {
            nav.classList.remove('is-open');
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Ouvrir le menu');
        };

        button.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            button.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
        });

        document.addEventListener('click', (event) => {
            if (!nav.classList.contains('is-open')) {
                return;
            }

            if (button.contains(event.target) || nav.contains(event.target)) {
                return;
            }

            closeMenu();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeMenu();
            }
        });
    })();

    (() => {
        const button = document.getElementById('themeToggle');
        if (!button) return;

        const label = button.querySelector('[data-theme-label]');
        const moonIcon = button.querySelector('[data-theme-icon="moon"]');
        const sunIcon = button.querySelector('[data-theme-icon="sun"]');

        const syncLabel = () => {
            const theme = document.documentElement.dataset.theme;
            const isDark = theme === 'dark';

            const nextLabel = isDark ? 'Mode clair' : 'Mode sombre';
            const nextAria = isDark ? 'Activer le mode clair' : 'Activer le mode sombre';

            if (label) label.textContent = nextLabel;
            button.setAttribute('aria-label', nextAria);
            button.setAttribute('title', nextLabel);

            if (moonIcon) moonIcon.hidden = isDark;
            if (sunIcon) sunIcon.hidden = !isDark;
        };

        syncLabel();

        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem('tournabad-theme', nextTheme);
            syncLabel();
        });
    })();
</script>
</body>
</html>
