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
    <title>SECURE: VAULT - Bunker Control</title>
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css">  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js" integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .pane { display: none; }
        .pane.active { display: block; animation: fadeIn 0.3s ease; }

        .blur-text { filter: blur(6px); cursor: copy; transition: 0.2s; background: rgba(0,255,65,0.1); padding: 2px 5px; border-radius: 3px; user-select: none; }
        .blur-text:hover { filter: blur(0); }
        
        .copy-target { cursor: copy; transition: 0.2s; padding: 2px 5px; border-radius: 3px; }
        .copy-target:hover { background: rgba(0,255,65,0.1); color: var(--t-green) !important; text-shadow: 0 0 5px var(--t-green); }

        .dynamic-fields { display: none; margin-top: 15px; }

        .cli-search { border: none; border-bottom: 1px dashed var(--t-green); border-radius: 0; background: transparent; padding-left: 0; color: var(--t-green); font-family: monospace; font-size: 1.1rem; }
        .cli-search:focus { background: transparent; color: #fff; border-bottom: 1px solid var(--t-green); box-shadow: none; }
        .cli-search::placeholder { color: var(--t-green-dim); opacity: 0.5; }

        /* Filter Tabs */
        .filter-tab { opacity: 0.5; border: 1px dashed transparent; transition: 0.3s; }
        .filter-tab:hover { opacity: 0.8; border-color: var(--t-green-dim); }
        .filter-tab.active { opacity: 1; border: 1px solid var(--t-green); box-shadow: 0 0 8px rgba(0,255,65,0.2); }
    </style>
</head>
<body class="t-crt">

    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > INITIALIZING_SECURE_VAULT<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3 mt-4">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> SECURE: VAULT</h2>
                <div class="text-muted fs-small mt-1">> ZERO-KNOWLEDGE MULTI-CLOUD ENABLED.</div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div id="pane-setup" class="pane t-card mb-4 text-center p-4">
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

        <div id="pane-unlock" class="pane t-card mb-4 text-center p-4">
            <h3 class="mb-3 text-danger t-flicker">> VAULT_LOCKED</h3>
            <p class="text-muted fs-small mb-4">Terminal linked to Satellites. Awaiting Master Decryption Key.</p>
            <div class="t-center-box">
                <input type="password" id="master-password" class="t-input text-center text-success font-bold mb-4" style="letter-spacing: 2px; font-size: 1.2rem;" placeholder="ENTER MASTER PASSWORD..." onkeypress="if(event.key === 'Enter') unlockVault()">
                <button onclick="unlockVault()" id="btn-unlock" class="t-btn t-btn-block font-bold t-glow">[ DECRYPT_PAYLOAD ]</button>
                <div class="mt-5 t-border-top pt-4 pb-2">
                    <button onclick="purgeSetup()" class="t-btn danger t-btn-sm mt-3">> PURGE_TERMINAL_LINK</button>
                </div>
            </div>
        </div>

        <div id="pane-init" class="pane t-card mb-4 text-center p-4" style="border-color: var(--t-yellow);">
            <h3 class="mb-3 text-warning t-flicker">> SATELLITES_BLANK</h3>
            <p class="text-muted fs-small mb-4">Detected empty payloads in Clouds. Please create a new Master Password to initialize your Vault.</p>
            <div class="t-center-box text-left">
                <label class="t-form-label text-warning">> CREATE_MASTER_PASSWORD</label>
                <input type="password" id="init-password" class="t-input mb-3 text-warning font-bold" style="letter-spacing: 2px;">
                <label class="t-form-label text-warning">> CONFIRM_PASSWORD</label>
                <input type="password" id="init-confirm" class="t-input mb-4 text-warning font-bold" style="letter-spacing: 2px;" onkeypress="if(event.key === 'Enter') initializeVault()">
                <button onclick="initializeVault()" class="t-btn t-btn-block font-bold text-warning" style="border-color: var(--t-yellow);">[ FORMAT_AND_ENCRYPT ]</button>
                <div class="mt-5 text-center t-border-top pt-4 pb-2">
                    <button onclick="purgeSetup()" class="t-btn danger t-btn-sm mt-3">> PURGE_TERMINAL_LINK</button>
                </div>
            </div>
        </div>

        <div id="pane-vault" class="pane">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button onclick="toggleAddForm()" class="t-btn font-bold t-glow">[ + ADD_NEW_PAYLOAD ]</button>
                <button onclick="lockVault()" class="t-btn danger t-btn-sm">[ LOCK_VAULT ]</button>
            </div>

            <div id="add-form" class="t-card mb-4" style="display: none; background: rgba(0,255,65,0.02);">
                <div class="t-card-header">> ENCRYPT_NEW_DATA</div>
                
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="width: 150px;">
                        <label class="t-form-label">> TYPE</label>
                        <select id="entry-type" class="t-select m-0" onchange="toggleFormMode('add')">
                            <option value="LOGIN">LOGIN</option>
                            <option value="NOTE">NOTE</option>
                            <option value="CARD">CARD</option>
                            <option value="WIFI">WI-FI</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="t-form-label">> TITLE / IDENTIFIER</label>
                        <input type="text" id="entry-title" class="t-input m-0" placeholder="e.g., ProtonMail, BNI Debit, Home Router">
                    </div>
                </div>
                
                <div id="add-fields-LOGIN" class="dynamic-fields">
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> ID / EMAIL</label>
                            <input type="text" id="entry-login-email" class="t-input m-0" placeholder="Identify...">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label class="t-form-label">> PASSWORD <span id="strength-entry-pass" class="fs-small ml-2"></span></label>
                            <div class="t-input-group m-0">
                                <input type="text" id="entry-login-pass" class="t-input m-0" placeholder="The Password" oninput="checkStrength(this.value, 'strength-entry-pass')">
                                <button type="button" class="t-input-action-btn text-warning" onclick="generateAndFill('entry-login-pass', 'strength-entry-pass')" title="Generate Secure Password">[ GEN ]</button>
                            </div>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> URL_TARGET</label>
                            <input type="url" id="entry-login-url" class="t-input m-0" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <div id="add-fields-CARD" class="dynamic-fields">
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> NAME_ON_CARD</label>
                            <input type="text" id="entry-card-name" class="t-input m-0" placeholder="Account Holder">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label class="t-form-label">> CARD / ACCOUNT_NUMBER</label>
                            <input type="text" id="entry-card-number" class="t-input m-0" placeholder="0000 0000 0000 0000">
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="flex: 1; min-width: 100px;">
                            <label class="t-form-label">> EXPIRE (MM/YY)</label>
                            <input type="text" id="entry-card-exp" class="t-input m-0" placeholder="12/28">
                        </div>
                        <div style="flex: 1; min-width: 100px;">
                            <label class="t-form-label">> SECURITY (CVC/CVV)</label>
                            <input type="text" id="entry-card-cvc" class="t-input m-0" placeholder="***">
                        </div>
                        <div style="flex: 1; min-width: 100px;">
                            <label class="t-form-label">> PIN_CODE</label>
                            <input type="text" id="entry-card-pin" class="t-input m-0" placeholder="******">
                        </div>
                    </div>
                </div>

                <div id="add-fields-WIFI" class="dynamic-fields">
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="flex: 1; min-width: 250px;">
                            <label class="t-form-label">> WIFI_PASSWORD <span id="strength-entry-wifi" class="fs-small ml-2"></span></label>
                            <div class="t-input-group m-0">
                                <input type="text" id="entry-wifi-pass" class="t-input m-0" placeholder="Password for this SSID" oninput="checkStrength(this.value, 'strength-entry-wifi')">
                                <button type="button" class="t-input-action-btn text-warning" onclick="generateAndFill('entry-wifi-pass', 'strength-entry-wifi')">[ GEN ]</button>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="t-form-label mt-3">> PRIVATE_NOTES / DETAILS</label>
                <textarea id="entry-content" class="t-textarea mb-4" rows="3" placeholder="Additional details..."></textarea>
                
                <button onclick="saveEntry()" class="t-btn t-btn-block" id="btn-save">[ ENCRYPT & SYNC TO SATELLITES ]</button>
            </div>

            <div class="mb-4 t-card" style="border-style: dashed; background: transparent; padding: 15px;">
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 t-border-bottom">
                    <div class="fs-small text-muted">> DATA_SOURCE: <span id="badge-satellite" class="t-badge primary">UNKNOWN</span></div>
                    <div id="badge-sync" class="fs-small text-warning font-bold t-flicker" style="display:none;">> PENDING UPLOAD</div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="text-success font-bold">></span>
                    <input type="text" id="live-search" class="t-input cli-search w-100 m-0" placeholder="QUERY_VAULT_DATA..." onkeyup="renderVault()">
                </div>
                
                <div class="d-flex gap-2 flex-wrap" id="filter-container">
                    <button class="t-btn t-btn-sm filter-tab active" onclick="setFilter('ALL', this)" id="tab-ALL">[ ALL: 0 ]</button>
                    <button class="t-btn t-btn-sm filter-tab" onclick="setFilter('LOGIN', this)" id="tab-LOGIN">[ LOGIN: 0 ]</button>
                    <button class="t-btn t-btn-sm filter-tab" onclick="setFilter('NOTE', this)" id="tab-NOTE">[ NOTE: 0 ]</button>
                    <button class="t-btn t-btn-sm filter-tab" onclick="setFilter('CARD', this)" id="tab-CARD">[ CARD: 0 ]</button>
                    <button class="t-btn t-btn-sm filter-tab" onclick="setFilter('WIFI', this)" id="tab-WIFI">[ WI-FI: 0 ]</button>
                </div>
            </div>

            <div id="vault-items"></div>
        </div>

    </div>

    <div id="editModal" class="t-modal">
        <div class="t-modal-content">
            <div class="t-card-header d-flex justify-content-between align-items-center">
                <span class="text-success">> MODIFY_PAYLOAD</span>
                <button class="t-btn danger t-btn-sm" onclick="Terminal.modal.close('editModal')">[ X CLOSE ]</button>
            </div>
            
            <input type="hidden" id="edit-id">
            
            <div class="d-flex gap-3 mb-3 flex-wrap">
                <div style="width: 150px;">
                    <label class="t-form-label">> TYPE</label>
                    <select id="edit-type" class="t-select m-0" onchange="toggleFormMode('edit')">
                        <option value="LOGIN">LOGIN</option>
                        <option value="NOTE">NOTE</option>
                        <option value="CARD">CARD</option>
                        <option value="WIFI">WI-FI</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label class="t-form-label">> TITLE</label>
                    <input type="text" id="edit-title" class="t-input m-0" placeholder="Title">
                </div>
            </div>
            
            <div id="edit-fields-LOGIN" class="dynamic-fields">
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 120px;">
                        <label class="t-form-label">> ID/EMAIL</label>
                        <input type="text" id="edit-login-email" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="t-form-label">> PASS <span id="strength-edit-pass" class="fs-small ml-2"></span></label>
                        <div class="t-input-group m-0">
                            <input type="text" id="edit-login-pass" class="t-input m-0" oninput="checkStrength(this.value, 'strength-edit-pass')">
                            <button type="button" class="t-input-action-btn text-warning" onclick="generateAndFill('edit-login-pass', 'strength-edit-pass')">[ GEN ]</button>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label class="t-form-label">> URL</label>
                        <input type="url" id="edit-login-url" class="t-input m-0">
                    </div>
                </div>
            </div>

            <div id="edit-fields-CARD" class="dynamic-fields">
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 150px;">
                        <label class="t-form-label">> NAME_ON_CARD</label>
                        <input type="text" id="edit-card-name" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="t-form-label">> CARD / ACCOUNT_NUM</label>
                        <input type="text" id="edit-card-number" class="t-input m-0">
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 100px;">
                        <label class="t-form-label">> EXP (MM/YY)</label>
                        <input type="text" id="edit-card-exp" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 100px;">
                        <label class="t-form-label">> CVC</label>
                        <input type="text" id="edit-card-cvc" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 100px;">
                        <label class="t-form-label">> PIN</label>
                        <input type="text" id="edit-card-pin" class="t-input m-0">
                    </div>
                </div>
            </div>

            <div id="edit-fields-WIFI" class="dynamic-fields">
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 200px;">
                        <label class="t-form-label">> WIFI_PASSWORD <span id="strength-edit-wifi" class="fs-small ml-2"></span></label>
                        <div class="t-input-group m-0">
                            <input type="text" id="edit-wifi-pass" class="t-input m-0" oninput="checkStrength(this.value, 'strength-edit-wifi')">
                            <button type="button" class="t-input-action-btn text-warning" onclick="generateAndFill('edit-wifi-pass', 'strength-edit-wifi')">[ GEN ]</button>
                        </div>
                    </div>
                </div>
            </div>

            <label class="t-form-label mt-3">> PRIVATE_NOTES</label>
            <textarea id="edit-content" class="t-textarea mb-4" rows="3"></textarea>
            
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="t-btn danger" onclick="Terminal.modal.close('editModal')">[ ABORT ]</button>
                <button onclick="saveEditEntry()" id="btn-edit-save" class="t-btn t-glow font-bold">[ OVERRIDE_PAYLOAD ]</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>
    <script>
        // MULTI-CLOUD CREDENTIALS (ISOLATED NAMESPACE FOR VAULT)
        let ghToken = localStorage.getItem('vault_gh_token');
        let gistId = localStorage.getItem('vault_gist_id');
        let glToken = localStorage.getItem('vault_gl_token');
        let snippetId = localStorage.getItem('vault_gl_id');

        let vaultData = [];
        let activePassword = '';
        let satelliteState = 'LOCKED';
        let currentFilter = 'ALL';
        let activeSatellite = 'UNKNOWN'; 
        let clipboardTimer = null; 

        document.addEventListener("DOMContentLoaded", () => {
            Terminal.splash.close(1000);
            
            syncPendingData().then(() => {
                checkSatelliteStatus();
            });
            
            window.addEventListener('online', () => {
                Terminal.toast('> INTERNET CONNECTION RESTORED.', 'normal');
                syncPendingData();
            });
            
            toggleFormMode('add');
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

        // ================= BUNKER PASSGEN =================
        function checkStrength(pwd, elId) {
            const el = document.getElementById(elId);
            if(!el) return;
            if(!pwd) { el.innerHTML = ''; return; }
            
            let score = 0;
            if(pwd.length >= 8) score++;
            if(pwd.length >= 12) score++;
            if(/[A-Z]/.test(pwd)) score++;
            if(/[a-z]/.test(pwd)) score++;
            if(/[0-9]/.test(pwd)) score++;
            if(/[^A-Za-z0-9]/.test(pwd)) score++;

            if(score <= 2) el.innerHTML = '<span class="text-danger">[ WEAK ]</span>';
            else if(score <= 4) el.innerHTML = '<span class="text-warning">[ FAIR ]</span>';
            else el.innerHTML = '<span class="text-success">[ STRONG ]</span>';
        }

        function generateAndFill(inputId, strengthId) {
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
            let pwd = "";
            const array = new Uint32Array(16);
            window.crypto.getRandomValues(array);
            for (let i = 0; i < 16; i++) {
                pwd += chars[array[i] % chars.length];
            }
            const input = document.getElementById(inputId);
            input.value = pwd;
            if(input.type === 'password') input.type = 'text'; 
            checkStrength(pwd, strengthId);
        }

        // ================= DIAGNOSTIK UI =================
        function updateSatelliteBadge() {
            const badge = document.getElementById('badge-satellite');
            if(badge) {
                if(activeSatellite === 'GITHUB') {
                    badge.className = 't-badge success'; 
                    badge.innerText = 'GITHUB GIST';
                } else if(activeSatellite === 'GITLAB') {
                    badge.className = 't-badge warning'; 
                    badge.innerText = 'GITLAB SNIPPET';
                } else if(activeSatellite === 'LOCAL_CACHE') {
                    badge.className = 't-badge danger t-flicker'; 
                    badge.innerText = 'LOCAL CACHE (OFFLINE)';
                } else {
                    badge.className = 't-badge primary'; 
                    badge.innerText = 'UNKNOWN';
                }
            }
            const syncBadge = document.getElementById('badge-sync');
            if(syncBadge) {
                syncBadge.style.display = (localStorage.getItem('bunker_vault_pending_sync') === 'true') ? 'block' : 'none';
            }
        }

        // ================= UI CONTROLS =================
        function cleanUrl(val) {
            let cl = val.replace(/\s+/g, '');
            if (cl.includes('/')) return cl.split('/').pop();
            return cl;
        }

        function setFilter(filterType, btnElement) {
            currentFilter = filterType;
            document.querySelectorAll('.filter-tab').forEach(btn => btn.classList.remove('active'));
            if(btnElement) btnElement.classList.add('active');
            renderVault();
        }

        function toggleAccordion(btn) {
            const allActive = document.querySelectorAll('#vault-items .t-accordion-btn.active');
            allActive.forEach(activeBtn => {
                if (activeBtn !== btn) {
                    Terminal.accordion(activeBtn); 
                }
            });
            Terminal.accordion(btn);
        }

        function toggleFormMode(mode) {
            const prefix = mode === 'add' ? 'entry' : 'edit';
            const type = document.getElementById(`${prefix}-type`).value;
            
            document.querySelectorAll(mode === 'add' ? '#add-form .dynamic-fields' : '#editModal .dynamic-fields').forEach(el => {
                el.style.display = 'none';
            });
            
            if (type !== 'NOTE') {
                const targetField = document.getElementById(`${mode}-fields-${type}`);
                if (targetField) targetField.style.display = 'block';
            }
        }

        function copyText(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                Terminal.toast('DATA COPIED TO CLIPBOARD', 'normal');
                const originalText = element.innerText;
                const isBlur = element.classList.contains('blur-text');
                
                if (isBlur) element.classList.remove('blur-text');
                element.innerText = '[ COPIED_TO_MEM ]';
                element.style.color = 'var(--t-green)';
                element.style.fontWeight = 'bold';
                
                setTimeout(() => {
                    element.innerText = originalText;
                    element.style.color = '';
                    element.style.fontWeight = 'normal';
                    if (isBlur) element.classList.add('blur-text');
                }, 1500);

                if(clipboardTimer) clearTimeout(clipboardTimer);
                clipboardTimer = setTimeout(() => {
                    navigator.clipboard.writeText(' ').then(() => {
                        Terminal.toast('> CLIPBOARD SECURED (AUTO-CLEARED)', 'warning');
                    }).catch(e => console.log(e));
                }, 30000);

            }).catch(err => {
                Terminal.toast('SYS_ERR: CLIPBOARD ACCESS DENIED', 'danger');
            });
        }

        function toggleAddForm() {
            const f = document.getElementById('add-form');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }

        function updateUI() {
            document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
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
                document.getElementById('pane-vault').classList.add('active');
                renderVault(); 
            }
        }

        // ================= SATELLITE COMMS (DUAL READ) =================
        async function fetchFromSatellites() {
            let content = null;
            try {
                const resGH = await fetch(`https://api.github.com/gists/${gistId}?t=${Date.now()}`, {
                    headers: { "Accept": "application/vnd.github.v3+json", "Authorization": `token ${ghToken}` }
                });
                const dataGH = await resGH.json();
                if (dataGH.message) throw new Error("GH_REJECTED: " + dataGH.message);
                
                if (dataGH.files && dataGH.files["bunker_vault.enc"]) {
                    content = dataGH.files["bunker_vault.enc"].content;
                    Terminal.toast('> CONNECTED TO GITHUB', 'normal');
                    return { content: content, source: 'GITHUB' };
                } else {
                    throw new Error("GH_MISSING_FILE");
                }
            } catch(e) {
                console.warn("> GH Fetch Failed:", e.message);
            }

            if (glToken && snippetId) {
                try {
                    const resGL = await fetch(`https://gitlab.com/api/v4/snippets/${snippetId}`, { 
                        headers: { "PRIVATE-TOKEN": glToken }
                    });
                    const dataGL = await resGL.json();
                    if(dataGL.files) {
                        const fileMeta = dataGL.files.find(f => f.path === "bunker_vault.enc");
                        if(fileMeta) {
                            const rawRes = await fetch(fileMeta.raw_url, { headers: { "PRIVATE-TOKEN": glToken }});
                            content = await rawRes.text();
                            Terminal.toast('> GITHUB DOWN. ROUTED TO GITLAB.', 'warning');
                            return { content: content, source: 'GITLAB' };
                        } else {
                            throw new Error("GL_MISSING_FILE");
                        }
                    }
                } catch(e) {
                    console.warn("> GL Fetch Failed:", e.message);
                }
            }
            throw new Error("ALL_SATELLITES_UNREACHABLE");
        }

        async function checkSatelliteStatus() {
            if (!ghToken || !gistId) { updateUI(); return; }
            try {
                let result = await fetchFromSatellites();
                activeSatellite = result.source;
                localStorage.setItem('bunker_vault_cache', result.content);
                satelliteState = (result.content.trim() === 'INIT' || result.content === '') ? 'INIT' : 'LOCKED';
                updateUI();
            } catch(e) {
                const cachedContent = localStorage.getItem('bunker_vault_cache');
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

        async function syncPendingData() {
            if (localStorage.getItem('bunker_vault_pending_sync') === 'true') {
                const cachedPayload = localStorage.getItem('bunker_vault_cache');
                if (cachedPayload && ghToken) {
                    Terminal.toast('> DETECTED OFFLINE CHANGES. SYNCING TO SATELLITES...', 'warning');
                    await syncToSatellites(cachedPayload);
                }
            }
        }

        // --- PROTOCOL DUAL-WRITE (SYNC / SAVE) ---
        async function syncToSatellites(encryptedPayload) {
            Terminal.splash.show('> ENCRYPTING & SYNCING TO SATELLITES...');
            localStorage.setItem('bunker_vault_cache', encryptedPayload);
            const promises = [];

            if (ghToken && gistId) {
                promises.push(fetch(`https://api.github.com/gists/${gistId}`, {
                    method: 'PATCH',
                    headers: { "Accept": "application/vnd.github.v3+json", "Authorization": `token ${ghToken}`, "Content-Type": "application/json" },
                    body: JSON.stringify({ files: { "bunker_vault.enc": { content: encryptedPayload } } })
                }).then(res => { if(!res.ok) throw new Error(); return 'GH'; }));
            }

            if (glToken && snippetId) {
                promises.push(fetch(`https://gitlab.com/api/v4/snippets/${snippetId}`, {
                    method: 'PUT',
                    headers: { "PRIVATE-TOKEN": glToken, "Content-Type": "application/json" },
                    body: JSON.stringify({ files: [{ action: "update", file_path: "bunker_vault.enc", content: encryptedPayload }] })
                }).then(res => { if(!res.ok) throw new Error(); return 'GL'; }));
            }

            if(promises.length === 0) return;

            try {
                const results = await Promise.allSettled(promises);
                const success = results.filter(r => r.status === 'fulfilled').length;

                if (success === 0) throw new Error("ALL_FAILED");
                else if (success < promises.length) {
                    Terminal.toast('PARTIAL SYNC. ONE SATELLITE DOWN.', 'warning');
                    localStorage.setItem('bunker_vault_pending_sync', 'true');
                } else {
                    Terminal.toast('DUAL-SATELLITE SYNC SUCCESSFUL', 'normal');
                    localStorage.removeItem('bunker_vault_pending_sync');
                }
            } catch (e) {
                localStorage.setItem('bunker_vault_pending_sync', 'true');
                Terminal.toast('SAVED LOCALLY. PENDING SYNC (OFFLINE)', 'warning');
            }
            updateSatelliteBadge(); 
            Terminal.splash.close(500);
        }

        function saveSetup() {
            let tGH = document.getElementById('setup-gh-token').value.replace(/\s+/g, '');
            let iGH = cleanUrl(document.getElementById('setup-gh-id').value);
            let tGL = document.getElementById('setup-gl-token').value.replace(/\s+/g, '');
            let iGL = cleanUrl(document.getElementById('setup-gl-id').value);

            if(tGH && iGH) {
                Terminal.splash.show('> LINKING TO SATELLITES...');
                setTimeout(() => {
                    localStorage.setItem('vault_gh_token', tGH);
                    localStorage.setItem('vault_gist_id', iGH);
                    if(tGL && iGL) { 
                        localStorage.setItem('vault_gl_token', tGL); 
                        localStorage.setItem('vault_gl_id', iGL); 
                    }
                    ghToken = tGH; gistId = iGH; glToken = tGL; snippetId = iGL;
                    checkSatelliteStatus();
                    Terminal.splash.close(500);
                }, 800);
            } else {
                Terminal.toast('GITHUB CREDENTIALS ARE REQUIRED', 'danger');
            }
        }

        function purgeSetup() {
            if(confirm("> WARNING: Destroy all terminal link coordinates?")) {
                localStorage.removeItem('vault_gh_token'); 
                localStorage.removeItem('vault_gist_id');
                localStorage.removeItem('vault_gl_token'); 
                localStorage.removeItem('vault_gl_id');
                ghToken = null; gistId = null; glToken = null; snippetId = null;
                satelliteState = 'LOCKED';
                updateUI();
            }
        }

        async function initializeVault() {
            const pwd1 = document.getElementById('init-password').value;
            const pwd2 = document.getElementById('init-confirm').value;
            if(!pwd1 || !pwd2) return;
            if(pwd1 !== pwd2) { Terminal.toast('PASSWORDS DO NOT MATCH', 'danger'); return; }

            vaultData = []; activePassword = pwd1; satelliteState = 'UNLOCKED';
            document.getElementById('init-password').value = ''; document.getElementById('init-confirm').value = '';
            
            Terminal.toast('VAULT FORMATTED. SYNCING CLOUDS...', 'warning');
            await syncToSatellites(encryptPayload("[]", activePassword));
            updateUI();
        }

        async function unlockVault() {
            const pwd = document.getElementById('master-password').value;
            if(!pwd) return;
            
            Terminal.splash.show('> DECRYPTING PAYLOAD...');
            let content = '';
            
            try {
                let result = await fetchFromSatellites();
                content = result.content;
                activeSatellite = result.source;
                localStorage.setItem('bunker_vault_cache', content);
            } catch(e) {
                content = localStorage.getItem('bunker_vault_cache');
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
                
                vaultData = JSON.parse(decryptedStr);
                
                let isMigrated = false;
                // JIKA DATA BUKAN v3 (SHA-256), MAKA AUTO MIGRASI
                if (!content.startsWith('v3|')) {
                    isMigrated = true;
                    Terminal.toast('> LEGACY DATA DETECTED. AUTO-UPGRADING TO SHA-256...', 'warning');
                }

                vaultData = vaultData.map(item => {
                    if (item.type === 'PASSWORD') { item.type = 'LOGIN'; isMigrated = true; }
                    return item;
                });
                
                activePassword = pwd;
                satelliteState = 'UNLOCKED';
                document.getElementById('master-password').value = ''; 
                updateUI();
                Terminal.toast('ACCESS GRANTED', 'normal');
                
                // JIKA TERDETEKSI MIGRASI, LANGSUNG ENKRIPSI ULANG & SYNC KE SATELIT
                if(isMigrated) {
                    syncToSatellites(encryptPayload(JSON.stringify(vaultData), activePassword));
                }
            } catch(e) {
                Terminal.toast('INVALID MASTER PASSWORD', 'danger');
            }
            Terminal.splash.close(500);
        }

        function lockVault() {
            vaultData = []; activePassword = ''; satelliteState = 'LOCKED';
            document.getElementById('live-search').value = ''; currentFilter = 'ALL';
            checkSatelliteStatus();
            Terminal.toast('VAULT SECURED', 'warning');
        }

        // ================= CRUD OPERATIONS =================

        async function saveEntry() {
            const type = document.getElementById('entry-type').value;
            const title = document.getElementById('entry-title').value.trim();
            const content = document.getElementById('entry-content').value.trim();
            if(!title) { Terminal.toast('TITLE/IDENTIFIER IS REQUIRED', 'danger'); return; }

            const newEntry = { id: Date.now(), type: type, title: title, content: content, date: new Date().toISOString().split('T')[0] };

            if (type === 'LOGIN') {
                newEntry.email = document.getElementById('entry-login-email').value.trim();
                newEntry.password = document.getElementById('entry-login-pass').value.trim();
                newEntry.url = document.getElementById('entry-login-url').value.trim();
            } else if (type === 'CARD') {
                newEntry.cardName = document.getElementById('entry-card-name').value.trim();
                newEntry.cardNumber = document.getElementById('entry-card-number').value.trim();
                newEntry.cardExpiry = document.getElementById('entry-card-exp').value.trim();
                newEntry.cardCvc = document.getElementById('entry-card-cvc').value.trim();
                newEntry.cardPin = document.getElementById('entry-card-pin').value.trim();
            } else if (type === 'WIFI') {
                newEntry.wifiPass = document.getElementById('entry-wifi-pass').value.trim();
            }
            
            vaultData.unshift(newEntry);
            await syncToSatellites(encryptPayload(JSON.stringify(vaultData), activePassword));
            
            document.querySelectorAll('#add-form input, #add-form textarea').forEach(el => {
                if(el.type !== 'button') el.value = '';
            });
            document.querySelectorAll('[id^="strength-"]').forEach(el => el.innerHTML = ''); 
            toggleAddForm();
            renderVault();
        }

        function openEditModal(id) {
            const item = vaultData.find(i => i.id === id);
            if(!item) return;

            document.querySelectorAll('#editModal input:not([type="hidden"]), #editModal textarea').forEach(el => {
                if(el.type !== 'button') el.value = '';
            });
            document.querySelectorAll('[id^="strength-"]').forEach(el => el.innerHTML = '');

            document.getElementById('edit-id').value = item.id;
            document.getElementById('edit-type').value = item.type;
            document.getElementById('edit-title').value = item.title || '';
            document.getElementById('edit-content').value = item.content || '';
            
            if (item.type === 'LOGIN') {
                document.getElementById('edit-login-email').value = item.email || '';
                document.getElementById('edit-login-pass').value = item.password || '';
                document.getElementById('edit-login-url').value = item.url || '';
                checkStrength(item.password, 'strength-edit-pass');
            } else if (item.type === 'CARD') {
                document.getElementById('edit-card-name').value = item.cardName || '';
                document.getElementById('edit-card-number').value = item.cardNumber || '';
                document.getElementById('edit-card-exp').value = item.cardExpiry || '';
                document.getElementById('edit-card-cvc').value = item.cardCvc || '';
                document.getElementById('edit-card-pin').value = item.cardPin || '';
            } else if (item.type === 'WIFI') {
                document.getElementById('edit-wifi-pass').value = item.wifiPass || '';
                checkStrength(item.wifiPass, 'strength-edit-wifi');
            }

            toggleFormMode('edit');
            Terminal.modal.open('editModal');
        }

        async function saveEditEntry() {
            const id = parseInt(document.getElementById('edit-id').value);
            const index = vaultData.findIndex(i => i.id === id);
            if(index === -1) return;

            const type = document.getElementById('edit-type').value;
            const title = document.getElementById('edit-title').value.trim();
            if(!title) { Terminal.toast('TITLE IS REQUIRED', 'danger'); return; }

            delete vaultData[index].email; delete vaultData[index].password; delete vaultData[index].url;
            delete vaultData[index].cardName; delete vaultData[index].cardNumber; delete vaultData[index].cardExpiry; delete vaultData[index].cardCvc; delete vaultData[index].cardPin;
            delete vaultData[index].wifiPass;

            vaultData[index].type = type;
            vaultData[index].title = title;
            vaultData[index].content = document.getElementById('edit-content').value.trim();

            if (type === 'LOGIN') {
                vaultData[index].email = document.getElementById('edit-login-email').value.trim();
                vaultData[index].password = document.getElementById('edit-login-pass').value.trim();
                vaultData[index].url = document.getElementById('edit-login-url').value.trim();
            } else if (type === 'CARD') {
                vaultData[index].cardName = document.getElementById('edit-card-name').value.trim();
                vaultData[index].cardNumber = document.getElementById('edit-card-number').value.trim();
                vaultData[index].cardExpiry = document.getElementById('edit-card-exp').value.trim();
                vaultData[index].cardCvc = document.getElementById('edit-card-cvc').value.trim();
                vaultData[index].cardPin = document.getElementById('edit-card-pin').value.trim();
            } else if (type === 'WIFI') {
                vaultData[index].wifiPass = document.getElementById('edit-wifi-pass').value.trim();
            }

            await syncToSatellites(encryptPayload(JSON.stringify(vaultData), activePassword));
            Terminal.modal.close('editModal');
            renderVault();
        }

        async function deleteEntry(id) {
            if(confirm("> WARNING: Irrevocably destroy this payload?")) {
                vaultData = vaultData.filter(i => i.id !== id);
                await syncToSatellites(encryptPayload(JSON.stringify(vaultData), activePassword));
                renderVault();
            }
        }

        function renderVault() {
            updateSatelliteBadge(); 

            const container = document.getElementById('vault-items');
            const query = document.getElementById('live-search').value.toLowerCase();
            container.innerHTML = '';
            
            const countAll = vaultData.length;
            const countLogin = vaultData.filter(i => i.type === 'LOGIN').length;
            const countNote = vaultData.filter(i => i.type === 'NOTE').length;
            const countCard = vaultData.filter(i => i.type === 'CARD').length;
            const countWifi = vaultData.filter(i => i.type === 'WIFI').length;

            document.getElementById('tab-ALL').innerText = `[ ALL: ${countAll} ]`;
            document.getElementById('tab-LOGIN').innerText = `[ LOGIN: ${countLogin} ]`;
            document.getElementById('tab-NOTE').innerText = `[ NOTE: ${countNote} ]`;
            document.getElementById('tab-CARD').innerText = `[ CARD: ${countCard} ]`;
            document.getElementById('tab-WIFI').innerText = `[ WI-FI: ${countWifi} ]`;

            let displayData = vaultData;

            if (currentFilter !== 'ALL') {
                displayData = displayData.filter(item => item.type === currentFilter);
            }

            if (query.trim() !== '') {
                displayData = displayData.filter(item => {
                    const str = `${item.title || ''} ${item.content || ''} ${item.email || ''} ${item.url || ''} ${item.cardName || ''} ${item.cardNumber || ''} ${item.wifiPass || ''}`.toLowerCase();
                    return str.includes(query);
                });
            }

            if(displayData.length === 0) {
                container.innerHTML = `<div class="text-center text-muted p-5 t-border border-dashed">> ${query === '' ? 'VAULT EMPTY / NO CATEGORY DATA.' : 'NO MATCHING DATA FOUND.'}</div>`;
                return;
            }

            displayData.forEach(item => {
                let badgeClass = 'primary';
                if (item.type === 'LOGIN') badgeClass = 'danger';
                if (item.type === 'CARD') badgeClass = 'warning';
                if (item.type === 'WIFI') badgeClass = 'success';
                
                let detailHtml = '';
                
                if (item.type === 'LOGIN') {
                    const eHtml = item.email ? `<div class="mb-2"><span class="text-muted">> ID/EMAIL:</span> <span class="text-success copy-target" onclick="copyText('${item.email}', this)">${item.email}</span></div>` : '';
                    const pHtml = item.password ? `<div class="mb-2"><span class="text-muted">> PASS_KEY:</span> <span class="blur-text text-success" onclick="copyText('${item.password}', this)">${item.password}</span></div>` : '';
                    const uHtml = item.url ? `<div><span class="text-muted">> URL_TARGET:</span> <a href="${item.url}" target="_blank" class="text-success" style="text-decoration:underline dashed;">${item.url}</a></div>` : '';
                    if(eHtml || pHtml || uHtml) detailHtml = eHtml + pHtml + uHtml + `<div class="t-border-bottom pb-3 mt-3 mb-3"></div>`;
                } 
                else if (item.type === 'CARD') {
                    const nm = item.cardName ? `<div class="mb-1"><span class="text-muted">> NAME:</span> <span class="text-success copy-target" onclick="copyText('${item.cardName}', this)">${item.cardName}</span></div>` : '';
                    const no = item.cardNumber ? `<div class="mb-1"><span class="text-muted">> NUMBER:</span> <span class="copy-target text-success" onclick="copyText('${item.cardNumber}', this)">${item.cardNumber}</span></div>` : '';
                    const ex = item.cardExpiry ? `<div class="mb-1"><span class="text-muted">> EXP:</span> <span class="text-success">${item.cardExpiry}</span></div>` : '';
                    const cv = item.cardCvc ? `<div class="mb-1"><span class="text-muted">> CVC:</span> <span class="blur-text text-success" onclick="copyText('${item.cardCvc}', this)">${item.cardCvc}</span></div>` : '';
                    const pi = item.cardPin ? `<div><span class="text-muted">> PIN:</span> <span class="blur-text text-success" onclick="copyText('${item.cardPin}', this)">${item.cardPin}</span></div>` : '';
                    if(nm || no || ex || cv || pi) detailHtml = nm + no + ex + cv + pi + `<div class="t-border-bottom pb-3 mt-3 mb-3"></div>`;
                }
                else if (item.type === 'WIFI') {
                    const wp = item.wifiPass ? `<div><span class="text-muted">> PASSWORD:</span> <span class="blur-text text-success" onclick="copyText('${item.wifiPass}', this)">${item.wifiPass}</span></div>` : '';
                    if(wp) detailHtml = wp + `<div class="t-border-bottom pb-3 mt-3 mb-3"></div>`;
                }

                const notesHtml = item.content ? `<div class="text-success">${item.content.replace(/\n/g, '<br>')}</div>` : '';

                const div = document.createElement('div');
                div.className = `t-accordion mb-3`;
                div.innerHTML = `
                    <button class="t-accordion-btn" onclick="toggleAccordion(this)" style="justify-content: flex-start; gap: 12px;">
                        <span class="d-flex align-items-center gap-2">${item.title} <span class="t-badge ${badgeClass}">${item.type}</span></span>
                        <span class="text-muted fs-small d-none d-md-inline" style="margin-left: auto;">${item.date}</span>
                    </button>
                    <div class="t-accordion-content">
                        <div class="mb-4" style="font-size: 0.95rem;">
                            ${detailHtml}
                            ${notesHtml}
                        </div>
                        <div class="d-flex justify-content-end gap-2 t-border-top pt-3">
                            <button onclick="openEditModal(${item.id})" class="t-btn t-btn-sm">[ EDIT ]</button>
                            <button onclick="deleteEntry(${item.id})" class="t-btn danger t-btn-sm">[ PURGE ]</button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }
    </script>
</body>
</html>