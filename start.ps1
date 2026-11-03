# Menjalankan seluruh layanan Loker Paket Pintar
# Pemakaian: .\start.ps1

$ErrorActionPreference = 'Stop'

$root       = Split-Path -Parent $MyInvocation.MyCommand.Path
$php        = 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'
$mysqld     = 'D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
$mysqlIni   = 'D:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini'
$laravelDir = Join-Path $root 'Website-implemntasi'

function Test-Port {
    param([int]$Port)
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $client.Connect('127.0.0.1', $Port)
        $client.Close()
        return $true
    } catch {
        return $false
    }
}

Write-Host '=== Loker Paket Pintar ===' -ForegroundColor Cyan

if (-not (Test-Port 3306)) {
    Write-Host '[1/3] Menyalakan MySQL Laragon...' -ForegroundColor Yellow
    Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=$mysqlIni" `
        -WorkingDirectory (Split-Path $mysqld) -WindowStyle Hidden

    $siap = $false
    for ($i = 0; $i -lt 20; $i++) {
        Start-Sleep -Seconds 1
        if (Test-Port 3306) { $siap = $true; break }
    }
    if (-not $siap) {
        Write-Host 'MySQL gagal dinyalakan. Buka Laragon lalu klik Start All.' -ForegroundColor Red
    }
} else {
    Write-Host '[1/3] MySQL sudah berjalan.' -ForegroundColor Green
}

if (-not (Test-Port 5000)) {
    Write-Host '[2/3] Menyalakan service model Python...' -ForegroundColor Yellow
    Start-Process powershell.exe -ArgumentList @(
        '-NoExit', '-Command', "cd '$root'; python ml_service/app.py"
    )
    Start-Sleep -Seconds 3
} else {
    Write-Host '[2/3] Service model sudah berjalan di port 5000.' -ForegroundColor Green
}

if (-not (Test-Port 8000)) {
    Write-Host '[3/3] Menyalakan Laravel di http://localhost:8000 ...' -ForegroundColor Yellow
    Start-Process powershell.exe -ArgumentList @(
        '-NoExit', '-Command', "cd '$laravelDir'; & '$php' artisan serve --host=127.0.0.1 --port=8000"
    )
    Start-Sleep -Seconds 3
} else {
    Write-Host '[3/3] Laravel sudah berjalan di port 8000.' -ForegroundColor Green
}

Write-Host ''
Write-Host 'Membuka http://localhost:8000 ...' -ForegroundColor Cyan
Start-Process 'http://localhost:8000'
Write-Host 'Selesai. Tutup jendela PowerShell tambahan untuk menghentikan service.' -ForegroundColor Green
