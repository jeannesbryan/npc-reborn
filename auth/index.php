<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TOKEN: AUTH - Bunker Control</title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">  
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css"> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js" integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/otpauth/9.4.1/otpauth.umd.min.js" integrity="sha512-IqeYQB64l+zM1qCkq5vR3GbyDQ19+04nnhzbeS687ENsjTeomAbq49yoPABzp8UZEq1TWFGrfbhAdCXnJIaqjw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="t-crt">

    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > INITIALIZING_DUAL_SATELLITE_AUTH<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3 mt-4">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> TOKEN: AUTH</h2>
                <div class="text-muted fs-small mt-1">> ZERO-KNOWLEDGE 2FA MULTI-CLOUD ENABLED.</div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div id="pane-setup" class="t-pane t-card mb-4 text-center p-4">
            <h3 class="text-success mb-3">> PROTOCOL: COLD START</h3>
            <p class="text-muted fs-small mb-4">Unrecognized terminal. Inject cloud coordinates to establish secure connection.</p>
            
            <div class="t-center-box text-left">
                <div class="t-border-bottom pb-3 mb-3">
                    <span class="text-success font-bold">> PRIMARY SATELLITE (GITHUB GIST)</span>
                    <div class="mt-2">
                        <label class="t-form-label">> GITHUB_PAT_TOKEN</label>
                        <input type="password" id="setup-gh-token" class="t-input mb-3" style="letter-spacing: 1px;">
                        <label class="t-form-label">> GITHUB_GIST_ID</label>
                        <input type="text" id="setup-gh-id" class="t-input mb-2" style="letter-spacing: 1px;">
                    </div>
                </div>

                <div class="mb-4">
                    <span class="text-warning font-bold">> FALLBACK SATELLITE (GITLAB SNIPPET)</span>
                    <div class="mt-2">
                        <label class="t-form-label">> GITLAB_PRIVATE_TOKEN</label>
                        <input type="password" id="setup-gl-token" class="t-input mb-3" placeholder="glpat-..." style="letter-spacing: 1px;">
                        <label class="t-form-label">> GITLAB_SNIPPET_ID</label>
                        <input type="text" id="setup-gl-id" class="t-input m-0" style="letter-spacing: 1px;">
                    </div>
                </div>

                <button onclick="saveSetup()" class="t-btn t-btn-block font-bold t-glow">[ ESTABLISH_DUAL_LINK ]</button>
            </div>
        </div>

        <div id="pane-unlock" class="t-pane t-card mb-4 text-center p-4">
            <h3 class="mb-3 text-danger t-flicker">> TOKEN_AUTH_LOCKED</h3>
            <p class="text-muted fs-small mb-4">Terminal linked to Satellites. Awaiting Master Decryption Key.</p>
            <div class="t-center-box">
                <input type="password" id="master-password" class="t-input text-center text-success font-bold mb-4" style="letter-spacing: 2px; font-size: 1.2rem;" placeholder="ENTER MASTER PASSWORD..." onkeypress="if(event.key === 'Enter') unlockCore()">
                <button onclick="unlockCore()" id="btn-unlock" class="t-btn t-btn-block font-bold t-glow">[ DECRYPT_KEYS ]</button>
                <div class="mt-5 t-border-top pt-4 pb-2">
                    <button onclick="purgeSetup()" class="t-btn danger t-btn-sm mt-3">> PURGE_TERMINAL_LINK</button>
                </div>
            </div>
        </div>

        <div id="pane-init" class="t-pane t-card mb-4 text-center p-4" style="border-color: var(--t-yellow);">
            <h3 class="mb-3 text-warning t-flicker">> SATELLITES_BLANK</h3>
            <p class="text-muted fs-small mb-4">Detected empty payloads in Clouds. Please create a new Master Password to initialize your 2FA Vault.</p>
            <div class="t-center-box text-left">
                <label class="t-form-label text-warning">> CREATE_MASTER_PASSWORD</label>
                <input type="password" id="init-password" class="t-input mb-3 text-warning font-bold" style="letter-spacing: 2px;">
                <label class="t-form-label text-warning">> CONFIRM_PASSWORD</label>
                <input type="password" id="init-confirm" class="t-input mb-4 text-warning font-bold" style="letter-spacing: 2px;" onkeypress="if(event.key === 'Enter') initializeCore()">
                <button onclick="initializeCore()" class="t-btn t-btn-block font-bold text-warning" style="border-color: var(--t-yellow);">[ FORMAT_AND_ENCRYPT ]</button>
                
                <div class="mt-5 text-center t-border-top pt-4 pb-2">
                    <button onclick="purgeSetup()" class="t-btn danger t-btn-sm mt-3">> PURGE_TERMINAL_LINK</button>
                </div>
            </div>
        </div>

        <div id="pane-auth" class="t-pane">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button onclick="toggleAddForm()" class="t-btn font-bold t-glow">[ + INJECT_NEW_KEY ]</button>
                <div class="d-flex gap-2 align-items-center">
                    <span id="auto-lock-timer" class="text-muted fs-small">> AUTO_LOCK: 10:00</span>
                    <button onclick="lockCore()" class="t-btn danger t-btn-sm">[ LOCK ]</button>
                </div>
            </div>

            <div id="add-form" class="t-card mb-4" style="display: none; background: rgba(0,255,65,0.02);">
                <div class="t-card-header">> INJECT_MANUAL_TOTP_KEY</div>
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 150px;">
                        <label class="t-form-label">> TARGET_ISSUER (e.g. GitHub)</label>
                        <input type="text" id="entry-issuer" class="t-input m-0" placeholder="Issuer Name">
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label class="t-form-label">> ACCOUNT_ID (e.g. email)</label>
                        <input type="text" id="entry-account" class="t-input m-0" placeholder="User ID">
                    </div>
                </div>
                <label class="t-form-label">> SECRET_KEY (Base32)</label>
                <div class="t-input-group mb-4">
                    <input type="password" id="entry-secret" class="t-input m-0" placeholder="JBSWY3DPEHPK3PXP" style="letter-spacing: 2px;">
                    <button type="button" class="t-input-action-btn" onclick="Terminal.toggleInputAction('entry-secret', this)">[ SHOW ]</button>
                </div>
                <button onclick="saveEntry()" class="t-btn t-btn-block">[ ENCRYPT & SYNC TO SATELLITES ]</button>
            </div>

            <div class="mb-4 d-flex flex-column gap-3 p-3 t-card" style="border-style: dashed; background: transparent;">
                <div class="d-flex justify-content-between align-items-center pb-2 t-border-bottom">
                    <div class="fs-small text-muted">> DATA_SOURCE: <span id="badge-satellite" class="t-badge primary">UNKNOWN</span></div>
                    <div id="badge-sync" class="fs-small text-warning font-bold t-flicker" style="display:none;">> PENDING UPLOAD</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-success font-bold">></span>
                    <input type="text" id="live-search" class="t-input m-0 border-0" placeholder="QUERY_AUTH_DATA..." onkeyup="filterAuth(this.value)" style="box-shadow: none; flex: 1;">
                </div>
            </div>

            <div class="t-grid t-grid-2" id="auth-items"></div>
        </div>

    </div>

    <div id="editModal" class="t-modal">
        <div class="t-modal-content">
            <div class="t-card-header d-flex justify-content-between align-items-center">
                <span class="text-success">> MODIFY_AUTH_KEY</span>
                <button class="t-btn danger t-btn-sm" onclick="Terminal.modal.close('editModal')">[ X CLOSE ]</button>
            </div>
            
            <input type="hidden" id="edit-id">
            
            <div class="d-flex gap-3 mb-3 flex-wrap">
                <div style="flex: 1; min-width: 150px;">
                    <label class="t-form-label">> TARGET_ISSUER</label>
                    <input type="text" id="edit-issuer" class="t-input m-0" placeholder="Issuer Name">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label class="t-form-label">> ACCOUNT_ID</label>
                    <input type="text" id="edit-account" class="t-input m-0" placeholder="User ID">
                </div>
            </div>
            
            <label class="t-form-label">> SECRET_KEY (Base32)</label>
            <div class="t-input-group mb-4">
                <input type="password" id="edit-secret" class="t-input m-0" style="letter-spacing: 2px;">
                <button type="button" class="t-input-action-btn" onclick="Terminal.toggleInputAction('edit-secret', this)">[ SHOW ]</button>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="t-btn danger" onclick="Terminal.modal.close('editModal')">[ ABORT ]</button>
                <button onclick="saveEditEntry()" id="btn-edit-save" class="t-btn t-glow font-bold">[ OVERRIDE_KEY ]</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>

    <script>
        // MULTI-CLOUD CREDENTIALS
        let ghToken = localStorage.getItem('bunker_gh_token');
        let gistId = localStorage.getItem('bunker_gist_id');
        let glToken = localStorage.getItem('bunker_gl_token');
        let snippetId = localStorage.getItem('bunker_gl_id');

        let authData = [];
        let activePassword = '';
        
        let totpInterval = null;
        let inactivityTimer = null;
        const INACTIVITY_LIMIT = 600; 
        let timeRemaining = INACTIVITY_LIMIT;
        
        let satelliteState = 'LOCKED';
        let activeSatellite = 'UNKNOWN';
        let clipboardTimer = null; // Timer untuk auto-clear

        document.addEventListener("DOMContentLoaded", () => {
            Terminal.splash.close(1000);
            
            syncPendingData().then(() => {
                checkSatelliteStatus();
            });
            
            ['mousemove', 'keydown', 'click', 'scroll'].forEach(evt => {
                document.addEventListener(evt, resetInactivityTimer);
            });

            window.addEventListener('online', () => {
                Terminal.toast('> INTERNET CONNECTION RESTORED.', 'normal');
                syncPendingData();
            });
        });

        // ================= KRIPTOGRAFI MILITER (PBKDF2) =================
        function encryptPayload(dataStr, pwd) {
            const salt = CryptoJS.lib.WordArray.random(128/8);
            // MESIN BARU: SHA-256 (Versi 3)
            const key = CryptoJS.PBKDF2(pwd, salt, { keySize: 256/32, iterations: 10000, hasher: CryptoJS.algo.SHA256 });
            const iv = CryptoJS.lib.WordArray.random(128/8);
            const encrypted = CryptoJS.AES.encrypt(dataStr, key, { iv: iv });
            // TANDAI SEBAGAI v3
            return 'v3|' + salt.toString() + '|' + iv.toString() + '|' + encrypted.ciphertext.toString();
        }

        function decryptPayload(cipherText, pwd) {
            if (cipherText.startsWith('v3|')) {
                // DEKRIPSI PAYLOAD BARU (SHA-256)
                const parts = cipherText.split('|');
                const salt = CryptoJS.enc.Hex.parse(parts[1]);
                const iv = CryptoJS.enc.Hex.parse(parts[2]);
                const cipherParams = CryptoJS.lib.CipherParams.create({ ciphertext: CryptoJS.enc.Hex.parse(parts[3]) });
                const key = CryptoJS.PBKDF2(pwd, salt, { keySize: 256/32, iterations: 10000, hasher: CryptoJS.algo.SHA256 });
                const decrypted = CryptoJS.AES.decrypt(cipherParams, key, { iv: iv });
                return decrypted.toString(CryptoJS.enc.Utf8);
            } else if (cipherText.startsWith('v2|')) {
                // DEKRIPSI PAYLOAD LAMA (SHA-1)
                const parts = cipherText.split('|');
                const salt = CryptoJS.enc.Hex.parse(parts[1]);
                const iv = CryptoJS.enc.Hex.parse(parts[2]);
                const cipherParams = CryptoJS.lib.CipherParams.create({ ciphertext: CryptoJS.enc.Hex.parse(parts[3]) });
                const key = CryptoJS.PBKDF2(pwd, salt, { keySize: 256/32, iterations: 10000, hasher: CryptoJS.algo.SHA1 });
                const decrypted = CryptoJS.AES.decrypt(cipherParams, key, { iv: iv });
                return decrypted.toString(CryptoJS.enc.Utf8);
            } else {
                // DEKRIPSI PAYLOAD SANGAT LAMA (TANPA PBKDF2)
                const bytes = CryptoJS.AES.decrypt(cipherText, pwd);
                return bytes.toString(CryptoJS.enc.Utf8);
            }
        }

        // ================= DIAGNOSTIK UI =================
        function updateSatelliteBadge() {
            const badge = document.getElementById('badge-satellite');
            if(badge) {
                if(activeSatellite === 'GITHUB') {
                    badge.className = 't-badge success'; badge.innerText = 'GITHUB GIST';
                } else if(activeSatellite === 'GITLAB') {
                    badge.className = 't-badge warning'; badge.innerText = 'GITLAB SNIPPET';
                } else if(activeSatellite === 'LOCAL_CACHE') {
                    badge.className = 't-badge danger t-flicker'; badge.innerText = 'LOCAL CACHE (OFFLINE)';
                } else {
                    badge.className = 't-badge primary'; badge.innerText = 'UNKNOWN';
                }
            }
            const syncBadge = document.getElementById('badge-sync');
            if(syncBadge) {
                syncBadge.style.display = (localStorage.getItem('bunker_auth_pending_sync') === 'true') ? 'block' : 'none';
            }
        }

        // ================= HELPER & LOGIC =================
        function cleanUrl(val) {
            let cl = val.replace(/\s+/g, '');
            if (cl.includes('/')) return cl.split('/').pop();
            return cl;
        }

        function copyTotp(text, element) {
            navigator.clipboard.writeText(text.replace(/\s/g, '')).then(() => {
                Terminal.toast('TOTP COPIED TO CLIPBOARD', 'normal');
                const originalText = element.innerText;
                element.innerText = 'COPIED';
                
                // Kembalikan ke teks TOTP semula setelah 1 detik
                setTimeout(() => { element.innerText = originalText; }, 1000);

                // GLOBAL CLIPBOARD AUTO-CLEAR (30 Detik)
                if(clipboardTimer) clearTimeout(clipboardTimer);
                clipboardTimer = setTimeout(() => {
                    navigator.clipboard.writeText(' ').then(() => {
                        Terminal.toast('> CLIPBOARD SECURED (AUTO-CLEARED)', 'warning');
                    }).catch(e => console.log(e));
                }, 30000);

            }).catch(err => {
                Terminal.toast('SYS_ERR: CLIPBOARD DENIED', 'danger');
            });
        }

        function resetInactivityTimer() {
            if (activePassword !== '') {
                timeRemaining = INACTIVITY_LIMIT;
                updateLockDisplay();
            }
        }

        function updateLockDisplay() {
            if (activePassword === '') return;
            const min = String(Math.floor(timeRemaining / 60)).padStart(2, '0');
            const sec = String(timeRemaining % 60).padStart(2, '0');
            document.getElementById('auto-lock-timer').innerText = `> AUTO_LOCK: ${min}:${sec}`;
        }

        function updateUI() {
            document.querySelectorAll('.t-pane').forEach(p => p.classList.remove('active'));
            if (!ghToken || !gistId) {
                document.getElementById('pane-setup').classList.add('active');
            } else if (!activePassword) {
                if (satelliteState === 'INIT') {
                    document.getElementById('pane-init').classList.add('active');
                    document.getElementById('init-password').focus();
                } else {
                    document.getElementById('pane-unlock').classList.add('active');
                    document.getElementById('master-password').focus();
                }
            } else {
                document.getElementById('pane-auth').classList.add('active');
                renderAuthCards();
                startTotpLoop();
                startInactivityLoop();
            }
        }

        // --- PROTOCOL DUAL-READ (FETCH) ---
        async function fetchFromSatellites() {
            let content = null;
            // 1. Try GitHub (Primary)
            try {
                const resGH = await fetch(`https://api.github.com/gists/${gistId}?t=${Date.now()}`, {
                    headers: { "Accept": "application/vnd.github.v3+json", "Authorization": `token ${ghToken}` }
                });
                const dataGH = await resGH.json();
                if (dataGH.message) throw new Error("GH_REJECTED");
                
                if (dataGH.files && dataGH.files["bunker_auth.enc"]) {
                    content = dataGH.files["bunker_auth.enc"].content;
                    Terminal.toast('> CONNECTED TO GITHUB', 'normal');
                    return { content: content, source: 'GITHUB' };
                }
            } catch(e) {}

            // 2. Try GitLab (Fallback)
            if (glToken && snippetId) {
                try {
                    const resGL = await fetch(`https://gitlab.com/api/v4/snippets/${snippetId}`, { 
                        headers: { "PRIVATE-TOKEN": glToken }
                    });
                    const dataGL = await resGL.json();
                    if(dataGL.files) {
                        const fileMeta = dataGL.files.find(f => f.path === "bunker_auth.enc");
                        if(fileMeta) {
                            const rawRes = await fetch(fileMeta.raw_url, { headers: { "PRIVATE-TOKEN": glToken }});
                            content = await rawRes.text();
                            Terminal.toast('> GITHUB DOWN. ROUTED TO GITLAB.', 'warning');
                            return { content: content, source: 'GITLAB' };
                        }
                    }
                } catch(e) {}
            }
            throw new Error("ALL_SATELLITES_UNREACHABLE");
        }

        async function checkSatelliteStatus() {
            if (!ghToken || !gistId) { updateUI(); return; }
            
            try {
                let result = await fetchFromSatellites();
                activeSatellite = result.source;
                localStorage.setItem('bunker_auth_cache', result.content);
                satelliteState = (result.content.trim() === 'INIT' || result.content === '') ? 'INIT' : 'LOCKED';
                updateUI();
            } catch(e) {
                const cachedContent = localStorage.getItem('bunker_auth_cache');
                if (cachedContent) {
                    Terminal.toast("> CLOUDS UNREACHABLE. USING LOCAL CACHE.", 'warning');
                    activeSatellite = 'LOCAL_CACHE';
                    satelliteState = (cachedContent.trim() === 'INIT' || cachedContent === '') ? 'INIT' : 'LOCKED';
                } else {
                    activeSatellite = 'UNKNOWN';
                    satelliteState = 'LOCKED';
                }
                updateUI();
            }
        }

        // --- PROTOCOL DUAL-WRITE (SYNC / SAVE) ---
        async function syncToSatellites(encryptedPayload) {
            Terminal.splash.show('> ENCRYPTING & SYNCING TO SATELLITES...');
            localStorage.setItem('bunker_auth_cache', encryptedPayload);
            const promises = [];

            // Target 1: GitHub Gist (bunker_auth.enc)
            if (ghToken && gistId) {
                promises.push(fetch(`https://api.github.com/gists/${gistId}`, {
                    method: 'PATCH',
                    headers: { "Accept": "application/vnd.github.v3+json", "Authorization": `token ${ghToken}`, "Content-Type": "application/json" },
                    body: JSON.stringify({ files: { "bunker_auth.enc": { content: encryptedPayload } } })
                }).then(res => { if(!res.ok) throw new Error(); return 'GH'; }));
            }

            // Target 2: GitLab Snippet (bunker_auth.enc)
            if (glToken && snippetId) {
                promises.push(fetch(`https://gitlab.com/api/v4/snippets/${snippetId}`, {
                    method: 'PUT',
                    headers: { "PRIVATE-TOKEN": glToken, "Content-Type": "application/json" },
                    body: JSON.stringify({ files: [{ action: "update", file_path: "bunker_auth.enc", content: encryptedPayload }] })
                }).then(res => { if(!res.ok) throw new Error(); return 'GL'; }));
            }

            if(promises.length === 0) return;

            try {
                const results = await Promise.allSettled(promises);
                const success = results.filter(r => r.status === 'fulfilled').length;

                if (success === 0) {
                    throw new Error("ALL_FAILED");
                } else if (success < promises.length) {
                    // Split-Brain Mitigation
                    Terminal.toast('PARTIAL SYNC. ONE SATELLITE DOWN.', 'warning');
                    localStorage.setItem('bunker_auth_pending_sync', 'true');
                } else {
                    Terminal.toast('DUAL-SATELLITE SYNC SUCCESSFUL', 'normal');
                    localStorage.removeItem('bunker_auth_pending_sync');
                }
            } catch (e) {
                localStorage.setItem('bunker_auth_pending_sync', 'true');
                Terminal.toast('SAVED LOCALLY. PENDING SYNC (OFFLINE)', 'warning');
            }
            updateSatelliteBadge();
            Terminal.splash.close(500);
        }

        async function syncPendingData() {
            if (localStorage.getItem('bunker_auth_pending_sync') === 'true') {
                const cachedPayload = localStorage.getItem('bunker_auth_cache');
                if (cachedPayload && ghToken) {
                    Terminal.toast('> DETECTED OFFLINE CHANGES. SYNCING...', 'warning');
                    await syncToSatellites(cachedPayload);
                }
            }
        }

        function saveSetup() {
            let tGH = document.getElementById('setup-gh-token').value.replace(/\s+/g, '');
            let iGH = cleanUrl(document.getElementById('setup-gh-id').value);
            let tGL = document.getElementById('setup-gl-token').value.replace(/\s+/g, '');
            let iGL = cleanUrl(document.getElementById('setup-gl-id').value);

            if(tGH && iGH) {
                Terminal.splash.show('> LINKING TO SATELLITES...');
                setTimeout(() => {
                    localStorage.setItem('bunker_gh_token', tGH);
                    localStorage.setItem('bunker_gist_id', iGH);
                    
                    if(tGL && iGL) { 
                        localStorage.setItem('bunker_gl_token', tGL); 
                        localStorage.setItem('bunker_gl_id', iGL); 
                    }
                    
                    ghToken = tGH; gistId = iGH;
                    glToken = tGL; snippetId = iGL;
                    
                    checkSatelliteStatus();
                    Terminal.splash.close(500);
                }, 800);
            } else {
                Terminal.toast('GITHUB CREDENTIALS ARE REQUIRED', 'danger');
            }
        }

        function purgeSetup() {
            if(confirm("> WARNING: Destroy all terminal link coordinates?")) {
                localStorage.removeItem('bunker_gh_token'); localStorage.removeItem('bunker_gist_id');
                localStorage.removeItem('bunker_gl_token'); localStorage.removeItem('bunker_gl_id');
                ghToken = null; gistId = null; glToken = null; snippetId = null;
                satelliteState = 'LOCKED';
                updateUI();
            }
        }

        async function initializeCore() {
            const pwd1 = document.getElementById('init-password').value;
            const pwd2 = document.getElementById('init-confirm').value;
            
            if(!pwd1 || !pwd2) return;
            if(pwd1 !== pwd2) { Terminal.toast('PASSWORDS DO NOT MATCH', 'danger'); return; }

            authData = [];
            activePassword = pwd1;
            satelliteState = 'UNLOCKED';
            
            document.getElementById('init-password').value = '';
            document.getElementById('init-confirm').value = '';
            
            Terminal.toast('CORE FORMATTED. SYNCING CLOUDS...', 'warning');
            await syncToSatellites(encryptPayload("[]", activePassword));
            updateUI();
        }

        async function unlockCore() {
            const pwd = document.getElementById('master-password').value;
            if(!pwd) return;
            
            Terminal.splash.show('> DECRYPTING TOKEN_AUTH...');
            let content = '';
            
            try {
                let result = await fetchFromSatellites();
                content = result.content;
                activeSatellite = result.source;
                localStorage.setItem('bunker_auth_cache', content);
            } catch(e) {
                content = localStorage.getItem('bunker_auth_cache');
                if (!content) {
                    Terminal.toast('SYS_ERR: FETCH FAILED. NO CACHE.', 'danger');
                    activeSatellite = 'UNKNOWN';
                    Terminal.splash.close(500); return;
                }
                activeSatellite = 'LOCAL_CACHE';
                Terminal.toast('DECRYPTING FROM LOCAL CACHE...', 'warning');
            }

            if (content.trim() === 'INIT') {
                Terminal.toast('SATELLITE SYNC DELAY. RETRY IN 5 SECONDS.', 'warning');
                Terminal.splash.close(500); return;
            }

            try {
                const decryptedStr = decryptPayload(content, pwd);
                if(!decryptedStr) throw new Error("DECRYPTION FAILED");
                
                authData = JSON.parse(decryptedStr);

                // CEK MIGRASI (AES Lama/SHA-1 ke SHA-256 Baru)
                let isMigrated = false;
                if (!content.startsWith('v3|')) {
                    isMigrated = true;
                    Terminal.toast('> LEGACY DATA DETECTED. AUTO-UPGRADING TO SHA-256...', 'warning');
                }

                activePassword = pwd;
                satelliteState = 'UNLOCKED';
                document.getElementById('master-password').value = ''; 
                updateUI();
                Terminal.toast('ACCESS GRANTED', 'normal');

                if(isMigrated) {
                    syncToSatellites(encryptPayload(JSON.stringify(authData), activePassword));
                }

            } catch(e) {
                Terminal.toast('INVALID MASTER PASSWORD', 'danger');
            }
            Terminal.splash.close(500);
        }

        function lockCore() {
            authData = [];
            activePassword = '';
            satelliteState = 'LOCKED';
            document.getElementById('live-search').value = ''; 
            clearInterval(totpInterval);
            clearInterval(inactivityTimer);
            checkSatelliteStatus(); 
            Terminal.toast('TOKEN: AUTH SECURED', 'warning');
        }

        function toggleAddForm() {
            const f = document.getElementById('add-form');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }

        async function saveEntry() {
            const issuer = document.getElementById('entry-issuer').value.trim();
            const account = document.getElementById('entry-account').value.trim();
            const secret = document.getElementById('entry-secret').value.replace(/\s+/g, '').toUpperCase(); 
            
            if(!issuer || !secret) { Terminal.toast('ISSUER & SECRET REQUIRED', 'danger'); return; }
            if (!/^[A-Z2-7]+=*$/.test(secret)) { Terminal.toast('INVALID BASE32 SECRET', 'danger'); return; }

            const newEntry = { id: Date.now(), issuer: issuer, account: account, secret: secret };
            authData.push(newEntry);
            
            await syncToSatellites(encryptPayload(JSON.stringify(authData), activePassword));
            
            document.getElementById('entry-issuer').value = '';
            document.getElementById('entry-account').value = '';
            document.getElementById('entry-secret').value = '';
            toggleAddForm();
            renderAuthCards();
        }

        function openEditModal(id) {
            const item = authData.find(i => i.id === id);
            if(!item) return;

            document.getElementById('edit-id').value = item.id;
            document.getElementById('edit-issuer').value = item.issuer;
            document.getElementById('edit-account').value = item.account;
            document.getElementById('edit-secret').value = item.secret;
            Terminal.modal.open('editModal');
        }

        async function saveEditEntry() {
            const id = parseInt(document.getElementById('edit-id').value);
            const index = authData.findIndex(i => i.id === id);
            if(index === -1) return;

            const issuer = document.getElementById('edit-issuer').value.trim();
            const account = document.getElementById('edit-account').value.trim();
            const secret = document.getElementById('edit-secret').value.replace(/\s+/g, '').toUpperCase();

            if(!issuer || !secret) { Terminal.toast('ISSUER & SECRET REQUIRED', 'danger'); return; }
            if (!/^[A-Z2-7]+=*$/.test(secret)) { Terminal.toast('INVALID BASE32 SECRET', 'danger'); return; }

            authData[index].issuer = issuer;
            authData[index].account = account;
            authData[index].secret = secret;

            await syncToSatellites(encryptPayload(JSON.stringify(authData), activePassword));
            Terminal.modal.close('editModal');
            renderAuthCards();
        }

        async function deleteEntry(id) {
            if(confirm("> WARNING: Irrevocably destroy this 2FA Key?")) {
                authData = authData.filter(i => i.id !== id);
                await syncToSatellites(encryptPayload(JSON.stringify(authData), activePassword));
                renderAuthCards();
            }
        }

        function renderAuthCards() {
            updateSatelliteBadge(); // Selalu refresh diagnostic badge

            const container = document.getElementById('auth-items');
            container.innerHTML = '';
            
            if(authData.length === 0) {
                container.innerHTML = `<div class="w-100 text-center text-muted p-5 t-border border-dashed">> TOKEN: AUTH EMPTY. INJECT KEYS.</div>`;
                return;
            }

            authData.forEach(item => {
                const div = document.createElement('div');
                div.className = `t-card auth-card`;
                div.setAttribute('data-search', `${item.issuer.toLowerCase()} ${item.account.toLowerCase()}`);
                
                div.innerHTML = `
                    <div class="d-flex justify-content-between t-border-bottom pb-2 mb-3">
                        <div>
                            <span class="font-bold text-success">> ${item.issuer}</span>
                            <div class="fs-small text-muted">${item.account}</div>
                        </div>
                        <div class="t-dropdown">
                            <button class="t-btn t-btn-sm t-dropdown-btn" onclick="Terminal.dropdown(this)">[ v ]</button>
                            <div class="t-dropdown-menu">
                                <a class="t-dropdown-item text-warning" onclick="openEditModal(${item.id})">> EDIT_KEY</a>
                                <a class="t-dropdown-item text-danger" onclick="deleteEntry(${item.id})">> PURGE_KEY</a>
                            </div>
                        </div>
                    </div>
                    <div class="text-center" onclick="copyTotp(document.getElementById('totp-${item.id}').innerText, this)">
                        <div id="totp-${item.id}" class="text-success t-totp-code" style="cursor:pointer;" title="Click to Copy">------</div>
                    </div>
                    <div class="t-timer-line-bg">
                        <div id="bar-${item.id}" class="t-timer-line"></div>
                    </div>
                `;
                container.appendChild(div);
            });
            updateTotpValues(); 
        }

        function updateTotpValues() {
            if (!activePassword || authData.length === 0) return;
            
            const epoch = Math.floor(Date.now() / 1000);
            const count = epoch % 30;
            const remaining = 30 - count;
            const percent = (remaining / 30) * 100;
            const isWarning = remaining <= 5; 

            authData.forEach(item => {
                const textEl = document.getElementById(`totp-${item.id}`);
                const barEl = document.getElementById(`bar-${item.id}`);
                if (!textEl || !barEl) return;

                if (remaining === 30 || textEl.innerText === '------' || textEl.innerText === 'ERROR') {
                    try {
                        let totp = new OTPAuth.TOTP({
                            issuer: item.issuer, label: item.account, algorithm: 'SHA1',
                            digits: 6, period: 30, secret: OTPAuth.Secret.fromBase32(item.secret)
                        });
                        let code = totp.generate();
                        textEl.innerText = code.slice(0,3) + ' ' + code.slice(3); 
                    } catch (e) {
                        console.error("> SYS_ERR (TOTP_CALC):", e);
                        textEl.innerText = 'ERROR';
                    }
                }

                barEl.style.width = `${percent}%`;
                
                if (isWarning) {
                    textEl.classList.add('warning', 't-flicker'); barEl.classList.add('warning');
                } else {
                    textEl.classList.remove('warning', 't-flicker'); barEl.classList.remove('warning');
                }
            });
        }

        function startTotpLoop() {
            if (totpInterval) clearInterval(totpInterval);
            totpInterval = setInterval(updateTotpValues, 1000);
        }

        function startInactivityLoop() {
            if (inactivityTimer) clearInterval(inactivityTimer);
            inactivityTimer = setInterval(() => {
                timeRemaining--; updateLockDisplay();
                if (timeRemaining <= 0) lockCore();
            }, 1000);
        }

        function filterAuth(query) {
            const q = query.toLowerCase();
            document.querySelectorAll('.auth-card').forEach(card => {
                card.style.display = card.getAttribute('data-search').includes(q) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>