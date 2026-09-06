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

function Put-Json($url, $body, $token) {
    try {
        $headers = @{"Authorization"="Bearer $token"; "Content-Type"="application/json"}
        $r = Invoke-RestMethod -Uri "$base$url" -Method PUT -Body $body -Headers $headers -TimeoutSec 8
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
Write-Host "  USERS MODULE E2E TEST" -ForegroundColor Cyan
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
$propToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"prop@test.com","password":"password"}' -ContentType "application/json").data.token
Assert "Prop login" ($null -ne $propToken) "null"

# --- Tests ---
Write-Host "`n[Tests]" -ForegroundColor Yellow

# === GET /users/ (admin list) ===

# 1: GET /users/ (admin) — 200, paginé
$r = Invoke-RestMethod -Uri "$base/users/" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "1. GET /users/ (admin) -> 200" ($r.success -eq $true) "success=$($r.success)"
Assert "1b. data is array" ($r.data -is [System.Array]) "type=$($r.data.GetType().Name)"
Assert "1c. meta present" ($null -ne $r.meta) "null"
Assert "1d. meta.total > 0" ($r.meta.total -gt 0) "total=$($r.meta.total)"
Write-Host "    total=$($r.meta.total) page=$($r.meta.current_page)" -ForegroundColor Gray

# 2: GET /users/?role=locataire — filtre
$r2 = Invoke-RestMethod -Uri "$base/users/?role=locataire" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "2. Filter by role=locataire -> 200" ($r2.success -eq $true) "success=$($r2.success)"
$nonLoc = $r2.data | Where-Object { $_.role -ne 'locataire' }
Assert "2b. All results are locataires" ($null -eq $nonLoc) "non-locataires found"

# 3: GET /users/?search=admin — recherche
$r3 = Invoke-RestMethod -Uri "$base/users/?search=admin" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "3. Search by name=admin -> 200" ($r3.success -eq $true) "success=$($r3.success)"
Assert "3b. Results found" ($r3.data.Count -gt 0) "count=$($r3.data.Count)"

# 4: GET /users/ (locataire) — 403
try {
    Invoke-RestMethod -Uri "$base/users/" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json" -TimeoutSec 8 > $null
    Assert "4. GET /users/ (locataire) -> 403" $false "expected 403, got 200"
} catch {
    $code = 0
    if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
    Assert "4. GET /users/ (locataire) -> 403" ($code -eq 403) "status=$code"
}

# === GET /users/profile ===

# 5: GET /users/profile (locataire) — 200, données correctes
$r5 = Invoke-RestMethod -Uri "$base/users/profile" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json"
Assert "5. GET /profile (locataire) -> 200" ($r5.success -eq $true) "success=$($r5.success)"
Assert "5b. email matches" ($r5.data.email -eq "test@example.com") "email=$($r5.data.email)"
Assert "5c. role is locataire" ($r5.data.role -eq "locataire") "role=$($r5.data.role)"

# 14: Verify password is NOT in response
$passwordLeak = $r5.data.PSObject.Properties | Where-Object { $_.Name -eq 'password' }
Assert "14. password field absent from profile response" ($null -eq $passwordLeak) "found password field"

# === PUT /users/profile (updateProfile) ===

# 6: PUT /users/profile (name, telephone) — 200
$ts = Get-Date -Format 'yyyyMMddHHmmss'
$r6 = Put-Json "/users/profile" "{`"name`":`"TestUser-$ts`",`"telephone`":`"034$ts`"}" $locToken
Assert "6. Update name+telephone -> 200" ($r6.ok -and $r6.data.success -eq $true) "status=$($r6.status)"
Assert "6b. name updated" ($r6.data.data.name -eq "TestUser-$ts") "name=$($r6.data.data.name)"
Assert "6c. telephone updated" ($r6.data.data.telephone -eq "034$ts") "tel=$($r6.data.data.telephone)"

# 7: PUT /users/profile (role=admin) — silently ignored
$originalRole = $r6.data.data.role
$r7 = Put-Json "/users/profile" '{"role":"admin"}' $locToken
Assert "7. role=admin silently ignored -> 200" ($r7.ok -and $r7.data.success -eq $true) "status=$($r7.status)"
Assert "7b. role unchanged" ($r7.data.data.role -eq $originalRole) "role=$($r7.data.data.role) (expected $originalRole)"

# 8: PUT /users/profile (email) — silently ignored
$r8 = Put-Json "/users/profile" '{"email":"hacker@test.com"}' $locToken
Assert "8. email silently ignored -> 200" ($r8.ok -and $r8.data.success -eq $true) "status=$($r8.status)"
Assert "8b. email unchanged" ($r8.data.data.email -eq "test@example.com") "email=$($r8.data.data.email)"

# 9: PUT /users/profile (password) — silently ignored
$r9 = Put-Json "/users/profile" '{"password":"newpassword123"}' $locToken
Assert "9. password silently ignored -> 200" ($r9.ok -and $r9.data.success -eq $true) "status=$($r9.status)"
# Verify old password still works
try {
    $loginAfterPw = Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"test@example.com","password":"password"}' -ContentType "application/json"
    Assert "9b. old password still works" ($null -ne $loginAfterPw.data.token) "null"
} catch {
    Assert "9b. old password still works" $false "status=$([int]$_.Exception.Response.StatusCode)"
}

# 10: PUT /users/profile (profession, locataire) — 200
$r10 = Put-Json "/users/profile" '{"profession":"Dev"}' $locToken
Assert "10. Update profession (locataire) -> 200" ($r10.ok -and $r10.data.success -eq $true) "status=$($r10.status)"
Assert "10b. profession updated" ($r10.data.data.profession -eq "Dev") "profession=$($r10.data.data.profession)"

# 11: PUT /users/profile (profession, propriétaire) — silently ignored
$r11 = Put-Json "/users/profile" '{"profession":"Hacker"}' $propToken
Assert "11. profession silently ignored (proprietaire) -> 200" ($r11.ok -and $r11.data.success -eq $true) "status=$($r11.status)"
Assert "11b. profession not set" ($null -eq $r11.data.data.profession) "profession=$($r11.data.data.profession)"

# 12: PUT /users/profile (adresse, propriétaire) — 200
$r12 = Put-Json "/users/profile" '{"adresse":"123 Rue Test"}' $propToken
Assert "12. Update adresse (proprietaire) -> 200" ($r12.ok -and $r12.data.success -eq $true) "status=$($r12.status)"
Assert "12b. adresse updated" ($r12.data.data.adresse -eq "123 Rue Test") "adresse=$($r12.data.data.adresse)"

# 13: PUT /users/profile (cin duplicate) — 422
$existingCin = (Invoke-RestMethod -Uri "$base/users/" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json").data | Where-Object { $null -ne $_.cin } | Select-Object -First 1
if ($existingCin) {
    $r13 = Put-Json "/users/profile" "{`"cin`":`"$($existingCin.cin)`"}" $locToken
    Assert "13. Duplicate cin -> 422" ((-not $r13.ok) -and $r13.status -eq 422) "status=$($r13.status)"
} else {
    Write-Host "  SKIP: 13. (no existing cin to test duplicate)" -ForegroundColor Yellow
}

# Cleanup: restore original name
Put-Json "/users/profile" '{"name":"Test User","telephone":""}' $locToken > $null

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  RESULTS: $global:passed passed, $global:failed failed" -ForegroundColor $(if ($global:failed -eq 0) { "Green" } else { "Red" })
Write-Host "========================================`n" -ForegroundColor Cyan

Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
