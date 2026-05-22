<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO: title, description, OG, Twitter Card, canonical, robots, favicon --}}
    {!! seo(new \RalphJSmit\Laravel\SEO\Support\SEOData(
        title: 'FM Macedonia — Stream Macedonian Radio Live',
        description: 'Stream 33 Macedonian FM radio stations live in your browser. Pop, folk, rock, news and more.',
        image: 'images/og-image.png',
        url: url('/'),
        site_name: 'FM Macedonia',
        enableTitleSuffix: false,
    )) !!}

    {{-- PWA: manifest link, theme-color, apple meta, service-worker registration tag --}}
    @PwaHead

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green: #1ED760;
            --green-dim: #169c43;
            --black: #000000;
            --bg: #121212;
            --card: #181818;
            --hover: #282828;
            --border: #2a2a2a;
            --text: #FFFFFF;
            --muted: #A7A7A7;
            --dim: #535353;
            --player-h: 90px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }

        /* Base: percentage heights so overflow constraints propagate correctly */
        html, body {
            height: 100%;
            overflow: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: 'Figtree', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        /* dvh enhancement for browsers that support it (fixes iOS address-bar jump) */
        @supports (height: 100dvh) {
            html, body { height: 100dvh; }
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--dim); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        /* ── Layout shell ── */
        #app {
            display: grid;
            grid-template-rows: 1fr auto;
            grid-template-columns: 240px 1fr;
            /* height: 100% inherits from html/body; dvh applied via @supports above */
            height: 100%;
            gap: 8px;
            padding: 8px;
            padding-bottom: 0;
            /* Respect notch/dynamic island */
            padding-left: max(8px, var(--safe-left));
            padding-right: max(8px, var(--safe-right));
            padding-top: max(8px, var(--safe-top));
        }
        @supports (height: 100dvh) {
            #app { height: 100dvh; }
        }

        @media (max-width: 1023px) {
            #app { grid-template-columns: 1fr; }
            #sidebar { display: none; }
        }

        /* ── Sidebar ── */
        #sidebar {
            grid-row: 1;
            grid-column: 1;
            background: var(--black);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0; /* required for grid overflow behaviour */
        }

        /* ── Main ── */
        #main {
            grid-row: 1;
            grid-column: 2;
            background: var(--bg);
            border-radius: 10px;
            /* scroll must be on the grid child itself, not a nested wrapper */
            overflow-y: scroll;   /* 'scroll' not 'auto' — iOS requires this */
            overflow-x: hidden;
            position: relative;
            min-height: 0;        /* lets grid row compress below content height */
            /* Momentum scroll on iOS (still respected as enhancement) */
            -webkit-overflow-scrolling: touch;
            /* Tells the browser this element handles vertical panning */
            touch-action: pan-y;
            overscroll-behavior-y: contain;
        }

        @media (max-width: 1023px) {
            #main { grid-column: 1; }
        }

        /* ── Player bar ── */
        #player-bar {
            grid-row: 2;
            grid-column: 1 / -1;
            background: #181818;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 16px;
            /* Account for iPhone home indicator */
            padding-bottom: max(8px, var(--safe-bottom));
            min-height: var(--player-h);
            /* Extra left/right safe area for landscape notch */
            padding-left: max(16px, calc(var(--safe-left) + 8px));
            padding-right: max(16px, calc(var(--safe-right) + 8px));
        }

        /* ── Top gradient header ── */
        #main-header {
            position: sticky;
            top: 0;
            z-index: 20;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(to bottom, rgba(18,18,18,0.97) 70%, transparent);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        /* First child (hamburger+logo) stays left */
        #main-header > div:first-child { flex-shrink: 0; }
        /* Search trigger fills middle space */
        .search-trigger { flex: 1; min-width: 0; }
        /* Live badge stays right */
        .live-badge { flex-shrink: 0; }

        /* ── Quick-picks (featured grid) ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }
        @media (min-width: 640px) { .quick-grid { grid-template-columns: repeat(3, 1fr); } }

        .quick-card {
            display: flex;
            align-items: center;
            background: var(--hover);
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            transition: background 0.15s;
            position: relative;
            /* Prevent iOS tap highlight */
            -webkit-tap-highlight-color: transparent;
        }
        .quick-card:hover { background: #3e3e3e; }
        .quick-card .qc-art {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            overflow: hidden;
            background: linear-gradient(160deg, #1f1f1f 0%, #0d0d0d 100%);
        }
        .quick-card .qc-art img {
            width: 100%; height: 100%;
            object-fit: contain;
            padding: 8%;
        }
        @media (min-width: 480px) { .quick-card .qc-art { width: 64px; height: 64px; } }

        .quick-card .qc-title {
            font-size: 0.78rem;
            font-weight: 700;
            flex: 1;
            min-width: 0; /* CRITICAL: allows text-overflow to work in flex */
            padding: 0 8px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        @media (min-width: 480px) {
            .quick-card .qc-title { font-size: 0.8rem; padding: 0 12px; }
        }

        /* Play button: absolute-positioned so it NEVER consumes layout space */
        .quick-card .qc-play {
            position: absolute;
            right: 8px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--green);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(4px) scale(0.85);
            transition: opacity 0.2s, transform 0.2s;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(0,0,0,0.6);
            pointer-events: none; /* card itself handles click */
        }
        /* Desktop hover & active */
        @media (hover: hover) {
            .quick-card:hover .qc-play { opacity: 1; transform: translateY(0) scale(1); }
        }
        .quick-card.is-active .qc-play { opacity: 1; transform: translateY(0) scale(1); }
        .quick-card.is-active { background: #1a2e1a; }

        /* ── Station cards ── */
        .station-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        @media (min-width: 480px)  { .station-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; } }
        @media (min-width: 768px)  { .station-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; } }
        @media (min-width: 1280px) { .station-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
        @media (min-width: 1536px) { .station-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }

        .station-card {
            background: var(--card);
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            animation: fadeUp 0.4s ease both;
            -webkit-tap-highlight-color: transparent;
            display: flex;
            flex-direction: column;
            min-width: 0; /* allow grid cell to shrink, prevents overflow */
        }
        @media (min-width: 480px) { .station-card { padding: 16px; } }
        .station-card:hover { background: var(--hover); }
        @media (hover: hover) {
            .station-card:hover { transform: translateY(-2px); }
        }
        .station-card.is-active { background: #1f2e1f; box-shadow: 0 0 0 1px rgba(30,215,96,.25); }

        .card-art-wrap {
            position: relative;
            padding-bottom: 100%; /* enforce 1:1 aspect ratio */
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            background: #0e0e0e;
        }
        .card-art-inner {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(120% 90% at 30% 20%, rgba(255,255,255,.06), transparent 60%),
                linear-gradient(160deg, #1f1f1f 0%, #0d0d0d 100%);
        }
        .card-art-inner img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 12%;
        }
        .card-art-inner .placeholder {
            width: 100%;
            height: 100%;
        }

        /* ── Card text rows: fixed heights so every card aligns ── */
        .card-title {
            font-weight: 600;
            font-size: .875rem;
            line-height: 1.25rem;
            color: var(--text);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 2.5rem; /* always reserves 2 lines */
        }
        .card-meta {
            color: var(--muted);
            font-size: .75rem;
            line-height: 1rem;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 1rem;
        }
        .card-meta-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }
        .card-play-btn {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--green);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,.6);
            transition: opacity 0.2s, transform 0.2s;
            /* Touch: always visible at reduced opacity; desktop: hover-only */
            opacity: 0;
            transform: translateY(6px);
        }
        /* Desktop: reveal on hover */
        @media (hover: hover) {
            .station-card:hover .card-play-btn { opacity: 1; transform: translateY(0); }
        }
        /* Touch: always show at low opacity so users know the card is tappable */
        @media (hover: none) {
            .card-play-btn { opacity: 0.35; transform: translateY(0); }
            .station-card:active .card-play-btn { opacity: 1; }
        }
        .station-card.is-active .card-play-btn { opacity: 1; transform: translateY(0); }

        /* ── Equalizer ── */
        .eq {
            display: none;
            align-items: flex-end;
            gap: 2px;
            height: 20px;
        }
        .is-active .eq { display: flex; }
        .eq span {
            display: block;
            width: 3px;
            background: var(--green);
            border-radius: 2px;
        }
        .eq span:nth-child(1) { animation: bar1 .7s ease-in-out infinite; }
        .eq span:nth-child(2) { animation: bar2 .5s ease-in-out infinite; }
        .eq span:nth-child(3) { animation: bar3 .8s ease-in-out infinite; }
        .eq span:nth-child(4) { animation: bar1 .6s ease-in-out infinite .1s; }

        .card-eq-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.55);
            display: none;
            align-items: center;
            justify-content: center;
        }
        .is-active .card-eq-overlay { display: flex; }
        .card-eq-overlay .eq { height: 28px; }
        .card-eq-overlay .eq span { width: 4px; background: var(--green); }

        @keyframes bar1 { 0%,100%{height:4px} 50%{height:18px} }
        @keyframes bar2 { 0%,100%{height:14px} 50%{height:6px} }
        @keyframes bar3 { 0%,100%{height:8px} 50%{height:20px} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        @keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:.3} }
        @keyframes spin { to{transform:rotate(360deg)} }

        /* ── Player ── */
        #player-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
        #player-center { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
        #player-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        @media (max-width: 479px) {
            #player-left  { gap: 8px; }
            #player-right { display: none; }
        }
        @media (min-width: 480px) and (max-width: 767px) {
            /* Mid-size: show volume but not prev/next on center */
            #player-right { display: flex; }
        }

        #player-art-box {
            width: 48px; height: 48px; border-radius: 4px;
            overflow: hidden; flex-shrink: 0;
            background: linear-gradient(160deg, #1f1f1f 0%, #0d0d0d 100%);
        }
        #player-art-box img { width: 100%; height: 100%; object-fit: contain; padding: 8%; }
        @media (min-width: 480px) { #player-art-box { width: 56px; height: 56px; } }

        .ctrl-btn {
            background: none; border: none; cursor: pointer;
            color: var(--muted); transition: color 0.15s;
            display: flex; align-items: center; justify-content: center;
            padding: 4px;
            -webkit-tap-highlight-color: transparent;
        }
        .ctrl-btn:hover { color: var(--text); }
        .ctrl-btn:active { color: var(--text); }

        /* Hide skip buttons on very small screens */
        @media (max-width: 479px) {
            .skip-btn { display: none; }
        }

        #pp-btn {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--text); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: transform 0.15s;
            -webkit-tap-highlight-color: transparent;
        }
        #pp-btn:hover { transform: scale(1.07); }
        #pp-btn:active { transform: scale(0.92); }

        #vol-slider {
            -webkit-appearance: none; appearance: none;
            width: 70px; height: 4px; border-radius: 2px;
            background: var(--dim); outline: none; cursor: pointer;
        }
        #vol-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 14px; height: 14px;
            border-radius: 50%; background: var(--text);
            cursor: pointer;
        }

        /* ── Sidebar styles ── */
        .sb-nav-item {
            display: flex; align-items: center; gap: 14px;
            padding: 8px 12px; border-radius: 6px;
            font-size: .875rem; font-weight: 600;
            color: var(--muted); transition: color .15s, background .15s;
            cursor: pointer; text-decoration: none;
        }
        .sb-nav-item:hover, .sb-nav-item.active { color: var(--text); }

        .sb-fav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 8px; border-radius: 6px;
            cursor: pointer; transition: background .15s;
        }
        .sb-fav-item:hover { background: var(--hover); }

        /* ── Live badge ── */
        .live-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(30,215,96,.12); border: 1px solid rgba(30,215,96,.3);
            border-radius: 20px; padding: 4px 10px;
            font-size: .68rem; font-weight: 700; letter-spacing: .05em;
            color: var(--green); text-transform: uppercase;
            white-space: nowrap;
        }
        .live-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: livePulse 1.5s ease-in-out infinite; flex-shrink: 0; }
        /* Hide "· 33 STATIONS" text on very small screens, keep just "LIVE" */
        @media (max-width: 399px) { .live-count { display: none; } }

        /* ── Search trigger — responsive ── */
        .search-trigger {
            display: flex; align-items: center; gap: 7px;
            background: #1e1e1e; border: 1px solid #333; border-radius: 8px;
            padding: 8px 12px; cursor: pointer; transition: border-color .15s, background .15s;
            color: var(--muted); font-family: 'Figtree', sans-serif; font-size: .8rem;
            overflow: hidden; min-width: 36px;
            -webkit-tap-highlight-color: transparent;
        }
        .search-trigger:hover { border-color: #555; background: #222; color: var(--text); }
        .search-trigger:active { background: #2a2a2a; }
        /* Hide text + kbd hint on small screens — icon only */
        @media (max-width: 479px) {
            .st-text { display: none; }
            .st-kbd  { display: none; }
            .search-trigger { padding: 8px; justify-content: center; flex: 0; }
        }
        @media (min-width: 480px) and (max-width: 639px) {
            .st-kbd { display: none; }
        }

        /* ── Mobile sidebar drawer ── */
        #mobile-drawer {
            position: fixed; inset: 0; z-index: 200;
            display: none;
        }
        #mobile-drawer.open { display: block; }
        .drawer-bg { position: absolute; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); }
        .drawer-panel {
            position: absolute; left: 0; top: 0; bottom: 0; width: min(280px, 85vw);
            background: #111; padding: 20px 16px; overflow-y: auto;
            transform: translateX(-100%); transition: transform .25s ease;
            padding-top: max(20px, var(--safe-top));
            padding-bottom: max(20px, var(--safe-bottom));
            padding-left: max(16px, var(--safe-left));
        }
        #mobile-drawer.open .drawer-panel { transform: translateX(0); }

        /* ── Vinyl spin for now-playing art ── */
        @keyframes vinyl { to { transform: rotate(360deg); } }
        #player-art-box.spinning { animation: vinyl 4s linear infinite; }

        /* Package floating button disabled via config — no override needed */

        /* ── Search modal ── */
        #search-overlay {
            position: fixed; inset: 0; z-index: 300;
            background: rgba(0,0,0,.75);
            backdrop-filter: blur(6px);
            display: none; align-items: flex-start; justify-content: center;
            padding-top: clamp(40px, 10vh, 120px);
        }
        #search-overlay.open { display: flex; }

        #search-box {
            width: 100%; max-width: 600px; margin: 0 16px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0,0,0,.8);
            animation: searchIn .18s ease;
        }
        @keyframes searchIn { from{opacity:0;transform:scale(.96) translateY(-8px)} to{opacity:1;transform:scale(1) translateY(0)} }

        #search-input-wrap {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #2a2a2a;
        }
        #search-input {
            flex: 1; background: none; border: none; outline: none;
            font-family: 'Figtree', sans-serif;
            font-size: 1.05rem; color: var(--text);
        }
        #search-input::placeholder { color: var(--dim); }

        #search-kbd {
            font-size: .68rem; color: var(--dim);
            background: #2a2a2a; border: 1px solid #3a3a3a;
            border-radius: 5px; padding: 2px 7px; white-space: nowrap;
        }

        #search-results {
            max-height: 400px; overflow-y: auto;
        }
        .sr-item {
            display: flex; align-items: center; gap: 14px;
            padding: 10px 20px; cursor: pointer;
            transition: background .12s;
            border-bottom: 1px solid rgba(255,255,255,.04);
        }
        .sr-item:last-child { border-bottom: none; }
        .sr-item:hover, .sr-item.sr-focused { background: #282828; }
        .sr-item.sr-focused .sr-play { opacity: 1; }
        .sr-art {
            width: 44px; height: 44px; border-radius: 6px;
            overflow: hidden; flex-shrink: 0; background: var(--hover);
        }
        .sr-art img { width: 100%; height: 100%; object-fit: cover; }
        .sr-art .sr-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; color: rgba(255,255,255,.7);
        }
        .sr-info { flex: 1; min-width: 0; }
        .sr-name { font-size: .875rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sr-genre { font-size: .72rem; color: var(--muted); margin-top: 2px; }
        .sr-play {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--green); display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; opacity: 0; transition: opacity .15s;
        }
        #search-empty {
            padding: 32px 20px; text-align: center;
            color: var(--muted); font-size: .875rem; display: none;
        }

        /* .search-trigger defined above in main CSS block */

    </style>
</head>
<body>

{{-- ─── App Shell ─────────────────────────────── --}}
<div id="app">

    {{-- ─── SIDEBAR ─────────────────────────────── --}}
    <aside id="sidebar">
        {{-- Logo --}}
        <div style="padding: 18px 16px 8px;">
            <img src="/images/logo.svg" alt="FM Macedonia" style="height:36px; width:auto;">
        </div>

        {{-- Nav --}}
        <nav style="padding: 8px 8px 0;">
            <a class="sb-nav-item active" href="#">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                Home
            </a>
            <a class="sb-nav-item" href="#">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </a>
        </nav>

        {{-- Library --}}
        <div style="margin-top:16px; padding: 0 8px; display:flex; align-items:center; justify-content:space-between;">
            <button class="sb-nav-item" style="gap:10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Your Library
            </button>
            <button onclick="toggleFavoriteFilter()" style="background:none;border:none;cursor:pointer;color:var(--muted);" class="ctrl-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>

        {{-- Favorites list --}}
        <div id="sb-favs" style="flex:1; overflow-y:auto; padding: 8px 8px; margin-top:4px;">
            <p id="sb-empty" style="color:var(--dim); font-size:.75rem; padding:8px 4px;">
                Songs you like will appear here
            </p>
        </div>
    </aside>

    {{-- ─── MAIN CONTENT ─────────────────────────── --}}
    <main id="main">

        {{-- Sticky header --}}
        <div id="main-header">
            {{-- Mobile: hamburger + logo --}}
            <div style="display:flex; align-items:center; gap:12px;">
                <button id="menu-btn" onclick="openDrawer()" style="display:none; background:none; border:none; cursor:pointer; color:white; padding:4px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <img id="mobile-title" src="/images/logo-icon.svg" alt="FM" style="display:none; height:28px; width:28px;">
            </div>

            {{-- Search trigger: icon-only on mobile, full text on larger screens --}}
            <button class="search-trigger" onclick="openSearch()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span class="st-text">Search stations</span>
                <span class="st-kbd" style="font-size:.63rem; color:var(--dim); background:#2a2a2a; border:1px solid #3a3a3a; border-radius:4px; padding:1px 6px; white-space:nowrap;">⌘K</span>
            </button>

            <div class="live-badge">
                <span class="live-dot"></span>
                Live<span class="live-count"> · {{ $stations->count() }}</span>
            </div>
        </div>

        {{-- Page content --}}
        <div style="padding: 0 clamp(12px, 4vw, 24px) 32px;">

            {{-- Greeting --}}
            <div style="margin-bottom:24px;">
                <h1 style="font-family:'Outfit',sans-serif; font-weight:800; font-size:clamp(1.6rem,4vw,2.2rem); letter-spacing:-.02em; line-height:1.1;">
                    Macedonian Radio
                </h1>
                <p style="color:var(--muted); font-size:.875rem; margin-top:4px;">Stream live FM stations</p>
            </div>

            {{-- Quick Picks ─ first 6 stations --}}
            @if($stations->count())
            <div class="quick-grid" style="margin-bottom:40px;">
                @foreach($stations->take(6) as $s)
                @php
                    $h1 = abs(crc32($s->title)) % 360;
                    $h2 = ($h1 + 45) % 360;
                    $gradient = "linear-gradient(135deg,hsl({$h1},55%,28%),hsl({$h2},60%,18%))";
                @endphp
                <div
                    class="quick-card"
                    id="qc-{{ $s->id }}"
                    onclick="play({{ $s->id }})"
                    data-id="{{ $s->id }}"
                >
                    <div class="qc-art">
                        @if($s->photo)
                            <img src="{{ $s->photo }}" alt="{{ $s->title }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.outerHTML='<div class=\'placeholder\' style=\'width:100%;height:100%;background:{{ $gradient }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;color:rgba(255,255,255,.8);\'>{{ strtoupper(substr($s->title,0,1)) }}</div>'">
                        @else
                            <div style="width:100%;height:100%;background:{{ $gradient }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;color:rgba(255,255,255,.8);">{{ strtoupper(substr($s->title,0,1)) }}</div>
                        @endif
                    </div>
                    <span class="qc-title">{{ $s->title }}</span>

                    {{-- Inline eq for active state --}}
                    <div class="eq" style="margin-right:12px;" id="qc-eq-{{ $s->id }}">
                        <span></span><span></span><span></span><span></span>
                    </div>

                    <div class="qc-play" id="qc-play-{{ $s->id }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="black" stroke="black" stroke-width="2" id="qc-play-icon-{{ $s->id }}" style="margin-left:2px">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- All Stations --}}
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1.25rem; letter-spacing:-.01em;">All stations</h2>
                <span style="color:var(--muted); font-size:.8rem; font-weight:600; cursor:pointer;" onmouseenter="this.style.color='white'" onmouseleave="this.style.color='var(--muted)'">Show all</span>
            </div>

            <div class="station-grid">
                @foreach($stations as $i => $s)
                @php
                    $h1 = abs(crc32($s->title)) % 360;
                    $h2 = ($h1 + 45) % 360;
                    $gradient = "linear-gradient(135deg,hsl({$h1},55%,28%),hsl({$h2},60%,18%))";
                    // Trim subtitle: drop "MP3 128kbps" / "AAC+ 64kbps" segments — keep country + tags
                    $subtitle = trim(preg_replace('/\s*·?\s*[A-Z][A-Z0-9+]+\s*\d+kbps/i', '', (string) ($s->subtitle ?? ''))) ?: 'FM Radio · Live';
                @endphp
                <div
                    class="station-card"
                    id="sc-{{ $s->id }}"
                    data-id="{{ $s->id }}"
                    onclick="play({{ $s->id }})"
                    style="animation-delay:{{ min($i, 11) * 55 }}ms"
                >
                    <div class="card-art-wrap">
                        <div class="card-art-inner">
                            @if($s->photo)
                                <img src="{{ $s->photo }}" alt="{{ $s->title }}" loading="lazy" onerror="this.outerHTML='<div class=\'placeholder\' style=\'width:100%;height:100%;background:{{ $gradient }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:2rem;color:rgba(255,255,255,.85);\'>{{ strtoupper(substr($s->title,0,1)) }}</div>'">
                            @else
                                <div class="placeholder" style="background:{{ $gradient }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:2rem;color:rgba(255,255,255,.85);">{{ strtoupper(substr($s->title,0,1)) }}</div>
                            @endif

                            {{-- EQ overlay --}}
                            <div class="card-eq-overlay" id="sc-eq-{{ $s->id }}">
                                <div class="eq" style="height:28px;">
                                    <span></span><span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>

                        {{-- Hover play button --}}
                        <div class="card-play-btn" id="sc-play-{{ $s->id }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="black" id="sc-play-icon-{{ $s->id }}" style="margin-left:2px">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </div>

                    <div class="card-title" title="{{ $s->title }}">{{ $s->title }}</div>
                    <div class="card-meta">
                        <div class="eq" id="sc-mini-eq-{{ $s->id }}" style="height:14px;">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="card-meta-text">{{ $subtitle }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </main>

    {{-- ─── PLAYER BAR ────────────────────────────── --}}
    <div id="player-bar">
        {{-- Left: art + info + like --}}
        <div id="player-left">
            <div id="player-art-box">
                <div id="player-art-inner" style="width:100%;height:100%;background:var(--hover);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--muted);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49"/></svg>
                </div>
            </div>
            <div style="min-width:0; flex:1;">
                <div id="player-title" style="font-size:.85rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text);">—</div>
                <div id="player-sub" style="font-size:.72rem; color:var(--muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Select a station</div>
            </div>
            <button id="like-btn" onclick="toggleLike()" class="ctrl-btn" style="flex-shrink:0;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="like-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
        </div>

        {{-- Center: controls + live --}}
        <div id="player-center">
            <div style="display:flex; align-items:center; gap:20px;">
                <button class="ctrl-btn skip-btn" onclick="prevStation()" title="Previous">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
                </button>
                <button id="pp-btn" onclick="togglePlay()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="black" id="pp-icon">
                        <polygon points="5 3 19 12 5 21 5 3"/>
                    </svg>
                </button>
                <button class="ctrl-btn skip-btn" onclick="nextStation()" title="Next">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
                </button>
            </div>
            <div class="live-badge" style="font-size:.62rem; padding:2px 8px;">
                <span class="live-dot" style="width:5px;height:5px;"></span>
                LIVE
            </div>
        </div>

        {{-- Right: volume --}}
        <div id="player-right">
            <button class="ctrl-btn" onclick="toggleMute()" id="vol-btn">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="vol-icon"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
            </button>
            <input type="range" id="vol-slider" min="0" max="100" value="80" oninput="setVol(this.value)">
        </div>
    </div>
</div>{{-- /#app --}}

{{-- ─── MOBILE DRAWER ───────────────────────────── --}}
<div id="mobile-drawer">
    <div class="drawer-bg" onclick="closeDrawer()"></div>
    <div class="drawer-panel">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <span style="font-family:'Outfit',sans-serif; font-weight:800; font-size:1rem;">Your Library</span>
            <button onclick="closeDrawer()" style="background:none;border:none;cursor:pointer;color:var(--muted);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div id="drawer-favs">
            <p style="color:var(--dim); font-size:.8rem;">Stations you like will appear here</p>
        </div>
    </div>
</div>

{{-- ─── SEARCH MODAL ────────────────────────────── --}}
<div id="search-overlay" onclick="closeSearchOnOverlay(event)">
    <div id="search-box">
        <div id="search-input-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A7A7A7" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input id="search-input" type="text" placeholder="Search stations, genres…" autocomplete="off" oninput="filterSearch(this.value)" onkeydown="searchKeyNav(event)">
            <span id="search-kbd" onclick="closeSearch()">esc</span>
        </div>
        <div id="search-results"></div>
        <div id="search-empty">No stations found</div>
    </div>
</div>

{{-- ─── AUDIO ────────────────────────────────────── --}}
<audio id="audio" preload="none"></audio>

@php
$stationsJs = $stations->values()->map(fn($s) => [
    'id'        => $s->id,
    'title'     => $s->title,
    'subtitle'  => $s->subtitle,
    'photo'     => $s->photo,
    'audio_url' => $s->audio_url,
]);
@endphp

<script>
const audio = document.getElementById('audio');
const stations = @json($stationsJs);

let activeId   = null;
let activeIdx  = -1;
let playing    = false;
let muted      = false;

// ── Responsive show/hide ──────────────────────────────
const mq = window.matchMedia('(max-width: 1023px)');
function applyMq() {
    const mob = mq.matches;
    document.getElementById('menu-btn').style.display   = mob ? 'block' : 'none';
    document.getElementById('mobile-title').style.display = mob ? 'block' : 'none';
}
mq.addEventListener('change', applyMq);
applyMq();

// ── Play ──────────────────────────────────────────────
function play(id) {
    const idx = stations.findIndex(s => s.id == id);
    if (idx < 0) return;
    const s = stations[idx];

    if (activeId === id && playing) { pause(); return; }

    clearActive();
    activeId  = id;
    activeIdx = idx;

    audio.src = s.audio_url;
    audio.volume = document.getElementById('vol-slider').value / 100;
    audio.play().then(() => { playing = true; activateUI(s); }).catch(() => {});
}

function pause() {
    audio.pause();
    playing = false;
    setPpIcon(false);
    clearActive();
}

function togglePlay() {
    if (!activeId) return;
    if (playing) { pause(); }
    else {
        audio.play().then(() => { playing = true; setPpIcon(true); setActive(activeId); });
    }
}

function nextStation() {
    if (activeIdx < stations.length - 1) {
        const n = stations[activeIdx + 1];
        play(n.id);
    }
}

function prevStation() {
    if (activeIdx > 0) {
        const p = stations[activeIdx - 1];
        play(p.id);
    }
}

// ── UI helpers ────────────────────────────────────────
function activateUI(s) {
    // Player bar info
    document.getElementById('player-title').textContent = s.title;
    document.getElementById('player-sub').textContent   = s.subtitle || 'FM Radio · Live';

    // Art
    const box = document.getElementById('player-art-box');
    box.style.display = 'block';
    const inner = document.getElementById('player-art-inner');
    if (s.photo) {
        inner.innerHTML = `<img src="${s.photo}" alt="${s.title}" style="width:100%;height:100%;object-fit:contain;padding:8%;" onerror="this.outerHTML='<div style=\\'width:100%;height:100%;background:#333;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;\\'>${s.title[0]?.toUpperCase()}</div>'">`;
    } else {
        const h = Math.abs(s.title.split('').reduce((a,c)=>a+c.charCodeAt(0),0)) % 360;
        inner.innerHTML = `<div style="width:100%;height:100%;background:linear-gradient(135deg,hsl(${h},55%,28%),hsl(${(h+45)%360},60%,18%));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;color:rgba(255,255,255,.85);">${s.title[0]?.toUpperCase()}</div>`;
    }

    setPpIcon(true);
    setActive(s.id);
    updateLikeIcon(s.id);
}

function setActive(id) {
    // Station cards
    document.querySelectorAll('.station-card').forEach(el => {
        el.classList.toggle('is-active', el.dataset.id == id);
    });
    // Quick cards
    document.querySelectorAll('.quick-card').forEach(el => {
        const active = el.dataset.id == id;
        el.classList.toggle('is-active', active);
        const playIcon = document.getElementById(`qc-play-icon-${el.dataset.id}`);
        if (playIcon) {
            playIcon.innerHTML = active
                ? '<rect x="6" y="4" width="4" height="16" fill="black"/><rect x="14" y="4" width="4" height="16" fill="black"/>'
                : '<polygon points="5 3 19 12 5 21 5 3"/>';
        }
        // Show/hide eq in quick card
        const qcEq = document.getElementById(`qc-eq-${el.dataset.id}`);
        if (qcEq) qcEq.style.display = active ? 'flex' : 'none';
        const qcPlay = document.getElementById(`qc-play-${el.dataset.id}`);
        if (qcPlay) qcPlay.style.opacity = active ? '1' : '';
    });
    // Card play icons
    document.querySelectorAll('.station-card').forEach(el => {
        const scIcon = document.getElementById(`sc-play-icon-${el.dataset.id}`);
        if (scIcon) {
            scIcon.innerHTML = el.dataset.id == id
                ? '<rect x="6" y="4" width="4" height="16" fill="black"/><rect x="14" y="4" width="4" height="16" fill="black"/>'
                : '<polygon points="5 3 19 12 5 21 5 3"/>';
        }
        const scPlay = document.getElementById(`sc-play-${el.dataset.id}`);
        if (scPlay) scPlay.style.opacity = el.dataset.id == id ? '1' : '';
    });
}

function clearActive() {
    playing = false;
    setPpIcon(false);
    document.querySelectorAll('.station-card').forEach(el => {
        el.classList.remove('is-active');
        const ic = document.getElementById(`sc-play-icon-${el.dataset.id}`);
        if (ic) ic.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
        const pb = document.getElementById(`sc-play-${el.dataset.id}`);
        if (pb) pb.style.opacity = '';
    });
    document.querySelectorAll('.quick-card').forEach(el => {
        el.classList.remove('is-active');
        const ic = document.getElementById(`qc-play-icon-${el.dataset.id}`);
        if (ic) ic.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
        const qe = document.getElementById(`qc-eq-${el.dataset.id}`);
        if (qe) qe.style.display = 'none';
    });
}

function setPpIcon(isPlay) {
    document.getElementById('pp-icon').innerHTML = isPlay
        ? '<rect x="6" y="4" width="4" height="16" fill="black"/><rect x="14" y="4" width="4" height="16" fill="black"/>'
        : '<polygon points="5 3 19 12 5 21 5 3"/>';
}

// ── Volume ─────────────────────────────────────────────
function setVol(v) {
    audio.volume = v / 100;
    muted = (v == 0);
    updateVolIcon(v);
}

function toggleMute() {
    const sl = document.getElementById('vol-slider');
    if (muted) { sl.value = 80; audio.volume = 0.8; muted = false; updateVolIcon(80); }
    else        { sl.value = 0;  audio.volume = 0;   muted = true;  updateVolIcon(0); }
}

function updateVolIcon(v) {
    const paths = v == 0
        ? '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/>'
        : v < 50
            ? '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>'
            : '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>';
    document.getElementById('vol-icon').innerHTML = paths;
}

// ── Favorites ──────────────────────────────────────────
function getFavs()      { return JSON.parse(localStorage.getItem('fm_favs') || '[]'); }
function saveFavs(f)    { localStorage.setItem('fm_favs', JSON.stringify(f)); }
function isLiked(id)    { return getFavs().some(f => f.id == id); }

function toggleLike() {
    if (!activeId) return;
    const s = stations.find(s => s.id == activeId);
    if (!s) return;
    let favs = getFavs();
    const idx = favs.findIndex(f => f.id == s.id);
    if (idx >= 0) favs.splice(idx, 1);
    else favs.push(s);
    saveFavs(favs);
    updateLikeIcon(activeId);
    renderFavs();
}

function updateLikeIcon(id) {
    const ic = document.getElementById('like-icon');
    if (!ic) return;
    ic.setAttribute('fill', isLiked(id) ? '#1ED760' : 'none');
    ic.setAttribute('stroke', isLiked(id) ? '#1ED760' : 'currentColor');
}

function renderFavs() {
    const favs = getFavs();
    const empty = '<p style="color:var(--dim);font-size:.75rem;padding:4px;">Stations you like will appear here</p>';
    const html = favs.length ? favs.map(f => `
        <div class="sb-fav-item" onclick="play(${f.id})">
            <div style="width:40px;height:40px;border-radius:4px;overflow:hidden;flex-shrink:0;background:var(--hover);">
                ${f.photo
                    ? `<img src="${f.photo}" alt="${f.title}" style="width:100%;height:100%;object-fit:cover;">`
                    : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:var(--muted);">${(f.title||'R')[0].toUpperCase()}</div>`
                }
            </div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:${activeId==f.id?'var(--green)':'var(--text)'};">${f.title}</div>
                <div style="font-size:.7rem;color:var(--muted);">FM Radio</div>
            </div>
            <button onclick="event.stopPropagation(); removeFav(${f.id})" style="background:none;border:none;cursor:pointer;color:var(--dim);flex-shrink:0;" onmouseenter="this.style.color='white'" onmouseleave="this.style.color='var(--dim)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    `).join('') : empty;

    ['sb-favs', 'drawer-favs'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = html;
    });
    const em = document.getElementById('sb-empty');
    if (em) em.style.display = 'none';
}

function removeFav(id) {
    let favs = getFavs().filter(f => f.id != id);
    saveFavs(favs);
    renderFavs();
    if (activeId == id) updateLikeIcon(id);
}

// ── Mobile drawer ──────────────────────────────────────
function openDrawer()  { document.getElementById('mobile-drawer').classList.add('open'); renderFavs(); }
function closeDrawer() { document.getElementById('mobile-drawer').classList.remove('open'); }

// ── Audio events ───────────────────────────────────────
audio.addEventListener('pause',  () => { playing = false; setPpIcon(false); });
audio.addEventListener('play',   () => { playing = true;  setPpIcon(true); });
audio.addEventListener('error',  () => {
    setTimeout(() => { if (activeId && playing) { audio.load(); audio.play(); } }, 2000);
});

// ── Init ───────────────────────────────────────────────
renderFavs();
document.querySelectorAll('[id^="qc-eq-"]').forEach(el => el.style.display = 'none');

// ── localStorage migration: fix stale /storage/storage/ paths ─
(function migrateFavPaths() {
    const favs = getFavs();
    const fixed = favs.map(f => ({
        ...f,
        photo: f.photo ? f.photo.replace('/storage/storage/', '/storage/') : f.photo
    }));
    saveFavs(fixed);
})();

// ── Keyboard shortcuts ─────────────────────────────────
document.addEventListener('keydown', e => {
    const tag = document.activeElement.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA') return;

    if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); openSearch(); return; }
    if (e.key === 'Escape')   { closeSearch(); return; }
    if (e.key === ' ')        { e.preventDefault(); togglePlay(); return; }
    if (e.key === 'ArrowRight') nextStation();
    if (e.key === 'ArrowLeft')  prevStation();
    if (e.key === 'm' || e.key === 'M') toggleMute();
});

// ── Search ─────────────────────────────────────────────
let searchFocusIdx = -1;

function openSearch() {
    document.getElementById('search-overlay').classList.add('open');
    document.getElementById('search-input').value = '';
    searchFocusIdx = -1;
    filterSearch('');
    setTimeout(() => document.getElementById('search-input').focus(), 50);
}

function closeSearch() {
    document.getElementById('search-overlay').classList.remove('open');
}

function closeSearchOnOverlay(e) {
    if (e.target === document.getElementById('search-overlay')) closeSearch();
}

function filterSearch(q) {
    const term = q.trim().toLowerCase();
    const list = term
        ? stations.filter(s =>
            (s.title || '').toLowerCase().includes(term) ||
            (s.subtitle || '').toLowerCase().includes(term))
        : stations;

    searchFocusIdx = -1;
    renderSearchResults(list);
}

function renderSearchResults(list) {
    const container = document.getElementById('search-results');
    const empty = document.getElementById('search-empty');

    if (!list.length) {
        container.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    container.innerHTML = list.map((s, i) => {
        const h = Math.abs((s.title||'').split('').reduce((a,c)=>a+c.charCodeAt(0),0)) % 360;
        const art = s.photo
            ? `<img src="${s.photo}" alt="${s.title}" onerror="this.outerHTML='<div class=\\'sr-placeholder\\' style=\\'background:linear-gradient(135deg,hsl(${h},50%,25%),hsl(${(h+40)%360},55%,18%));\\'>'+encodeURIComponent(s.title[0]||'?')+'</div>'">`
            : `<div class="sr-placeholder" style="background:linear-gradient(135deg,hsl(${h},50%,25%),hsl(${(h+40)%360},55%,18%));">${(s.title||'?')[0].toUpperCase()}</div>`;

        const genres = (s.subtitle || '').split(' ').filter(Boolean)
            .map(g => `<span style="background:#2a2a2a;border-radius:3px;padding:1px 6px;font-size:.65rem;">${g}</span>`)
            .join('');

        const isActive = s.id == activeId;
        return `<div class="sr-item${isActive?' sr-focused':''}" data-idx="${i}" data-id="${s.id}"
                     onclick="searchPlay(${s.id})"
                     onmouseenter="searchHover(${i})">
            <div class="sr-art">${art}</div>
            <div class="sr-info">
                <div class="sr-name" style="color:${isActive?'var(--green)':'var(--text)'};">${s.title}</div>
                <div class="sr-genre" style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">${genres}</div>
            </div>
            <div class="sr-play">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="black" style="margin-left:1px"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </div>
        </div>`;
    }).join('');
}

function searchHover(idx) { searchFocusIdx = idx; highlightSearchItem(); }

function highlightSearchItem() {
    document.querySelectorAll('.sr-item').forEach((el, i) => {
        el.classList.toggle('sr-focused', i === searchFocusIdx);
    });
    const active = document.querySelector('.sr-item.sr-focused');
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function searchKeyNav(e) {
    const items = document.querySelectorAll('.sr-item');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        searchFocusIdx = Math.min(searchFocusIdx + 1, items.length - 1);
        highlightSearchItem();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        searchFocusIdx = Math.max(searchFocusIdx - 1, 0);
        highlightSearchItem();
    } else if (e.key === 'Enter') {
        if (searchFocusIdx >= 0 && items[searchFocusIdx]) {
            const id = items[searchFocusIdx].dataset.id;
            searchPlay(id);
        }
    } else if (e.key === 'Escape') {
        closeSearch();
    }
}

function searchPlay(id) {
    play(id);
    closeSearch();
}

</script>

{{-- PWA service worker registration (erag/laravel-pwa) --}}
@RegisterServiceWorkerScript
</body>
</html>
