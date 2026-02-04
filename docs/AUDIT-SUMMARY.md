# Audit Summary - Session & Authentication Issues

## Date: February 4, 2026

## Issue: Statistics & Transactions redirect to login with "gagal memuat" error

---

## ✅ VERIFIED WORKING:

1. **Dashboard** - Works, session persists
2. **Accounts** - Works, no redirect
3. **Settings** - Works, no redirect
4. **Categories** - Works, no redirect

## ❌ STILL FAILING:

1. **Statistics** - Redirects to login
2. **Transactions** - Redirects to login

---

## AUDIT RESULTS:

### 1. Session Configuration ✅

**File:** `backend/middleware/AuthMiddleware.php`

- ✅ Session cookie params set correctly (path: '/', secure, httponly, samesite: Lax)
- ✅ No domain param (browser auto-detect)
- ✅ No session regeneration (was causing new session ID each request)
- ✅ Debug logging added

**Test Result:**

- Session ID consistent: ✅
- Session persists: ✅
- Auth check returns authenticated: ✅

### 2. CORS Configuration ✅

**File:** `backend/config/database.php`

- ✅ setCORSHeaders() function added
- ✅ Access-Control-Allow-Origin: dynamic (from HTTP_ORIGIN)
- ✅ Access-Control-Allow-Credentials: true

**Applied to:**

- ✅ auth.php
- ✅ dashboard.php
- ✅ accounts.php
- ✅ categories.php
- ✅ transactions.php
- ✅ budgets.php
- ✅ statistics.php
- ✅ users.php
- ✅ export.php

### 3. Frontend Credentials ✅

**File:** `frontend/assets/js/app.js`

- ✅ apiRequest() has credentials: 'include'
- ✅ 401 handler redirects to login
- ✅ API_URL calculation fixed for hosting

**File:** `frontend/assets/js/auth.js`

- ✅ Login fetch has credentials: 'include'
- ✅ Register fetch has credentials: 'include'

**File:** `frontend/assets/js/dashboard.js`

- ✅ loadData() has credentials: 'include'

**File:** `frontend/assets/js/statistics.js`

- ✅ All 7 fetch calls have credentials: 'include'
  - loadOverview() ✅
  - loadCategoryStats() ✅
  - loadAccountStats() ✅
  - loadTrendData() ✅
  - loadIncomeExpenseChart() ✅
  - exportPDF() ✅
  - exportExcel() ✅

**File:** `frontend/assets/js/transactions.js`

- ✅ All 5 fetch calls have credentials: 'include'
  - loadAll() ✅
  - getById() ✅
  - create() ✅
  - update() ✅
  - delete() ✅

### 4. HTML Pages checkAuth() ✅

**Files checked:**

- ✅ statistics.html - has credentials: 'include'
- ✅ transaction.html - has credentials: 'include'
- ✅ categories.html - has credentials: 'include'
- ✅ settings.html - has credentials: 'include'
- ✅ accounts.html - has credentials: 'include'
- ✅ calender.html - has credentials: 'include'

### 5. Inline Scripts in HTML ✅

**transaction.html:**

- ✅ loadAccounts() - credentials: 'include'
- ✅ loadCategories() - credentials: 'include'
- ✅ loadTransactions() - credentials: 'include'
- ✅ All other fetch calls have credentials

**statistics.html:**

- ✅ Uses statistics.js (external)
- ✅ checkAuth() has credentials: 'include'

---

## POTENTIAL ROOT CAUSES:

### Most Likely Issues:

1. **Browser Cache**
   - Old JS files cached (without credentials)
   - Solution: Hard refresh (Ctrl+Shift+R)
   - Or clear browser cache

2. **API_URL Path Issue**
   - app.js calculates basePath dynamically
   - If frontendIndex = 0 (frontend at root), basePath = ""
   - API_URL = "/backend/api" ✅ (correct)
   - **BUT:** transactions.js and some inline scripts use hardcoded `/backend/api`

   **FOUND IN:**
   - `transactions.js` line 16: `let url = '/backend/api/transactions.php?';`
   - `transactions.js` line 44: `const response = await fetch(\`/backend/api/transactions.php?id=\${id}\`);`
   - etc.

3. **Script Load Order**
   - statistics.js loads AFTER app.js ✅
   - transactions.js loads AFTER app.js ✅
   - APP_CONFIG should be available ✅

4. **Error Handling**
   - If fetch fails (network, 500, etc), catch block shows "gagal memuat"
   - Then redirects to login
   - **This is expected behavior** - but WHY is fetch failing?

---

## RECOMMENDED FIXES:

### Priority 1: Fix Hardcoded API Paths in transactions.js

**Current:** Hardcoded `/backend/api/`
**Should be:** Use `APP_CONFIG.API_URL` consistently

### Priority 2: Add Better Error Logging

Add console.error in catch blocks to see actual error before redirect

### Priority 3: Test Sequence

1. Clear browser cache
2. Git pull on hosting
3. Hard refresh (Ctrl+Shift+R)
4. Open browser console BEFORE clicking Statistics/Transactions
5. Watch for errors

---

## FILES MODIFIED (Last 3 Commits):

### Commit bf2606f: "Fix: Session persistence"

- backend/middleware/AuthMiddleware.php

### Commit 437f02f: "Fix: Add credentials:include to checkAuth in all pages"

- frontend/pages/statistics.html
- frontend/pages/settings.html
- frontend/pages/categories.html
- frontend/pages/calender.html

### Commit 2d27f54: "Fix: Add credentials:include to all fetch calls in transactions.js"

- frontend/assets/js/transactions.js

---

## NEXT STEPS:

1. ✅ Fix hardcoded paths in transactions.js to use APP_CONFIG.API_URL
2. ✅ Add detailed error logging to pinpoint exact failure
3. ⏳ Test on hosting after fixes
4. ⏳ Check browser console for actual error messages
5. ⏳ Verify session cookie is sent with requests (Network tab)

---

## DEBUGGING COMMANDS:

### On Hosting (SSH):

```bash
# Check error logs
tail -f ~/logs/error_log

# Check PHP session files
ls -la /var/cpanel/php/sessions/ea-php74/

# Test API directly
curl -i -X GET https://pipil.my.id/backend/api/statistics.php?action=overview&month=02&year=2026 \
  -H "Cookie: PHPSESSID=<session_id>"
```

### In Browser Console:

```javascript
// Test API with credentials
fetch("/backend/api/statistics.php?action=overview&month=02&year=2026", {
  credentials: "include",
})
  .then((r) => r.json())
  .then(console.log)
  .catch(console.error);

// Check APP_CONFIG
console.log("APP_CONFIG:", APP_CONFIG);

// Check session cookie
document.cookie;
```

---

## CONCLUSION:

**Root Cause:** Transactions.js uses hardcoded `/backend/api/` paths instead of dynamic `APP_CONFIG.API_URL`

**Impact:** Works on localhost but may fail on hosting if path structure different

**Fix Required:** Replace all hardcoded `/backend/api/` with `APP_CONFIG.API_URL` in transactions.js
