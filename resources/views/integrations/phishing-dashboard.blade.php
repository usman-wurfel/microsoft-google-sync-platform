<x-app-layout>
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-body py-4">
                    <h1 class="h4 fw-bold text-dark mb-2">
                        <i class="fas fa-shield-alt text-primary me-2"></i>
                        Phishing Reports Dashboard
                    </h1>
                    <p class="text-muted mb-0">
                        View reports, blocked senders, and run tenant-wide purge.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data source info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm mb-0" role="alert">
                <h6 class="alert-heading mb-2"><i class="fas fa-database me-2"></i>Data from your backend</h6>
                <ul class="small mb-0 ps-3">
                    <li><strong>Recent Reports:</strong> When a user reports phishing from the Outlook add-in or the <a href="{{ route('integrations.phishing.test') }}" class="alert-link">Phishing Test</a> page, the request hits <code>POST /api/phishing/report</code>. The message is fetched from Graph, analyzed, and saved to the <strong>phishing_reports</strong> table. That data is shown here.</li>
                    <li><strong>Blocked Senders:</strong> When a report has verdict <strong>MALICIOUS</strong> or <strong>SUSPICIOUS</strong>, its sender is automatically added to the <strong>blocked_senders</strong> table. You can add these to the Microsoft 365 tenant block list (Defender → Policies → Tenant allow/block lists).</li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    @isset($blockedSenders)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-ban text-danger me-2"></i>
                        Blocked Senders
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Senders from MALICIOUS/SUSPICIOUS reports. Add them in Microsoft 365 Defender → Policies → Threat policies → Tenant allow/block lists.</p>
                    <div class="table-responsive" style="min-width: 0;">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-envelope me-1"></i>Sender</th>
                                    <th><i class="fas fa-globe me-1"></i>Domain</th>
                                    <th><i class="fas fa-clock me-1"></i>Blocked at</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($blockedSenders as $bs)
                                <tr>
                                    <td class="align-middle"><code class="small">{{ $bs->sender_email }}</code></td>
                                    <td class="align-middle">{{ $bs->domain ?? '—' }}</td>
                                    <td class="align-middle"><small class="text-muted">{{ $bs->blocked_at->format('M d, H:i') }}</small></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-muted small text-center py-3">No blocked senders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisset

    <!-- Recent Reports -->
    <div class="row mb-4">
        <div class="col-12" style="min-width: 0;">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list-alt text-primary me-2"></i>
                        Recent Reports
                    </h5>
                    <a href="{{ route('integrations.phishing.dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </a>
                </div>
                <div class="card-body overflow-card">
                    <div class="table-responsive" style="min-width: 0;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-nowrap"><i class="fas fa-calendar-alt me-1"></i>Received</th>
                                    <th class="text-nowrap"><i class="fas fa-user me-1"></i>Reporter</th>
                                    <th><i class="fas fa-envelope me-1"></i>Email</th>
                                    <th class="text-nowrap"><i class="fas fa-chart-line me-1"></i>Risk</th>
                                    <th class="text-nowrap"><i class="fas fa-bolt me-1"></i>Action</th>
                                    <th class="text-nowrap"><i class="fas fa-trash-alt me-1"></i>Purge</th>
                                    <th class="text-nowrap"><i class="fas fa-info-circle me-1"></i>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                <tr>
                                    <td class="align-middle">
                                        <small class="text-muted d-block">{{ $report->received_at->diffForHumans() }}</small>
                                        <small>{{ $report->received_at->format('M d, H:i') }}</small>
                                    </td>
                                    <td class="align-middle">
                                        <div class="fw-bold">{{ explode('@', $report->user_email)[0] }}</div>
                                        <small class="text-muted text-break">{{ $report->user_email }}</small>
                                    </td>
                                    <td class="align-middle">
                                        <div class="text-truncate" style="max-width: 220px;" title="{{ $report->subject ?? '(No Subject)' }}">
                                            <strong>{{ $report->subject ?? '(No Subject)' }}</strong>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $report->sender }}">
                                            From: {{ $report->sender }}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        @php
                                            $verdictClass = match($report->verdict) {
                                                'MALICIOUS' => 'bg-danger',
                                                'SUSPICIOUS' => 'bg-warning text-dark',
                                                'CLEAN' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            $scoreClass = 'text-success';
                                            if($report->risk_score > 70) $scoreClass = 'text-danger fw-bold';
                                            elseif($report->risk_score > 30) $scoreClass = 'text-warning fw-bold';
                                        @endphp
                                        <span class="badge {{ $verdictClass }}">{{ $report->verdict }}</span>
                                        <div class="small mt-1 {{ $scoreClass }}">Score: {{ $report->risk_score }}/100</div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-light text-dark border">
                                            {{ str_replace('_', ' ', $report->action_taken) }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        @if($report->internet_message_id && $report->action_taken !== 'PURGED')
                                        <form action="{{ route('integrations.phishing.purge', $report) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this message from ALL mailboxes in the tenant?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Purge tenant</button>
                                        </form>
                                        @elseif($report->action_taken === 'PURGED')
                                        <span class="small text-success"><i class="fas fa-check me-1"></i>Purged</span>
                                        @else
                                        <span class="small text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#details-{{ $report->id }}">
                                            <i class="fas fa-chevron-down me-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse bg-light" id="details-{{ $report->id }}">
                                    <td colspan="7" class="p-4">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <h6 class="text-primary mb-2"><i class="fas fa-database me-1"></i>Metadata</h6>
                                                <ul class="list-unstyled small mb-0">
                                                    <li class="mb-1"><strong>Message ID:</strong> <code class="text-muted">{{ Str::limit($report->message_id, 50) }}</code></li>
                                                    <li class="mb-1"><strong>Internet Message ID:</strong> <code class="text-muted">{{ $report->internet_message_id ?? 'N/A' }}</code></li>
                                                    <li><strong>Tenant ID:</strong> {{ $report->tenant_id }}</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-primary mb-2"><i class="fas fa-search me-1"></i>Analysis</h6>
                                                @if(isset($report->analysis_details['flags']) && count($report->analysis_details['flags']) > 0)
                                                    <div class="mb-2">
                                                        @foreach($report->analysis_details['flags'] as $flag)
                                                            <span class="badge bg-warning text-dark me-1">{{ $flag }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="small text-muted mb-2">No specific threat flags.</p>
                                                @endif
                                                @if(isset($report->analysis_details['urls']) && count($report->analysis_details['urls']) > 0)
                                                    <h6 class="small fw-bold mt-2">URLs ({{ count($report->analysis_details['urls']) }})</h6>
                                                    <div class="small text-muted" style="max-height: 100px; overflow-y: auto;">
                                                        @foreach($report->analysis_details['urls'] as $url)
                                                            <div class="text-truncate">{{ $url }}</div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No phishing reports yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($reports->hasPages())
                    <div class="d-flex justify-content-center mt-3 pt-3 border-top">
                        {{ $reports->links('pagination::bootstrap-5') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
