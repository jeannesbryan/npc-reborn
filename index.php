<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYS_NODE: Jeannes Bryan</title>
    <meta name="google-site-verification" content="E0ICtVu6Sc2hBMPr2c9_D8EwGO2XuTGfVESUSqde2Y8" />
	<meta name="msvalidate.01" content="69F30B6CF7C799C59DE89EB81E6EE067" />
	<meta name="yandex-verification" content="56be8ebae84bd731" />
    <meta name="description" content="Entity Tracker. Status: Monitoring the void." />
    <meta name="theme-color" content="#030303">
    <link rel="canonical" href="https://npc.my.id/" />
    <meta name="keywords" content="Jeannes Bryan, Tech Enthusiast, NPC, Personal Website, Portfolio" />
    <meta name="author" content="Jeannes Bryan" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://npc.my.id/" />
    <meta property="og:title" content="SYS_NODE: Jeannes Bryan" />
    <meta property="og:description" content="Entity Tracker. Status: Monitoring the void." />
    <meta property="og:image" content="https://npc.my.id/assets/jeannesbryan.webp" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="SYS_NODE: Jeannes Bryan" />
    <meta name="twitter:description" content="Entity Tracker. Status: Monitoring the void." />
    <meta name="twitter:image" content="https://npc.my.id/assets/jeannesbryan.webp" />
    <link rel="icon" type="image/svg+xml" href="assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .landing-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .profile-card { padding: 40px 20px; text-align: center; border-top: 4px solid var(--text-main); }
        .social-area { margin-top: 35px; padding-top: 30px; padding-bottom: 30px; border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color); }
        .social-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; }
    </style>
</head>
<body>

    <div id="splash-overlay" class="splash-hidden">
        <div class="splash-content" id="splash-text"></div>
    </div>

    <div class="container landing-wrapper">
        <div class="card profile-card w-100">
            <img src="assets/jeannesbryan.webp" alt="Jeannes Bryan" class="avatar-main" width="120" height="120" fetchpriority="high" decoding="async">
            
            <h1 class="mb-1">ENTITY_ID: JEANNES_BRYAN</h1>
            <div class="text-muted fs-small mb-4">CLASS: NPC // STATUS: ONLINE</div>
            
            <p class="text-main mt-2 mb-4" style="max-width: 450px; line-height: 1.6; margin: 0 auto; text-align: center;">
                > INITIALIZING SCAN...<br>
                > MATCH FOUND: Tech enthusiast, Sports observer, Professional thinker.<br>
                > CURRENT DIRECTIVE: Monitoring the void.
            </p>

            <div class="social-area">
                <div class="text-muted fs-small mb-3">AVAILABLE CHANNELS:</div>
                <div class="social-links">
                    <a href="echo/index.php" class="btn btn-main app-link" title="Echo" data-text="> INTERCEPTING_ORBITAL_SIGNAL_TELEMETRY">
                        [ RECV: ECHO_BROADCAST ]
                    </a>
                    <a href="mailto:jeannesbryan@duck.com" class="btn btn-main" title="Email">
                        [ SEND: DIRECT_COMMS ]
                    </a>
                </div>
            </div>

            <div class="text-muted fs-small mt-4">
                SYSTEM UPTIME: OPTIMAL <span class="blinking-cursor"></span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const splash = document.getElementById('splash-overlay');
            const splashText = document.getElementById('splash-text');
            const appLinks = document.querySelectorAll('.app-link');
            
            appLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    const targetUrl = this.getAttribute('href');
                    const customText = this.getAttribute('data-text');

                    splashText.innerHTML = `${customText}<span class="loading-dots"></span>`;
                    splash.classList.remove('splash-hidden');

                    setTimeout(() => { window.location.href = targetUrl; }, 3000);
                });
            });
        });
    </script>
</body>
</html>