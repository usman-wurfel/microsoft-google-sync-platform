<x-app-layout>
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-4">
                    <h1 class="display-6 fw-bold text-dark mb-2">
                        <i class="fas fa-envelope text-info me-3"></i>
                        Graph API Mail Delivery - Test
                    </h1>
                    <p class="lead text-muted mb-0">
                        Send emails directly via Microsoft Graph API
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Check -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3" style="font-size: 2rem;"></i>
                    <div>
                        <h6 class="mb-1">✅ Graph Mail Delivery Connected</h6>
                        <small class="text-muted">You can now send emails via Microsoft Graph API</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Sending Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-paper-plane me-2"></i>
                        Send Test Email
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="emailForm">
                        @csrf
                        
                        <!-- Recipient Email -->
                        <div class="mb-3">
                            <label for="recipient" class="form-label">
                                <i class="fas fa-user me-2"></i>Recipient Email *
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="recipient" 
                                   name="recipient" 
                                   placeholder="user@example.com"
                                   required>
                            <small class="text-muted">Enter the email address of the recipient</small>
                        </div>

                        <!-- Subject -->
                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-heading me-2"></i>Subject *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="subject" 
                                   name="subject" 
                                   placeholder="Test Email from Graph API"
                                   required>
                        </div>

                        <!-- Email Body -->
                        <div class="mb-3">
                            <label for="body" class="form-label">
                                <i class="fas fa-align-left me-2"></i>Email Body (HTML) *
                            </label>
                            <textarea class="form-control" 
                                      id="body" 
                                      name="body" 
                                      rows="8" 
                                      required><h2>Hello from Microsoft Graph API!</h2>
<p>This is a test email sent directly via Microsoft Graph API, bypassing SMTP.</p>
<p><strong>Features:</strong></p>
<ul>
    <li>Direct inbox injection</li>
    <li>No SMTP configuration needed</li>
    <li>Better deliverability</li>
</ul>
<p>This email was sent at: <strong>{{ now()->format('F j, Y g:i A') }}</strong></p></textarea>
                            <small class="text-muted">You can use HTML tags for formatting</small>
                        </div>

                        <!-- Send Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info btn-lg" id="sendBtn">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span id="btnText">Send Email</span>
                                <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Section -->
    <div class="row mt-4" id="responseSection" style="display: none;">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-lg" id="responseCard">
                <div class="card-header" id="responseHeader">
                    <h5 class="mb-0" id="responseTitle"></h5>
                </div>
                <div class="card-body">
                    <pre id="responseBody" class="mb-0" style="white-space: pre-wrap;"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="row mt-5">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-code me-2"></i>
                        API Documentation
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">API Endpoint:</h6>
                    <code class="d-block p-3 bg-light rounded mb-3">
                        POST {{ url('/api/mail/send') }}
                    </code>

                    <h6 class="fw-bold">Request Body (JSON):</h6>
                    <pre class="bg-light p-3 rounded mb-3"><code>{
    "recipient": "user@example.com",
    "subject": "Test Email",
    "body": "&lt;h1&gt;Hello&lt;/h1&gt;&lt;p&gt;This is a test&lt;/p&gt;"
}</code></pre>

                    <h6 class="fw-bold">cURL Example:</h6>
                    <pre class="bg-dark text-white p-3 rounded"><code>curl -X POST {{ url('/api/mail/send') }} \
  -H "Content-Type: application/json" \
  -d '{
    "recipient": "user@example.com",
    "subject": "Test Email",
    "body": "&lt;h1&gt;Hello&lt;/h1&gt;"
  }'</code></pre>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>

<script>
document.getElementById('emailForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('sendBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const responseSection = document.getElementById('responseSection');
    const responseCard = document.getElementById('responseCard');
    const responseHeader = document.getElementById('responseHeader');
    const responseTitle = document.getElementById('responseTitle');
    const responseBody = document.getElementById('responseBody');
    
    // Show loading state
    btn.disabled = true;
    btnText.textContent = 'Sending...';
    btnSpinner.classList.remove('d-none');
    responseSection.style.display = 'none';
    
    try {
        const formData = {
            recipient: document.getElementById('recipient').value,
            subject: document.getElementById('subject').value,
            body: document.getElementById('body').value
        };
        
        const response = await fetch('{{ route("api.mail.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        // Show response
        responseSection.style.display = 'block';
        
        if (response.ok && data.success) {
            // Success
            responseCard.className = 'card border-0 shadow-lg border-success';
            responseHeader.className = 'card-header bg-success text-white';
            responseTitle.innerHTML = '<i class="fas fa-check-circle me-2"></i>Email Sent Successfully!';
            responseBody.textContent = JSON.stringify(data, null, 2);
            
            // Show success toast
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Email Sent!',
                    text: 'Email was delivered successfully via Microsoft Graph API',
                    timer: 3000
                });
            }
            
            // Reset form
            document.getElementById('emailForm').reset();
        } else {
            // Error
            responseCard.className = 'card border-0 shadow-lg border-danger';
            responseHeader.className = 'card-header bg-danger text-white';
            responseTitle.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error Sending Email';
            responseBody.textContent = JSON.stringify(data, null, 2);
            
            // Show error toast
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Send Failed',
                    text: data.error || 'An error occurred while sending email',
                });
            }
        }
        
        // Scroll to response
        responseSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
    } catch (error) {
        console.error('Error:', error);
        
        responseSection.style.display = 'block';
        responseCard.className = 'card border-0 shadow-lg border-danger';
        responseHeader.className = 'card-header bg-danger text-white';
        responseTitle.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Network Error';
        responseBody.textContent = error.message;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not connect to server',
            });
        }
    } finally {
        // Reset button state
        btn.disabled = false;
        btnText.textContent = 'Send Email';
        btnSpinner.classList.add('d-none');
    }
});
</script>

<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

pre code {
    font-size: 0.9rem;
}

.form-control:focus {
    border-color: #0dcaf0;
    box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.25);
}

#responseBody {
    max-height: 400px;
    overflow-y: auto;
    font-size: 0.85rem;
}
</style>
