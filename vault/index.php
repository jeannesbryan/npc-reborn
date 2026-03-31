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
    
    <link rel="stylesheet" href="../assets/terminal.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    
    <style>
        /* Logic Visibility Pane */
        .pane { display: none; }
        .pane.active { display: block; animation: fadeIn 0.3s ease; }

        /* Fitur Sensor & Copy khusus Vault */
        .blur-text { filter: blur(6px); cursor: copy; transition: 0.2s; background: rgba(0,255,65,0.1); padding: 2px 5px; border-radius: 3px; user-select: none; }
        .blur-text:hover { filter: blur(0); }
        
        .copy-target { cursor: copy; transition: 0.2s; padding: 2px 5px; border-radius: 3px; }
        .copy-target:hover { background: rgba(0,255,65,0.1); color: var(--t-green) !important; text-shadow: 0 0 5px var(--t-green); }

        #password-fields, #edit-password-fields { display: none; margin-top: 15px; }

        /* Search Bar CLI */
        .cli-search { border: none; border-bottom: 1px dashed var(--t-green); border-radius: 0; background: transparent; padding-left: 0; color: var(--t-green); font-family: monospace; font-size: 1.1rem; }
        .cli-search:focus { background: transparent; color: #fff; border-bottom: 1px solid var(--t-green); box-shadow: none; }
        .cli-search::placeholder { color: var(--t-green-dim); opacity: 0.5; }
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
                <div class="text-muted fs-small mt-1">> ZERO-KNOWLEDGE ENCRYPTION ENABLED.</div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div id="pane-setup" class="pane t-card mb-4 text-center p-4">
            <h3 class="text-success mb-3">> PROTOCOL: COLD START</h3>
            <p class="text-muted fs-small mb-4">Unrecognized terminal. Inject GitHub coordinates to establish secure connection.</p>
            
            <div class="t-center-box text-left">
                <label class="t-form-label">> GITHUB_PAT_TOKEN</label>
                <input type="password" id="setup-token" class="t-input mb-3" style="letter-spacing: 1px;" placeholder="ghp_xxxxxxxxxxxx...">
                
                <label class="t-form-label">> GIST_ID</label>
                <input type="text" id="setup-gist" class="t-input mb-4" style="letter-spacing: 1px;" placeholder="8a7b6c5d4...">
                
                <button onclick="saveSetup()" class="t-btn t-btn-block font-bold t-glow">[ INJECT_COORDINATES ]</button>
            </div>
        </div>

        <div id="pane-unlock" class="pane t-card mb-4 text-center p-4">
            <h3 class="mb-3 text-danger t-flicker">> VAULT_LOCKED</h3>
            <p class="text-muted fs-small mb-4">Terminal linked to Satellite. Awaiting Master Decryption Key.</p>
            
            <div class="t-center-box">
                <input type="password" id="master-password" class="t-input text-center text-success font-bold mb-4" style="letter-spacing: 2px; font-size: 1.2rem;" placeholder="ENTER MASTER PASSWORD..." onkeypress="if(event.key === 'Enter') unlockVault()">
                
                <button onclick="unlockVault()" id="btn-unlock" class="t-btn t-btn-block font-bold t-glow">[ DECRYPT_PAYLOAD ]</button>
                
                <div class="mt-5 t-border-top pt-4 pb-2">
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
                        <select id="entry-type" class="t-select m-0" onchange="toggleFormMode()">
                            <option value="NOTE">NOTE</option>
                            <option value="PASSWORD">PASSWORD</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label class="t-form-label">> TITLE</label>
                        <input type="text" id="entry-title" class="t-input m-0" placeholder="e.g., ProtonMail, Debian Server">
                    </div>
                </div>
                
                <div id="password-fields">
                    <div class="d-flex gap-3 mb-3 flex-wrap">
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> ID / EMAIL</label>
                            <input type="text" id="entry-email" class="t-input m-0" placeholder="Identify...">
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> URL_TARGET</label>
                            <input type="url" id="entry-url" class="t-input m-0" placeholder="https://...">
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <label class="t-form-label">> PASSWORD</label>
                            <input type="text" id="entry-pass" class="t-input m-0" placeholder="The Password">
                        </div>
                    </div>
                </div>

                <label class="t-form-label mt-3">> PRIVATE_NOTES / DETAILS</label>
                <textarea id="entry-content" class="t-textarea mb-4" rows="3" placeholder="Details..."></textarea>
                
                <button onclick="saveEntry()" class="t-btn t-btn-block" id="btn-save">[ ENCRYPT & UPLOAD TO SATELLITE ]</button>
            </div>

            <div class="mb-4 d-flex align-items-center gap-2 p-3 t-card" style="border-style: dashed; background: transparent;">
                <span class="text-success font-bold">></span>
                <input type="text" id="live-search" class="t-input cli-search w-100 m-0" placeholder="QUERY_VAULT_DATA... (Search Title, ID, or Note)" onkeyup="renderVault(this.value)">
            </div>

            <div id="vault-items">
                </div>
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
                    <select id="edit-type" class="t-select m-0" onchange="toggleEditFormMode()">
                        <option value="NOTE">NOTE</option>
                        <option value="PASSWORD">PASSWORD</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label class="t-form-label">> TITLE</label>
                    <input type="text" id="edit-title" class="t-input m-0" placeholder="Title">
                </div>
            </div>
            
            <div id="edit-password-fields">
                <div class="d-flex gap-3 mb-3 flex-wrap">
                    <div style="flex: 1; min-width: 120px;">
                        <label class="t-form-label">> ID/EMAIL</label>
                        <input type="text" id="edit-email" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label class="t-form-label">> URL</label>
                        <input type="url" id="edit-url" class="t-input m-0">
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label class="t-form-label">> PASS</label>
                        <input type="text" id="edit-pass" class="t-input m-0">
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

    <script src="../assets/terminal.js"></script>

    <script>
        let ghToken = localStorage.getItem('bunker_gh_token');
        let gistId = localStorage.getItem('bunker_gist_id');
        let vaultData = [];
        let activePassword = '';

        document.addEventListener("DOMContentLoaded", () => {
            Terminal.splash.close(1000);
            updateUI();
        });

        // Fitur Copy Teks dengan Interaksi Terminal
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
            }).catch(err => {
                Terminal.toast('SYS_ERR: CLIPBOARD ACCESS DENIED', 'danger');
            });
        }

        function toggleFormMode() {
            const type = document.getElementById('entry-type').value;
            document.getElementById('password-fields').style.display = (type === 'PASSWORD') ? 'block' : 'none';
        }

        function toggleEditFormMode() {
            const type = document.getElementById('edit-type').value;
            document.getElementById('edit-password-fields').style.display = (type === 'PASSWORD') ? 'block' : 'none';
        }

        function updateUI() {
            document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
            if (!ghToken || !gistId) {
                document.getElementById('pane-setup').classList.add('active');
            } else if (!activePassword) {
                document.getElementById('pane-unlock').classList.add('active');
                document.getElementById('master-password').focus();
            } else {
                document.getElementById('pane-vault').classList.add('active');
                renderVault(); 
            }
        }

        function saveSetup() {
            const t = document.getElementById('setup-token').value.trim();
            const g = document.getElementById('setup-gist').value.trim();
            if(t && g) {
                Terminal.splash.show('> LINKING TO SATELLITE...');
                setTimeout(() => {
                    localStorage.setItem('bunker_gh_token', t);
                    localStorage.setItem('bunker_gist_id', g);
                    ghToken = t; gistId = g;
                    updateUI();
                    Terminal.splash.close(500);
                }, 800);
            }
        }

        function purgeSetup() {
            if(confirm("> WARNING: Destroy terminal link coordinates?")) {
                localStorage.removeItem('bunker_gh_token');
                localStorage.removeItem('bunker_gist_id');
                ghToken = null; gistId = null;
                updateUI();
            }
        }

        async function unlockVault() {
            const pwd = document.getElementById('master-password').value;
            if(!pwd) return;
            
            Terminal.splash.show('> DECRYPTING PAYLOAD FROM SATELLITE...');
            
            try {
                const res = await fetch(`https://api.github.com/gists/${gistId}`, {
                    headers: { "Authorization": `token ${ghToken}` }
                });
                
                const data = await res.json();
                if (data.message) throw new Error("SATELLITE REJECTED: " + data.message);
                if (!data.files || !data.files["bunker_vault.enc"]) throw new Error("PAYLOAD MISSING.");

                const content = data.files["bunker_vault.enc"].content;

                if (content.trim() === 'INIT') {
                    vaultData = [];
                    activePassword = pwd;
                    document.getElementById('master-password').value = ''; 
                    updateUI();
                    Terminal.toast('VAULT INITIALIZED', 'success');
                } else {
                    try {
                        const bytes = CryptoJS.AES.decrypt(content, pwd);
                        const decrypted = bytes.toString(CryptoJS.enc.Utf8);
                        if(!decrypted) throw new Error("DECRYPTION FAILED");
                        
                        vaultData = JSON.parse(decrypted);
                        activePassword = pwd;
                        document.getElementById('master-password').value = ''; 
                        updateUI();
                        Terminal.toast('ACCESS GRANTED', 'normal');
                    } catch(e) {
                        Terminal.toast('INVALID MASTER PASSWORD', 'danger');
                    }
                }
            } catch(e) {
                Terminal.toast(e.message, 'danger');
            }
            Terminal.splash.close(500);
        }

        function lockVault() {
            vaultData = [];
            activePassword = '';
            document.getElementById('live-search').value = ''; 
            updateUI();
            Terminal.toast('VAULT SECURED', 'warning');
        }

        async function saveToGitHub() {
            Terminal.splash.show('> ENCRYPTING & UPLOADING TO SATELLITE...');
            
            try {
                const payloadStr = JSON.stringify(vaultData);
                const encrypted = CryptoJS.AES.encrypt(payloadStr, activePassword).toString();
                
                const res = await fetch(`https://api.github.com/gists/${gistId}`, {
                    method: 'PATCH',
                    headers: { 
                        "Authorization": `token ${ghToken}`,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        files: { "bunker_vault.enc": { content: encrypted } }
                    })
                });
                if(!res.ok) throw new Error("Failed to upload payload.");
                Terminal.toast('UPLOAD SUCCESSFUL', 'normal');
            } catch(e) {
                Terminal.toast(e.message, 'danger');
            }
            
            Terminal.splash.close(500);
        }

        function toggleAddForm() {
            const f = document.getElementById('add-form');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }

        async function saveEntry() {
            const type = document.getElementById('entry-type').value;
            const title = document.getElementById('entry-title').value.trim();
            const content = document.getElementById('entry-content').value.trim();
            
            if(!title) { Terminal.toast('TITLE IS REQUIRED', 'danger'); return; }

            const newEntry = {
                id: Date.now(),
                type: type,
                title: title,
                content: content,
                date: new Date().toISOString().split('T')[0]
            };

            if (type === 'PASSWORD') {
                newEntry.email = document.getElementById('entry-email').value.trim();
                newEntry.url = document.getElementById('entry-url').value.trim();
                newEntry.password = document.getElementById('entry-pass').value.trim();
            }
            
            vaultData.unshift(newEntry);
            await saveToGitHub();
            
            document.getElementById('entry-title').value = '';
            document.getElementById('entry-content').value = '';
            document.getElementById('entry-email').value = '';
            document.getElementById('entry-url').value = '';
            document.getElementById('entry-pass').value = '';
            toggleAddForm();
            renderVault();
        }

        function openEditModal(id) {
            const item = vaultData.find(i => i.id === id);
            if(!item) return;

            document.getElementById('edit-id').value = item.id;
            document.getElementById('edit-title').value = item.title || '';
            document.getElementById('edit-content').value = item.content || '';
            document.getElementById('edit-type').value = item.type;
            
            if(item.type === 'PASSWORD') {
                document.getElementById('edit-email').value = item.email || '';
                document.getElementById('edit-url').value = item.url || '';
                document.getElementById('edit-pass').value = item.password || '';
            } else {
                document.getElementById('edit-email').value = '';
                document.getElementById('edit-url').value = '';
                document.getElementById('edit-pass').value = '';
            }

            toggleEditFormMode();
            Terminal.modal.open('editModal');
        }

        async function saveEditEntry() {
            const id = parseInt(document.getElementById('edit-id').value);
            const index = vaultData.findIndex(i => i.id === id);
            
            if(index === -1) return;

            const type = document.getElementById('edit-type').value;
            const title = document.getElementById('edit-title').value.trim();
            
            if(!title) { Terminal.toast('TITLE IS REQUIRED', 'danger'); return; }

            vaultData[index].type = type;
            vaultData[index].title = title;
            vaultData[index].content = document.getElementById('edit-content').value.trim();

            if (type === 'PASSWORD') {
                vaultData[index].email = document.getElementById('edit-email').value.trim();
                vaultData[index].url = document.getElementById('edit-url').value.trim();
                vaultData[index].password = document.getElementById('edit-pass').value.trim();
            } else {
                delete vaultData[index].email;
                delete vaultData[index].url;
                delete vaultData[index].password;
            }

            await saveToGitHub();
            Terminal.modal.close('editModal');
            renderVault(document.getElementById('live-search').value);
        }

        async function deleteEntry(id) {
            if(confirm("> WARNING: Irrevocably destroy this payload?")) {
                vaultData = vaultData.filter(i => i.id !== id);
                await saveToGitHub();
                renderVault(document.getElementById('live-search').value);
            }
        }

        function renderVault(query = '') {
            const container = document.getElementById('vault-items');
            container.innerHTML = '';
            
            let displayData = vaultData;
            if (query.trim() !== '') {
                const q = query.toLowerCase();
                displayData = vaultData.filter(item => {
                    return (
                        (item.title && item.title.toLowerCase().includes(q)) ||
                        (item.email && item.email.toLowerCase().includes(q)) ||
                        (item.url && item.url.toLowerCase().includes(q)) ||
                        (item.content && item.content.toLowerCase().includes(q))
                    );
                });
            }

            if(displayData.length === 0) {
                container.innerHTML = `<div class="text-center text-muted p-5 t-border border-dashed">> ${query === '' ? 'VAULT EMPTY. NO DATA CHUNKS FOUND.' : 'NO MATCHING DATA FOUND.'}</div>`;
                return;
            }

            displayData.forEach(item => {
                const isPass = item.type === 'PASSWORD';
                const badgeClass = isPass ? 'danger' : 'warning';
                
                let detailHtml = '';
                
                if (isPass) {
                    const emailHtml = item.email ? `<div class="mb-2"><span class="text-muted">> ID/EMAIL:</span> <span class="text-success copy-target" onclick="copyText('${item.email}', this)" title="Click to copy">${item.email}</span></div>` : '';
                    const urlHtml = item.url ? `<div class="mb-2"><span class="text-muted">> URL_TARGET:</span> <a href="${item.url}" target="_blank" class="text-success" style="text-decoration:underline dashed;">${item.url}</a></div>` : '';
                    const passHtml = item.password ? `<div><span class="text-muted">> PASS_KEY:</span> <span class="blur-text text-success" onclick="copyText('${item.password}', this)" title="Click to copy">${item.password}</span></div>` : '';
                    const borderHtml = (item.email || item.url || item.password) ? `<div class="t-border-bottom pb-3 mb-3"></div>` : '';
                    
                    detailHtml = emailHtml + urlHtml + passHtml + borderHtml;
                }

                const notesHtml = item.content ? `<div class="text-success">${item.content.replace(/\n/g, '<br>')}</div>` : '';

                // Menggunakan struktur Native t-accordion
                // Modifikasi: Gaya flex-start agar konten rapat ke kiri [Judul] [Badge] ... [Tanggal] di kanan
                const div = document.createElement('div');
                div.className = `t-accordion mb-3`;
                div.innerHTML = `
                    <button class="t-accordion-btn" onclick="Terminal.accordion(this)" style="justify-content: flex-start; gap: 12px;">
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