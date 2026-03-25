<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeannes Bryan | NPC</title>
    <meta name="description" content="Hi, I'm Jeannes Bryan, a NPC who loves watching sports, a tech enthusiast, and a professional thinker." />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Jeannes Bryan | NPC" />
    <meta property="og:url" content="https://npc.my.id" />
    <meta property="og:description" content="Hi, I'm Jeannes Bryan, a NPC who loves watching sports, a tech enthusiast, and a professional thinker." />
    <meta name="theme-color" content="#121212">    
    <link rel="icon" type="image/svg+xml" href="assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="assets/npc-icon.svg">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .landing-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .profile-card { padding: 40px 20px; text-align: center; }
        .avatar-main { width: 120px; height: 120px; border-radius: 50%; border: 2px solid var(--border-color); object-fit: cover; margin-bottom: 20px; }
        
        .social-area {
            margin-top: 35px;
            padding-top: 30px;
            padding-bottom: 30px;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .social-links { display: flex; justify-content: center; flex-wrap: wrap; gap: 40px; }
        .social-icon { 
            color: var(--text-muted); 
            transition: color 0.2s, transform 0.2s; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            text-decoration: none; 
        }
        .social-icon:hover { color: var(--text-main); transform: translateY(-2px); }
        .icon-label { font-size: 0.8rem; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="container landing-wrapper">
        <div class="card profile-card w-100">
            <img src="assets/jeannesbryan.webp" alt="Jeannes Bryan" class="avatar-main">
            
            <h1 class="mb-1">Jeannes Bryan</h1>
            
            <p class="text-muted mt-2 mb-4" style="max-width: 400px; line-height: 1.6; margin: 0 auto; text-align: center;">
                A NPC who loves watching sports, a tech enthusiast, and a professional thinker.
            </p>

            <div class="social-area">
                <div class="social-links">
                    <a href="echo/index.php" class="social-icon" title="Echo">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M11.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m1 0A4.5 4.5 0 1 0 8 12.5a4.5 4.5 0 0 0 4.5-4.5M14 8A6 6 0 1 1 2 8a6 6 0 0 1 12 0m1 0A7 7 0 1 0 8 15a7 7 0 0 0 7-7"/><circle cx="8" cy="8" r="1.5"/></svg>
                        <span class="icon-label">Echo</span>
                    </a>
                    
                    <a href="mailto:jeannesbryan@duck.com" class="social-icon" title="Email">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>
                        <span class="icon-label">Email</span>
                    </a>
                </div>
            </div>

            <div class="text-muted fs-small mt-4">
                (&#x2184;) THE VOID
            </div>
        </div>
    </div>

</body>
</html>