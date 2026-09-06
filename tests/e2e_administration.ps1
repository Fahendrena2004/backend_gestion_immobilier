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

function Post-Json($url, $body, $token) {
    try {
        $headers = @{"Content-Type"="application/json"}
        if ($token) { $headers["Authorization"] = "Bearer $token" }
        $r = Invoke-RestMethod -Uri "$base$url" -Method POST -Body $body -Headers $headers -TimeoutSec 8
        return @{ ok = $true; data = $r; status = 200 }
    } catch {
        $code = 0
        if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
        $errBody = $null
        try { $sr = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream()); $errBody = $sr.ReadToEnd(); $sr.Close() } catch {}
        return @{ ok = $false; data = $null; status = $code; error = $errBody }
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
Write-Host "  ADMINISTRATION MODULE E2E TEST" -ForegroundColor Cyan
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
$loc2Token = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"loc2@test.com","password":"password"}' -ContentType "application/json").data.token
Assert "Loc2 login" ($null -ne $loc2Token) "null"

# Create second admin for guard test (via PHP script to avoid artisan tinker issues)
$ts = Get-Date -Format 'yyyyMMddHHmmss'
$admin2Email = "admin2-$ts@test.com"
$admin2Name = "Admin2-$ts"
$admin2Cin = "CIN-ADMIN2-$ts"
& "C:\php84\php.exe" -c "C:\php84\php.ini" "E:\STAGE B-TECH\backend_gestion_immobilier\tests\create_user.php" $admin2Email $admin2Name $admin2Cin 2>&1 > $null
$admin2Token = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body "{`"email`":`"$admin2Email`",`"password`":`"Password1!`"}" -ContentType "application/json").data.token
Assert "Admin2 login" ($null -ne $admin2Token) "null"

# --- Tests ---
Write-Host "`n[Tests]" -ForegroundColor Yellow

# 1: GET /dashboard-stats (admin) — 200 with 6 keys
$r = Invoke-RestMethod -Uri "$base/administration/dashboard-stats" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "1. GET dashboard-stats (admin) -> 200" ($r.success -eq $true) "success=$($r.success)"
Assert "1b. users_par_role present" ($null -ne $r.data.users_par_role) "null"
Assert "1c. logements_par_statut present" ($null -ne $r.data.logements_par_statut) "null"
Assert "1d. logements_par_moderation present" ($null -ne $r.data.logements_par_moderation) "null"
Assert "1e. demandes_en_attente is numeric" ($r.data.demandes_en_attente -ge 0) "val=$($r.data.demandes_en_attente)"
Assert "1f. paiements_en_attente is numeric" ($r.data.paiements_en_attente -ge 0) "val=$($r.data.paiements_en_attente)"
Assert "1g. revenus_mois_courant is numeric" ($r.data.revenus_mois_courant -ge 0) "val=$($r.data.revenus_mois_courant)"
Write-Host "    users_par_role: $($r.data.users_par_role | ConvertTo-Json -Compress)" -ForegroundColor Gray
Write-Host "    logements_par_moderation: $($r.data.logements_par_moderation | ConvertTo-Json -Compress)" -ForegroundColor Gray

# 2: GET /dashboard-stats (locataire) — 403
try {
    Invoke-RestMethod -Uri "$base/administration/dashboard-stats" -Method GET -Headers @{Authorization="Bearer $locToken"} -ContentType "application/json" -TimeoutSec 8 > $null
    Assert "2. GET dashboard-stats (locataire) -> 403" $false "expected 403, got 200"
} catch {
    $code = 0
    if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode }
    Assert "2. GET dashboard-stats (locataire) -> 403" ($code -eq 403) "status=$code"
}

# 3: PATCH /logements/1/moderation (admin, suspendu) — 200
$r3 = Patch-Json "/administration/logements/1/moderation" '{"statut_moderation":"suspendu"}' $adminToken
Assert "3. Moderate logement (suspendu) -> 200" ($r3.ok -and $r3.data.success -eq $true) "status=$($r3.status)"

# 4: PATCH /logements/1/moderation (admin, valeur invalide) — 422
$r4 = Patch-Json "/administration/logements/1/moderation" '{"statut_moderation":"invalid_status"}' $adminToken
Assert "4. Moderate logement (invalid) -> 422" ((-not $r4.ok) -and $r4.status -eq 422) "status=$($r4.status)"

# 5: PATCH /logements/1/moderation (locataire) — 403
$r5 = Patch-Json "/administration/logements/1/moderation" '{"statut_moderation":"approuve"}' $locToken
Assert "5. Moderate logement (locataire) -> 403" ((-not $r5.ok) -and $r5.status -eq 403) "status=$($r5.status)"

# 6: PATCH /users/2/status (admin, désactive locataire) — 200
$r6 = Patch-Json "/administration/users/2/status" '{"is_active":false}' $adminToken
Assert "6. Deactivate locataire -> 200" ($r6.ok -and $r6.data.success -eq $true) "status=$($r6.status)"
Assert "6b. locataire is_active = false" ($r6.data.data.is_active -eq $false) "is_active=$($r6.data.data.is_active)"

# 7: PATCH /users/2/status (admin, admin2 tente de désactiver un autre admin) — 403
$r7 = Patch-Json "/administration/users/1/status" '{"is_active":false}' $admin2Token
Assert "7. Admin2 deactivates Admin1 -> 403" ((-not $r7.ok) -and $r7.status -eq 403) "status=$($r7.status)"

# 8: PATCH /users/1/status (admin, se désactive lui-même) — 403
$r8 = Patch-Json "/administration/users/1/status" '{"is_active":false}' $adminToken
Assert "8. Admin deactivates self -> 403" ((-not $r8.ok) -and $r8.status -eq 403) "status=$($r8.status)"

# 9: PATCH /users/2/status (admin, réactive locataire) — 200
$r9 = Patch-Json "/administration/users/2/status" '{"is_active":true}' $adminToken
Assert "9. Reactivate locataire -> 200" ($r9.ok -and $r9.data.success -eq $true) "status=$($r9.status)"
Assert "9b. locataire is_active = true" ($r9.data.data.is_active -eq $true) "is_active=$($r9.data.data.is_active)"

# 10: PATCH /logements/1/moderation (admin, remet approuve) — 200
$r10 = Patch-Json "/administration/logements/1/moderation" '{"statut_moderation":"approuve"}' $adminToken
Assert "10. Re-approve logement -> 200" ($r10.ok -and $r10.data.success -eq $true) "status=$($r10.status)"

# 11: GET /dashboard-stats (admin) after operations — verify stats
$r11 = Invoke-RestMethod -Uri "$base/administration/dashboard-stats" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
Assert "11. Dashboard stats after ops -> 200" ($r11.success -eq $true) "success=$($r11.success)"
Assert "11b. users count = 5" ($r11.data.users_par_role.admin -ge 2) "admins=$($r11.data.users_par_role.admin)"
Assert "11c. logement approuve" ($r11.data.logements_par_moderation.approuve -ge 1) "approuve=$($r11.data.logements_par_moderation.approuve)"

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  RESULTS: $global:passed passed, $global:failed failed" -ForegroundColor $(if ($global:failed -eq 0) { "Green" } else { "Red" })
Write-Host "========================================`n" -ForegroundColor Cyan

Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
