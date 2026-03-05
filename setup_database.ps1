# =============================================================
# SCRIPT TỰ ĐỘNG TẠO DATABASE WEBSITE BÁN ĐIỆN THOẠI
# =============================================================
# Cách dùng:
#   .\setup_database.ps1
#   hoặc: .\setup_database.ps1 -MySQLUser root -MySQLPass yourpassword
# =============================================================

param(
    [string]$MySQLUser = "root",
    [string]$MySQLPass = "",
    [string]$MySQLHost = "localhost",
    [string]$MySQLPort = "3306"
)

$ScriptDir  = Split-Path -Parent $MyInvocation.MyCommand.Definition
$SqlFile    = Join-Path $ScriptDir "database_dienthoai.sql"
$DBName     = "bandienthoai"

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "   SETUP DATABASE: WEBSITE BAN DIEN THOAI                  " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# ── Kiểm tra file SQL tồn tại ──────────────────────────────
if (-not (Test-Path $SqlFile)) {
    Write-Host "[LOI] Khong tim thay file: $SqlFile" -ForegroundColor Red
    exit 1
}

# ── Kiểm tra mysql.exe có trong PATH ───────────────────────
$mysqlExe = Get-Command mysql -ErrorAction SilentlyContinue
if (-not $mysqlExe) {
    # Thử tìm theo đường dẫn phổ biến
    $commonPaths = @(
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 5.7\bin\mysql.exe",
        "C:\xampp\mysql\bin\mysql.exe",
        "C:\wamp64\bin\mysql\mysql8.0\bin\mysql.exe",
        "C:\laragon\bin\mysql\mysql-8.0\bin\mysql.exe"
    )
    foreach ($p in $commonPaths) {
        if (Test-Path $p) { $mysqlExe = $p; break }
    }
    if (-not $mysqlExe) {
        Write-Host "[LOI] Khong tim thay mysql.exe." -ForegroundColor Red
        Write-Host "      Hay them thu muc bin cua MySQL vao bien moi truong PATH," -ForegroundColor Yellow
        Write-Host "      hoac chinh sua bien commonPaths trong script nay." -ForegroundColor Yellow
        exit 1
    }
} else {
    $mysqlExe = $mysqlExe.Source
}

Write-Host "[OK] MySQL tim thay tai: $mysqlExe" -ForegroundColor Green

# ── Xây dựng tham số kết nối ───────────────────────────────
$connArgs = @("-h", $MySQLHost, "-P", $MySQLPort, "-u", $MySQLUser)
if ($MySQLPass -ne "") {
    $connArgs += "-p$MySQLPass"
}

# ── Kiểm tra kết nối ───────────────────────────────────────
Write-Host "[...] Dang ket noi MySQL ($MySQLUser@$MySQLHost`:$MySQLPort)..." -ForegroundColor Yellow
$testResult = & $mysqlExe @connArgs -e "SELECT 1" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[LOI] Khong the ket noi MySQL:" -ForegroundColor Red
    Write-Host "      $testResult" -ForegroundColor Red
    Write-Host ""
    Write-Host "Goi y: Chay lai script voi mat khau:" -ForegroundColor Yellow
    Write-Host "  .\setup_database.ps1 -MySQLPass your_password" -ForegroundColor White
    exit 1
}
Write-Host "[OK] Ket noi MySQL thanh cong!" -ForegroundColor Green

# ── Kiểm tra database đã tồn tại chưa ─────────────────────
Write-Host "[...] Kiem tra database '$DBName'..." -ForegroundColor Yellow
$dbExists = & $mysqlExe @connArgs -e "SHOW DATABASES LIKE '$DBName';" 2>&1
if ($dbExists -match $DBName) {
    Write-Host "[!]  Database '$DBName' DA TON TAI." -ForegroundColor Yellow
    $choice = Read-Host "     Ban co muon XOA va tao lai khong? (y/N)"
    if ($choice -match "^[yY]$") {
        Write-Host "[...] Dang xoa database cu '$DBName'..." -ForegroundColor Yellow
        & $mysqlExe @connArgs -e "DROP DATABASE IF EXISTS ``$DBName``;" 2>&1 | Out-Null
        Write-Host "[OK] Da xoa database cu." -ForegroundColor Green
    } else {
        Write-Host "[!]  Bo qua. Database giu nguyen." -ForegroundColor Cyan
        exit 0
    }
}

# ── Thực thi file SQL ──────────────────────────────────────
Write-Host ""
Write-Host "[...] Dang import '$SqlFile'..." -ForegroundColor Yellow
Write-Host "      (Co the mat vai giay...)" -ForegroundColor Gray

$importResult = & $mysqlExe @connArgs 2>&1 `
    --default-character-set=utf8mb4 `
    -e "SOURCE $($SqlFile -replace '\\','/');"

if ($LASTEXITCODE -ne 0) {
    Write-Host "[LOI] Import that bai:" -ForegroundColor Red
    Write-Host "      $importResult" -ForegroundColor Red
    exit 1
}

# ── Xác nhận các bảng đã tạo ──────────────────────────────
Write-Host ""
Write-Host "[OK] Import hoan thanh!" -ForegroundColor Green
Write-Host ""
Write-Host "Bang ke cac bang trong database '$DBName':" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor Cyan

$tables = & $mysqlExe @connArgs $DBName -e "SHOW TABLES;" 2>&1
$tables | ForEach-Object { Write-Host "  - $_" -ForegroundColor White }

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  HOAN THANH! Database '$DBName' da duoc tao thanh cong.   " -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Thong tin ket noi:" -ForegroundColor Cyan
Write-Host "  Host : $MySQLHost`:$MySQLPort" -ForegroundColor White
Write-Host "  User : $MySQLUser" -ForegroundColor White
Write-Host "  DB   : $DBName" -ForegroundColor White
Write-Host ""
