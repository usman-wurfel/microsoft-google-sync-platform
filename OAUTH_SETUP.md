# OAuth Configuration Setup

## Microsoft Entra ID Setup

### Step 1: Create App Registration
1. Go to [Azure Portal](https://portal.azure.com/)
2. Navigate to "Azure Active Directory" > "App registrations"
3. Click "New registration"
4. Fill in the details:
   - **Name**: "MS GWS Sync Application"
   - **Supported account types**: "Accounts in this organizational directory only"
   - **Redirect URI**: `http://localhost:8000/sync/microsoft/callback`
5. Click "Register"

### Step 2: Get Application Details
After creation, note down:
- **Application (client) ID**: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- **Directory (tenant) ID**: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

### Step 3: Create Client Secret
1. Go to "Certificates & secrets"
2. Click "New client secret"
3. Add description: "MS GWS Sync Secret"
4. Choose expiration: "24 months" (recommended)
5. Click "Add"
6. **Copy the secret value immediately** (it won't be shown again)

### Step 4: Configure API Permissions
1. Go to "API permissions"
2. Click "Add a permission"
3. Select "Microsoft Graph"
4. Choose "Application permissions"
5. Add these permissions:
   - **User.Read.All** (read all users' profiles)
   - **Group.Read.All** (read all groups)
   - **Directory.Read.All** (read directory data)
   - **Team.ReadBasic.All** (read Teams information)
   - **TeamMember.Read.All** (read Teams members)
   - **Organization.Read.All** (read organization info)
6. Click "Add permissions"

### Step 5: Grant Admin Consent
1. Click "Grant admin consent for [Your Organization]"
2. Confirm the action
3. All permissions should show "Granted for [Your Organization]"

### Step 6: Configure Authentication
1. Go to "Authentication"
2. Add redirect URIs:
   - `http://localhost:8000/sync/microsoft/callback` (for local testing)
   - `http://localhost:8000/sync/microsoft/callback` (for production)
3. Under "Advanced settings":
   - Enable "Allow public client flows": **No**
   - Enable "Live SDK support": **No**

### Step 7: Test Configuration
1. Verify all permissions are granted
2. Ensure client secret is valid
3. Test the redirect URI works

## Google Workspace Setup

### Step 1: Create/Select Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project OR select existing project
3. Note your project name for reference

### Step 2: Enable Required APIs
1. Go to "APIs & Services" > "Library"
2. Search and enable these APIs:
   - **Admin SDK API** (for user/group sync)
   - **Google+ API** (for profile photos)
   - **Google Workspace Admin SDK** (if available)

### Step 3: Configure OAuth Consent Screen
1. Go to "APIs & Services" > "OAuth consent screen"
2. Choose "External" user type
3. Fill required fields:
   - **App name**: "MS GWS Sync"
   - **User support email**: your email
   - **Developer contact**: your email
4. Add scopes:
   - `https://www.googleapis.com/auth/admin.directory.user.readonly`
   - `https://www.googleapis.com/auth/admin.directory.group.readonly`
   - `https://www.googleapis.com/auth/admin.directory.orgunit.readonly`
   - `https://www.googleapis.com/auth/admin.directory.domain.readonly`
   - `https://www.googleapis.com/auth/userinfo.email`
   - `https://www.googleapis.com/auth/userinfo.profile`

### Step 4: Create OAuth 2.0 Credentials
1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth 2.0 Client ID"
3. Choose "Web application"
4. Set name: "MS GWS Sync App"
5. Add authorized redirect URIs:
   - `http://localhost:8000/sync/google/callback` (for local testing)
   - `http://localhost:8000/sync/google/callback` (for production)
6. Click "Create"

### Step 5: Get Credentials
After creating OAuth client, note down:
- **Client ID**: `xxxxxx.apps.googleusercontent.com`
- **Client Secret**: `xxxxxx`

### Step 6: Domain Verification (if required)
1. Go to "APIs & Services" > "OAuth consent screen"
2. If domain verification is required, follow the verification process
3. This is needed for production use

### Step 7: Admin Access Requirements
- The Google account must have **Super Admin** privileges
- Must be part of the Google Workspace organization
- Required for accessing user and group data

## Environment Configuration

### Step 1: Update .env File
Add these variables to your `.env` file:

```env
# Microsoft OAuth Configuration
MICROSOFT_CLIENT_ID=your_microsoft_client_id_here
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret_here
MICROSOFT_REDIRECT_URI=http://localhost:8000/sync/microsoft/callback
MICROSOFT_TENANT_ID=your_tenant_id_here

# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/sync/google/callback

# Production URLs (update when deploying)
# MICROSOFT_REDIRECT_URI=http://localhost:8000/sync/microsoft/callback
# GOOGLE_REDIRECT_URI=http://localhost:8000/sync/google/callback
```

### Step 2: Update config/services.php
Ensure these configurations are in `config/services.php`:

```php
'microsoft' => [
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'redirect' => env('MICROSOFT_REDIRECT_URI'),
    'tenant' => env('MICROSOFT_TENANT_ID'),
],

'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

## Database Configuration

Make sure your database is configured in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ms_gws
DB_USERNAME=root
DB_PASSWORD=your_password
```

## Running the Application

### Step 1: Database Setup
1. Run migrations: `php artisan migrate`
2. Run seeders: `php artisan db:seed` (if available)

### Step 2: Start Development Server
1. Start the server: `php artisan serve`
2. Visit: `http://localhost:8000`
3. Register/Login to access the sync dashboard

### Step 3: Test Sync Functionality
1. Click "Sync from Microsoft" button
2. Click "Sync from Google Workspace" button
3. Check dashboard for synced data

## Troubleshooting

### Common Microsoft Issues
- **"AADSTS65001: The user or administrator has not consented"**
  - Solution: Grant admin consent in Azure Portal
- **"AADSTS70011: The provided value for the 'scope' parameter is not valid"**
  - Solution: Check API permissions are correctly configured
- **"AADSTS50020: User account from identity provider does not exist"**
  - Solution: Use correct tenant ID

### Common Google Issues
- **"Error 403: Access denied"**
  - Solution: Ensure Super Admin privileges
- **"Error 400: Invalid client"**
  - Solution: Check Client ID and Secret
- **"Error 400: Redirect URI mismatch"**
  - Solution: Verify redirect URIs in Google Console

### General Issues
- **"Class 'Laravel\Socialite\SocialiteServiceProvider' not found"**
  - Solution: Run `composer install`
- **"Database connection failed"**
  - Solution: Check database credentials in `.env`
- **"Sync failed: No users found"**
  - Solution: Check API permissions and admin access

### Debug Steps
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify OAuth configurations
3. Test API endpoints manually
4. Check network connectivity
5. Verify admin privileges

## Allowlist and Email Policy (Exchange Online)

The allowlist sync sends three kinds of configuration to the tenant:

1. **Domain allow entries** – Domains from which email is allowed.
2. **IP allow list entries** – IP addresses to allow.
3. **Email policy (Level 0)** – Allows emails that have a specific header (e.g. `X-AP2T-Simulation`) and are from one of the allowed domains. This is implemented as a **mail flow rule (transport rule)** in Exchange Online: when a message has that header and the sender domain is in the allow list, the message is treated as allowed (SCL Level 0) and not blocked.

### Email policy payload (API)

When triggering allowlist sync (e.g. `POST /integrations/allowlist/sync`), include:

```json
{
  "ips": "1.2.3.4, 5.6.7.8",
  "domains": "example.com, yourdomain.com",
  "email_policy": {
    "enabled": true,
    "header_name": "X-AP2T-Simulation",
    "scl_level": 0
  }
}
```

- **header_name**: The email header that, when present, marks the message for allow (e.g. `X-AP2T-Simulation`). Combined with sender domain in the allow list, the message is allowed (Level 0).
- **scl_level**: `0` = allow (do not block). Applied via the transport rule in Exchange.

### How it is applied in Microsoft

- Domain and IP allow lists are applied to the tenant’s anti-spam/connection filter configuration.
- The email policy is applied as an Exchange Online **transport rule**: condition = message header contains `header_name` and sender domain is in the allow list; action = set SCL to 0 (allow).

## Production Deployment

### Step 1: Update URLs
1. Change redirect URIs to production URLs
2. Update `.env` file with production values
3. Test OAuth flows in production

### Step 2: Security Considerations
1. Use strong client secrets
2. Enable HTTPS only
3. Regular secret rotation
4. Monitor API usage
5. Set up proper logging

### Step 3: Performance Optimization
1. Implement caching for API responses
2. Use database indexing
3. Monitor API rate limits
4. Set up background job processing
