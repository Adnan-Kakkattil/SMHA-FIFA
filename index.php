<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$csrfToken = (string) $_SESSION['csrf_token'];
?>
<!DOCTYPE html>

<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>FIFA World Cup 2026 Auction Live Board</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=Space+Grotesk:wght@500;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Theme Config -->
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "on-primary": "#000000",
                "tertiary": "#ff5252",
                "tertiary-container": "#ffda37",
                "on-secondary-fixed-variant": "#5700c9",
                "surface-container-low": "#171c22",
                "on-secondary": "#ffffff",
                "tertiary-fixed-dim": "#e9c400",
                "surface-container-highest": "#353940",
                "on-primary-fixed": "#002203",
                "on-tertiary": "#ffffff",
                "primary": "#0096ff",
                "error-container": "#93000a",
                "secondary": "#00c853",
                "secondary-fixed": "#e9ddff",
                "outline": "#84967e",
                "on-secondary-fixed": "#23005b",
                "primary-fixed": "#0096ff",
                "on-surface-variant": "#b9ccb2",
                "surface-variant": "#2d382a",
                "background": "#0f141a",
                "on-background": "#dae6d2",
                "on-tertiary-container": "#725f00",
                "surface-container-lowest": "#0a0e14",
                "secondary-container": "#00c853",
                "surface-container": "#1d232a",
                "surface-dim": "#0f141a",
                "on-tertiary-fixed-variant": "#544600",
                "on-primary-container": "#ffffff",
                "on-primary-fixed-variant": "#00530e",
                "inverse-on-surface": "#283326",
                "inverse-primary": "#006e16",
                "tertiary-fixed": "#ffe16d",
                "outline-variant": "#3b4b37",
                "surface": "#0f141a",
                "surface-bright": "#353940",
                "error": "#ffb4ab",
                "on-error": "#690005",
                "surface-container-high": "#222d20",
                "on-error-container": "#ffdad6",
                "primary-container": "#0096ff",
                "on-tertiary-fixed": "#221b00",
                "surface-tint": "#0096ff",
                "on-secondary-container": "#ffffff",
                "on-surface": "#ffffff",
                "primary-fixed-dim": "#0096ff",
                "inverse-surface": "#dae6d2",
                "secondary-fixed-dim": "#d1bcff"
            },
            "borderRadius": {
                "DEFAULT": "9999px",
                "lg": "9999px",
                "xl": "9999px",
                "full": "9999px"
            },
            "spacing": {
                "sm": "12px",
                "md": "24px",
                "container-max": "1440px",
                "xs": "4px",
                "gutter": "24px",
                "xl": "80px",
                "lg": "48px",
                "base": "8px"
            },
            "fontFamily": {
                "headline-lg": ["plusJakartaSans"],
                "display-lg": ["plusJakartaSans"],
                "headline-lg-mobile": ["plusJakartaSans"],
                "body-lg": ["plusJakartaSans"],
                "title-md": ["plusJakartaSans"],
                "label-caps": ["spaceGrotesk"],
                "display-md": ["plusJakartaSans"],
                "body-md": ["plusJakartaSans"]
            }
          },
        },
      }
    </script>
<style>
        body {
            background-color: #0f141a;
            color: #ffffff;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
        }

        .card-3d-wrap {
            perspective: 1500px;
        }

        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .unity-pulse-glow {
            background: radial-gradient(circle at 50% -20%, rgba(0, 150, 255, 0.15) 0%, transparent 70%);
        }

        .neon-border-multi {
            box-shadow: 0 0 20px rgba(0, 150, 255, 0.2), inset 0 0 10px rgba(0, 150, 255, 0.1);
        }

        .pulse-glow-primary {
            animation: pulse-blue 2s infinite;
        }

        @keyframes pulse-blue {
            0% { box-shadow: 0 0 0 0 rgba(0, 150, 255, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(0, 150, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 150, 255, 0); }
        }

        .bar-3d {
            transform: translateZ(20px);
            transition: height 1s ease-out;
        }

        .diagonal-pattern {
            background-image: repeating-linear-gradient(45deg, rgba(255,255,255,0.02) 0px, rgba(255,255,255,0.02) 1px, transparent 1px, transparent 15px);
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .floating-logo {
            position: fixed;
            top: 18px;
            left: 32px;
            z-index: 50;
            height: 52px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 22px rgba(0, 150, 255, 0.35));
        }

        .page-shell {
            height: 100vh;
            min-height: 0;
            overflow: hidden;
            padding: 84px 32px 24px;
        }

        .board-wrap {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .top-board {
            flex: 1 1 auto;
            min-height: 0;
        }

        .auction-card-panel,
        .leaderboard-panel {
            height: 100%;
            min-height: 0;
        }

        .auction-card-panel {
            padding: 18px;
        }

        .player-card-wrap {
            max-width: clamp(320px, calc((100vh - 470px) * 0.78), 520px);
        }

        .player-image {
            margin-bottom: 12px;
        }

        .is-hidden {
            display: none !important;
        }

        .no-player-state {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 50% 20%, rgba(0, 150, 255, 0.18), transparent 52%),
                linear-gradient(145deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
                #0b1117;
            color: rgba(255, 255, 255, 0.78);
            font-family: "Sora", sans-serif;
            font-size: clamp(1.3rem, 2.6vw, 2.4rem);
            font-weight: 800;
            line-height: 1.05;
            text-align: center;
            text-transform: uppercase;
        }

        .player-card-wrap .card-3d {
            padding: clamp(10px, 1vw, 16px);
        }

        .player-card-wrap h2 {
            font-size: clamp(2rem, 2.45vw, 3.35rem);
            line-height: 1.03;
        }

        .player-card-wrap .bg-secondary {
            font-size: clamp(10px, 0.8vw, 13px);
            padding: 5px 14px;
        }

        .current-bid-text {
            font-size: clamp(2.45rem, 4.5vw, 5.35rem);
            line-height: 1;
            white-space: nowrap;
        }

        .bid-action-bar {
            margin-top: 14px;
        }

        .bid-action-bar button:disabled {
            cursor: not-allowed;
            filter: grayscale(0.35);
            opacity: 0.52;
        }

        .close-bid-button {
            background: linear-gradient(90deg, #ffda37, #ff5252);
            color: #241300;
            box-shadow: 0 0 28px rgba(255, 218, 55, 0.28), 0 0 22px rgba(255, 82, 82, 0.18);
        }

        .sound-toggle {
            position: fixed;
            top: 18px;
            right: 32px;
            z-index: 50;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 15px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.82);
            font-family: "Space Grotesk", sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 0 24px rgba(0, 150, 255, 0.16);
            backdrop-filter: blur(18px);
            transition: color 180ms ease, border-color 180ms ease, background 180ms ease, box-shadow 180ms ease;
        }

        .sound-toggle span {
            font-size: 18px;
        }

        .sound-toggle.sound-ready {
            border-color: rgba(0, 200, 83, 0.45);
            background: rgba(0, 200, 83, 0.12);
            color: #dbffd9;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14), 0 0 26px rgba(0, 200, 83, 0.22);
        }

        .leaderboard-panel {
            padding: 22px;
            overflow: hidden;
        }

        .leaderboard-title-row {
            margin-bottom: 18px;
        }

        .leaderboard-list {
            gap: 12px;
        }

        .leaderboard-item {
            padding: 14px;
        }

        .ranking-footer {
            margin-top: 18px;
            padding-top: 14px;
        }

        .auction-card-panel.bid-flash::after {
            content: "";
            position: absolute;
            inset: -45%;
            z-index: 2;
            pointer-events: none;
            background: linear-gradient(110deg, transparent 35%, rgba(0, 255, 65, 0.18) 46%, rgba(0, 150, 255, 0.22) 54%, transparent 66%);
            transform: translateX(-68%) rotate(8deg);
            animation: broadcast-sweep 900ms cubic-bezier(0.2, 0, 0, 1);
        }

        .player-card-wrap.bid-surge {
            animation: card-surge 900ms cubic-bezier(0.2, 0, 0, 1);
        }

        .current-bid-text {
            font-variant-numeric: tabular-nums;
        }

        .current-bid-text.bid-counting {
            animation: bid-count-pop 900ms cubic-bezier(0.2, 0, 0, 1);
        }

        .current-bid-text::after {
            content: "";
            display: block;
            height: 2px;
            width: 0;
            margin: 10px auto 0;
            border-radius: 999px;
            background: linear-gradient(90deg, transparent, #00c853, #0096ff, transparent);
            box-shadow: 0 0 18px rgba(0, 200, 83, 0.55);
        }

        .current-bid-text.bid-counting::after {
            animation: bid-underline 900ms cubic-bezier(0.2, 0, 0, 1);
        }

        .bid-burst {
            position: absolute;
            left: 50%;
            top: 47%;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 22px;
            border: 1px solid rgba(0, 255, 65, 0.55);
            border-radius: 999px;
            background: rgba(6, 20, 12, 0.86);
            color: #eaffdf;
            font-family: "Space Grotesk", sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: 0 0 28px rgba(0, 255, 65, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.18);
            transform: translate(-50%, -50%);
            animation: bid-burst 1050ms cubic-bezier(0.2, 0, 0, 1) forwards;
        }

        .bid-burst.manual {
            border-color: rgba(255, 218, 55, 0.7);
            box-shadow: 0 0 30px rgba(255, 218, 55, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .leaderboard-item.bid-leader-pulse {
            animation: leader-pulse 1000ms cubic-bezier(0.2, 0, 0, 1);
        }

        .leaderboard-item.bid-leader-pulse .bg-secondary {
            animation: leader-bar-charge 1000ms cubic-bezier(0.2, 0, 0, 1);
        }

        .bid-button-fired {
            animation: button-kick 520ms cubic-bezier(0.2, 0, 0, 1);
        }

        .auction-card-panel.bid-closed {
            border-color: rgba(255, 218, 55, 0.36);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4), 0 0 42px rgba(255, 218, 55, 0.14);
        }

        .player-card-wrap.player-switching {
            animation: player-switch 620ms cubic-bezier(0.2, 0, 0, 1);
        }

        .auction-card-panel.bid-closed::before {
            content: "SOLD";
            position: absolute;
            top: 34px;
            right: -52px;
            z-index: 18;
            width: 220px;
            padding: 11px 0;
            background: linear-gradient(90deg, #ffda37, #00c853);
            color: #1c1600;
            font-family: "Sora", sans-serif;
            font-size: 18px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            transform: rotate(34deg);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.32), 0 0 28px rgba(255, 218, 55, 0.32);
            animation: sold-ribbon 700ms cubic-bezier(0.2, 0, 0, 1);
        }

        .confetti-piece {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 24;
            width: 9px;
            height: 18px;
            border-radius: 3px;
            background: var(--confetti-color, #ffda37);
            pointer-events: none;
            transform: translate(-50%, -50%) rotate(var(--r, 0deg));
            animation: confetti-pop 1350ms cubic-bezier(0.12, 0.74, 0.3, 1) forwards;
            box-shadow: 0 0 12px color-mix(in srgb, var(--confetti-color, #ffda37), transparent 38%);
        }

        .close-burst {
            position: absolute;
            left: 50%;
            top: 42%;
            z-index: 25;
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 0 28px;
            border: 1px solid rgba(255, 218, 55, 0.68);
            border-radius: 999px;
            background: rgba(24, 16, 4, 0.88);
            color: #fff8d7;
            font-family: "Space Grotesk", sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: 0 0 38px rgba(255, 218, 55, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: close-burst 1550ms cubic-bezier(0.2, 0, 0, 1) forwards;
            transform: translate(-50%, -50%);
        }

        @keyframes broadcast-sweep {
            0% { opacity: 0; transform: translateX(-68%) rotate(8deg); }
            18% { opacity: 1; }
            100% { opacity: 0; transform: translateX(68%) rotate(8deg); }
        }

        @keyframes card-surge {
            0% { filter: brightness(1); transform: scale(1); }
            35% { filter: brightness(1.18) saturate(1.16); transform: scale(1.045); }
            100% { filter: brightness(1); transform: scale(1); }
        }

        @keyframes bid-count-pop {
            0% { color: #ffffff; letter-spacing: 0; text-shadow: 0 0 0 rgba(0, 200, 83, 0); transform: translateY(8px) scale(0.96); }
            35% { color: #ffda37; text-shadow: 0 0 30px rgba(255, 218, 55, 0.5), 0 0 44px rgba(0, 200, 83, 0.35); transform: translateY(-4px) scale(1.05); }
            100% { color: #00c853; letter-spacing: 0; text-shadow: 0 0 15px rgba(0, 200, 83, 0.5); transform: translateY(0) scale(1); }
        }

        @keyframes bid-underline {
            0% { width: 0; opacity: 0; }
            35% { width: 100%; opacity: 1; }
            100% { width: 0; opacity: 0; }
        }

        @keyframes bid-burst {
            0% { opacity: 0; transform: translate(-50%, 12px) scale(0.82); }
            18% { opacity: 1; transform: translate(-50%, -12px) scale(1); }
            74% { opacity: 1; transform: translate(-50%, -36px) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -58px) scale(0.94); }
        }

        @keyframes leader-pulse {
            0% { background: rgba(255, 255, 255, 0.055); box-shadow: none; transform: translateX(0); }
            28% { background: rgba(0, 200, 83, 0.16); box-shadow: 0 0 24px rgba(0, 200, 83, 0.28); transform: translateX(-4px) scale(1.015); }
            100% { background: rgba(255, 255, 255, 0.055); box-shadow: none; transform: translateX(0); }
        }

        @keyframes leader-bar-charge {
            0% { filter: brightness(1); box-shadow: 0 0 10px rgba(0,200,83,0.5); }
            38% { filter: brightness(1.6); box-shadow: 0 0 24px rgba(0,255,65,0.78), 0 0 44px rgba(0,150,255,0.28); }
            100% { filter: brightness(1); box-shadow: 0 0 10px rgba(0,200,83,0.5); }
        }

        @keyframes button-kick {
            0% { transform: scale(1); }
            32% { transform: scale(1.055); box-shadow: 0 0 42px rgba(0, 150, 255, 0.85), 0 0 28px rgba(0, 200, 83, 0.35); }
            100% { transform: scale(1); }
        }

        @keyframes sold-ribbon {
            0% { opacity: 0; transform: translateY(-20px) rotate(34deg) scale(0.86); }
            100% { opacity: 1; transform: translateY(0) rotate(34deg) scale(1); }
        }

        @keyframes confetti-pop {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.7) rotate(var(--r, 0deg)); }
            16% { opacity: 1; }
            100% {
                opacity: 0;
                transform: translate(calc(-50% + var(--tx, 0px)), calc(-50% + var(--ty, -140px))) rotate(calc(var(--r, 0deg) + 360deg)) scale(1);
            }
        }

        @keyframes close-burst {
            0% { opacity: 0; transform: translate(-50%, 10px) scale(0.82); }
            16% { opacity: 1; transform: translate(-50%, -18px) scale(1.02); }
            72% { opacity: 1; transform: translate(-50%, -42px) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -74px) scale(0.94); }
        }

        @keyframes player-switch {
            0% { opacity: 0; transform: translateY(18px) scale(0.96); filter: brightness(1.25); }
            100% { opacity: 1; transform: translateY(0) scale(1); filter: brightness(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 1ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 1ms !important;
            }
        }

        @media (min-width: 1024px) and (max-height: 760px) {
            .page-shell {
                padding-top: 66px;
                padding-bottom: 14px;
            }

            .board-wrap {
                gap: 12px;
            }

            .floating-logo {
                height: 44px;
                top: 12px;
            }

            .sound-toggle {
                top: 12px;
                right: 22px;
                min-height: 34px;
                padding: 0 12px;
            }

            .auction-card-panel {
                padding: 14px;
            }

            .player-card-wrap {
                max-width: clamp(260px, calc((100vh - 380px) * 0.82), 310px);
            }

            .card-3d {
                padding: 8px;
            }

            .player-image {
                margin-bottom: 10px;
            }

            .bid-action-bar {
                margin-top: 12px;
            }

            .leaderboard-panel {
                padding: 18px;
            }

            .leaderboard-item {
                padding: 11px 14px;
            }

        }

        @media (max-width: 1023px) {
            body {
                overflow-y: auto;
            }

            .page-shell {
                height: auto;
                min-height: 100vh;
                overflow: visible;
                padding: 88px 18px 24px;
            }

            .board-wrap {
                height: auto;
            }

            .auction-card-panel,
            .leaderboard-panel {
                height: auto;
                min-height: auto;
            }

        }

        @media (max-width: 640px) {
            .floating-logo {
                left: 18px;
                height: 42px;
            }

            .sound-toggle {
                right: 14px;
                top: 14px;
            }

            .page-shell {
                padding-left: 12px;
                padding-right: 12px;
            }
        }
    </style>
</head>
<body class="dark font-body-md text-body-md selection:bg-primary selection:text-white">
<img alt="FIFA World Cup 2026 Logo" class="floating-logo" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDZlJLsu1-5aC0in-_NJGnXm21WymPK2Bt2RbELvwIM0JloU1umlGbOW-ODtWcxLfLkFnYGM8h0bAD8Wgj43GFiwWgcPqTN_4hPxyNONXIxAByrHPhvbiPlR2BQSLnQ4JrwJRC3Ng7y0BV6L-KfFIOtxR6DKaZ1hX8ESpX50KmRy0UU03nHxSbh8Xncv-OfDCuczVzlCtLYqYmO6op0-yiaIpfp2tDvP_7eK3zy4WVfLSsMEiC7LBWP_fajpckWtQ8XcUxAMdXnwRSG"/>
<button id="soundToggle" class="sound-toggle" type="button" aria-label="Enable sound" title="Enable sound">
<span class="material-symbols-outlined" aria-hidden="true">volume_up</span>
Sound
</button>
<!-- Main Content -->
<main class="page-shell unity-pulse-glow diagonal-pattern">
<div class="max-w-container-max mx-auto board-wrap">
<!-- Main Grid -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4 top-board">
<!-- Auction Stage -->
<section class="lg:col-span-8 flex flex-col min-h-0">
<div class="glass-panel rounded-[32px] auction-card-panel relative overflow-hidden flex flex-col justify-center items-center group">
<!-- Atmospheric effect -->
<div class="absolute inset-0 bg-gradient-to-tr from-primary/5 via-secondary/5 to-tertiary/5 pointer-events-none"></div>
<div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/10 blur-[120px] rounded-full pointer-events-none"></div>
<div class="absolute -bottom-32 -right-32 w-96 h-96 bg-tertiary/10 blur-[120px] rounded-full pointer-events-none"></div>
<!-- 3D Player Card -->
<div class="card-3d-wrap w-full player-card-wrap">
<div class="card-3d glass-panel rounded-[32px] border border-white/10 p-sm relative z-10 neon-border-multi">
<div class="relative rounded-[24px] overflow-hidden aspect-[3/4] player-image shadow-2xl">
<img id="playerImage" class="w-full h-full object-cover is-hidden" data-alt="" alt="" src=""/>
<div id="noPlayerState" class="no-player-state">Loading players</div>
<div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
<div class="absolute bottom-md left-md">
<div id="playerRole" class="bg-secondary text-on-secondary text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest mb-xs inline-block">AUCTION</div>
<h2 id="playerName" class="font-display-md text-2xl md:text-3xl text-white drop-shadow-lg uppercase tracking-tight">Loading players</h2>
</div>
</div>
<div class="text-center py-xs">
<p class="font-label-caps text-xs text-white/60 uppercase mb-xs tracking-widest">Current Bid</p>
<div id="currentBid" class="text-4xl md:text-5xl current-bid-text font-extrabold text-secondary drop-shadow-[0_0_15px_rgba(0,200,83,0.5)]" aria-live="polite">₹0</div>
</div>
</div>
</div>
<!-- Action Bar -->
<div class="bid-action-bar w-full max-w-2xl flex flex-col md:flex-row gap-sm">
<button id="incrementBid" class="flex-1 py-sm bg-white/5 border border-white/10 rounded-full font-bold uppercase tracking-widest text-xs hover:bg-white/10 transition-all hover:scale-[1.02]">Increment +₹100k</button>
<button id="placeBid" class="flex-[2] py-sm bg-primary text-white rounded-full font-extrabold uppercase tracking-widest text-sm shadow-[0_0_30px_rgba(0,150,255,0.6)] pulse-glow-primary hover:scale-105 active:scale-95 transition-all">PLACE BID NOW</button>
<button id="closeBid" class="flex-1 py-sm close-bid-button rounded-full font-extrabold uppercase tracking-widest text-xs hover:scale-[1.02] active:scale-95 transition-all">Close Bid</button>
</div>
</div>
</section>
<!-- Leaderboard Section -->
<aside class="lg:col-span-4 flex flex-col min-h-0">
<div class="glass-panel rounded-[32px] leaderboard-panel flex flex-col">
<div class="flex justify-between items-center leaderboard-title-row">
<h3 class="text-xl font-bold text-white uppercase tracking-tighter">Leaderboard</h3>
<span class="material-symbols-outlined text-primary">military_tech</span>
</div>
<div id="leaderboardList" class="leaderboard-list flex-grow flex flex-col">
<div class="relative leaderboard-item rounded-2xl bg-white/5 border-l-4 border-white/20 group hover:bg-white/10 transition-colors">
<div class="flex justify-between items-center mb-xs">
<div class="flex items-center gap-sm">
<span class="font-bold text-white/40">--</span>
<span class="font-bold text-white">Loading teams</span>
</div>
<span class="font-bold text-white">₹0.00M</span>
</div>
<div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
<div class="h-full bg-white/30 w-0"></div>
</div>
</div>
</div>
<div class="ranking-footer border-t border-white/10">
<button class="w-full text-center font-label-caps text-xs text-white/40 hover:text-primary transition-colors tracking-widest uppercase">VIEW FULL RANKINGS</button>
</div>
</div>
</aside>
</div>
</div>
</main>
<!-- Interactive script -->
<script>
        const API_URL = 'api.php';
        const csrfToken = '<?= h($csrfToken) ?>';
        const bidEl = document.getElementById('currentBid');
        const incrementButton = document.getElementById('incrementBid');
        const placeBidButton = document.getElementById('placeBid');
        const closeBidButton = document.getElementById('closeBid');
        const soundToggle = document.getElementById('soundToggle');
        const auctionPanel = document.querySelector('.auction-card-panel');
        const playerWrap = document.querySelector('.player-card-wrap');
        const playerImage = document.getElementById('playerImage');
        const noPlayerState = document.getElementById('noPlayerState');
        const playerName = document.getElementById('playerName');
        const playerRole = document.getElementById('playerRole');
        const leaderboardList = document.getElementById('leaderboardList');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        let topLeaderRow = null;
        let topLeaderBid = null;
        let topLeaderBar = null;
        let auctionPlayers = [];
        let currentPlayer = null;
        let currentPlayerIndex = 0;
        let currentBid = 0;
        let activeBidTimer = null;
        let activeBidFallback = null;
        let audioContext = null;
        let soundReady = false;
        let bidClosed = true;

        function formatRupee(value) {
            return `₹${Math.round(Number(value) || 0).toLocaleString('en-US')}`;
        }

        function formatShortRupee(value) {
            return `₹${((Number(value) || 0) / 1000000).toFixed(2)}M`;
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = String(value ?? '');
            return div.innerHTML;
        }

        async function apiGet(action) {
            const response = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'API request failed.');
            }
            return data;
        }

        async function apiPost(action, payload) {
            const response = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload),
                cache: 'no-store',
                keepalive: true
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'API request failed.');
            }
            return data;
        }

        function updateSoundToggle() {
            if (!soundToggle) return;
            soundToggle.classList.toggle('sound-ready', soundReady);
            soundToggle.setAttribute('aria-label', soundReady ? 'Sound enabled' : 'Enable sound');
            soundToggle.setAttribute('title', soundReady ? 'Sound enabled' : 'Enable sound');
        }

        function unlockAudio(playConfirm = false) {
            if (!AudioContextClass) return null;
            if (!audioContext) {
                audioContext = new AudioContextClass();
            }

            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

            soundReady = true;
            updateSoundToggle();

            if (playConfirm) {
                window.setTimeout(playBidSound, 40);
            }

            return audioContext;
        }

        function tone(ctx, freq, start, duration, type = 'sine', peak = 0.08, endFreq = freq) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, start);
            osc.frequency.exponentialRampToValueAtTime(Math.max(20, endFreq), start + duration);
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(peak, start + 0.018);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            osc.connect(gain).connect(ctx.destination);
            osc.start(start);
            osc.stop(start + duration + 0.025);
        }

        function noiseBurst(ctx, start, duration, peak = 0.05, filterFreq = 2400) {
            const sampleCount = Math.max(1, Math.floor(ctx.sampleRate * duration));
            const buffer = ctx.createBuffer(1, sampleCount, ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < sampleCount; i += 1) {
                data[i] = (Math.random() * 2 - 1) * (1 - i / sampleCount);
            }

            const source = ctx.createBufferSource();
            const filter = ctx.createBiquadFilter();
            const gain = ctx.createGain();
            source.buffer = buffer;
            filter.type = 'bandpass';
            filter.frequency.setValueAtTime(filterFreq, start);
            filter.Q.setValueAtTime(0.9, start);
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(peak, start + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
            source.connect(filter).connect(gain).connect(ctx.destination);
            source.start(start);
            source.stop(start + duration + 0.025);
        }

        function playBidSound() {
            if (!soundReady || reduceMotion) return;
            const ctx = unlockAudio(false);
            if (!ctx) return;
            const now = ctx.currentTime;
            tone(ctx, 96, now, 0.11, 'sine', 0.055, 68);
            noiseBurst(ctx, now, 0.045, 0.032, 1800);
            noiseBurst(ctx, now + 0.018, 0.055, 0.018, 7200);
            tone(ctx, 740, now + 0.028, 0.075, 'square', 0.028, 1046);
            tone(ctx, 932, now + 0.085, 0.095, 'triangle', 0.05, 1244);
            tone(ctx, 1244, now + 0.15, 0.115, 'triangle', 0.056, 1661);
            tone(ctx, 1865, now + 0.235, 0.12, 'sine', 0.036, 2489);
            tone(ctx, 2489, now + 0.315, 0.08, 'sine', 0.024, 3136);
            noiseBurst(ctx, now + 0.24, 0.11, 0.022, 9400);
        }

        function playCloseSound() {
            if (!soundReady || reduceMotion) return;
            const ctx = unlockAudio(false);
            if (!ctx) return;
            const now = ctx.currentTime;
            tone(ctx, 130, now, 0.18, 'sine', 0.08, 80);
            noiseBurst(ctx, now + 0.03, 0.18, 0.07, 950);
            tone(ctx, 523, now + 0.12, 0.22, 'triangle', 0.06, 659);
            tone(ctx, 784, now + 0.25, 0.24, 'triangle', 0.07, 988);
            tone(ctx, 1175, now + 0.42, 0.32, 'sine', 0.052, 1568);
            noiseBurst(ctx, now + 0.26, 0.16, 0.045, 4200);
            noiseBurst(ctx, now + 0.48, 0.18, 0.05, 6200);
        }

        function restartClass(element, className, duration = 1000) {
            if (!element) return;
            element.classList.remove(className);
            void element.offsetWidth;
            element.classList.add(className);
            window.setTimeout(() => element.classList.remove(className), duration);
        }

        function setBidControlsEnabled(enabled) {
            if (incrementButton) incrementButton.disabled = !enabled;
            if (placeBidButton) placeBidButton.disabled = !enabled;
            if (closeBidButton) closeBidButton.disabled = !enabled;
        }

        function renderLeaderboard(teams = []) {
            if (!leaderboardList) return;
            topLeaderRow = null;
            topLeaderBid = null;
            topLeaderBar = null;
            leaderboardList.innerHTML = '';

            if (!teams.length) {
                leaderboardList.innerHTML = `
                    <div class="relative leaderboard-item rounded-2xl bg-white/5 border-l-4 border-white/20 group hover:bg-white/10 transition-colors">
                        <div class="flex justify-between items-center mb-xs">
                            <div class="flex items-center gap-sm">
                                <span class="font-bold text-white/40">--</span>
                                <span class="font-bold text-white">No teams created</span>
                            </div>
                            <span class="font-bold text-white">${formatShortRupee(0)}</span>
                        </div>
                        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-white/30 w-0"></div>
                        </div>
                    </div>`;
                return;
            }

            const accents = ['#00c853', '#0096ff', '#ff5252', 'rgba(255,255,255,0.32)', 'rgba(255,255,255,0.32)'];
            const maxAmount = Math.max(...teams.map((team) => Number(team.amount) || 0), 1);

            teams.forEach((team, index) => {
                const rank = index + 1;
                const amount = Number(team.amount) || 0;
                const accent = accents[index] || accents[4];
                const width = amount > 0 ? Math.max(10, Math.round((amount / maxAmount) * 100)) : 0;
                const row = document.createElement('div');
                row.className = 'relative leaderboard-item rounded-2xl bg-white/5 border-l-4 group hover:bg-white/10 transition-colors';
                row.style.borderLeftColor = accent;
                row.innerHTML = `
                    <div class="flex justify-between items-center mb-xs">
                        <div class="flex items-center gap-sm min-w-0">
                            <span class="font-bold" style="color:${accent}">${String(rank).padStart(2, '0')}</span>
                            <span class="font-bold text-white truncate">${escapeHtml(team.name)}</span>
                        </div>
                        <span class="leader-amount font-bold text-white">${formatShortRupee(amount)}</span>
                    </div>
                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="leader-bar h-full" style="width:${width}%; background:${accent}; box-shadow:0 0 10px ${accent};"></div>
                    </div>`;
                leaderboardList.appendChild(row);

                if (rank === 1) {
                    topLeaderRow = row;
                    topLeaderBid = row.querySelector('.leader-amount');
                    topLeaderBar = row.querySelector('.leader-bar');
                }
            });
        }

        function setNoPlayersState(message = 'No players available') {
            currentPlayer = null;
            currentBid = 0;
            bidClosed = true;
            playerImage?.classList.add('is-hidden');
            if (playerImage) {
                playerImage.removeAttribute('src');
                playerImage.alt = '';
            }
            noPlayerState?.classList.remove('is-hidden');
            if (noPlayerState) noPlayerState.textContent = message;
            if (playerName) playerName.textContent = message;
            if (playerRole) playerRole.textContent = 'ADD PLAYERS IN ADMIN';
            if (bidEl) bidEl.textContent = formatRupee(0);
            auctionPanel?.classList.remove('bid-closed');
            if (closeBidButton) closeBidButton.textContent = 'No Players';
            setBidControlsEnabled(false);
        }

        function loadPlayer(index) {
            const player = auctionPlayers[index];

            if (!player) {
                setNoPlayersState();
                return;
            }

            currentPlayerIndex = index;
            currentPlayer = player;
            currentBid = Number(player.currentBid ?? player.baseBid ?? 0);
            bidClosed = false;
            auctionPanel?.classList.remove('bid-closed');
            if (playerImage) {
                playerImage.src = player.image || '';
                playerImage.alt = `${player.name} action portrait`;
                playerImage.dataset.alt = `${player.name} action portrait`;
                playerImage.classList.toggle('is-hidden', !player.image);
            }
            noPlayerState?.classList.toggle('is-hidden', Boolean(player.image));

            if (noPlayerState && !player.image) {
                noPlayerState.textContent = 'No image available';
            }

            if (playerName) playerName.textContent = player.name;
            if (playerRole) playerRole.textContent = String(player.role || 'PLAYER | AUCTION').toUpperCase();
            if (bidEl) bidEl.textContent = formatRupee(currentBid);
            if (closeBidButton) closeBidButton.textContent = 'Close Bid';
            setBidControlsEnabled(true);
            restartClass(playerWrap, 'player-switching', 650);
        }

        async function loadAuctionState(resetPlayer = false) {
            try {
                const data = await apiGet('state');
                auctionPlayers = Array.isArray(data.players) ? data.players : [];
                renderLeaderboard(Array.isArray(data.leaderboard) ? data.leaderboard : []);

                if (!auctionPlayers.length) {
                    setNoPlayersState();
                    return;
                }

                if (resetPlayer || !currentPlayer || !auctionPlayers.some((player) => player.id === currentPlayer.id)) {
                    loadPlayer(0);
                }
            } catch (error) {
                console.error(error);
                setNoPlayersState('Database not ready');
            }
        }

        function showBidBurst(amount, source) {
            if (!auctionPanel) return;
            auctionPanel.querySelectorAll('.bid-burst').forEach((item) => item.remove());
            const burst = document.createElement('div');
            burst.className = `bid-burst ${source === 'auto' ? '' : 'manual'}`;
            burst.textContent = `${source === 'auto' ? 'Live raise' : 'Bid raised'} +₹${(amount / 1000).toFixed(0)}K`;
            auctionPanel.appendChild(burst);
            burst.addEventListener('animationend', () => burst.remove(), { once: true });
            window.setTimeout(() => {
                if (burst.isConnected) {
                    burst.remove();
                }
            }, 1600);
        }

        function showCloseBurst() {
            if (!auctionPanel) return;
            auctionPanel.querySelectorAll('.close-burst').forEach((item) => item.remove());
            const burst = document.createElement('div');
            burst.className = 'close-burst';
            burst.textContent = `Bid closed at ${formatRupee(currentBid)}`;
            auctionPanel.appendChild(burst);
            burst.addEventListener('animationend', () => burst.remove(), { once: true });
            window.setTimeout(() => {
                if (burst.isConnected) {
                    burst.remove();
                }
            }, 1900);
        }

        function launchPoppers() {
            if (!auctionPanel || reduceMotion) return;
            auctionPanel.querySelectorAll('.confetti-piece').forEach((item) => item.remove());
            const colors = ['#00c853', '#0096ff', '#ffda37', '#ff5252', '#ffffff'];
            const centerShift = [-190, -105, -40, 45, 120, 195];
            for (let i = 0; i < 58; i += 1) {
                const piece = document.createElement('span');
                const spread = centerShift[i % centerShift.length] + ((Math.random() - 0.5) * 120);
                const lift = -130 - Math.random() * 210;
                piece.className = 'confetti-piece';
                piece.style.setProperty('--tx', `${spread}px`);
                piece.style.setProperty('--ty', `${lift}px`);
                piece.style.setProperty('--r', `${Math.random() * 260}deg`);
                piece.style.setProperty('--confetti-color', colors[i % colors.length]);
                piece.style.animationDelay = `${Math.random() * 120}ms`;
                auctionPanel.appendChild(piece);
                window.setTimeout(() => piece.remove(), 1900);
            }
        }

        function animateNumber(start, end) {
            if (!bidEl) return;
            if (activeBidTimer) {
                clearInterval(activeBidTimer);
            }
            if (activeBidFallback) {
                clearTimeout(activeBidFallback);
            }

            if (reduceMotion) {
                bidEl.textContent = formatRupee(end);
                return;
            }

            const duration = 820;
            const startTime = Date.now();
            const easeOutExpo = (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);

            function tick() {
                const progress = Math.min(1, (Date.now() - startTime) / duration);
                const eased = easeOutExpo(progress);
                const value = start + ((end - start) * eased);
                bidEl.textContent = formatRupee(value);

                if (progress >= 1) {
                    bidEl.textContent = formatRupee(end);
                    clearInterval(activeBidTimer);
                    activeBidTimer = null;
                }
            }

            tick();
            activeBidTimer = setInterval(tick, 16);
            activeBidFallback = setTimeout(() => {
                bidEl.textContent = formatRupee(end);
                clearInterval(activeBidTimer);
                activeBidTimer = null;
            }, duration + 140);
        }

        function syncBid(playerId, nextBid, startBid, source) {
            apiPost('bid', { player_id: playerId, amount: nextBid, source })
                .then((data) => {
                    auctionPlayers = Array.isArray(data.players) ? data.players : auctionPlayers;
                    renderLeaderboard(Array.isArray(data.leaderboard) ? data.leaderboard : []);
                    const fresh = auctionPlayers.find((player) => player.id === playerId);
                    if (fresh) {
                        currentPlayer = fresh;
                    }
                })
                .catch((error) => {
                    console.error(error);
                    if (currentBid === nextBid) {
                        currentBid = startBid;
                        if (currentPlayer && currentPlayer.id === playerId) {
                            currentPlayer.currentBid = startBid;
                        }
                        if (bidEl) bidEl.textContent = formatRupee(startBid);
                    }
                });
        }

        function animateBidIncrease(amount, source = 'auto', triggerButton = null) {
            if (bidClosed || !currentPlayer || auctionPlayers.length === 0) return;
            const startBid = currentBid;
            const nextBid = currentBid + amount;
            currentBid = nextBid;
            currentPlayer.currentBid = nextBid;

            if (source !== 'auto') {
                unlockAudio(false);
            }

            animateNumber(startBid, nextBid);
            showBidBurst(amount, source);
            playBidSound();
            restartClass(bidEl, 'bid-counting', 950);
            restartClass(playerWrap, 'bid-surge', 950);
            restartClass(auctionPanel, 'bid-flash', 950);
            restartClass(topLeaderRow, 'bid-leader-pulse', 1050);

            if (triggerButton) {
                restartClass(triggerButton, 'bid-button-fired', 560);
            }

            if (topLeaderBid) {
                topLeaderBid.textContent = formatShortRupee(nextBid);
            }

            if (topLeaderBar) {
                const baseBid = Number(currentPlayer.baseBid || 0);
                const added = Math.min(88, Math.max(0, (nextBid - baseBid) / 120000));
                topLeaderBar.style.width = `${Math.min(100, 12 + added)}%`;
            }

            syncBid(currentPlayer.id, nextBid, startBid, source);
        }

        async function closeCurrentBid() {
            if (bidClosed || !currentPlayer || auctionPlayers.length === 0) return;
            unlockAudio(false);
            bidClosed = true;
            auctionPanel?.classList.add('bid-closed');
            showCloseBurst();
            launchPoppers();
            playCloseSound();
            restartClass(playerWrap, 'bid-surge', 1200);
            restartClass(topLeaderRow, 'bid-leader-pulse', 1200);
            if (closeBidButton) closeBidButton.textContent = 'Closed';
            setBidControlsEnabled(false);

            try {
                const closedPlayerId = currentPlayer.id;
                const data = await apiPost('close', { player_id: closedPlayerId, sold_amount: currentBid });
                renderLeaderboard(Array.isArray(data.leaderboard) ? data.leaderboard : []);
                window.setTimeout(() => {
                    auctionPlayers = Array.isArray(data.players) ? data.players : [];
                    loadPlayer(0);
                }, 3000);
            } catch (error) {
                console.error(error);
                bidClosed = false;
                auctionPanel?.classList.remove('bid-closed');
                if (closeBidButton) closeBidButton.textContent = 'Close Bid';
                setBidControlsEnabled(true);
            }
        }

        incrementButton?.addEventListener('click', () => {
            animateBidIncrease(100000, 'manual', incrementButton);
        });

        placeBidButton?.addEventListener('click', () => {
            animateBidIncrease(250000, 'place', placeBidButton);
        });

        closeBidButton?.addEventListener('click', closeCurrentBid);

        soundToggle?.addEventListener('click', () => {
            unlockAudio(true);
        });

        document.addEventListener('pointerdown', () => {
            unlockAudio(false);
        }, { once: true });

        window.smhaAuction = {
            raiseBid: animateBidIncrease,
            closeBid: () => closeBidButton?.click(),
            refresh: () => loadAuctionState(false)
        };

        setInterval(() => {
            if (!bidClosed && currentPlayer && auctionPlayers.length > 0 && Math.random() > 0.55) {
                const autoStep = (Math.floor(Math.random() * 4) + 1) * 50000;
                animateBidIncrease(autoStep, 'auto');
            }
        }, 4500);

        loadAuctionState(true);
    </script>
</body></html>
