<x-app-layout>
    <div class="row">
        <!-- Main Test Card -->
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-warning text-dark py-3">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Phishing Report Test</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        This tool simulates the Outlook Add-in action. It fetches your recent emails and allows you to "Report" them to the backend API, moving them to Junk.
                    </p>

                    <!-- Alert Box -->
                    <div id="alertBox" class="alert d-none" role="alert"></div>

                    <!-- Step 1: Fetch Emails -->
                    <div class="mb-4">
                        <h5>Step 1: Select an Email to Report</h5>
                        <p class="small text-muted">Fetches last 5 emails from your Connected Inbox.</p>
                        
                        <button id="fetchEmailsBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i> Load Recent Emails
                        </button>
                    </div>

                    <!-- Email List Container -->
                    <div id="emailListContainer" class="d-none">
                        <div class="list-group mb-4" id="emailList">
                            <!-- Populated via JS -->
                        </div>
                    </div>

                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Communicating with Microsoft Graph...</p>
                    </div>
                    
                    <hr class="my-5">

                    <!-- App-only test (Outlook Add-in style: no session, tenant_id + user + message_id) -->
                    <div class="mb-4 p-3 border rounded bg-light">
                        <h5><i class="fas fa-plug me-2"></i>Test App-only (Add-in style)</h5>
                        <p class="small text-muted mb-2">Tests the API without your session: backend uses client credentials.</p>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Note:</strong> App-only mode requires a <strong>Microsoft 365 business tenant</strong> with admin consent. 
                            Personal accounts (Hotmail, Outlook.com) work with the "Report" button above (delegated mode), but may not work with app-only mode.
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4" hidden>
                                <label class="form-label small">Tenant ID</label>
                                <input type="text" id="appOnlyTenantId" value="{{ env('MICROSOFT_TENANT_ID') }}" class="form-control form-control-sm font-monospace" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">User (Microsoft mailbox email)</label>
                                <input type="text" id="appOnlyUser" class="form-control form-control-sm" placeholder="user@company.com or user@hotmail.com">
                                <small class="text-muted">Auto-filled after loading emails</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Message ID</label>
                                <input type="text" id="appOnlyMessageId" class="form-control form-control-sm font-monospace" placeholder="AAMkAGVm...">
                            </div>
                        </div>
                        <button type="button" id="appOnlyReportBtn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i> Test App-only Report
                        </button>
                        <div id="appOnlyResult" class="mt-2 small d-none"></div>
                    </div>
                    
                    <hr class="my-5">
                    
                    <!-- Documentation -->
                    <div class="bg-light p-4 rounded">
                        <h5><i class="fas fa-code me-2"></i>API Documentation</h5>
                        <p class="small text-muted">Use this endpoint for the Outlook Add-in (app-only) or with session (delegated).</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">API Endpoint</label>
                            <div class="input-group">
                                <span class="input-group-text">POST</span>
                                <input type="text" class="form-control font-monospace" value="{{ route('api.phishing.report') }}" readonly>
                                <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ route('api.phishing.report') }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Payload – App-only (Add-in)</label>
                            <pre class="bg-dark text-white p-3 rounded small mb-1">
{
    "message_id": "AAMkAGVm...",
    "user": "bob@company.com",
    "tenant_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}</pre>
                            <span class="text-muted small">No login required; backend uses client credentials.</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payload – Delegated (session)</label>
                            <pre class="bg-dark text-white p-3 rounded small">
{
    "message_id": "...",
    "reporter_email": "user@example.com",
    "reason": "Suspicious link" (optional)
}</pre>
                        </div>
                        
                        <div class="mt-3">
                             <a href="{{ route('integrations.phishing.manifest') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-file-code me-2"></i> Download Outlook Manifest XML
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fetchBtn = document.getElementById('fetchEmailsBtn');
            const listContainer = document.getElementById('emailListContainer');
            const emailList = document.getElementById('emailList');
            const spinner = document.getElementById('loadingSpinner');
            const alertBox = document.getElementById('alertBox');

            function showAlert(message, type) {
                alertBox.className = `alert alert-${type}`;
                alertBox.innerHTML = message;
                alertBox.classList.remove('d-none');
            }

            // Fetch Messages
            fetchBtn.addEventListener('click', function() {
                spinner.classList.remove('d-none');
                listContainer.classList.add('d-none');
                alertBox.classList.add('d-none');
                
                fetch('{{ route('integrations.phishing.messages') }}')
                    .then(response => response.json())
                    .then(data => {
                        spinner.classList.add('d-none');
                        
                        if (data.error) {
                            // Specialized error for missing mailbox
                            if (data.error.code === 'MailboxNotEnabledForRESTAPI') {
                                showAlert('<strong>Error:</strong> The connected user does not have an active Exchange Online mailbox. Please connect with a licensed user (e.g., E3, Business Standard).', 'danger');
                            } else {
                                showAlert(`<strong>Error:</strong> ${data.error.message || JSON.stringify(data.error)}`, 'danger');
                            }
                            return;
                        }
                        
                        if (!data.value || data.value.length === 0) {
                            showAlert('No emails found in this mailbox.', 'warning');
                            return;
                        }

                        // Auto-populate Microsoft 365 email for app-only testing
                        if (data.connectedEmail) {
                            const appOnlyUserField = document.getElementById('appOnlyUser');
                            if (appOnlyUserField) {
                                appOnlyUserField.value = data.connectedEmail;
                                showAlert(`<strong>Emails loaded!</strong> Connected as: <code>${data.connectedEmail}</code>. Select an email to report or use the App-only test below.`, 'success');
                            }
                        }

                        renderEmails(data.value);
                    })
                    .catch(err => {
                        spinner.classList.add('d-none');
                        showAlert('Failed to contact server. Check console.', 'danger');
                        console.error(err);
                    });
            });

            // Render Email List
            function renderEmails(emails) {
                emailList.innerHTML = '';
                
                emails.forEach(email => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    
                    const date = new Date(email.receivedDateTime).toLocaleString();
                    const from = email.from ? email.from.emailAddress.name : 'Unknown';
                    const fromEmail = email.from ? email.from.emailAddress.address : '';
                    
                    item.innerHTML = `
                        <div class="me-3 overflow-hidden flex-grow-1">
                            <h6 class="mb-1 text-truncate" style="max-width: 500px;">${email.subject}</h6>
                            <p class="mb-1 small text-muted">From: <strong>${from}</strong> &lt;${fromEmail}&gt;</p>
                            <small class="text-muted">${date}</small>
                            <small class="d-block mt-1"><code class="small" title="Copy for App-only test">${email.id}</code> <button type="button" class="btn btn-link btn-sm p-0 ms-1 copy-id-btn" data-id="${email.id}" title="Copy ID">Copy ID</button></small>
                        </div>
                        <button class="btn btn-sm btn-danger report-btn" data-id="${email.id}" data-subject="${email.subject}">
                            <i class="fas fa-exclamation-triangle me-1"></i> Report
                        </button>
                    `;
                    
                    emailList.appendChild(item);
                });
                
                listContainer.classList.remove('d-none');
                
                // Attach Event Listeners to new buttons
                document.querySelectorAll('.report-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const subject = this.getAttribute('data-subject');
                        reportEmail(id, subject, this);
                    });
                });
                document.querySelectorAll('.copy-id-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        navigator.clipboard.writeText(id).then(() => {
                            const el = document.getElementById('appOnlyMessageId');
                            if (el) el.value = id;
                            showAlert('Message ID copied to clipboard and into App-only test field.', 'success');
                        });
                    });
                });
            }

            // Report Email Function
            function reportEmail(messageId, subject, btnElement) {
                if(!confirm(`Are you sure you want to report "${subject}" as phishing? It will be moved to Junk.`)) return;

                const originalText = btnElement.innerHTML;
                btnElement.disabled = true;
                btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                fetch('{{ route('api.phishing.report') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        message_id: messageId,
                        reporter_email: '{{ auth()->user()->email ?? "current-user@test.com" }}',
                        reason: 'User reported via Test Page'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btnElement.className = 'btn btn-sm btn-success';
                        btnElement.innerHTML = '<i class="fas fa-check"></i> Reported';
                        showAlert(`<strong>Success!</strong> Email "${subject}" has been reported and moved to Junk.`, 'success');
                    } else {
                        btnElement.disabled = false;
                        btnElement.innerHTML = originalText;
                        showAlert(`<strong>Failed:</strong> ${data.error}`, 'danger');
                    }
                })
                .catch(err => {
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalText;
                    showAlert('Failed to connect to API.', 'danger');
                    console.error(err);
                });
            }

            // App-only test
            document.getElementById('appOnlyReportBtn').addEventListener('click', function() {
                const tenantId = document.getElementById('appOnlyTenantId').value.trim();
                const user = document.getElementById('appOnlyUser').value.trim();
                const messageId = document.getElementById('appOnlyMessageId').value.trim();
                const resultEl = document.getElementById('appOnlyResult');
                resultEl.classList.add('d-none');
                
                // Validation
                if (!tenantId || !user || !messageId) {
                    resultEl.className = 'mt-2 small text-danger';
                    resultEl.textContent = 'Please fill User and Message ID. Click "Load Recent Emails" first to auto-detect the correct email.';
                    resultEl.classList.remove('d-none');
                    return;
                }
                
                // Block non-Microsoft accounts (Gmail, Yahoo)
                if (user.includes('@gmail.com') || user.includes('@yahoo.')) {
                    resultEl.className = 'mt-2 small text-danger';
                    resultEl.innerHTML = '<strong>Invalid:</strong> Gmail and Yahoo are not Microsoft accounts.';
                    resultEl.classList.remove('d-none');
                    return;
                }
                
                // Warn about personal Microsoft accounts (they won't work with app-only)
                if (user.includes('@hotmail.') || user.includes('@outlook.com') || user.includes('@live.') || user.includes('@msn.')) {
                    resultEl.className = 'mt-2 small text-warning';
                    resultEl.innerHTML = '<strong>⚠️ Personal Account Detected:</strong> App-only mode does NOT work with personal Microsoft accounts (Hotmail, Outlook.com). Please use the <strong>"Report" button</strong> above (delegated mode) instead. Or use a Microsoft 365 business email like <code>user@company.onmicrosoft.com</code>';
                    resultEl.classList.remove('d-none');
                    return;
                }
                
                this.disabled = true;
                fetch('{{ route('api.phishing.report') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message_id: messageId, user: user, tenant_id: tenantId })
                })
                .then(r => r.json())
                .then(data => {
                    this.disabled = false;
                    resultEl.classList.remove('d-none');
                    if (data.success) {
                        resultEl.className = 'mt-2 small text-success';
                        resultEl.textContent = 'Success: ' + (data.message || 'Reported and moved to Junk.');
                    } else {
                        resultEl.className = 'mt-2 small text-danger';
                        
                        // Parse specific error codes for better messages
                        let errorMsg = data.error || JSON.stringify(data);
                        const isPersonalAccount = user.includes('@hotmail.') || user.includes('@outlook.com') || user.includes('@live.') || user.includes('@msn.');
                        
                        if (data.details?.error?.code === 'ErrorInvalidUser') {
                            if (isPersonalAccount) {
                                errorMsg = `Personal Microsoft accounts (Hotmail, Outlook.com) don't support app-only mode. Please use the "Report" button above (delegated mode) instead, or use a Microsoft 365 business account.`;
                            } else {
                                errorMsg = `The user "${user}" is not found in this tenant. Make sure the email matches a mailbox in your Microsoft 365 organization.`;
                            }
                        } else if (data.details?.error?.code === 'ErrorItemNotFound') {
                            errorMsg = `Message not found. The message ID may be invalid or the email was already deleted/moved.`;
                        } else if (data.details?.error?.code === 'ErrorAccessDenied') {
                            errorMsg = `Access denied. The app may not have permission to access this mailbox. Ensure admin consent was granted.`;
                        }
                        
                        resultEl.innerHTML = '<strong>Error:</strong> ' + errorMsg;
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    resultEl.classList.remove('d-none');
                    resultEl.className = 'mt-2 small text-danger';
                    resultEl.textContent = 'Request failed: ' + err.message;
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
