<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYS_NODE: Jeannes Bryan</title>
    
    <meta name="google-site-verification" content="E0ICtVu6Sc2hBMPr2c9_EwGO2XuTGfVESUSqde2Y8" />
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
    
    <link rel="stylesheet" href="assets/terminal.css">

    <style>
        .profile-card { border-top: 4px solid var(--t-green); padding: 40px 20px; text-align: center; }
        .avatar-main { width: 120px; height: 120px; border-radius: 0; border: 2px solid var(--t-green-dim); object-fit: cover; display: block; margin: 0 auto 15px auto; }
        .social-area { margin-top: 35px; padding-top: 30px; padding-bottom: 30px; border-top: 1px dashed var(--t-green-dim); border-bottom: 1px dashed var(--t-green-dim); }
    </style>
</head>
<body class="t-crt"> <div id="splash-overlay" class="t-splash hidden">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor; text-align: center; max-width: 95%;"></div>
    </div>

    <div class="t-center-screen">
        
        <div class="t-center-box t-box-md">
            
            <div class="t-card profile-card mb-0">
                
                <img src="assets/jeannesbryan.webp" alt="Jeannes Bryan" class="avatar-main t-retro-filter mb-3" fetchpriority="high" decoding="async">
                
                <h1 class="mb-1" style="font-size: 1.5rem;">ENTITY_ID: JEANNES_BRYAN</h1>
                
                <div class="text-muted fs-small mb-5">CLASS: NPC // STATUS: ONLINE</div>
                
                <p class="text-success mt-2 mb-4" style="max-width: 480px; margin: 0 auto; line-height: 1.6;">
                    > INITIALIZING SCAN...<br>
                    > MATCH FOUND: Tech enthusiast, Sports observer, Professional thinker.<br>
                    > CURRENT DIRECTIVE: Monitoring the void.
                </p>

                <div class="social-area">
                    <div class="text-muted fs-small mb-3">AVAILABLE CHANNELS:</div>
                    
                    <div class="t-action-grid">
                        <a href="echo/index.php" class="t-btn t-btn-block app-link" title="Echo" data-text="> INTERCEPTING_ORBITAL_SIGNAL_TELEMETRY">
                            [ RECV: ECHO_BROADCAST ]
                        </a>
                        
                        <a href="blog/" class="t-btn t-btn-block app-link" title="Manifesto" data-text="> DECRYPTING_PUBLIC_MANIFESTO_ARCHIVES">
                            [ READ: MANIFESTO_LOGS ]
                        </a>

                        <a href="https://relay.npc.my.id" class="t-btn t-btn-block app-link" target="_blank" title="Relay" data-text="> ESTABLISHING_SECURE_RELAY_LINK">
                            [ GOTO: RELAY_NODE ]
                        </a>

                        <a href="mailto:jeannesbryan@duck.com" class="t-btn t-btn-block" title="Email">
                            [ SEND: DIRECT_COMMS ]
                        </a>
                    </div>
                </div>

                <div class="text-muted fs-small mt-4">
                    SYSTEM UPTIME: OPTIMAL <span class="t-blink">_</span>
                </div>
                
            </div>
        </div>
    </div>

    <script src="assets/terminal.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const splash = document.getElementById('splash-overlay');
            const splashText = document.getElementById('splash-text');
            const appLinks = document.querySelectorAll('.app-link');
            
            appLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    
                    const customText = this.getAttribute('data-text');
                    const targetUrl = this.getAttribute('href');
                    const targetAttr = this.getAttribute('target');

                    if (customText) {
                        e.preventDefault(); 
                        
                        splashText.innerHTML = `${customText}<span class="t-loading-dots"></span>`;
                        splash.classList.remove('hidden'); 

                        setTimeout(() => { 
                            if (targetAttr === '_blank') {
                                window.open(targetUrl, '_blank');
                                splash.classList.add('hidden'); 
                            } else {
                                window.location.href = targetUrl; 
                            }
                        }, 2500);
                    }
                });
            });
        });
    </script>
</body>
</html>