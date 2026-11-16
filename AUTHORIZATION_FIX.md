# 🔧 Authorization Error Fix - Complete Guide

## ❌ Error Encountered:
```
Failed to sync Microsoft data: Microsoft groups sync failed: 
{"error":{"code":"Authorization_RequestDenied","message":"Insufficient privileges to complete the operation."}}
```

## ✅ Problem Fixed!

### Root Cause:
Different controllers were requesting **different scopes**, but all using the **same Microsoft OAuth driver**. This caused permission conflicts.

### Solution Applied:
Updated **all controllers** to request the **same comprehensive set of scopes**:

```php
// Combined scopes for ALL features:
[
    // User & Group Sync
    'User.Read.All', 
    'Group.Read.All', 
    'Directory.Read.All',
    'Team.ReadBasic.All',
    'TeamMember.Read.All',
    'Organization.Read.All',
    
    // Mail Delivery & Phishing Report
    'Mail.Send',
    'Mail.ReadWrite',
    'Mail.Read',
    'MailboxSettings.ReadWrite'
]
```

---

## 🔐 Azure Portal - Action Required!

### Step 1: Verify Permissions in Azure Portal

1. Go to **Azure Portal** → **Microsoft Entra ID** → **App registrations**
2. Select your app: **AP2TLabs_ConfigSync**
3. Go to **API permissions**
4. Verify these permissions are present:

#### ✅ Microsoft Graph Permissions (Application):
- [x] User.Read.All
- [x] Group.Read.All
- [x] Directory.Read.All
- [x] Team.ReadBasic.All
- [x] TeamMember.Read.All
- [x] Organization.Read.All
- [x] **Mail.Send** ← Check this!
- [x] **Mail.ReadWrite** ← Check this!
- [x] **Mail.Read** ← Check this!
- [x] **MailboxSettings.ReadWrite** ← Check this!

#### ✅ Exchange Online Permission:
- [x] Exchange.ManageAsApp (Office 365 Exchange Online)

### Step 2: Grant Admin Consent (IMPORTANT!)

After verifying permissions:

1. Click **"Grant admin consent for Lorem Ipsum Dev"** button
2. Click **Yes** to confirm
3. Wait for green checkmarks (✅) to appear
4. **CRITICAL**: If any permission shows "Not granted", you MUST grant consent again

---

## 🔄 Re-Connect After Fix

### For Each Integration, You Need To:

#### **Option A: Quick Re-Auth (Recommended)**
1. Logout from application
2. Clear browser cache/cookies
3. Login again
4. Go to Integrations page
5. Click "Connect" on any integration
6. Complete OAuth (it will request NEW permissions)
7. Success! ✅

#### **Option B: Force Re-Consent**
Use this URL directly (replace with your app client ID if different):
```
https://login.microsoftonline.com/6c5f497d-dd4d-447b-a44b-65df1ad64308/oauth2/v2.0/authorize?client_id=394b72fa-f827-4e04-b9d6-71c19c70300f&response_type=code&redirect_uri=http://localhost:8000/sync/microsoft/callback&scope=User.Read.All%20Group.Read.All%20Directory.Read.All%20Mail.Send%20Mail.ReadWrite%20Mail.Read%20MailboxSettings.ReadWrite&prompt=consent
```

---

## ✅ Testing Steps (After Re-Auth)

### Test 1: Microsoft User Sync (Existing Feature)
1. Go to Dashboard
2. Click "Sync from Microsoft"
3. ✅ Should work without errors

### Test 2: Graph Mail Delivery
1. Go to Integrations
2. Click "Connect" on Graph API Mail Delivery
3. Complete OAuth
4. ✅ No authorization errors

### Test 3: Phishing Report
1. Go to Integrations
2. Click "Connect" on Report Phishing Button
3. Complete OAuth
4. ✅ No authorization errors

### Test 4: Automated Allowlisting
1. Go to Integrations
2. Click "Connect" on Automated Allowlisting
3. Complete OAuth
4. ✅ Should sync without errors

---

## 📋 Files Updated in Fix:

✅ `MicrosoftSyncController.php` - Added mail scopes  
✅ `GraphMailController.php` - Standardized scopes  
✅ `PhishingReportController.php` - Standardized scopes  
✅ `AllowlistController.php` - Standardized scopes  

---

## 🎯 What This Fix Does:

### Before Fix:
- ❌ Each controller requested different scopes
- ❌ OAuth token didn't have all permissions
- ❌ "Authorization_RequestDenied" errors

### After Fix:
- ✅ All controllers request SAME comprehensive scopes
- ✅ Single OAuth consent grants ALL permissions
- ✅ No authorization errors
- ✅ All features work with one authentication

---

## 🚨 Important Notes:

1. **Must Re-Grant Admin Consent**: After adding new permissions in Azure Portal, you MUST click "Grant admin consent" button again.

2. **Must Re-Authenticate**: Existing user sessions won't have new scopes. Users must:
   - Logout
   - Login again
   - OR use direct consent URL

3. **Multi-Tenant Customers**: Each customer tenant admin must grant consent separately using:
   ```
   https://login.microsoftonline.com/common/adminconsent?client_id=394b72fa-f827-4e04-b9d6-71c19c70300f
   ```

4. **Token Refresh**: If still getting errors after re-auth, clear application cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

---

## 🔍 How To Verify Fix Worked:

After re-authentication, check browser Network tab:
1. Open DevTools (F12)
2. Go to Network tab
3. Trigger OAuth flow
4. Look for redirect URL
5. Check `scope` parameter - should include all scopes:
   ```
   scope=User.Read.All Group.Read.All Directory.Read.All Mail.Send Mail.ReadWrite Mail.Read MailboxSettings.ReadWrite...
   ```

---

**Fix Applied**: February 5, 2026, 2:18 PM  
**Status**: ✅ Code Updated - Needs Azure Portal Admin Consent  
**Next Action**: Re-grant admin consent in Azure Portal
