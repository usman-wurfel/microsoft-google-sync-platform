<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Phishing</title>
    <script src="https://appsforoffice.microsoft.com/lib/1/hosted/office.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 16px;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .email-info {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .email-info h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }
        .email-info .detail {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        .email-info .subject {
            font-weight: 600;
            color: #333;
        }
        .btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
        }
        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }
        .status {
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
        }
        .status.loading {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status.error {
            background: #ffebee;
            color: #c62828;
        }
        .hidden {
            display: none;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Report Phishing</h1>
            <p>Report this suspicious email to your security team</p>
        </div>

        <div class="email-info" id="emailInfo">
            <h3>Selected Email</h3>
            <div class="detail">From: <span id="emailFrom">Loading...</span></div>
            <div class="detail subject">Subject: <span id="emailSubject">Loading...</span></div>
        </div>

        <!-- Analysis Result Section (Hidden by default) -->
        <div class="email-info hidden" id="analysisResult">
            <h3>🛡️ Analysis Report</h3>
            <div class="detail">Verdict: <strong id="verdictBadge"></strong></div>
            <div class="detail">Risk Score: <span id="riskScore"></span>/100</div>
            
            <div class="score-bar-container">
                <div class="score-bar" id="scoreBar" style="width: 0%"></div>
            </div>

            <div id="riskFlags" class="mt-2"></div>
        </div>
        <button class="btn btn-danger" id="reportBtn" onclick="reportPhishing()">
            <span id="btnText">🚨 Report as Phishing</span>
        </button>
 
        <div class="status hidden" id="statusBox"></div>

        <div class="footer">
            Powered by {{ config('app.name', 'WebExert Sync') }}
        </div>
    </div>

    <style>
        .score-bar-container {
            height: 6px;
            background: #eee;
            border-radius: 3px;
            margin: 8px 0;
            overflow: hidden;
        }
        .score-bar {
            height: 100%;
            background: #28a745;
            transition: width 0.5s ease;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            margin-right: 4px;
            margin-bottom: 4px;
        }
        .badge-danger { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .badge-warning { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }
        .badge-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .mt-2 { margin-top: 8px; }
    </style>

    <script>
        let mailboxItem = null;
        let tenantId = '{{ env("MICROSOFT_TENANT_ID", "") }}';
        let apiEndpoint = '{{ url("/api/phishing/report") }}';

        // Initialize Office.js
        Office.onReady(function(info) {
            if (info.host === Office.HostType.Outlook) {
                mailboxItem = Office.context.mailbox.item;
                loadEmailInfo();
            } else {
                showStatus('error', 'This add-in only works in Outlook.');
            }
        });

        function loadEmailInfo() {
            if (!mailboxItem) return;

            // Get sender info
            if (mailboxItem.from) {
                mailboxItem.from.getAsync(function(result) {
                    if (result.status === Office.AsyncResultStatus.Succeeded) {
                        const from = result.value;
                        document.getElementById('emailFrom').textContent = 
                            from.displayName + ' <' + from.emailAddress + '>';
                    }
                });
            }

            // Get subject
            if (mailboxItem.subject) {
                document.getElementById('emailSubject').textContent = mailboxItem.subject;
            }
        }

        function reportPhishing() {
            if (!mailboxItem) {
                showStatus('error', 'No email selected.');
                return;
            }

            const btn = document.getElementById('reportBtn');
            const btnText = document.getElementById('btnText');
            
            btn.disabled = true;
            btnText.innerHTML = '<span class="spinner"></span> Reporting...';
            showStatus('loading', 'Scanning email & reporting to security...');

            // Get the item ID
            const itemId = mailboxItem.itemId;
            const userEmail = Office.context.mailbox.userProfile.emailAddress;

            // Send report to backend
            fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message_id: itemId,
                    user: userEmail,
                    tenant_id: tenantId,
                    reporter_email: userEmail,
                    reason: 'Reported via Outlook Add-in'
                })
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    btnText.innerHTML = '✅ Reported';
                    btn.className = 'btn btn-secondary'; // Disable style but keep readable
                    btn.disabled = true; // Prevent double submit
                    
                    showStatus('success', 'Email moved to Junk.');
                    
                    // Show Analysis Result
                    if (data.analysis) {
                        showAnalysis(data.analysis);
                    }
                } else {
                    btnText.textContent = '🚨 Report as Phishing';
                    showStatus('error', 'Failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btnText.textContent = '🚨 Report as Phishing';
                showStatus('error', 'Network error: ' + error.message);
            });
        }

        function showAnalysis(analysis) {
            const resultBox = document.getElementById('analysisResult');
            const verdictBadge = document.getElementById('verdictBadge');
            const riskScore = document.getElementById('riskScore');
            const scoreBar = document.getElementById('scoreBar');
            const flagsContainer = document.getElementById('riskFlags');
            
            resultBox.classList.remove('hidden');
            
            // Set Verdict
            verdictBadge.textContent = analysis.verdict;
            if (analysis.verdict === 'MALICIOUS') {
                verdictBadge.className = 'badge badge-danger';
                scoreBar.style.backgroundColor = '#dc3545';
            } else if (analysis.verdict === 'SUSPICIOUS') {
                verdictBadge.className = 'badge badge-warning';
                scoreBar.style.backgroundColor = '#ffc107';
            } else {
                verdictBadge.className = 'badge badge-success';
                scoreBar.style.backgroundColor = '#28a745';
            }
            
            // Set Score
            riskScore.textContent = analysis.risk_score;
            scoreBar.style.width = analysis.risk_score + '%';
            
            // Set Flags
            flagsContainer.innerHTML = '';
            if (analysis.flags && analysis.flags.length > 0) {
                analysis.flags.forEach(flag => {
                    const badge = document.createElement('span');
                    badge.className = 'badge badge-warning';
                    badge.textContent = flag.replace(/_/g, ' ');
                    flagsContainer.appendChild(badge);
                });
            } else {
                const badge = document.createElement('span');
                badge.className = 'badge badge-success';
                badge.textContent = 'No Threads Found';
                flagsContainer.appendChild(badge);
            }
        }

        function showStatus(type, message) {
            const statusBox = document.getElementById('statusBox');
            statusBox.className = 'status ' + type;
            statusBox.textContent = message;
            statusBox.classList.remove('hidden');
        }
    </script>
</body>
</html>
