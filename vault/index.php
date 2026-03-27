<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../bunker/index.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SECURE: VAULT - Bunker Control</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <style>
        .vault-container { max-width: 800px; margin: 0 auto; }
        .pane { display: none; }
        .pane.active { display: block; }
        
        .item-card { background: var(--bg-dark); border: 1px solid var(--border-color); padding: 20px; margin-bottom: 15px; border-left: 3px solid var(--text-muted); }
        .item-card.type-password { border-left-color: var(--text-main); }
        
        .item-title { font-weight: bold; font-size: 1.1rem; color: var(--text-main); display: flex; justify-content: space-between; cursor: pointer; }
        .item-content { margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color); display: none; color: var(--light); font-family: monospace; word-break: break-all; line-height: 1.5; }
        
        .badge { font-size: 0.7rem; padding: 2px 6px; border: 1px solid; border-radius: 3px; }
        .badge-note { color: var(--text-muted); border-color: var(--text-muted); }
        .badge-pass { color: var(--text-main); border-color: var(--text-main); }
        
        /* Efek Blur Sensor & Copy */
        .blur-text { filter: blur(6px); cursor: copy; transition: 0.2s; background: rgba(0,255,65,0.1); padding: 2px 5px; border-radius: 3px; user-select: none; }
        .blur-text:hover { filter: blur(0); }
        
        .copy-target { cursor: copy; transition: 0.2s; padding: 2px 5px; border-radius: 3px; }
        .copy-target:hover { background: rgba(0,255,65,0.1); color: var(--text-main) !important; text-shadow: 0 0 5px var(--text-main); }

        .card-padded { padding: 30px !important; }
        .form-control { box-sizing: border-box; }
        
        #password-fields, #edit-password-fields { display: none; margin-top: 15px; }

        /* Search Bar CLI */
        .cli-search { border: none; border-bottom: 1px dashed var(--text-main); border-radius: 0; background: transparent; padding-left: 0; color: var(--text-main); box-shadow: none !important; font-family: monospace; font-size: 1.1rem; }
        .cli-search:focus { background: transparent; color: var(--light); border-bottom: 1px solid var(--light); }
        .cli-search::placeholder { color: var(--text-muted); opacity: 0.5; }

        /* Modal Edit Styling */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; visibility: hidden; transition: 0.2s; backdrop-filter: blur(3px); }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-dialog { width: 100%; max-width: 600px; background: var(--bg-dark); border: 1px solid var(--text-main); box-shadow: 0 0 15px rgba(0,255,65,0.1); padding: 30px; }
    </style>
</head>
<body>
    <div class="container vault-container">
        
        <div class="d-flex justify-content-between align-items-center pb-3 mt-4 mb-4 border-bottom">
            <div>
                <h2 class="mb-0 text-main">[ SECURE_VAULT ]</h2>
                <div class="text-muted fs-small mt-1">> ZERO-KNOWLEDGE ENCRYPTION ENABLED.</div>
            </div>
            <a href="../bunker/dashboard.php" class="btn btn-dark border-secondary">[ RETURN_TO_BUNKER ]</a>
        </div>

        <div id="pane-setup" class="pane card card-padded mb-4 text-center" style="border-color: var(--text-main);">
            <h3 class="text-main mb-3">> PROTOCOL: COLD START</h3>
            <p class="text-muted fs-small mb-4">Unrecognized terminal. Inject GitHub coordinates to establish secure connection.</p>
            
            <div style="max-width: 450px; margin: 0 auto; text-align: left;">
                <div class="form-group mb-3">
                    <label class="text-muted fs-small mb-1">> GITHUB_PAT_TOKEN</label>
                    <input type="password" id="setup-token" class="form-control" style="letter-spacing: 1px; padding: 10px 15px;" placeholder="ghp_xxxxxxxxxxxx...">
                </div>
                <div class="form-group mb-4">
                    <label class="text-muted fs-small mb-1">> GIST_ID</label>
                    <input type="text" id="setup-gist" class="form-control" style="letter-spacing: 1px; padding: 10px 15px;" placeholder="8a7b6c5d4...">
                </div>
                <button onclick="saveSetup()" class="btn btn-light w-100 mt-2" style="padding: 10px;">[ INJECT_COORDINATES ]</button>
            </div>
        </div>

        <div id="pane-unlock" class="pane card card-padded mb-4 text-center" style="border-color: var(--text-main);">
            <h3 class="mb-3 text-main">> VAULT LOCKED</h3>
            <p class="text-muted fs-small mb-4">Terminal linked. Awaiting Master Decryption Key.</p>
            
            <div class="form-group mb-4" style="max-width: 400px; margin: 0 auto;">
                <input type="password" id="master-password" class="form-control text-main border-secondary" style="text-align: center; letter-spacing: 2px; font-weight: bold; width: 100%; padding: 10px 15px;" placeholder="ENTER MASTER PASSWORD..." onkeypress="if(event.key === 'Enter') unlockVault()">
            </div>
            
            <button onclick="unlockVault()" id="btn-unlock" class="btn btn-light" style="max-width: 400px; width: 100%; margin: 0 auto; display: block; padding: 10px;">[ DECRYPT_PAYLOAD ]</button>
            
            <div class="mt-4 border-top pt-4">
                <button onclick="purgeSetup()" class="btn btn-dark btn-sm text-muted border-secondary">> PURGE_TERMINAL_LINK</button>
            </div>
        </div>

        <div id="pane-vault" class="pane">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button onclick="lockVault()" class="btn btn-outline-danger btn-sm">[ SECURE & LOCK VAULT ]</button>
                <button onclick="toggleAddForm()" class="btn btn-light btn-sm">[+ ADD_ENTRY ]</button>
            </div>

            <div id="add-form" class="card card-padded mb-4" style="display: none; border-color: var(--text-main); background: rgba(0,255,65,0.02);">
                <div class="d-flex gap-3 mb-3" style="flex-wrap: wrap;">
                    <select id="entry-type" class="form-control" style="width: 150px; padding: 10px;" onchange="toggleFormMode()">
                        <option value="NOTE">NOTE</option>
                        <option value="PASSWORD">PASSWORD</option>
                    </select>
                    <input type="text" id="entry-title" class="form-control" placeholder="Title (e.g., ProtonMail, Debian Server)" style="flex: 1; padding: 10px 15px; min-width: 200px;">
                </div>
                
                <div id="password-fields">
                    <div class="d-flex gap-3 mb-3" style="flex-wrap: wrap;">
                        <input type="text" id="entry-email" class="form-control" placeholder="ID / Email" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                        <input type="url" id="entry-url" class="form-control" placeholder="URL Target (Optional)" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                        <input type="text" id="entry-pass" class="form-control" placeholder="The Password" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                    </div>
                </div>

                <textarea id="entry-content" class="form-control mb-4" rows="4" style="padding: 15px; margin-top: 15px;" placeholder="Notes / Detail payload..."></textarea>
                <button onclick="saveEntry()" class="btn btn-light w-100" id="btn-save" style="padding: 10px;">[ ENCRYPT & UPLOAD ]</button>
            </div>

            <div class="mb-4 d-flex align-items-center gap-2">
                <span class="text-main">></span>
                <input type="text" id="live-search" class="form-control cli-search w-100" placeholder="QUERY_VAULT_DATA... (Title, ID, or Note)" onkeyup="renderVault(this.value)">
            </div>

            <div id="vault-items">
                </div>
        </div>

    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-dialog">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h3 class="mb-0 text-main">> MODIFY_PAYLOAD</h3>
                <button class="btn-danger-text" style="font-size: 1.5rem; line-height: 1;" onclick="closeEditModal()" title="Abort">&times;</button>
            </div>
            
            <input type="hidden" id="edit-id">
            
            <div class="d-flex gap-3 mb-3" style="flex-wrap: wrap;">
                <select id="edit-type" class="form-control" style="width: 150px; padding: 10px;" onchange="toggleEditFormMode()">
                    <option value="NOTE">NOTE</option>
                    <option value="PASSWORD">PASSWORD</option>
                </select>
                <input type="text" id="edit-title" class="form-control" placeholder="Title" style="flex: 1; padding: 10px 15px; min-width: 200px;">
            </div>
            
            <div id="edit-password-fields">
                <div class="d-flex gap-3 mb-3" style="flex-wrap: wrap;">
                    <input type="text" id="edit-email" class="form-control" placeholder="ID / Email" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                    <input type="url" id="edit-url" class="form-control" placeholder="URL Target" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                    <input type="text" id="edit-pass" class="form-control" placeholder="The Password" style="flex: 1; padding: 10px 15px; min-width: 150px;">
                </div>
            </div>

            <textarea id="edit-content" class="form-control mb-4" rows="4" style="padding: 15px; margin-top: 15px;" placeholder="Notes / Detail payload..."></textarea>
            
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-dark border-secondary" onclick="closeEditModal()">[ ABORT ]</button>
                <button onclick="saveEditEntry()" id="btn-edit-save" class="btn btn-light">[ OVERRIDE_PAYLOAD ]</button>
            </div>
        </div>
    </div>

    <script>
        let ghToken = localStorage.getItem('bunker_gh_token');
        let gistId = localStorage.getItem('bunker_gist_id');
        let vaultData = [];
        let activePassword = '';

        function copyText(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = element.innerText;
                const isBlur = element.classList.contains('blur-text');
                
                if (isBlur) element.classList.remove('blur-text');
                element.innerText = '[COPIED!]';
                element.style.color = 'var(--text-main)';
                element.style.fontWeight = 'bold';
                
                setTimeout(() => {
                    element.innerText = originalText;
                    element.style.color = '';
                    element.style.fontWeight = 'normal';
                    if (isBlur) element.classList.add('blur-text');
                }, 1500);
            }).catch(err => {
                alert("> SYS_ERR: Clipboard access denied.");
            });
        }

        // Toggle form (Add)
        function toggleFormMode() {
            const type = document.getElementById('entry-type').value;
            const passFields = document.getElementById('password-fields');
            passFields.style.display = (type === 'PASSWORD') ? 'block' : 'none';
        }

        // Toggle form (Edit)
        function toggleEditFormMode() {
            const type = document.getElementById('edit-type').value;
            const passFields = document.getElementById('edit-password-fields');
            passFields.style.display = (type === 'PASSWORD') ? 'block' : 'none';
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
                localStorage.setItem('bunker_gh_token', t);
                localStorage.setItem('bunker_gist_id', g);
                ghToken = t; gistId = g;
                updateUI();
            }
        }

        function purgeSetup() {
            if(confirm("WARNING: Destroy terminal link coordinates?")) {
                localStorage.removeItem('bunker_gh_token');
                localStorage.removeItem('bunker_gist_id');
                ghToken = null; gistId = null;
                updateUI();
            }
        }

        async function unlockVault() {
            const pwd = document.getElementById('master-password').value;
            if(!pwd) return;
            
            document.getElementById('btn-unlock').innerText = "[ FETCHING SATELLITE... ]";
            
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
                    updateUI();
                } else {
                    try {
                        const bytes = CryptoJS.AES.decrypt(content, pwd);
                        const decrypted = bytes.toString(CryptoJS.enc.Utf8);
                        if(!decrypted) throw new Error("DECRYPTION FAILED");
                        
                        vaultData = JSON.parse(decrypted);
                        activePassword = pwd;
                        document.getElementById('master-password').value = ''; 
                        updateUI();
                    } catch(e) {
                        alert("> SYS_ERR: INVALID MASTER PASSWORD OR CORRUPT DATA.");
                    }
                }
            } catch(e) {
                alert("> SYS_ERR: " + e.message);
            }
            document.getElementById('btn-unlock').innerText = "[ DECRYPT_PAYLOAD ]";
        }

        function lockVault() {
            vaultData = [];
            activePassword = '';
            document.getElementById('live-search').value = ''; 
            updateUI();
        }

        async function saveToGitHub(btnId = 'btn-save', btnText = '[ ENCRYPT & UPLOAD ]') {
            const btn = document.getElementById(btnId);
            if(btn) btn.innerText = "[ ENCRYPTING... ]";
            
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
            } catch(e) {
                alert("> SYS_ERR: " + e.message);
            }
            
            if(btn) btn.innerText = btnText;
        }

        function toggleAddForm() {
            const f = document.getElementById('add-form');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }

        async function saveEntry() {
            const type = document.getElementById('entry-type').value;
            const title = document.getElementById('entry-title').value.trim();
            const content = document.getElementById('entry-content').value.trim();
            
            if(!title) { alert("> SYS_ERR: TITLE IS REQUIRED."); return; }

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
            await saveToGitHub('btn-save', '[ ENCRYPT & UPLOAD ]');
            
            document.getElementById('entry-title').value = '';
            document.getElementById('entry-content').value = '';
            document.getElementById('entry-email').value = '';
            document.getElementById('entry-url').value = '';
            document.getElementById('entry-pass').value = '';
            toggleAddForm();
            renderVault();
        }

        // --- EDIT LOGIC ---
        const modal = document.getElementById('editModal');

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
            modal.classList.add('active');
        }

        function closeEditModal() {
            modal.classList.remove('active');
        }

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeEditModal();
        });

        async function saveEditEntry() {
            const id = parseInt(document.getElementById('edit-id').value);
            const index = vaultData.findIndex(i => i.id === id);
            
            if(index === -1) return;

            const type = document.getElementById('edit-type').value;
            const title = document.getElementById('edit-title').value.trim();
            
            if(!title) { alert("> SYS_ERR: TITLE IS REQUIRED."); return; }

            // Update data
            vaultData[index].type = type;
            vaultData[index].title = title;
            vaultData[index].content = document.getElementById('edit-content').value.trim();

            if (type === 'PASSWORD') {
                vaultData[index].email = document.getElementById('edit-email').value.trim();
                vaultData[index].url = document.getElementById('edit-url').value.trim();
                vaultData[index].password = document.getElementById('edit-pass').value.trim();
            } else {
                // Jika dirubah dari Password ke Note, hapus data credential-nya agar file Gist tetap ringan
                delete vaultData[index].email;
                delete vaultData[index].url;
                delete vaultData[index].password;
            }

            await saveToGitHub('btn-edit-save', '[ OVERRIDE_PAYLOAD ]');
            closeEditModal();
            renderVault(document.getElementById('live-search').value);
        }

        // --- DELETE & RENDER LOGIC ---
        async function deleteEntry(id) {
            if(confirm("WARNING: Irrevocably destroy this payload?")) {
                vaultData = vaultData.filter(i => i.id !== id);
                await saveToGitHub();
                renderVault(document.getElementById('live-search').value);
            }
        }

        function toggleItem(id) {
            const contentDiv = document.getElementById('content-' + id);
            const isCurrentlyOpen = contentDiv.style.display === 'block';

            // Tutup semua laci brankas yang sedang terbuka
            document.querySelectorAll('.item-content').forEach(el => {
                el.style.display = 'none';
            });

            // Jika laci yang diklik sebelumnya tertutup, maka buka
            if (!isCurrentlyOpen) {
                contentDiv.style.display = 'block';
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
                container.innerHTML = `<div class="text-center text-muted p-5 border border-secondary">> ${query === '' ? 'VAULT EMPTY. NO DATA CHUNKS FOUND.' : 'NO MATCHING DATA FOUND.'}</div>`;
                return;
            }

            displayData.forEach(item => {
                const isPass = item.type === 'PASSWORD';
                const badgeClass = isPass ? 'badge-pass' : 'badge-note';
                
                let detailHtml = '';
                
                if (isPass) {
                    const emailHtml = item.email ? `<div class="mb-2"><span class="text-muted">> ID/EMAIL:</span> <span class="text-light copy-target" onclick="copyText('${item.email}', this)" title="Click to copy">${item.email}</span></div>` : '';
                    const urlHtml = item.url ? `<div class="mb-2"><span class="text-muted">> URL_TARGET:</span> <a href="${item.url}" target="_blank" class="text-main" style="text-decoration:none;">${item.url}</a></div>` : '';
                    const passHtml = item.password ? `<div><span class="text-muted">> PASS_KEY:</span> <span class="blur-text text-light" onclick="copyText('${item.password}', this)" title="Click to copy">${item.password}</span></div>` : '';
                    const borderHtml = (item.email || item.url || item.password) ? `<div class="border-bottom border-secondary pb-3 mb-3"></div>` : '';
                    
                    detailHtml = emailHtml + urlHtml + passHtml + borderHtml;
                }

                const notesHtml = item.content ? `<div class="text-light">${item.content.replace(/\n/g, '<br>')}</div>` : '';

                const div = document.createElement('div');
                div.className = `item-card ${isPass ? 'type-password' : ''}`;
                div.innerHTML = `
                    <div class="item-title" onclick="toggleItem(${item.id})">
                        <span><span class="badge ${badgeClass} me-2">${item.type}</span> ${item.title}</span>
                        <span class="text-muted fs-small">${item.date} [+]</span>
                    </div>
                    <div class="item-content" id="content-${item.id}">
                        <div class="mb-4" style="font-size: 0.95rem;">
                            ${detailHtml}
                            ${notesHtml}
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button onclick="openEditModal(${item.id})" class="btn-dark border-secondary text-main btn-sm hover-light">[ EDIT ]</button>
                            <button onclick="deleteEntry(${item.id})" class="btn-dark border-secondary text-muted btn-sm hover-danger">[ PURGE ]</button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // INIT
        updateUI();
    </script>
</body>
</html>