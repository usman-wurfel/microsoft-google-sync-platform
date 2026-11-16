<x-app-layout>
    <div class="row">
        <!-- Main Test Card -->
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-success text-white py-3">
                    <h4 class="mb-0"><i class="fas fa-lock-open me-2"></i>Allowlist Sync Test</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        This tool manually triggers the Allowlist synchronization process. It will add the specified IPs and domains to your Exchange Online allowlist (mock test).
                    </p>

                    <!-- Alert Box -->
                    <div id="alertBox" class="alert d-none" role="alert"></div>

                    <!-- Input Form -->
                    <div class="mb-4">
                        <div class="mb-3">
                            <label for="ips" class="form-label fw-bold">IP Addresses (Comma separated)</label>
                            <textarea id="ips" class="form-control" rows="3" placeholder="1.1.1.1, 2.2.2.2">52.20.184.201, 54.172.229.218, 3.208.213.82</textarea>
                            <small class="text-muted">These IPs will be added to the allowlist.</small>
                        </div>

                        <div class="mb-3">
                            <label for="domains" class="form-label fw-bold">Domains (Comma separated)</label>
                            <textarea id="domains" class="form-control" rows="3" placeholder="example.com, test.com">phin.security, webexert.us, localhost</textarea>
                            <small class="text-muted">These domains will be added to the allowlist.</small>
                        </div>

                        <div class="mb-3 p-3 border rounded bg-light">
                            <label class="form-label fw-bold">Email policy (Level 0)</label>
                            <p class="small text-muted mb-2">
                                Allow emails (SCL Level 0) when the message includes a specific header and is from one of your allowed domains. This prevents simulation emails from your domains from being blocked.
                            </p>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="emailPolicyEnabled" checked>
                                <label class="form-check-label" for="emailPolicyEnabled">Enable email policy</label>
                            </div>
                            <div class="mb-0">
                                <label for="emailPolicyHeader" class="form-label small">Header name</label>
                                <input type="text" id="emailPolicyHeader" class="form-control form-control-sm font-monospace" value="X-AP2T-Simulation" placeholder="X-AP2T-Simulation">
                                <small class="text-muted">If the email has this header and is from an allowed domain, it will be allowed (Level 0).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Status -->
                    <div class="text-center mb-4">
                        <div id="syncStatus" class="align-items-center">
                            <i class="fas fa-sync fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Ready to Sync</h5>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button id="syncBtn" class="btn btn-success w-100 py-3">
                        <i class="fas fa-sync-alt me-2"></i> Sync Allowlist Now
                    </button>
                    
                    <hr class="my-5">
                    
                    <!-- Documentation -->
                    <div class="bg-light p-4 rounded">
                        <h5><i class="fas fa-code me-2"></i>API Documentation</h5>
                        <p class="small text-muted">Use this endpoint to trigger sync programmatically.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">API Endpoint</label>
                            <div class="input-group">
                                <span class="input-group-text">POST</span>
                                <input type="text" class="form-control font-monospace" value="{{ route('integrations.allowlist.sync') }}" readonly>
                                <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ route('integrations.allowlist.sync') }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                             <label class="form-label fw-bold">Optional Payload</label>
                             <pre class="bg-dark text-white p-3 rounded small mb-0">
{
    "ips": "1.2.3.4, 5.6.7.8",
    "domains": "example.com",
    "email_policy": {
        "enabled": true,
        "header_name": "X-AP2T-Simulation",
        "scl_level": 0
    }
}</pre>
                             <small class="text-muted">email_policy: Level 0 allows emails with the given header from your allowed domains.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const syncBtn = document.getElementById('syncBtn');
            const alertBox = document.getElementById('alertBox');
            const syncIcon = document.querySelector('#syncStatus i');
            const syncText = document.querySelector('#syncStatus h5');
            const ipsInput = document.getElementById('ips');
            const domainsInput = document.getElementById('domains');

            function showAlert(message, type) {
                alertBox.className = `alert alert-${type}`;
                alertBox.innerHTML = message;
                alertBox.classList.remove('d-none');
            }

            syncBtn.addEventListener('click', function() {
                // UI Loading State
                syncBtn.disabled = true;
                syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Syncing...';
                alertBox.classList.add('d-none');
                
                syncIcon.className = 'fas fa-sync fa-spin fa-3x text-primary mb-3';
                syncText.innerText = 'Syncing in progress...';
                syncText.className = 'text-primary';

                const emailPolicyEnabled = document.getElementById('emailPolicyEnabled').checked;
                const emailPolicyHeader = document.getElementById('emailPolicyHeader').value.trim() || 'X-AP2T-Simulation';

                fetch('{{ route('integrations.allowlist.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ips: ipsInput.value,
                        domains: domainsInput.value,
                        email_policy: {
                            enabled: emailPolicyEnabled,
                            header_name: emailPolicyHeader,
                            scl_level: 0
                        }
                    })
                })
                .then(response => response.json())
                .then(data => {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Sync Allowlist Now';

                    if (data.success) {
                        syncIcon.className = 'fas fa-check-circle fa-3x text-success mb-3';
                        syncText.innerText = 'Sync Complete!';
                        syncText.className = 'text-success';
                        
                        const policyMsg = data.email_policy_applied ? ' Email policy (Level 0) applied.' : '';
                        showAlert(`<strong>Success!</strong> ${data.message}. IPs: ${data.ips_added}, Domains: ${data.domains_added}.${policyMsg}`, 'success');
                    } else {
                        throw new Error(data.error || 'Sync failed');
                    }
                })
                .catch(err => {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Sync Allowlist Now';
                    
                    syncIcon.className = 'fas fa-exclamation-triangle fa-3x text-danger mb-3';
                    syncText.innerText = 'Sync Failed';
                    syncText.className = 'text-danger';
                    
                    showAlert(`<strong>Error:</strong> ${err.message}`, 'danger');
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
