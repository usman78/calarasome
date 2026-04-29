<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Calarasome | Dermatology booking workflow for pilot clinics</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <meta name="description" content="Calarasome is a dermatology scheduling and workflow platform designed to reduce no-shows, protect revenue, and make front-desk booking work easier for skin care practices.">
        <meta name="keywords" content="dermatology scheduling software, medical appointment scheduling, dermatology practice management, med spa booking software, no-show reduction system">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="https://calarasome.pragmatrics.com">
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://calarasome.pragmatrics.com">
        <meta property="og:title" content="Calarasome - Dermatology Scheduling, Reimagined">
        <meta property="og:description" content="The scheduling platform designed to reduce no-shows, streamline front-desk work, and keep dermatology schedules full.">
        <meta property="og:image" content="https://calarasome.pragmatrics.com/og-image.jpg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="Calarasome by Pragmatrics">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <!-- Styles -->
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

            :root {
            --ink: #0E1117;
            --ink-70: rgba(14,17,23,0.7);
            --ink-45: rgba(14,17,23,0.45);
            --ink-15: rgba(14,17,23,0.1);
            --white: #FFFFFF;
            --page: #F7F4EF;

            --obsidian: #14131A;
            --slate: #2B3149;
            --slate-mid: #3D4A6B;

            --blush: #E8D5CB;
            --rose: #C47F6A;
            --rose-deep: #9B5A47;
            --sand: #F2EBE4;
            --sand-dark: #E8DDD4;
            --sage: #8FA38C;
            --sage-deep: #4D6B4A;

            --gold: #C4A86A;
            --gold-light: #F0E4C6;
            }

            html { font-size: 16px; scroll-behavior: smooth; }

            body {
            font-family: 'Syne', sans-serif;
            background: var(--white);
            color: var(--ink);
            line-height: 1.6;
            overflow-x: hidden;
            }

            nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 0 64px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--ink-15);
            transition: all 0.3s ease;
            }

            .nav-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-style: italic;
            font-size: 24px;
            color: var(--rose-deep);
            letter-spacing: 0.01em;
            text-decoration: none;
            }

            .nav-logo span {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            font-style: normal;
            font-weight: 400;
            color: var(--ink-45);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-left: 10px;
            vertical-align: middle;
            }

            .nav-links {
            display: flex;
            align-items: center;
            gap: 36px;
            list-style: none;
            }

            .nav-links a {
            font-size: 13px;
            font-weight: 500;
            color: var(--ink-70);
            text-decoration: none;
            letter-spacing: 0.03em;
            transition: color 0.2s;
            }

            .nav-links a:hover { color: var(--rose-deep); }

            .nav-cta {
            background: var(--slate) !important;
            color: white !important;
            padding: 10px 22px;
            font-size: 13px !important;
            font-weight: 600 !important;
            letter-spacing: 0.04em;
            text-decoration: none;
            transition: background 0.2s !important;
            }

            .nav-cta:hover { background: var(--slate-mid) !important; color: white !important; }

            .hero {
            min-height: 100vh;
            padding-top: 68px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            position: relative;
            overflow: hidden;
            }

            .hero-left {
            padding: 100px 64px 80px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 32px;
            position: relative;
            z-index: 2;
            }

            .hero-eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            }

            .hero-eyebrow-pill {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--rose-deep);
            background: var(--sand);
            padding: 6px 14px;
            border: 1px solid var(--blush);
            }

            .hero-eyebrow-line {
            width: 40px;
            height: 1px;
            background: var(--rose);
            }

            .hero-h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(44px, 5vw, 72px);
            line-height: 1.05;
            color: var(--ink);
            letter-spacing: -0.02em;
            }

            .hero-h1 em {
            font-style: italic;
            color: var(--rose-deep);
            }

            .hero-h1 strong {
            font-weight: 600;
            display: block;
            }

            .hero-sub {
            font-size: 16px;
            color: var(--ink-70);
            max-width: 440px;
            line-height: 1.75;
            font-weight: 400;
            }

            .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            }

            .btn-primary-hero {
            background: var(--rose-deep);
            color: white;
            padding: 16px 32px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.05em;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            display: inline-block;
            }

            .btn-primary-hero:hover {
            background: #7D4636;
            transform: translateY(-1px);
            }

            .btn-secondary-hero {
            color: var(--ink-70);
            font-family: 'Syne', sans-serif;
            font-weight: 500;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
            }

            .btn-secondary-hero:hover { color: var(--rose-deep); }

            .btn-secondary-hero::after {
            content: '\2192';
            transition: transform 0.2s;
            }

            .btn-secondary-hero:hover::after { transform: translateX(4px); }

            .hero-trust {
            display: flex;
            align-items: center;
            gap: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--ink-15);
            }

            .trust-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            }

            .trust-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1;
            }

            .trust-label {
            font-size: 11px;
            color: var(--ink-45);
            letter-spacing: 0.05em;
            }

            .trust-divider {
            width: 1px;
            height: 36px;
            background: var(--ink-15);
            }

            .hero-right {
            background: var(--sand);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            }

            .hero-right::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196,127,106,0.2) 0%, transparent 70%);
            pointer-events: none;
            }

            .mockup-card {
            background: white;
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--ink-15);
            box-shadow: 0 24px 64px rgba(0,0,0,0.08), 0 4px 16px rgba(0,0,0,0.04);
            position: relative;
            z-index: 1;
            animation: floatCard 4s ease-in-out infinite;
            }

            @keyframes floatCard {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            }

            .mockup-header {
            background: var(--slate);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            }

            .mockup-header-title {
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.05em;
            }

            .mockup-header-date {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.08em;
            }

            .mockup-body { padding: 20px; }

            .mockup-step-indicator {
            display: flex;
            gap: 6px;
            margin-bottom: 20px;
            align-items: center;
            }

            .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--blush);
            }

            .step-dot.active { background: var(--rose); width: 24px; border-radius: 4px; }
            .step-dot.done { background: var(--sage); }

            .mockup-step-label {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            color: var(--rose);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-left: auto;
            }

            .mockup-section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 4px;
            }

            .mockup-section-sub {
            font-size: 12px;
            color: var(--ink-45);
            margin-bottom: 20px;
            }

            .provider-list { display: flex; flex-direction: column; gap: 10px; }

            .provider-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--ink-15);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            }

            .provider-row.selected {
            border-color: var(--rose);
            background: var(--sand);
            }

            .provider-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
            }

            .pa-teal { background: #E0F2EE; color: #0D6B52; }
            .pa-rose { background: var(--sand); color: var(--rose-deep); }
            .pa-slate { background: #E8EBF2; color: var(--slate); }

            .provider-info { flex: 1; }

            .provider-name {
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 2px;
            }

            .provider-spec {
            font-size: 11px;
            color: var(--ink-45);
            }

            .provider-badge {
            font-family: 'DM Mono', monospace;
            font-size: 9px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 8px;
            }

            .badge-open { background: #EAF6EF; color: #1A6B38; border: 1px solid #B6E0C5; }
            .badge-selected { background: var(--sand); color: var(--rose-deep); border: 1px solid var(--blush); }

            .mockup-any-available {
            margin-top: 10px;
            padding: 11px 14px;
            border: 1.5px dashed var(--blush);
            display: flex;
            align-items: center;
            justify-content: space-between;
            }

            .any-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--rose-deep);
            }

            .any-sub {
            font-size: 10px;
            color: var(--ink-45);
            }

            .mockup-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--ink-15);
            display: flex;
            align-items: center;
            gap: 12px;
            }

            .mockup-reservation {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            color: var(--rose);
            letter-spacing: 0.06em;
            flex: 1;
            }

            .reservation-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--rose);
            animation: pulse 1.5s ease-in-out infinite;
            }

            @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
            }

            .mockup-next-btn {
            background: var(--rose-deep);
            color: white;
            padding: 10px 20px;
            font-family: 'Syne', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            border: none;
            }

            /* Floating alert cards */
            .float-card {
            position: absolute;
            background: white;
            border: 1px solid var(--ink-15);
            padding: 12px 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            max-width: 200px;
            z-index: 2;
            }

            .float-card.top-right {
            top: 80px; right: 24px;
            animation: floatAlt 4.5s ease-in-out infinite 1s;
            }

            .float-card.bottom-left {
            bottom: 100px; left: 24px;
            animation: floatAlt 3.8s ease-in-out infinite 0.5s;
            }

            @keyframes floatAlt {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
            }

            .float-icon {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            margin-bottom: 8px;
            }

            .float-icon.green { background: #EAF6EF; }
            .float-icon.gold  { background: var(--gold-light); }

            .float-title {
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 2px;
            }

            .float-sub {
            font-size: 10px;
            color: var(--ink-45);
            line-height: 1.5;
            }

            section { padding: 120px 80px; }

            .section-eyebrow {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--rose);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            }

            .section-eyebrow::before {
            content: '';
            display: block;
            width: 28px; height: 1px;
            background: var(--rose);
            }

            .section-h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(36px, 4vw, 56px);
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: var(--ink);
            }

            .section-h2 em { font-style: italic; color: var(--rose-deep); }

            .section-sub {
            font-size: 16px;
            color: var(--ink-70);
            max-width: 520px;
            line-height: 1.75;
            margin-top: 16px;
            font-weight: 400;
            }


            .pain-section {
            background: var(--obsidian);
            padding: 120px 80px;
            }

            .pain-section .section-eyebrow { color: var(--rose); }
            .pain-section .section-eyebrow::before { background: var(--rose); }
            .pain-section .section-h2 { color: white; }
            .pain-section .section-sub { color: rgba(255,255,255,0.5); }

            .pain-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            margin-top: 60px;
            background: rgba(255,255,255,0.08);
            }

            .pain-item {
            background: var(--obsidian);
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: background 0.3s;
            }

            .pain-item:hover { background: #1E1D28; }

            .pain-number {
            font-family: 'Cormorant Garamond', serif;
            font-size: 56px;
            font-weight: 300;
            color: rgba(255,255,255,0.08);
            line-height: 1;
            letter-spacing: -0.03em;
            }

            .pain-icon-row {
            display: flex;
            align-items: center;
            gap: 12px;
            }

            .pain-icon {
                width: 40px; height: 40px;
                background: rgba(196,127,106,0.12);
                border: 1px solid rgba(196,127,106,0.2);
                display: flex; align-items: center; justify-content: center;
                font-size: 16px;
                flex-shrink: 0;
                padding: 5px;
            }

            .pain-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            }

            .pain-body {
            font-size: 14px;
            color: rgba(255,255,255,0.45);
            line-height: 1.7;
            }

            .pain-stat {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-style: italic;
            color: var(--rose);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 16px;
            margin-top: auto;
            }


            .features-section { background: var(--page); }

            .features-intro {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: end;
            margin-bottom: 80px;
            }

            .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2px;
            background: var(--ink-15);
            }

            .feature-block {
            background: var(--white);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: background 0.2s;
            }

            .feature-block:hover { background: var(--sand); }

            .feature-block.dark {
            background: var(--slate);
            }

            .feature-block.dark:hover { background: var(--slate-mid); }

            .feature-tag {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 4px 10px;
            border: 1px solid;
            display: inline-block;
            width: fit-content;
            }

            .feature-tag.light { color: var(--rose); border-color: var(--blush); background: var(--sand); }
            .feature-tag.dark-tag { color: rgba(255,255,255,0.5); border-color: rgba(255,255,255,0.15); }

            .feature-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            line-height: 1.2;
            color: var(--ink);
            }

            .feature-block.dark .feature-title { color: white; }

            .feature-body {
            font-size: 14px;
            color: var(--ink-70);
            line-height: 1.75;
            }

            .feature-block.dark .feature-body { color: rgba(255,255,255,0.5); }

            .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            }

            .feature-list li {
            font-size: 13px;
            color: var(--ink-70);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
            }

            .feature-block.dark .feature-list li { color: rgba(255,255,255,0.5); }

            .feature-list li::before {
            content: '—';
            color: var(--rose);
            flex-shrink: 0;
            font-size: 12px;
            margin-top: 1px;
            }

            .feature-block.dark .feature-list li::before { color: var(--blush); }


            .how-section { background: var(--white); }

            .steps-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 64px;
            position: relative;
            }

            .steps-container::before {
            content: '';
            position: absolute;
            top: 28px; left: calc(3% + 10px);
            width: calc(66.666% - 40px);
            height: 1px;
            background: linear-gradient(90deg, var(--blush), var(--rose), var(--blush));
            }

            .step-item {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            }

            .step-num-wrap {
            width: 56px; height: 56px;
            background: white;
            border: 1px solid var(--ink-15);
            display: flex; align-items: center; justify-content: center;
            position: relative;
            z-index: 1;
            }

            .step-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 600;
            color: var(--rose-deep);
            }

            .step-title {
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            }

            .step-body {
            font-size: 14px;
            color: var(--ink-70);
            line-height: 1.7;
            }


            .trust-strip {
            background: var(--sand);
            padding: 48px 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            border-top: 1px solid var(--ink-15);
            border-bottom: 1px solid var(--ink-15);
            }

            .trust-strip-item {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            }

            .trust-strip-icon {
                width: 40px; height: 40px;
                background: white;
                border: 1px solid var(--ink-15);
                display: flex; align-items: center; justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
                padding: 5px;
            }

            .trust-strip-text strong {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            display: block;
            margin-bottom: 2px;
            }

            .trust-strip-text span {
            font-size: 12px;
            color: var(--ink-45);
            }

            .trust-strip-divider {
            width: 1px; height: 48px;
            background: var(--ink-15);
            }


            .testimonials-section { background: var(--page); }

            .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 60px;
            }

            .testimonial-card {
            background: white;
            border: 1px solid var(--ink-15);
            padding: 36px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            }

            .testimonial-stars {
            display: flex;
            gap: 3px;
            }

            .star { width: 14px; height: 14px; background: var(--gold); clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%); }

            .testimonial-quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-style: italic;
            color: var(--ink);
            line-height: 1.5;
            flex: 1;
            }

            .testimonial-author {
            border-top: 1px solid var(--ink-15);
            padding-top: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            }

            .author-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
            }

            .av-1 { background: var(--sand); color: var(--rose-deep); }
            .av-2 { background: #E8EBF2; color: var(--slate); }
            .av-3 { background: #E0F2EE; color: #0D6B52; }

            .author-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            }

            .author-role {
            font-size: 11px;
            color: var(--ink-45);
            margin-top: 1px;
            }


            .pricing-section { background: var(--white); }

            .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 64px;
            align-items: start;
            }

            .pricing-card {
            border: 1px solid var(--ink-15);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            }

            .pricing-card.featured {
            background: var(--slate);
            border-color: var(--slate);
            position: relative;
            margin-top: -16px;
            padding-top: 56px;
            }

            .featured-badge {
            position: absolute;
            top: 20px; left: 50%;
            transform: translateX(-50%);
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--rose-deep);
            background: var(--sand);
            padding: 5px 14px;
            white-space: nowrap;
            }

            .pricing-plan {
            font-family: 'DM Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--rose);
            }

            .pricing-card.featured .pricing-plan { color: var(--blush); }

            .pricing-price {
            display: flex;
            align-items: baseline;
            gap: 4px;
            }

            .price-amount {
            font-family: 'Cormorant Garamond', serif;
            font-size: 52px;
            font-weight: 600;
            line-height: 1;
            color: var(--ink);
            }

            .pricing-card.featured .price-amount { color: white; }

            .price-period {
            font-size: 14px;
            color: var(--ink-45);
            }

            .pricing-card.featured .price-period { color: rgba(255,255,255,0.4); }

            .price-currency {
            font-size: 24px;
            font-weight: 400;
            color: var(--ink-70);
            align-self: flex-start;
            margin-top: 8px;
            }

            .pricing-card.featured .price-currency { color: rgba(255,255,255,0.6); }

            .pricing-desc {
            font-size: 13px;
            color: var(--ink-70);
            line-height: 1.65;
            }

            .pricing-card.featured .pricing-desc { color: rgba(255,255,255,0.5); }

            .pricing-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid var(--ink-15);
            }

            .pricing-card.featured .pricing-features { border-color: rgba(255,255,255,0.1); }

            .pricing-features li {
            font-size: 13px;
            color: var(--ink-70);
            display: flex;
            gap: 10px;
            align-items: flex-start;
            line-height: 1.5;
            }

            .pricing-card.featured .pricing-features li { color: rgba(255,255,255,0.6); }

            .pricing-features li::before {
            content: '\2713';
            color: var(--sage);
            font-weight: 700;
            flex-shrink: 0;
            }

            .btn-pricing {
            padding: 14px 24px;
            font-family: 'Syne', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-decoration: none;
            text-align: center;
            display: block;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            }

            .btn-pricing-outline {
            border: 1.5px solid var(--ink-15);
            color: var(--ink);
            background: transparent;
            }

            .btn-pricing-outline:hover { border-color: var(--rose-deep); color: var(--rose-deep); }

            .btn-pricing-filled {
            background: var(--rose-deep);
            color: white;
            }

            .btn-pricing-filled:hover { background: #7D4636; }

            .btn-pricing-light {
            background: white;
            color: var(--slate);
            }

            .btn-pricing-light:hover { background: var(--sand); }

            .cta-section {
            background: var(--obsidian);
            padding: 140px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            }

            .cta-section::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 700px; height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196,127,106,0.1) 0%, transparent 70%);
            pointer-events: none;
            }

            .cta-section .section-eyebrow { justify-content: center; }
            .cta-section .section-eyebrow::before { display: none; }
            .cta-section .section-h2 { color: white; margin: 0 auto 20px; max-width: 600px; }
            .cta-section .section-sub { color: rgba(255,255,255,0.5); margin: 0 auto 48px; text-align: center; }

            .cta-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            }

            .btn-cta-primary {
            background: var(--rose-deep);
            color: white;
            padding: 18px 40px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.04em;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            }

            .btn-cta-primary:hover { background: #7D4636; transform: translateY(-1px); }

            .btn-cta-ghost {
            color: rgba(255,255,255,0.6);
            padding: 18px 40px;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.04em;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.15);
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            }

            .btn-cta-ghost:hover { color: white; border-color: rgba(255,255,255,0.35); }

            .cta-note {
            font-family: 'DM Mono', monospace;
            font-size: 11px;
            color: rgba(255,255,255,0.25);
            letter-spacing: 0.08em;
            margin-top: 24px;
            }

            footer {
            background: #0B0A10;
            padding: 80px;
            }

            .footer-top {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 60px;
            padding-bottom: 60px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            }

            .footer-brand-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-style: italic;
            font-size: 28px;
            color: var(--blush);
            letter-spacing: 0.01em;
            display: block;
            margin-bottom: 16px;
            }

            .footer-brand-desc {
            font-size: 13px;
            color: rgba(255,255,255,0.3);
            line-height: 1.75;
            max-width: 240px;
            }

            .footer-col-title {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            margin-bottom: 20px;
            }

            .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            }

            .footer-links a {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            transition: color 0.2s;
            }

            .footer-links a:hover { color: var(--blush); }

            .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 32px;
            }

            .footer-bottom-left {
            display: flex;
            flex-direction: column;
            gap: 6px;
            }

            .footer-bottom-copy {
            font-size: 12px;
            color: rgba(255,255,255,0.2);
            }

            .footer-bottom-by {
            font-family: 'DM Mono', monospace;
            font-size: 10px;
            color: rgba(255,255,255,0.15);
            letter-spacing: 0.1em;
            }

            .footer-bottom-right {
            display: flex;
            gap: 24px;
            }

            .footer-bottom-right a {
            font-size: 12px;
            color: rgba(255,255,255,0.2);
            text-decoration: none;
            transition: color 0.2s;
            }

            .footer-bottom-right a:hover { color: rgba(255,255,255,0.5); }

            .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s ease, transform 0.6s ease;
            }

            .reveal.visible {
            opacity: 1;
            transform: translateY(0);
            }

            @media (max-width: 900px) {
            nav { padding: 0 24px; }
            .nav-links { display: none; }
            .hero { grid-template-columns: 1fr; min-height: auto; }
            .hero-left { padding: 60px 24px 40px; }
            .hero-right { min-height: 400px; padding: 40px 24px; }
            section { padding: 80px 24px; }
            .pain-section { padding: 80px 24px; }
            .pain-grid { grid-template-columns: 1fr; }
            .features-intro { grid-template-columns: 1fr; gap: 24px; }
            .features-grid { grid-template-columns: 1fr; }
            .steps-container { grid-template-columns: 1fr; }
            .steps-container::before { display: none; }
            .trust-strip { flex-direction: column; padding: 40px 24px; }
            .trust-strip-divider { width: 40px; height: 1px; }
            .testimonials-grid { grid-template-columns: 1fr; }
            .pricing-grid { grid-template-columns: 1fr; }
            .pricing-card.featured { margin-top: 0; }
            .cta-section { padding: 80px 24px; }
            footer { padding: 60px 24px; }
            .footer-top { grid-template-columns: 1fr; gap: 40px; }
            .footer-bottom { flex-direction: column; gap: 16px; text-align: center; }
            }
        </style>
        <script type="application/ld+json">
            {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "name": "Calarasome",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "description": "Dermatology appointment scheduling and workflow software designed to reduce no-shows, streamline deposits, and improve front-desk operations.",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "USD"
            },
            "publisher": {
                "@@type": "Organization",
                "name": "Pragmatrics",
                "url": "https://pragmatrics.com"
            }
            }
        </script>
    </head>
    <body>
        <!-- NAV -->
        <nav>
            <a class="nav-logo" href="#">Calarasome <span>by Pragmatrics</span></a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How it works</a></li>
                <li><a href="#pricing">Pilot</a></li>
                <li><a href="{{ route('login') }}" class="nav-cta">Sign In</a></li>
            </ul>
        </nav>

        <!-- HERO -->
        <div class="hero">
            <div class="hero-left">
                <div class="hero-eyebrow">
                <span class="hero-eyebrow-pill">Dermatology Booking Workflow</span>
                <span class="hero-eyebrow-line"></span>
                </div>

                <h1 class="hero-h1">
                Reduce no-shows.<br>
                Save front-desk<br>
                <em>time every day</em><br>
                <strong>and keep more of<br>your schedule full.</strong>
                </h1>

                <p class="hero-sub">Calarasome helps dermatology and aesthetic clinics automate booking, deposits, cancellations, waitlists, and insurance intake so fewer appointments fall through the cracks.</p>

                <div class="hero-actions">
                <a href="https://calendar.app.google/AvBTHifK8zDddBer6" class="btn-primary-hero" target="_blank">Request a Demo</a>
                <a href="#how-it-works" class="btn-secondary-hero">See how it works</a>
                </div>

                <div class="hero-trust">
                <div class="trust-item">
                    <span class="trust-num">Privacy-first</span>
                    <span class="trust-label">Patient communication workflows</span>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <span class="trust-num">6-step</span>
                    <span class="trust-label">Guided booking</span>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <span class="trust-num">Waitlist-ready</span>
                    <span class="trust-label">Cancellation recovery</span>
                </div>
                </div>
            </div>

            <div class="hero-right">
                <!-- Floating alert cards -->
                <div class="float-card top-right">
                <div class="float-icon green">&#10003;</div>
                <div class="float-title">Insurance reviewed</div>
                <div class="float-sub">Coverage checked for today's 2:30 PM visit</div>
                </div>

                <div class="float-card bottom-left">
                <div class="float-icon gold">&#9200;</div>
                <div class="float-title">Cancellation recovered</div>
                <div class="float-sub">Offer sent to the best-fit waitlist patient</div>
                </div>

                <!-- Main mockup -->
                <div class="mockup-card">
                <div class="mockup-header">
                    <span class="mockup-header-title">BOOK YOUR APPOINTMENT</span>
                    <span class="mockup-header-date">April 28, 2026</span>
                </div>
                <div class="mockup-body">
                    <div class="mockup-step-indicator">
                    <div class="step-dot done"></div>
                    <div class="step-dot done"></div>
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                    <span class="mockup-step-label">Step 3 of 6</span>
                    </div>
                    <div class="mockup-section-title">Choose your provider</div>
                    <div class="mockup-section-sub">Acne Treatment · New patient</div>

                    <div class="provider-list">
                    <div class="provider-row selected">
                        <div class="provider-avatar pa-rose">LM</div>
                        <div class="provider-info">
                        <div class="provider-name">Dr. Lena Marsh</div>
                        <div class="provider-spec">Cosmetic & Medical Derm</div>
                        </div>
                        <span class="badge badge-selected">Selected</span>
                    </div>
                    <div class="provider-row">
                        <div class="provider-avatar pa-teal">SK</div>
                        <div class="provider-info">
                        <div class="provider-name">Dr. Sam Kim</div>
                        <div class="provider-spec">Medical Dermatology</div>
                        </div>
                        <span class="badge badge-open">Available</span>
                    </div>
                    <div class="mockup-any-available">
                        <div>
                        <div class="any-label">Any Available Provider</div>
                        <div class="any-sub">Auto-assigned by availability</div>
                        </div>
                        <span style="color: var(--rose); font-size: 18px;">&rarr;</span>
                    </div>
                    </div>
                </div>
                <div class="mockup-footer">
                    <div class="mockup-reservation">
                    <div class="reservation-dot"></div>
                    Slot held · 9:47 remaining
                    </div>
                    <button class="mockup-next-btn">Continue &rarr;</button>
                </div>
                </div>
            </div>
        </div>

        <!-- PAIN SECTION -->
        <section class="pain-section" id="pain">
            <div class="section-eyebrow">The operational pain points</div>
            <h2 class="section-h2">Three workflow problems<br><em>that quietly drain revenue.</em></h2>
            <p class="section-sub">Most practices do not lose money because care is poor. They lose it because scheduling, cancellations, and follow-up still depend on too much manual work.</p>

            <div class="pain-grid">
                <div class="pain-item reveal">
                <div class="pain-number">01</div>
                <div class="pain-icon-row">
                    <div class="pain-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                        </svg>
                    </div>
                    <div class="pain-title">No-shows with no recourse</div>
                </div>
                <p class="pain-body">A patient misses their appointment. You cannot charge them. The slot is gone, the revenue is gone, and there is nothing you can do because no valid card or policy was in place ahead of time.</p>
                <p class="pain-stat">Missed appointments turn directly into lost chair time and lost revenue.</p>
                </div>
                <div class="pain-item reveal">
                <div class="pain-number">02</div>
                <div class="pain-icon-row">
                    <div class="pain-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div class="pain-title">Insurance verified too late</div>
                </div>
                <p class="pain-body">Patient arrives. Insurance is denied. The appointment can't proceed. Your clinic absorbs the cost of the time, the staff prep, and the patient's frustration.</p>
                <p class="pain-stat">Late insurance surprises create rework, delays, and avoidable frustration.</p>
                </div>
                <div class="pain-item reveal">
                <div class="pain-number">03</div>
                <div class="pain-icon-row">
                    <div class="pain-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
                        </svg>
                    </div>
                    <div class="pain-title">Waitlists managed manually</div>
                </div>
                <p class="pain-body">Someone cancels. You call down a list. The slot sits empty while staff leave voicemails, wait for replies, and restart the process manually.</p>
                <p class="pain-stat">Manual waitlists are slow, inconsistent, and hard to keep fair.</p>
                </div>
            </div>
        </section>

        <!-- TRUST STRIP -->
        <div class="trust-strip">
            <div class="trust-strip-item">
                <div class="trust-strip-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="black" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <div class="trust-strip-text">
                <strong>Privacy-conscious workflows</strong>
                <span>Consent-aware communication paths with secure access when needed</span>
                </div>
            </div>
            <div class="trust-strip-divider"></div>
            <div class="trust-strip-item">
                <div class="trust-strip-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="black" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                </div>
                <div class="trust-strip-text">
                <strong>Stripe-Powered Deposits</strong>
                <span>Auth holds instead of immediate charges for near-term bookings</span>
                </div>
            </div>
            <div class="trust-strip-divider"></div>
            <div class="trust-strip-item">
                <div class="trust-strip-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="black" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.893 13.393-1.135-1.135a2.252 2.252 0 0 1-.421-.585l-1.08-2.16a.414.414 0 0 0-.663-.107.827.827 0 0 1-.812.21l-1.273-.363a.89.89 0 0 0-.738 1.595l.587.39c.59.395.674 1.23.172 1.732l-.2.2c-.212.212-.33.498-.33.796v.41c0 .409-.11.809-.32 1.158l-1.315 2.191a2.11 2.11 0 0 1-1.81 1.025 1.055 1.055 0 0 1-1.055-1.055v-1.172c0-.92-.56-1.747-1.414-2.089l-.655-.261a2.25 2.25 0 0 1-1.383-2.46l.007-.042a2.25 2.25 0 0 1 .29-.787l.09-.15a2.25 2.25 0 0 1 2.37-1.048l1.178.236a1.125 1.125 0 0 0 1.302-.795l.208-.73a1.125 1.125 0 0 0-.578-1.315l-.665-.332-.091.091a2.25 2.25 0 0 1-1.591.659h-.18c-.249 0-.487.1-.662.274a.931.931 0 0 1-1.458-1.137l1.411-2.353a2.25 2.25 0 0 0 .286-.76m11.928 9.869A9 9 0 0 0 8.965 3.525m11.928 9.868A9 9 0 1 1 8.965 3.525" />
                    </svg>
                </div>
                <div class="trust-strip-text">
                <strong>Timezone-aware scheduling</strong>
                <span>Timezone-aware, Luxon-powered, UTC-stored</span>
                </div>
            </div>
            <div class="trust-strip-divider"></div>
            <div class="trust-strip-item">
                <div class="trust-strip-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="black" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                    </svg>
                </div>
                <div class="trust-strip-text">
                <strong>Protected slot reservation</strong>
                <span>Short reservation window to reduce booking collisions</span>
                </div>
            </div>
        </div>

        <!-- FEATURES -->
        <section class="features-section" id="features">
            <div class="features-intro">
                <div>
                <div class="section-eyebrow">Built around clinic outcomes</div>
                <h2 class="section-h2">The workflows that help clinics<br><em>run with less friction.</em></h2>
                </div>
                <p class="section-sub">Calarasome is focused on the parts of scheduling that create the most operational drag: missed appointments, cancellation gaps, deposit friction, insurance follow-up, and front-desk overload.</p>
            </div>

            <div class="features-grid">
                <div class="feature-block reveal">
                <span class="feature-tag light">Payments</span>
                <h3 class="feature-title">Deposit handling that protects revenue</h3>
                <p class="feature-body">For eligible appointments, Calarasome can use authorization holds instead of immediate charges. That gives clinics policy enforcement without creating unnecessary refund cleanup when patients cancel on time.</p>
                <ul class="feature-list">
                    <li>Auth holds for appointments within 7 days</li>
                    <li>Card-save only for far-future bookings</li>
                    <li>Automatic T-7 capture job runs hourly</li>
                    <li>Failed auth triggers 48-hour grace period</li>
                </ul>
                </div>

                <div class="feature-block dark reveal">
                <span class="feature-tag dark-tag">Waitlist</span>
                <h3 class="feature-title">Waitlists that actually fill slots</h3>
                <p class="feature-body">When a slot opens, the system helps your team offer it to the most relevant waitlist patients first, instead of restarting the scheduling process from scratch.</p>
                <ul class="feature-list">
                    <li>Automated tier-based staggered dispatch</li>
                    <li>Urgent &rarr; High &rarr; Standard dispatch windows</li>
                    <li>One-click slot claim cancels all pending rounds</li>
                    <li>Manual override always available</li>
                </ul>
                </div>

                <div class="feature-block reveal">
                <span class="feature-tag light">Insurance</span>
                <h3 class="feature-title">Insurance follow-up before the visit</h3>
                <p class="feature-body">Insurance-heavy bookings can be routed into an admin queue so your team sees what needs follow-up before the patient arrives, not at the front desk.</p>
                <ul class="feature-list">
                    <li>Real-time alert for critical & same-day bookings</li>
                    <li>Admin queue sorted by urgency level</li>
                    <li>One-click verify or mark-failed with patient notify</li>
                    <li>Secure 48-hour insurance update link emailed</li>
                </ul>
                </div>

                <div class="feature-block reveal">
                <span class="feature-tag light">Privacy</span>
                <h3 class="feature-title">Communication paths matched to patient consent</h3>
                <p class="feature-body">Patients choose how they want to receive communication. Private flows keep sensitive details out of email and move patients into a secure verification page instead.</p>
                <ul class="feature-list">
                    <li>PHI-safe de-identified email path</li>
                    <li>Secure token page with DOB verification</li>
                    <li>Lockout after 3 failed attempts</li>
                    <li>Consent stored with timestamp and IP</li>
                </ul>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="how-section" id="how-it-works">
            <div class="section-eyebrow">Pilot-friendly rollout</div>
            <h2 class="section-h2">Set up your clinic,<br><em>launch a pilot,</em><br>and learn from real bookings.</h2>

            <div class="steps-container">
                <div class="step-item reveal">
                <div class="step-num-wrap"><span class="step-num">1</span></div>
                <h3 class="step-title">Set up your clinic</h3>
                <p class="step-body">Add your providers, configure schedules, and define appointment types and deposit rules. The goal is a practical pilot setup, not a heavy implementation project.</p>
                </div>
                <div class="step-item reveal">
                <div class="step-num-wrap"><span class="step-num">2</span></div>
                <h3 class="step-title">Share your booking link</h3>
                <p class="step-body">Share your booking link anywhere patients already discover your practice - your website, Google Business profile, or marketing pages - and let them book through a guided workflow.</p>
                </div>
                <div class="step-item reveal">
                <div class="step-num-wrap"><span class="step-num">3</span></div>
                <h3 class="step-title">Run your dashboard</h3>
                <p class="step-body">Use the admin dashboard to monitor deposits, insurance follow-up, patient alerts, waitlist recovery, and booking activity as you gather feedback from the trial.</p>
                </div>
            </div>
        </section>

        <!-- TESTIMONIALS -->
        <section class="testimonials-section">
            <div class="section-eyebrow">What this helps solve</div>
            <h2 class="section-h2">What practice managers and owners<br><em>usually want to improve first.</em></h2>

            <div class="testimonials-grid">
                <div class="testimonial-card reveal">
                <div class="testimonial-stars">
                    <div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div>
                </div>
                <p class="testimonial-quote">Reduce lost revenue from late cancellations and no-shows by giving the clinic a clearer deposit and follow-up workflow.</p>
                <div class="testimonial-author">
                    <div class="author-avatar av-1">RP</div>
                    <div>
                    <div class="author-name">Revenue protection</div>
                    <div class="author-role">Deposits, cancellation rules, no-show handling</div>
                    </div>
                </div>
                </div>

                <div class="testimonial-card reveal">
                <div class="testimonial-stars">
                    <div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div>
                </div>
                <p class="testimonial-quote">Cut down on front-desk interruptions by collecting booking details earlier and routing exceptions into an admin queue.</p>
                <div class="testimonial-author">
                    <div class="author-avatar av-2">SE</div>
                    <div>
                    <div class="author-name">Staff efficiency</div>
                    <div class="author-role">Fewer phone calls, fewer manual follow-ups</div>
                    </div>
                </div>
                </div>

                <div class="testimonial-card reveal">
                <div class="testimonial-stars">
                    <div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div><div class="star"></div>
                </div>
                <p class="testimonial-quote">Refill canceled slots faster with a structured waitlist flow instead of starting outreach from scratch every time.</p>
                <div class="testimonial-author">
                    <div class="author-avatar av-3">SR</div>
                    <div>
                    <div class="author-name">Schedule recovery</div>
                    <div class="author-role">Waitlist offers, open-slot recovery, clearer patient communication</div>
                    </div>
                </div>
                </div>
            </div>
        </section>

        <!-- PRICING -->
        <section class="pricing-section" id="pricing">
            <div style="text-align: center; margin-bottom: 0;">
                <div class="section-eyebrow" style="justify-content: center;"><span style="display:none">&nbsp;</span>Pilot program</div>
                <h2 class="section-h2" style="text-align: center;">Start with a guided trial.<br><em>Refine it with real feedback.</em></h2>
                <p class="section-sub" style="margin: 16px auto 0; text-align: center;">The current focus is a small number of pilot clinics. The goal is to prove operational value with real bookings before locking in long-term pricing.</p>
            </div>

            <div class="pricing-grid">
                <div class="pricing-card reveal">
                <div class="pricing-plan">Free pilot</div>
                <div class="pricing-price">
                    <span class="price-currency"></span>
                    <span class="price-amount">30-60</span>
                    <span class="price-period"> days</span>
                </div>
                <p class="pricing-desc">For single-clinic pilots that want to test booking, deposits, and waitlist recovery with real patients.</p>
                <ul class="pricing-features">
                    <li>Up to 1 clinic in pilot scope</li>
                    <li>Guided onboarding for your core workflow</li>
                    <li>Privacy-conscious patient communication flows</li>
                    <li>Deposit and cancellation policy setup</li>
                    <li>Waitlist and open-slot recovery</li>
                    <li>Founder-led support during the trial</li>
                </ul>
                <a href="https://calendar.app.google/AvBTHifK8zDddBer6" class="btn-pricing btn-pricing-outline" target="_blank">Apply for a pilot</a>
                </div>

                <div class="pricing-card featured reveal">
                <div class="featured-badge">Recommended</div>
                <div class="pricing-plan">Pilot plus onboarding</div>
                <div class="pricing-price">
                    <span class="price-currency" style="color:rgba(255,255,255,0.5);"></span>
                    <span class="price-amount">Guided</span>
                    <span class="price-period"> rollout</span>
                </div>
                <p class="pricing-desc">For practices that want help configuring a fuller workflow across multiple providers during the trial period.</p>
                <ul class="pricing-features">
                    <li>Multi-provider setup support</li>
                    <li>Booking workflow review with your team</li>
                    <li>Waitlist, cancellation, and payment policy tuning</li>
                    <li>Insurance queue and exception workflow setup</li>
                    <li>Admin training for daily operations</li>
                    <li>Feedback loop during the pilot</li>
                    <li>Priority support while testing the product</li>
                </ul>
                <a href="https://calendar.app.google/AvBTHifK8zDddBer6" class="btn-pricing btn-pricing-light" target="_blank">Book a pilot call</a>
                </div>

                <div class="pricing-card reveal">
                <div class="pricing-plan">After the pilot</div>
                <div class="pricing-price">
                    <span class="price-amount">Custom</span>
                </div>
                <p class="pricing-desc">Once the pilot proves value, pricing and expansion can be shaped around clinic size, workflow complexity, and rollout scope.</p>
                <ul class="pricing-features">
                    <li>Rollout planning after pilot validation</li>
                    <li>Future multi-clinic expansion planning</li>
                    <li>Operational feedback incorporated into roadmap</li>
                    <li>Commercial terms discussed after the trial</li>
                    <li>Support for a phased adoption approach</li>
                    <li>Discovery for deeper integration needs</li>
                </ul>
                <a href="https://calendar.app.google/AvBTHifK8zDddBer6" class="btn-pricing btn-pricing-outline" target="_blank">Talk through fit</a>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta-section">
            <div class="section-eyebrow">Ready when you are</div>
            <h2 class="section-h2">Reduce no-shows before<br><em>they become lost revenue.</em></h2>
            <p class="section-sub">See how Calarasome could fit your clinic's workflow, then decide whether a short pilot is worth trying with real patients and real staff feedback.</p>
            <div class="cta-actions">
                <a href="https://calendar.app.google/AvBTHifK8zDddBer6" class="btn-cta-primary" target="_blank">Book a 30-min Demo</a>
                <a href="{{ asset('pdf/calarasome-onepager.pdf') }}" class="btn-cta-ghost" target="_blank">Download the one-pager</a>
            </div>
            <p class="cta-note">Free 30-60 day pilot available for a small number of clinics · setup support included</p>
        </section>

        <!-- FOOTER -->
        <footer>
            <div class="footer-top">
                <div>
                    <span class="footer-brand-logo">Calarasome</span>
                    <p class="footer-brand-desc">Dermatology booking and workflow software focused on reducing no-shows, easing front-desk work, and recovering canceled revenue.</p>
                    <p style="font-family: 'DM Mono', monospace; font-size: 10px; color: rgba(255,255,255,0.15); letter-spacing: 0.12em; margin-top: 20px;">A PRAGMATRICS PRODUCT</p>
                </div>

                <div>
                    <p class="footer-col-title">Product</p>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pilot program</a></li>
                        <li><a href="#how-it-works">How it works</a></li>
                        <li><a href="https://calendar.app.google/AvBTHifK8zDddBer6" target="_blank">Book a demo</a></li>
                        <li><a href="{{ asset('pdf/calarasome-onepager.pdf') }}" target="_blank">One-pager</a></li>
                    </ul>
                </div>

                <div>
                    <p class="footer-col-title">Company</p>
                    <ul class="footer-links">
                        <li><a href="https://pragmatrics.com" target="_blank">About Pragmatrics</a></li>
                        <li><a href="https://calendar.app.google/AvBTHifK8zDddBer6" target="_blank">Request a walkthrough</a></li>
                        <li><a href="#features">Workflow highlights</a></li>
                        <li><a href="#how-it-works">Pilot process</a></li>
                        <li><a href="{{ route('login') }}">Client sign in</a></li>
                    </ul>
                </div>

                <div>
                <p class="footer-col-title">Support</p>
                <ul class="footer-links">
                    <li><a href="https://calendar.app.google/AvBTHifK8zDddBer6" target="_blank">Book a demo</a></li>
                    <li><a href="mailto:hello@pragmatrics.com">Contact us</a></li>
                    <li><a href="https://calendar.app.google/AvBTHifK8zDddBer6" target="_blank">Request a Demo</a></li>
                    <li><a href="{{ asset('pdf/calarasome-onepager.pdf') }}" target="_blank">Download one-pager</a></li>
                </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-left">
                    <p class="footer-bottom-copy">© 2026 Pragmatrics Inc. All rights reserved.</p>
                    <p class="footer-bottom-by">Calarasome is designed for privacy-conscious scheduling workflows in dermatology and aesthetic clinics</p>
                </div>
                <div class="footer-bottom-right">
                    <a href="mailto:hello@pragmatrics.com">Privacy questions</a>
                    <a href="https://calendar.app.google/AvBTHifK8zDddBer6" target="_blank">Talk to us</a>
                </div>
            </div>
        </footer>
        <script>
            const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, 80);
                observer.unobserve(entry.target);
                }
            });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

            window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 20) {
                nav.style.boxShadow = '0 1px 24px rgba(0,0,0,0.06)';
            } else {
                nav.style.boxShadow = 'none';
            }
            });
        </script>
    </body>
</html>


