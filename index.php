<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYS_NODE: Jeannes Bryan</title>
    <meta name="description" content="Entity Tracker. Status: Monitoring the void." />
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="assets/npc-icon.svg">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .landing-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .profile-card { padding: 40px 20px; text-align: center; border-top: 4px solid var(--text-main); }
        .social-area { margin-top: 35px; padding-top: 30px; padding-bottom: 30px; border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color); }
        .social-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; }
        .social-btn { display: inline-block; padding: 10px 20px; border: 1px solid var(--border-color); color: var(--text-main); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; transition: 0.2s; background: rgba(0, 255, 65, 0.05); }
        .social-btn:hover { background: var(--text-main); color: var(--bg-dark); box-shadow: 0 0 15px var(--text-main); }

        /* ========================================= */
        /* SPLASH SCREEN CSS */
        /* ========================================= */
        #splash-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: var(--bg-dark, #050505); z-index: 99999; 
            display: flex; align-items: center; justify-content: center; 
            text-align: center; transition: opacity 0.5s ease; 
            background-image: radial-gradient(circle, #1a1a1a 1px, transparent 1px);
            background-size: 30px 30px;
            font-family: 'JetBrains Mono', monospace;
        }
        .splash-content { font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor; font-weight: bold; color: var(--text-main); }
        .splash-hidden { opacity: 0; pointer-events: none; }
        
        .loading-dots::after { content: ''; animation: dots 1.5s infinite; }
        @keyframes dots { 0%, 20% { content: ''; } 40% { content: '.'; } 60% { content: '..'; } 80%, 100% { content: '...'; } }
    </style>
</head>
<body>

    <div id="splash-overlay" class="splash-hidden">
        <div class="splash-content" id="splash-text">
            </div>
    </div>

    <div class="container landing-wrapper">
        <div class="card profile-card w-100">
            <img src="assets/jeannesbryan.webp" alt="Jeannes Bryan" class="avatar-main">
            
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
                    <a href="echo/index.php" class="social-btn app-link" title="Echo" data-text="> INTERCEPTING_ORBITAL_SIGNAL_TELEMETRY">
                        [ RECV: ECHO_BROADCAST ]
                    </a>
                    <a href="mailto:jeannesbryan@duck.com" class="social-btn" title="Email">
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

                    // Suntikkan teks dinamis + animasi titik-titik
                    splashText.innerHTML = `${customText}<span class="loading-dots"></span>`;
                    
                    // Gelapkan layar
                    splash.classList.remove('splash-hidden');

                    // Hitung mundur 3 detik, lalu pindah ke halaman Echo
                    setTimeout(() => { 
                        window.location.href = targetUrl; 
                    }, 3000);
                });
            });
        });
    </script>
</body>
</html>