# Runner dla Claude'a: wykonuje z gory ustalone komendy projektu.
# Claude zapisuje storage/app/runner/request.json, a ten skrypt uruchamia komende
# i zapisuje wynik w storage/app/runner/<id>.txt (ostatnia linia: EXIT <kod>).
# Akcja 'run' wykonuje DOWOLNA komende (tymczasowo, na prosbe uzytkownika). Zamknij okno, zeby to wylaczyc.

$ErrorActionPreference = 'Continue'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$dir = Join-Path $root 'storage\app\runner'
New-Item -ItemType Directory -Force -Path $dir | Out-Null
$request = Join-Path $dir 'request.json'

$sshHost = 'zbigniewkisly@s59.mydevil.net'
$appPath = 'domains/fistfulofideas.zbigniewkisly.usermd.net/app'

function Invoke-Action($req) {
    $a = @($req.args)

    switch ($req.action) {
        'test' { composer test }
        'types' { composer types:check }
        'lint' { composer lint }
        'migrate' { php artisan migrate --force }
        'ship' {
            if ($a.Count -lt 1 -or $a[0] -notmatch '^[\p{L}\p{N} .:;()/+\-]{1,120}$') { throw 'Zly opis zmiany' }
            & (Join-Path $root 'ship.bat') $a[0]
        }
        'deploy' { & (Join-Path $root 'deploy.bat') }
        'set-env' {
            if ($a.Count -lt 2 -or $a[0] -notmatch '^[A-Z][A-Z0-9_]*$' -or $a[1] -notmatch '^[\w.,:/@\-]{0,200}$') { throw 'Zla zmienna' }
            ssh $sshHost "bash $appPath/deploy/set-env.sh $($a[0]) '$($a[1])'"
        }
        'server-log' { ssh $sshHost "tail -n 80 $appPath/storage/logs/laravel.log" }
        'run' {
            # Dowolna komenda w katalogu projektu (wlaczone na prosbe uzytkownika, tymczasowo).
            if ($a.Count -lt 1 -or "$($a[0])".Trim() -eq '') { throw 'Pusta komenda' }
            $script = Join-Path $dir "run-$($req.id).cmd"
            Set-Content -Path $script -Encoding Default -Value "@echo off`r`ncd /d `"$root`"`r`n$($a[0])"
            try { & cmd /c $script } finally { Remove-Item $script -Force -ErrorAction SilentlyContinue }
        }
        default { throw "Nieznana akcja: $($req.action)" }
    }
}

Write-Host "Runner Claude'a dziala w $root"
Write-Host 'Dozwolone: test, types, lint, migrate, ship, deploy, set-env, server-log oraz run (dowolna komenda). Zamknij okno, zeby wylaczyc.'

while ($true) {
    if (Test-Path $request) {
        $raw = Get-Content $request -Raw -Encoding UTF8
        Remove-Item $request -Force

        try {
            $req = $raw | ConvertFrom-Json
        } catch {
            Write-Host 'Pominieto nieczytelna prosbe.'
            continue
        }

        $id = "$($req.id)"

        if ($id -notmatch '^[A-Za-z0-9_-]{1,40}$') {
            Write-Host 'Pominieto prosbe bez poprawnego id.'
            continue
        }

        $out = Join-Path $dir "$id.txt"
        $tmp = "$out.part"
        Write-Host "[$(Get-Date -Format HH:mm:ss)] $($req.action) $(@($req.args) -join ' ')"

        $global:LASTEXITCODE = 0
        $code = 0

        try {
            Invoke-Action $req *>&1 | ForEach-Object { "$_" } | Out-File -FilePath $tmp -Encoding utf8
            $code = $LASTEXITCODE
        } catch {
            "BLAD: $($_.Exception.Message)" | Out-File -FilePath $tmp -Encoding utf8 -Append
            $code = 1
        }

        "EXIT $code" | Out-File -FilePath $tmp -Encoding utf8 -Append
        Move-Item -Force $tmp $out
        Write-Host "  -> EXIT $code"
    }

    Start-Sleep -Milliseconds 700
}
