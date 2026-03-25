<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeannes Bryan | NPC</title>
    
    <meta name="description" content="Hi, I'm Jeannes Bryan, a NPC who loves watching sports, a tech enthusiast, and a professional thinker." />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Jeannes Bryan | NPC" />
    <meta property="og:url" content="https://npc.my.id" />
    <meta property="og:description" content="Hi, I'm Jeannes Bryan, a NPC who loves watching sports, a tech enthusiast, and a professional thinker." />
    <meta name="theme-color" content="#000000" />

    <style>
        :root {
            --matrix-green: #00ff41;
            --dark-green: #003b00;
            --bg-black: #000000;
        }

        * { box-sizing: border-box; }
        body, html {
            font-family: 'Courier New', monospace;
            background-color: var(--bg-black);
            color: var(--matrix-green);
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .npc-terminal {
            width: 95%;
            max-width: 550px;
            border: 1px solid var(--matrix-green);
            background: rgba(0, 5, 0, 0.95);
            box-shadow: 0 0 20px rgba(0, 255, 65, 0.1);
        }

        .terminal-header {
            background: var(--matrix-green);
            color: #000;
            padding: 5px 12px;
            font-size: 0.75rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }

        .terminal-body {
            padding: 40px;
            text-align: center;
        }

        .avatar-npc {
            width: 100px;
            height: 100px;
            border: 1px solid var(--matrix-green);
            filter: grayscale(100%) brightness(1.2);
            margin-bottom: 25px;
            object-fit: cover;
        }

        .identity-title {
            font-size: 1.4rem;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .tagline-text {
            font-size: 0.85rem;
            color: #00cc00;
            /* Margin bottom disesuaikan karena tombol besar hilang */
            margin-bottom: 35px; 
            line-height: 1.5;
        }

        .social-grid {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            /* Padding atas ditambahkan untuk memberi batas tegas */
            padding-top: 25px;
            border-top: 1px solid var(--dark-green);
        }

        .social-icon {
            color: var(--matrix-green);
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            text-decoration: none;
        }

        .social-icon:hover {
            color: #fff;
            transform: scale(1.2);
        }

        .system-footer {
            font-size: 0.8rem;
            color: var(--matrix-green); 
            margin-top: 35px;
            border-top: 1px solid var(--dark-green);
            padding-top: 20px;
            letter-spacing: 1px;
        }

        /* Responsivitas untuk layar kecil */
        @media (max-width: 400px) {
            .identity-title { font-size: 1.1rem; }
            .terminal-body { padding: 30px 20px; }
            .social-grid { gap: 15px; }
        }
    </style>
</head>
<body>

    <div class="npc-terminal">
        <div class="terminal-header">
            <span>GUEST ACCESS</span>
            <span>npc.my.id</span>
        </div>

        <div class="terminal-body">
            <img src="assets/jeannesbryan.webp" alt="Jeannes Bryan" class="avatar-npc">

            <div class="identity-title">Hi, I'm Jeannes Bryan</div>
            
            <p class="tagline-text">
                A NPC who loves watching sports, a tech enthusiast, and a professional thinker.
            </p>

            <div class="social-grid">
                <a href="echo/index.php" class="social-icon" title="Echo - Personal Feed">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m1 0A4.5 4.5 0 1 0 8 12.5a4.5 4.5 0 0 0 4.5-4.5M14 8A6 6 0 1 1 2 8a6 6 0 0 1 12 0m1 0A7 7 0 1 0 8 15a7 7 0 0 0 7-7"/>
                        <circle cx="8" cy="8" r="1.5"/>
                    </svg>
                </a>

                <a href="https://x.com/jeannesbryan" class="social-icon" title="X">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/></svg>
                </a>
                <a href="https://www.linkedin.com/in/jeannesbryan" class="social-icon" title="LinkedIn">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/></svg>
                </a>
                <a href="https://www.instagram.com/jnnsbryn" class="social-icon" title="Instagram">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"/></svg>
                </a>
                <a href="https://www.threads.net/@jnnsbryn" class="social-icon" title="Threads">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M6.321 6.016c-.27-.18-1.166-.802-1.166-.802.756-1.081 1.753-1.502 3.132-1.502.975 0 1.803.327 2.394.948s.928 1.509 1.005 2.644q.492.207.905.484c1.109.745 1.719 1.86 1.719 3.137 0 2.716-2.226 5.075-6.256 5.075C4.594 16 1 13.987 1 7.994 1 2.034 4.482 0 8.044 0 9.69 0 13.55.243 15 5.036l-1.36.353C12.516 1.974 10.163 1.43 8.006 1.43c-3.565 0-5.582 2.171-5.582 6.79 0 4.143 2.254 6.343 5.63 6.343 2.777 0 4.847-1.443 4.847-3.556 0-1.438-1.208-2.127-1.27-2.127-.236 1.234-.868 3.31-3.644 3.31-1.618 0-3.013-1.118-3.013-2.582 0-2.09 1.984-2.847 3.55-2.847.586 0 1.294.04 1.663.114 0-.637-.54-1.728-1.9-1.728-1.25 0-1.566.405-1.967.868ZM8.716 8.19c-2.04 0-2.304.87-2.304 1.416 0 .878 1.043 1.168 1.6 1.168 1.02 0 2.067-.282 2.232-2.423a6.2 6.2 0 0 0-1.528-.161"/></svg>
                </a>
                <a href="https://www.tiktok.com/@jeannesbryan" class="social-icon" title="TikTok">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z"/></svg>
                </a>
                <a href="https://www.facebook.com/jeannesbryan" class="social-icon" title="Facebook">
                    <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/></svg>
                </a>
            </div>

            <div class="system-footer">
                (&#x2184;) JEANNES BRYAN
            </div>
        </div>
    </div>

</body>
</html>