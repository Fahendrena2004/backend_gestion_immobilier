$ErrorActionPreference = "Continue"
$base = "http://127.0.0.1:8907/api"
$passed = 0
$failed = 0
Add-Type -AssemblyName System.Net.Http

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
        $r = Invoke-RestMethod -Uri "$base$url" -Method POST -Body $body -Headers @{Authorization="Bearer $token"; "Content-Type"="application/json"} -TimeoutSec 8
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

function Multipart-Post($url, $fields, $files, $token) {
    $boundary = [System.Guid]::NewGuid().ToString()
    $form = New-Object System.Net.Http.MultipartFormDataContent($boundary)
    foreach ($key in $fields.Keys) {
        $form.Add((New-Object System.Net.Http.StringContent($fields[$key])), $key)
    }
    foreach ($key in $files.Keys) {
        $fs = [System.IO.File]::OpenRead($files[$key])
        $fc = New-Object System.Net.Http.StreamContent($fs)
        $ext = [System.IO.Path]::GetExtension($files[$key])
        if ($ext -eq ".png") { $fc.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse("image/png") }
        elseif ($ext -eq ".jpg" -or $ext -eq ".jpeg") { $fc.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse("image/jpeg") }
        elseif ($ext -eq ".pdf") { $fc.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse("application/pdf") }
        $form.Add($fc, $key, [System.IO.Path]::GetFileName($files[$key]))
    }
    $uri = [System.Uri]::new("$base$url")
    $request = New-Object System.Net.Http.HttpRequestMessage([System.Net.Http.HttpMethod]::Post, $uri)
    $request.Content = $form
    $request.Headers.Authorization = [System.Net.Http.Headers.AuthenticationHeaderValue]::Parse("Bearer $token")
    try {
        $client = [System.Net.Http.HttpClient]::new()
        $client.Timeout = [TimeSpan]::FromSeconds(10)
        $response = $client.SendAsync($request).Result
        $body = $response.Content.ReadAsStringAsync().Result
        $fs.Close()
        $code = [int]$response.StatusCode
        $json = $body | ConvertFrom-Json -ErrorAction SilentlyContinue
        return @{ ok = ($code -ge 200 -and $code -lt 300); data = $json; status = $code }
    } catch {
        $fs.Close()
        return @{ ok = $false; data = $null; status = 0; error = $_.Exception.Message }
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  FINANCES MODULE E2E TEST" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Start server once
Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
Start-Process -FilePath "C:\php84\php.exe" -ArgumentList '-c C:\php84\php.ini artisan serve --host=127.0.0.1 --port=8907' -WorkingDirectory "E:\STAGE B-TECH\backend_gestion_immobilier" -WindowStyle Hidden
Start-Sleep -Seconds 5
Write-Host "Server started on :8907`n" -ForegroundColor Yellow

# Create temp preuve file
$preuveFile = "$env:TEMP\preuve_test.png"
$pngBytes = [byte[]]@(0x89,0x50,0x4E,0x47,0x0D,0x0A,0x1A,0x0A,0x00,0x00,0x00,0x0D,0x49,0x48,0x44,0x52,0x00,0x00,0x00,0x01,0x00,0x00,0x00,0x01,0x08,0x02,0x00,0x00,0x00,0x90,0x77,0x53,0xDE,0x00,0x00,0x00,0x0C,0x49,0x44,0x41,0x54,0x08,0xD7,0x63,0xF8,0xCF,0xC0,0x00,0x00,0x01,0x01,0x00,0x01,0x00,0x18,0xDD,0x8D,0xB4,0x00,0x00,0x00,0x00,0x49,0x45,0x4E,0x44,0xAE,0x42,0x60,0x82)
[System.IO.File]::WriteAllBytes($preuveFile, $pngBytes)

# --- Logins ---
Write-Host "[Logins]" -ForegroundColor Yellow
$adminToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"admin@btech.test","password":"password"}' -ContentType "application/json").data.token
Assert "Admin login" ($null -ne $adminToken) "null"
$propToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"prop@test.com","password":"password"}' -ContentType "application/json").data.token
Assert "Prop login" ($null -ne $propToken) "null"
$locToken = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"test@example.com","password":"password"}' -ContentType "application/json").data.token
Assert "Loc1 login" ($null -ne $locToken) "null"
$loc2Token = (Invoke-RestMethod -Uri "$base/auth/login" -Method POST -Body '{"email":"loc2@test.com","password":"password"}' -ContentType "application/json").data.token
Assert "Loc2 login" ($null -ne $loc2Token) "null"

# --- Setup ---
Write-Host "`n[Setup]" -ForegroundColor Yellow
$fres = Invoke-RestMethod -Uri "$base/finances/factures" -Method GET -Headers @{Authorization="Bearer $propToken"} -ContentType "application/json"
Assert "Get factures" ($fres.data.Count -ge 1) "count=$($fres.data.Count)"
$allFactures = $fres.data
$facture = $allFactures | Where-Object { $_.statut -eq 'impayee' -or $_.statut -eq 'en_retard' } | Select-Object -First 1
if (-not $facture) { $facture = $allFactures[0] }
$factureId = $facture.id
Write-Host "  facture_id=$factureId montant=$($facture.montant) statut=$($facture.statut)" -ForegroundColor Gray

$mres = Invoke-RestMethod -Uri "$base/finances/modes-paiement" -Method GET -Headers @{Authorization="Bearer $adminToken"} -ContentType "application/json"
$modeId = $mres.data[0].id
Write-Host "  mode_id=$modeId" -ForegroundColor Gray

# --- Tests ---
Write-Host "`n[Tests]" -ForegroundColor Yellow

# 1: Declare paiement with preuve -> 201
$r = Multipart-Post "/finances/paiements" @{facture_id="$factureId"; mode_paiement_id="$modeId"; montant="50000"; reference="REF-001"} @{preuve=$preuveFile} $locToken
$paiementId = $r.data.data.id
Assert "1. Declare paiement with preuve -> 201" ($r.ok -and $r.data.success -eq $true) "status=$($r.status)"

# 2: Admin valider -> 200
$r = Patch-Json "/finances/paiements/$paiementId/valider" '{"decision":"valider"}' $adminToken
Assert "2. Admin valider -> 200" ($r.ok -and $r.data.success -eq $true) "status=$($r.status)"
$quittanceId = $r.data.data.quittance.id

# 3: Double valider -> 422
$r = Patch-Json "/finances/paiements/$paiementId/valider" '{"decision":"valider"}' $adminToken
Assert "3. Double valider -> 422" ((-not $r.ok) -and $r.status -eq 422) "status=$($r.status)"

# 4: Locataire tries valider -> 403
$r = Patch-Json "/finances/paiements/$paiementId/valider" '{"decision":"valider"}' $locToken
Assert "4. Locataire valider -> 403" ((-not $r.ok) -and $r.status -eq 403) "status=$($r.status)"

# 5: Proprietaire tries valider -> 403
$r = Patch-Json "/finances/paiements/$paiementId/valider" '{"decision":"rejeter"}' $propToken
Assert "5. Proprietaire valider -> 403" ((-not $r.ok) -and $r.status -eq 403) "status=$($r.status)"

# 6: Download quittance PDF -> 200
try {
    $dr = Invoke-WebRequest -Uri "$base/finances/quittances/$quittanceId/pdf" -Method GET -Headers @{Authorization="Bearer $adminToken"} -UseBasicParsing -TimeoutSec 10
    Assert "6. Download quittance -> 200" ($dr.StatusCode -eq 200) "status=$($dr.StatusCode)"
} catch {
    Assert "6. Download quittance -> 200" $false "status=$([int]$_.Exception.Response.StatusCode)"
}

# 7: Declare 2nd paiement on different facture -> 201
$f2 = $allFactures | Where-Object { $_.id -ne $factureId -and ($_.statut -eq 'impayee' -or $_.statut -eq 'en_retard') } | Select-Object -First 1
$res2 = $null
if ($f2) {
    $r2 = Multipart-Post "/finances/paiements" @{facture_id="$($f2.id)"; mode_paiement_id="$modeId"; montant="75000"; reference="REF-002"} @{preuve=$preuveFile} $locToken
    $res2 = $r2
    Assert "7. Declare 2nd paiement -> 201" ($r2.ok -and $r2.data.success -eq $true) "status=$($r2.status)"
} else {
    Write-Host "  SKIP: 7. (no other unpaid factures)" -ForegroundColor Yellow
}

# 8: Invalid decision -> 422
if ($res2 -and $res2.ok) {
    $r = Patch-Json "/finances/paiements/$($res2.data.data.id)/valider" '{"decision":"invalid"}' $adminToken
    Assert "8. Invalid decision -> 422" ((-not $r.ok) -and $r.status -eq 422) "status=$($r.status)"
} else { Write-Host "  SKIP: 8." -ForegroundColor Yellow }

# 9: Admin rejeter -> 200
if ($res2 -and $res2.ok) {
    $r = Patch-Json "/finances/paiements/$($res2.data.data.id)/valider" '{"decision":"rejeter"}' $adminToken
    Assert "9. Admin rejeter -> 200" ($r.ok -and $r.data.success -eq $true) "status=$($r.status)"
} else { Write-Host "  SKIP: 9." -ForegroundColor Yellow }

# 10: Double declare same facture -> 422
$r3 = Multipart-Post "/finances/paiements" @{facture_id="$factureId"; mode_paiement_id="$modeId"; montant="50000"; reference="REF-003"} @{preuve=$preuveFile} $locToken
Assert "10. Double declare same facture -> 422" ((-not $r3.ok) -and $r3.status -eq 422) "status=$($r3.status)"

# 11: CRUD modes-paiement
$uniqueName = "ModeE2E-$(Get-Date -Format 'yyyyMMddHHmmss')"
$r = Post-Json "/finances/modes-paiement" "{`"libelle`":`"$uniqueName`",`"actif`":true}" $adminToken
$testModeId = $r.data.data.id
Assert "11a. Create mode -> 201" ($r.ok -and $r.data.success -eq $true) "status=$($r.status)"

if ($testModeId) {
    $r = Patch-Json "/finances/modes-paiement/$testModeId" '{"libelle":"ModeTestUpdated"}' $adminToken
    Assert "11b. Update mode -> 200" ($r.ok -and $r.data.success -eq $true) "status=$($r.status)"

    try {
        Invoke-WebRequest -Uri "$base/finances/modes-paiement/$testModeId" -Method DELETE -Headers @{Authorization="Bearer $adminToken"} -UseBasicParsing -TimeoutSec 8 > $null
        Assert "11c. Delete mode -> 200" $true "ok"
    } catch {
        Assert "11c. Delete mode -> 200" $false "status=$([int]$_.Exception.Response.StatusCode)"
    }
}

# 12: Locataire factures access
$r = Post-Json "/finances/factures" "" $loc2Token
# Actually GET, not POST
try {
    $lr = Invoke-RestMethod -Uri "$base/finances/factures" -Method GET -Headers @{Authorization="Bearer $loc2Token"} -ContentType "application/json"
    Assert "12. Locataire factures access" $true "ok"
} catch {
    Assert "12. Locataire factures access" $false "status=$([int]$_.Exception.Response.StatusCode)"
}

# Cleanup
Remove-Item $preuveFile -Force -ErrorAction SilentlyContinue

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  RESULTS: $global:passed passed, $global:failed failed" -ForegroundColor $(if ($global:failed -eq 0) { "Green" } else { "Red" })
Write-Host "========================================`n" -ForegroundColor Cyan

Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
