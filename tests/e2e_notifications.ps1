$ErrorActionPreference = "Continue"
$base = "http://127.0.0.1:8907/api"
$passed = 0
$failed = 0

function Assert($name, $cond, $msg) {
    if ($cond) {
        Write-Host "  PASS: $name" -ForegroundColor Green
        $global:passed++
    } else {
        Write-Host "  FAIL: $name - $msg" -ForegroundColor Red
        $global:failed++
    }
}

function Patch-Json($url, $body, $token) {
    try {
        $r = Invoke-RestMethod -Uri "$base$url" -Method PATCH -Body $body -Headers @{Authorization="Bearer $token"; "Content-Type"="application/json"} -TimeoutSec 8
        return @{ ok = $true; data = $r; status = 200 }
    } catch {
        $code = 0
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        $errBody = $null
        try { $sr = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream()); $errBody = $sr.ReadToEnd(); $sr.Close() } catch {}
        return @{ ok = $false; data = $null; status = $code; error = $errBody }
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  NOTIFICATIONS MODULE E2E TEST" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Start server once
Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
Start-Process -FilePath "C:\php84\php.exe" -ArgumentList '-c C:\php84\php.ini artisan serve --host=127.0.0.1 --port=8907' -WorkingDirectory "E:\STAGE B-TECH\backend_gestion_immobilier" -WindowStyle Hidden
Start-Sleep -Seconds 5
Write-Host "Server started on :8907`n" -ForegroundColor Yellow

# --- Logins ---
Write-Host "[Logins]" -ForegroundColor Yellow
$adminToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"admin@btech.test","password":"password"}' -ContentType "application/json").data.token
Assert "Admin login" ($null -ne $adminToken) "null"
$locToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"test@example.com","password":"password"}' -ContentType "application/json").data.token
Assert "Loc1 login" ($null -ne $locToken) "null"

# Verify existing notifications in DB
Write-Host "`n[Setup]" -ForegroundColor Yellow
$notifCount = (Invoke-RestMethod -Uri "$base/notifications" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json").meta.total
Write-Host "  Existing notifications for locataire: $notifCount" -ForegroundColor Gray

# --- Tests ---
Write-Host "`n[Tests]" -ForegroundColor Yellow

# 1: GET / (locataire) — returns real notifications from DB
$r = Invoke-RestMethod -Uri "$base/notifications" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json"
Assert "1. GET / (locataire) returns notifications" ($r.success -eq $true -and $r.data.Count -gt 0) "count=$($r.data.Count)"
Assert "1b. unread_count present" ($null -ne $r.unread_count) "null"
Write-Host "    unread_count=$($r.unread_count)" -ForegroundColor Gray

# 2: GET / (admin) — 0 notifications, admin has none
$r2 = Invoke-RestMethod -Uri "$base/notifications" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "2. GET / (admin) returns 0 notifications" ($r2.success -eq $true -and $r2.data.Count -eq 0) "count=$($r2.data.Count)"
Assert "2b. admin unread_count = 0" ($r2.unread_count -eq 0) "count=$($r2.unread_count)"

# 3: PATCH /1/read (locataire, owner) — 200, idempotent
$firstNotifId = $r.data[0].id
$r3 = Patch-Json "/notifications/$firstNotifId/read" '{}' $locToken
Assert "3. Mark as read (owner) -> 200" ($r3.ok -and $r3.status -eq 200) "status=$($r3.status)"
$r3b = Patch-Json "/notifications/$firstNotifId/read" '{}' $locToken
Assert "3b. Idempotent (already read) -> 200" ($r3b.ok -and $r3b.status -eq 200) "status=$($r3b.status)"

# 4: PATCH /1/read (admin, non-owner) — 403
$r4 = Patch-Json "/notifications/$firstNotifId/read" '{}' $adminToken
Assert "4. Admin tries to mark other user's notification -> 403" ((-not $r4.ok) -and $r4.status -eq 403) "status=$($r4.status)"

# 5: PATCH /read-all (locataire) — 200, all marked read
$r5 = Patch-Json "/notifications/read-all" '{}' $locToken
Assert "5. Mark all as read -> 200" ($r5.ok -and $r5.status -eq 200) "status=$($r5.status)"
Assert "5b. updated count > 0" ($r5.data.data.updated -gt 0) "updated=$($r5.data.data.updated)"

# 6: GET / (locataire) after read-all — unread_count = 0
$r6 = Invoke-RestMethod -Uri "$base/notifications" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json"
Assert "6. unread_count = 0 after read-all" ($r6.unread_count -eq 0) "count=$($r6.unread_count)"

# 7: PATCH /999/read (non-existent) — 404
$r7 = Patch-Json "/notifications/999/read" '{}' $locToken
Assert "7. Non-existent notification -> 404" ((-not $r7.ok) -and $r7.status -eq 404) "status=$($r7.status)"

# 8: PATCH /read-all (admin, 0 unread) — 200 with 0 updated
$r8 = Patch-Json "/notifications/read-all" '{}' $adminToken
Assert "8. Mark all as read (none unread) -> 200" ($r8.ok -and $r8.status -eq 200) "status=$($r8.status)"
Assert "8b. updated = 0" ($r8.data.data.updated -eq 0) "updated=$($r8.data.data.updated)"

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  RESULTS: $global:passed passed, $global:failed failed" -ForegroundColor $(if ($global:failed -eq 0) { "Green" } else { "Red" })
Write-Host "========================================`n" -ForegroundColor Cyan

Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
