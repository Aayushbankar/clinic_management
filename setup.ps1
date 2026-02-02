<#
.SYNOPSIS
    Clinic Management System - Windows Setup Script
.DESCRIPTION
    Automated setup for local development on Windows.
    - Checks/installs PHP 8.3 and MySQL 8.4 via winget
    - Creates database and dedicated user
    - Imports schema and seed data
    - Starts the development server
.NOTES
    Run as Administrator for installation
    Usage: .\setup.ps1 [-Install] [-Start] [-Reset]
#>

param(
    [switch]$Install,   # Install PHP and MySQL
    [switch]$Start,     # Start the dev server only
    [switch]$Reset      # Reset database
)

$ErrorActionPreference = "Stop"

# ============================================
# Configuration
# ============================================
$Config = @{
    DbHost     = "127.0.0.1"
    DbPort     = "3306"
    DbName     = "clinic_management"
    DbUser     = "cms_user"
    DbPass     = "CmsPass@2024"
    DbRootPass = ""  # Will prompt if needed
    ServerPort = "8000"
}

# ============================================
# Helper Functions
# ============================================
function Write-Header {
    param([string]$Text)
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host " $Text" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
}

function Write-Step {
    param([string]$Text)
    Write-Host "[*] $Text" -ForegroundColor Yellow
}

function Write-Success {
    param([string]$Text)
    Write-Host "[+] $Text" -ForegroundColor Green
}

function Write-Error {
    param([string]$Text)
    Write-Host "[!] $Text" -ForegroundColor Red
}

function Test-Admin {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $currentPrincipal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Test-Command {
    param([string]$Command)
    return [bool](Get-Command $Command -ErrorAction SilentlyContinue)
}

# ============================================
# Installation Functions
# ============================================
function Install-Dependencies {
    Write-Header "Installing Dependencies"
    
    if (-not (Test-Admin)) {
        Write-Error "Please run PowerShell as Administrator for installation!"
        Write-Host "Right-click PowerShell -> Run as Administrator" -ForegroundColor Gray
        exit 1
    }

    # Check winget
    if (-not (Test-Command "winget")) {
        Write-Error "winget not found. Please install App Installer from Microsoft Store."
        exit 1
    }

    # Install PHP
    Write-Step "Checking PHP..."
    if (Test-Command "php") {
        $phpVersion = (php -v | Select-Object -First 1)
        Write-Success "PHP already installed: $phpVersion"
    } else {
        Write-Step "Installing PHP 8.3..."
        winget install --id PHP.PHP.8.3 --silent --accept-package-agreements --accept-source-agreements
        
        # Refresh PATH
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
        
        if (Test-Command "php") {
            Write-Success "PHP installed successfully!"
            
            # Configure php.ini
            Write-Step "Configuring PHP extensions..."
            $phpPath = (Get-Command php).Source | Split-Path -Parent
            $phpIniDev = Join-Path $phpPath "php.ini-development"
            $phpIni = Join-Path $phpPath "php.ini"
            
            if ((Test-Path $phpIniDev) -and -not (Test-Path $phpIni)) {
                Copy-Item $phpIniDev $phpIni
                $content = Get-Content $phpIni
                $content = $content -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
                $content = $content -replace ';extension=mysqli', 'extension=mysqli'
                $content = $content -replace ';extension=openssl', 'extension=openssl'
                $content = $content -replace ';extension=mbstring', 'extension=mbstring'
                Set-Content $phpIni $content
                Write-Success "PHP extensions enabled!"
            }
        } else {
            Write-Error "PHP installation failed. Please install manually."
        }
    }

    # Install MySQL
    Write-Step "Checking MySQL..."
    if (Test-Command "mysql") {
        $mysqlVersion = (mysql --version)
        Write-Success "MySQL already installed: $mysqlVersion"
    } else {
        Write-Step "Installing MySQL 8.4..."
        winget install --id Oracle.MySQL --silent --accept-package-agreements --accept-source-agreements
        
        # Refresh PATH
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
        
        # Add MySQL to PATH if not found
        $mysqlPaths = @(
            "C:\Program Files\MySQL\MySQL Server 8.4\bin",
            "C:\Program Files\MySQL\MySQL Server 8.0\bin"
        )
        foreach ($path in $mysqlPaths) {
            if (Test-Path $path) {
                $env:Path += ";$path"
                [Environment]::SetEnvironmentVariable("Path", $env:Path, "User")
                break
            }
        }
        
        if (Test-Command "mysql") {
            Write-Success "MySQL installed successfully!"
        } else {
            Write-Error "MySQL installation failed. Please install manually and add to PATH."
        }
    }
}

# ============================================
# Database Setup Functions
# ============================================
function Get-MySqlRootPassword {
    if ([string]::IsNullOrEmpty($Config.DbRootPass)) {
        $securePass = Read-Host "Enter MySQL root password" -AsSecureString
        $Config.DbRootPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePass)
        )
    }
    return $Config.DbRootPass
}

function Setup-Database {
    Write-Header "Setting Up Database"
    
    $rootPass = Get-MySqlRootPassword
    $projectRoot = $PSScriptRoot
    
    # Test MySQL connection
    Write-Step "Testing MySQL connection..."
    try {
        $testResult = mysql -u root -p"$rootPass" -e "SELECT 1" 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Error "Cannot connect to MySQL. Check root password and ensure MySQL is running."
            Write-Host "Start MySQL: net start MySQL84" -ForegroundColor Gray
            exit 1
        }
        Write-Success "MySQL connection successful!"
    } catch {
        Write-Error "MySQL connection failed: $_"
        exit 1
    }

    # Create database
    Write-Step "Creating database '$($Config.DbName)'..."
    mysql -u root -p"$rootPass" -e "CREATE DATABASE IF NOT EXISTS $($Config.DbName) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
    Write-Success "Database created!"

    # Create dedicated user
    Write-Step "Creating database user '$($Config.DbUser)'..."
    $userSql = @"
CREATE USER IF NOT EXISTS '$($Config.DbUser)'@'localhost' IDENTIFIED BY '$($Config.DbPass)';
GRANT ALL PRIVILEGES ON $($Config.DbName).* TO '$($Config.DbUser)'@'localhost';
FLUSH PRIVILEGES;
"@
    mysql -u root -p"$rootPass" -e $userSql
    Write-Success "Database user created!"

    # Import schema
    Write-Step "Importing database schema..."
    $schemaPath = Join-Path $projectRoot "database\schema.sql"
    if (Test-Path $schemaPath) {
        Get-Content $schemaPath | mysql -u root -p"$rootPass" $($Config.DbName)
        Write-Success "Schema imported!"
    } else {
        Write-Error "schema.sql not found at: $schemaPath"
        exit 1
    }

    # Import seed data
    Write-Step "Importing seed data..."
    $seedPath = Join-Path $projectRoot "database\seed.sql"
    if (Test-Path $seedPath) {
        Get-Content $seedPath | mysql -u root -p"$rootPass" $($Config.DbName)
        Write-Success "Seed data imported!"
    } else {
        Write-Error "seed.sql not found at: $seedPath"
    }
}

function Reset-Database {
    Write-Header "Resetting Database"
    
    $rootPass = Get-MySqlRootPassword
    
    Write-Step "Dropping and recreating database..."
    mysql -u root -p"$rootPass" -e "DROP DATABASE IF EXISTS $($Config.DbName);"
    
    Setup-Database
    Write-Success "Database reset complete!"
}

# ============================================
# Environment Setup
# ============================================
function Set-Environment {
    Write-Header "Setting Environment Variables"
    
    $envVars = @{
        "CMS_DB_HOST" = $Config.DbHost
        "CMS_DB_PORT" = $Config.DbPort
        "CMS_DB_NAME" = $Config.DbName
        "CMS_DB_USER" = $Config.DbUser
        "CMS_DB_PASS" = $Config.DbPass
        "CMS_DEBUG"   = "1"
    }
    
    foreach ($key in $envVars.Keys) {
        $value = $envVars[$key]
        [Environment]::SetEnvironmentVariable($key, $value, "Process")
        Write-Step "$key = $value"
    }
    
    Write-Success "Environment variables set for this session!"
    Write-Host ""
    Write-Host "To set permanently, run:" -ForegroundColor Gray
    Write-Host '  [Environment]::SetEnvironmentVariable("CMS_DB_PASS", "CmsPass@2024", "User")' -ForegroundColor Gray
}

# ============================================
# Server Functions
# ============================================
function Start-DevServer {
    Write-Header "Starting Development Server"
    
    Set-Environment
    
    $projectRoot = $PSScriptRoot
    $publicPath = Join-Path $projectRoot "backend\public"
    
    if (-not (Test-Path $publicPath)) {
        Write-Error "backend\public not found. Are you in the project root?"
        exit 1
    }
    
    Write-Success "Server starting at http://127.0.0.1:$($Config.ServerPort)"
    Write-Host ""
    Write-Host "Demo Credentials:" -ForegroundColor Cyan
    Write-Host "  Admin   : admin@clinic.test   / Admin@123" -ForegroundColor White
    Write-Host "  Doctor  : doctor@clinic.test  / Doctor@123" -ForegroundColor White
    Write-Host "  Staff   : staff@clinic.test   / Staff@123" -ForegroundColor White
    Write-Host "  Patient : patient@clinic.test / Patient@123" -ForegroundColor White
    Write-Host ""
    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Gray
    Write-Host ""
    
    php -S "127.0.0.1:$($Config.ServerPort)" -t $publicPath
}

# ============================================
# Main Entry Point
# ============================================
function Show-Usage {
    Write-Host ""
    Write-Host "Clinic Management System - Setup Script" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Usage:" -ForegroundColor Yellow
    Write-Host "  .\setup.ps1              # Full setup (install + database + start)"
    Write-Host "  .\setup.ps1 -Install     # Install PHP and MySQL only"
    Write-Host "  .\setup.ps1 -Start       # Start server only (skip setup)"
    Write-Host "  .\setup.ps1 -Reset       # Reset database and start"
    Write-Host ""
}

# Main logic
if ($Start) {
    Start-DevServer
} elseif ($Reset) {
    Reset-Database
    Start-DevServer
} elseif ($Install) {
    Install-Dependencies
    Write-Host ""
    Write-Success "Installation complete! Run '.\setup.ps1' to set up database and start."
} else {
    # Full setup
    Write-Header "Clinic Management System Setup"
    
    # Check prerequisites
    $phpInstalled = Test-Command "php"
    $mysqlInstalled = Test-Command "mysql"
    
    if (-not $phpInstalled -or -not $mysqlInstalled) {
        Write-Host ""
        Write-Host "Missing dependencies detected:" -ForegroundColor Yellow
        if (-not $phpInstalled) { Write-Host "  - PHP not found" -ForegroundColor Red }
        if (-not $mysqlInstalled) { Write-Host "  - MySQL not found" -ForegroundColor Red }
        Write-Host ""
        
        $response = Read-Host "Install missing dependencies? (y/n)"
        if ($response -eq "y" -or $response -eq "Y") {
            Install-Dependencies
        } else {
            Write-Host "Please install dependencies manually and run again."
            Show-Usage
            exit 1
        }
    }
    
    # Setup database
    Setup-Database
    
    # Start server
    Start-DevServer
}
