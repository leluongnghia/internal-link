<#
.SYNOPSIS
Auto Release Script for Windows
.DESCRIPTION
Script for pushing updates to GitHub and creating releases for AI Internal Linker.
Usage: .\dev-scripts\up-len-github.ps1 "Mô tả thay đổi"
#>
[CmdletBinding()]
param (
    [Parameter(Position = 0)]
    [string]$Message = "Update plugin"
)

$ErrorActionPreference = "Stop"

# Fix Font/Character issues in Console Output
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# ── 1. Config ─────────────────────────────────────────────
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$PLUGIN_DIR = Split-Path -Parent $SCRIPT_DIR
$MAIN_FILE = Join-Path $PLUGIN_DIR "internal-links.php"
$OWNER = "leluongnghia"
$REPO = "internal-link"
$PLUGIN_SLUG = "internal-links"

Write-Host "`n============================================" -ForegroundColor Blue
Write-Host "  AI Internal Linker - Uploader  " -ForegroundColor Blue
Write-Host "============================================`n" -ForegroundColor Blue
Write-Host "Plugin Root: $PLUGIN_DIR" -ForegroundColor Cyan

# ── 2. Kiểm tra file chính ────────────────────────────────
if (-Not (Test-Path $MAIN_FILE)) {
    Write-Host "❌ Không tìm thấy file chính: $MAIN_FILE" -ForegroundColor Red
    exit 1
}

# ── 3. Đọc & Bump Version ─────────────────────────────────
# Sử dụng Text.UTF8Encoding($False) để ghi file ở dạng UTF-8 KHÔNG BOM
$Utf8NoBom = New-Object System.Text.UTF8Encoding $False

$MainContent = [System.IO.File]::ReadAllText($MAIN_FILE, $Utf8NoBom)
if ($MainContent -match "(?mi)^.*Version:\s*([0-9.]+).*$") {
    $CURRENT_VERSION = $Matches[1]
}
else {
    Write-Host "❌ Không đọc được version từ file chính!" -ForegroundColor Red
    exit 1
}

$V_PARTS = $CURRENT_VERSION.Split('.')
if ($V_PARTS.Length -lt 3) {
    Write-Host "❌ Định dạng version không đúng. Cần có 3 phần (vd: 1.0.0)" -ForegroundColor Red
    exit 1
}
$V_PARTS[2] = [int]$V_PARTS[2] + 1
$NEW_VERSION = "$($V_PARTS[0]).$($V_PARTS[1]).$($V_PARTS[2])"

Write-Host "⬆️  Bump version: $CURRENT_VERSION → $NEW_VERSION" -ForegroundColor Yellow

# Cập nhật version trong main file
$MainContent = $MainContent -replace "(?mi)(^ \* Version:\s*)$([regex]::Escape($CURRENT_VERSION))(.*$)", "`${1}$NEW_VERSION`${2}"
$MainContent = $MainContent -replace "define\('AIL_VERSION', '$CURRENT_VERSION'\);", "define('AIL_VERSION', '$NEW_VERSION');"
[System.IO.File]::WriteAllText($MAIN_FILE, $MainContent, $Utf8NoBom)

# Cập nhật README nếu có
$README_FILE = Join-Path $PLUGIN_DIR "README.md"
if (Test-Path $README_FILE) {
    $ReadmeContent = [System.IO.File]::ReadAllText($README_FILE, $Utf8NoBom)
    $ReadmeContent = $ReadmeContent -replace "version-$CURRENT_VERSION-blue", "version-$NEW_VERSION-blue"
    [System.IO.File]::WriteAllText($README_FILE, $ReadmeContent, $Utf8NoBom)
}

Write-Host "✅ Version updated" -ForegroundColor Green

# ── 4. Tạo ZIP sạch ────────────────────────────
Write-Host "`n📦 Tạo ZIP..." -ForegroundColor Cyan
Set-Location $PLUGIN_DIR

$TempDir = Join-Path ([System.IO.Path]::GetTempPath()) ([guid]::NewGuid().ToString())
$TempPluginDir = Join-Path $TempDir $PLUGIN_SLUG
New-Item -ItemType Directory -Path $TempPluginDir -Force | Out-Null

$ExcludePatterns = @(
    # Dev/Build folders
    'node_modules',
    '.git',
    '.agent',
    'dev-scripts',
    # Versioned zip folders
    "$PLUGIN_SLUG-v*",
    '1.*',
    # Files
    '.DS_Store',
    '*.code-workspace',
    '.gitignore',
    # Dev/test scripts
    'test*',
    '*.log',
    '*.bak',
    '*.zip',
    '*.ps1'
)

# Copy toàn bộ plugin dir sang temp
Write-Host "  Copying plugin files..." -ForegroundColor DarkGray
Copy-Item -Path "$PLUGIN_DIR\*" -Destination $TempPluginDir -Recurse -Force -ErrorAction SilentlyContinue

# Xóa các items theo exclude patterns
foreach ($Pattern in $ExcludePatterns) {
    Get-ChildItem -Path $TempPluginDir -Filter $Pattern -Recurse -Directory -Force -ErrorAction SilentlyContinue |
    Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

    Get-ChildItem -Path $TempPluginDir -Filter $Pattern -Recurse -File -Force -ErrorAction SilentlyContinue |
    Remove-Item -Force -ErrorAction SilentlyContinue
}

# Xóa các thư mục versioned (nếu lọt)
Get-ChildItem -Path $TempPluginDir -Directory -ErrorAction SilentlyContinue |
Where-Object { $_.Name -match "^$PLUGIN_SLUG-v\d" -or $_.Name -match '^1\.' } |
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

# Load .NET Compression Assembly
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$ZipFile = Join-Path $PLUGIN_DIR "$PLUGIN_SLUG-v$NEW_VERSION.zip"
if (Test-Path $ZipFile) { Remove-Item $ZipFile -Force }

# Tạo file zip an toàn cho Unix (Force Forward Slashes)
Write-Host "  Compressing..." -ForegroundColor DarkGray

try {
    $zipArchive = [System.IO.Compression.ZipFile]::Open($ZipFile, [System.IO.Compression.ZipArchiveMode]::Create)
    
    $files = Get-ChildItem -Path $TempDir -Recurse | Where-Object { ! $_.PSIsContainer }
    
    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($TempDir.Length + 1)
        
        $entryName = $relativePath.Replace('\', '/')
        $entry = $zipArchive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        
        $streamRead = [System.IO.File]::OpenRead($file.FullName)
        $streamWrite = $entry.Open()
        $streamRead.CopyTo($streamWrite)
        
        $streamWrite.Dispose()
        $streamRead.Dispose()
    }
}
finally {
    if ($zipArchive -ne $null) {
        $zipArchive.Dispose()
    }
    Set-Location $PLUGIN_DIR
    Remove-Item -Recurse -Force $TempDir -ErrorAction SilentlyContinue
}

if (-Not (Test-Path $ZipFile)) {
    Write-Host "❌ Không tạo được file ZIP!" -ForegroundColor Red
    exit 1
}

$ZipSize = "{0:N2} MB" -f ((Get-Item $ZipFile).Length / 1MB)
Write-Host "✅ ZIP tạo xong: $(Split-Path $ZipFile -Leaf) ($ZipSize)" -ForegroundColor Green

# ── 5. Git: commit → tag → push ───────────────────────────
Write-Host "`n📤 Đẩy lên Git..." -ForegroundColor Cyan

git add .
git commit -m "v$NEW_VERSION - $Message"
git push

git tag "v$NEW_VERSION"
git push origin "v$NEW_VERSION"

Write-Host "✅ Git push + tag xong" -ForegroundColor Green

# ── 6. Tạo GitHub Release ─────────────────────────────────
Write-Host "`n🚀 Tạo GitHub Release v$NEW_VERSION..." -ForegroundColor Cyan

if (-Not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Chưa cài GitHub CLI (gh). Cài từ: https://cli.github.com/" -ForegroundColor Red
    Write-Host "⚠️  ZIP đã có tại: $ZipFile" -ForegroundColor Yellow
    exit 1
}

& gh auth status --hostname github.com 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Chưa đăng nhập GitHub CLI. Chạy: gh auth login" -ForegroundColor Red
    exit 1
}

$ReleaseNotes = @"
$Message

## Thay đổi trong v$NEW_VERSION
- $Message
"@

& gh release create "v$NEW_VERSION" "$ZipFile" --repo "$OWNER/$REPO" --title "v$NEW_VERSION - $Message" --notes "$ReleaseNotes"

# ── 7. Kết quả ────────────────────────────────────────────
Write-Host "`n============================================" -ForegroundColor Green
Write-Host "  ✅ THÀNH CÔNG! Release v$NEW_VERSION đã tạo" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host "🔗 https://github.com/$OWNER/$REPO/releases/tag/v$NEW_VERSION" -ForegroundColor Cyan

# Mở browser trên Windows
Start-Process "https://github.com/$OWNER/$REPO/releases/tag/v$NEW_VERSION"
