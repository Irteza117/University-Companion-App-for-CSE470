# University Companion WebApp - Automated Setup Script
# This script will automatically set up your entire project!

Write-Host "🚀 University Companion WebApp - Automated Setup" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan

# 1. Check if XAMPP is installed
Write-Host "`n1. Checking XAMPP installation..." -ForegroundColor Yellow
$xamppPaths = @("C:\xampp", "C:\Program Files\xampp", "C:\Program Files (x86)\xampp")
$xamppFound = $false
$xamppPath = ""

foreach($path in $xamppPaths) {
    if(Test-Path "$path\xampp-control.exe") {
        Write-Host "   ✓ XAMPP found at: $path" -ForegroundColor Green
        $xamppPath = $path
        $xamppFound = $true
        break
    }
}

if(-not $xamppFound) {
    Write-Host "   ✗ XAMPP not found!" -ForegroundColor Red
    Write-Host "   Please ensure XAMPP is installed properly." -ForegroundColor Yellow
    Read-Host "   Press Enter to exit"
    exit
}

# 2. Start XAMPP Services
Write-Host "`n2. Starting XAMPP services..." -ForegroundColor Yellow
try {
    Start-Process "$xamppPath\xampp-control.exe" -WindowStyle Normal
    Write-Host "   ✓ XAMPP Control Panel started" -ForegroundColor Green
    Write-Host "   ⚠ Please manually start Apache and MySQL services in the control panel" -ForegroundColor Yellow
} catch {
    Write-Host "   ✗ Failed to start XAMPP Control Panel" -ForegroundColor Red
}

# 3. Copy project to htdocs
Write-Host "`n3. Setting up project files..." -ForegroundColor Yellow
$source = "C:\Users\irtez\Documents\470 project"
$destination = "$xamppPath\htdocs\university-companion"

if (Test-Path $destination) {
    Remove-Item $destination -Recurse -Force
    Write-Host "   ✓ Removed old project files" -ForegroundColor Green
}

try {
    Copy-Item $source $destination -Recurse -Force
    Write-Host "   ✓ Project copied to: $destination" -ForegroundColor Green
} catch {
    Write-Host "   ✗ Failed to copy project files" -ForegroundColor Red
    Write-Host "   Error: $_" -ForegroundColor Red
    Read-Host "   Press Enter to exit"
    exit
}

# 4. Create necessary directories
Write-Host "`n4. Creating required directories..." -ForegroundColor Yellow
$uploadDirs = @(
    "$destination\uploads\materials",
    "$destination\uploads\assignments",
    "$destination\uploads\profiles"
)

foreach($dir in $uploadDirs) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
    Write-Host "   ✓ Created: $(Split-Path $dir -Leaf)" -ForegroundColor Green
}

# 5. Wait for user to start services
Write-Host "`n5. Waiting for services..." -ForegroundColor Yellow
Write-Host "   Please start Apache and MySQL in XAMPP Control Panel" -ForegroundColor White
Read-Host "   Press Enter after starting both services"

# 6. Test Apache service
Write-Host "`n6. Testing web server..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost" -UseBasicParsing -TimeoutSec 10
    if($response.StatusCode -eq 200) {
        Write-Host "   ✓ Apache is running!" -ForegroundColor Green
    }
} catch {
    Write-Host "   ⚠ Apache may not be running. Please check XAMPP Control Panel." -ForegroundColor Yellow
}

# 7. Setup database
Write-Host "`n7. Setting up database..." -ForegroundColor Yellow

# Create a PHP script to set up database automatically
$dbSetupScript = @'
<?php
$host = "localhost";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS university_companion");
    echo "Database created successfully\n";
    
    // Use the database
    $pdo->exec("USE university_companion");
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . "/database_schema.sql";
    if(file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "Database schema imported successfully\n";
    } else {
        echo "SQL file not found\n";
    }
    
} catch(PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
}
?>
'@

$dbSetupPath = "$destination\setup_database.php"
Set-Content -Path $dbSetupPath -Value $dbSetupScript

# Try to run database setup
try {
    $phpPath = "$xamppPath\php\php.exe"
    if(Test-Path $phpPath) {
        $result = & $phpPath $dbSetupPath 2>&1
        if($result -like "*successfully*") {
            Write-Host "   ✓ Database setup completed automatically!" -ForegroundColor Green
        } else {
            Write-Host "   ⚠ Automatic database setup failed. Opening phpMyAdmin..." -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "   ⚠ Will open phpMyAdmin for manual database setup" -ForegroundColor Yellow
}

# 8. Open applications
Write-Host "`n8. Opening applications..." -ForegroundColor Yellow

# Open phpMyAdmin
Start-Sleep 2
Start-Process "http://localhost/phpmyadmin"
Write-Host "   ✓ phpMyAdmin opened" -ForegroundColor Green

# Open the application
Start-Sleep 2
Start-Process "http://localhost/university-companion"
Write-Host "   ✓ University Companion WebApp opened" -ForegroundColor Green

# Final success message
Write-Host "`n🎉 SETUP COMPLETE!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host "`nYour University Companion WebApp is ready!" -ForegroundColor Cyan
Write-Host "`nAccess URLs:" -ForegroundColor Yellow
Write-Host "• Application: http://localhost/university-companion" -ForegroundColor White
Write-Host "• phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor White
Write-Host "• XAMPP Dashboard: http://localhost" -ForegroundColor White

Write-Host "`nDemo Login Credentials:" -ForegroundColor Yellow
Write-Host "• Admin: admin / admin123" -ForegroundColor White
Write-Host "• Faculty: john.doe / faculty123" -ForegroundColor White
Write-Host "• Student: alice.brown / student123" -ForegroundColor White

Write-Host "`nIf database setup failed:" -ForegroundColor Yellow
Write-Host "1. Go to phpMyAdmin" -ForegroundColor White
Write-Host "2. Create database 'university_companion'" -ForegroundColor White
Write-Host "3. Import database_schema.sql file" -ForegroundColor White

Write-Host "`nPress Enter to exit..." -ForegroundColor Cyan
Read-Host