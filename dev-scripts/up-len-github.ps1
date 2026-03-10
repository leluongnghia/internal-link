<#
.SYNOPSIS
Auto Release Script for Windows
.DESCRIPTION
Script for pushing updates to GitHub and creating releases for AI Internal Linker.
Usage: .\dev-scripts\up-len-github.ps1 "Description of changes"
#>
[CmdletBinding()]
param (
    [Parameter(Position = 0)]
    [string]$Message = "Update plugin"
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# ─── 1. Config ────────────────────────────────────────────────────────────────
$SCRIPT_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path
$PLUGIN_DIR = Split-Path -Parent $SCRIPT_DIR
$MAIN_FILE = Join-Path $PLUGIN_DIR "internal-links.php"
$OWNER = "leluongnghia"
$REPO = "internal-link"
$PLUGIN_SLUG = "ai-internal-linker"

Write-Host ""
Write-Host "============================================" -ForegroundColor Blue
Write-Host "  AI Internal Linker - Auto Release Tool  " -ForegroundColor Blue
Write-Host "============================================" -ForegroundColor Blue
Write-Host "Plugin Root: $PLUGIN_DIR" -ForegroundColor Cyan

# ─── 2. Check main file ───────────────────────────────────────────────────────
if (-Not (Test-Path $MAIN_FILE)) {
    Write-Host "ERROR: Main file not found: $MAIN_FILE" -ForegroundColor Red
    exit 1
}

# ─── 3. Read & Bump Version ───────────────────────────────────────────────────
$Utf8NoBom = New-Object System.Text.UTF8Encoding $False

$MainContent = [System.IO.File]::ReadAllText($MAIN_FILE, $Utf8NoBom)
if ($MainContent -match "(?mi)^.*Version:\s*([0-9.]+).*$") {
    $CURRENT_VERSION = $Matches[1]
}
else {
    Write-Host "ERROR: Cannot read version from main file!" -ForegroundColor Red
    exit 1
}

$V_PARTS = $CURRENT_VERSION.Split('.')
if ($V_PARTS.Length -lt 3) {
    Write-Host "ERROR: Invalid version format. Need 3 parts (e.g. 1.0.0)" -ForegroundColor Red
    exit 1
}
$V_PARTS[2] = [int]$V_PARTS[2] + 1
$NEW_VERSION = "$($V_PARTS[0]).$($V_PARTS[1]).$($V_PARTS[2])"

Write-Host "Bump version: $CURRENT_VERSION -> $NEW_VERSION" -ForegroundColor Yellow

# Update version in main file
$MainContent = $MainContent -replace "(?mi)(^ \* Version:\s*)$([regex]::Escape($CURRENT_VERSION))(.*$)", "`${1}$NEW_VERSION`${2}"
$MainContent = $MainContent -replace "define\('AIL_VERSION', '$CURRENT_VERSION'\);", "define('AIL_VERSION', '$NEW_VERSION');"
[System.IO.File]::WriteAllText($MAIN_FILE, $MainContent, $Utf8NoBom)

# Update README if exists
$README_FILE = Join-Path $PLUGIN_DIR "README.md"
if (Test-Path $README_FILE) {
    $ReadmeContent = [System.IO.File]::ReadAllText($README_FILE, $Utf8NoBom)
    $ReadmeContent = $ReadmeContent -replace "version-$CURRENT_VERSION-blue", "version-$NEW_VERSION-blue"
    [System.IO.File]::WriteAllText($README_FILE, $ReadmeContent, $Utf8NoBom)
}

Write-Host "Version updated to $NEW_VERSION" -ForegroundColor Green

# ─── 4. Create clean ZIP ──────────────────────────────────────────────────────
Write-Host ""
Write-Host "Creating ZIP..." -ForegroundColor Cyan
Set-Location $PLUGIN_DIR

$TempDir = Join-Path ([System.IO.Path]::GetTempPath()) ([guid]::NewGuid().ToString())
$TempPluginDir = Join-Path $TempDir $PLUGIN_SLUG
New-Item -ItemType Directory -Path $TempPluginDir -Force | Out-Null

$ExcludePatterns = @(
    'node_modules', '.git', '.agent', 'dev-scripts',
    "$PLUGIN_SLUG-v*", '.DS_Store', '*.code-workspace',
    '.gitignore', 'test*', '*.log', '*.bak', '*.zip', '*.ps1'
)

Write-Host "  Copying plugin files..." -ForegroundColor DarkGray
Copy-Item -Path "$PLUGIN_DIR\*" -Destination $TempPluginDir -Recurse -Force -ErrorAction SilentlyContinue

foreach ($Pattern in $ExcludePatterns) {
    Get-ChildItem -Path $TempPluginDir -Filter $Pattern -Recurse -Directory -Force -ErrorAction SilentlyContinue |
    Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
    Get-ChildItem -Path $TempPluginDir -Filter $Pattern -Recurse -File -Force -ErrorAction SilentlyContinue |
    Remove-Item -Force -ErrorAction SilentlyContinue
}

# Remove versioned sub-folders that may have slipped through
Get-ChildItem -Path $TempPluginDir -Directory -ErrorAction SilentlyContinue |
Where-Object { $_.Name -match "^$PLUGIN_SLUG-v\d" -or $_.Name -match '^1\.' } |
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$ZipFile = Join-Path $PLUGIN_DIR "$PLUGIN_SLUG-v$NEW_VERSION.zip"
if (Test-Path $ZipFile) { Remove-Item $ZipFile -Force }

Write-Host "  Compressing..." -ForegroundColor DarkGray
try {
    $zipArchive = [System.IO.Compression.ZipFile]::Open($ZipFile, [System.IO.Compression.ZipArchiveMode]::Create)
    $files = Get-ChildItem -Path $TempDir -Recurse | Where-Object { -not $_.PSIsContainer }
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
    if ($null -ne $zipArchive) { $zipArchive.Dispose() }
    Set-Location $PLUGIN_DIR
    Remove-Item -Recurse -Force $TempDir -ErrorAction SilentlyContinue
}

if (-Not (Test-Path $ZipFile)) {
    Write-Host "ERROR: Failed to create ZIP!" -ForegroundColor Red
    exit 1
}

$ZipSize = "{0:N2} MB" -f ((Get-Item $ZipFile).Length / 1MB)
Write-Host "ZIP created: $(Split-Path $ZipFile -Leaf) ($ZipSize)" -ForegroundColor Green

# ─── 5. Git: commit -> tag -> push ────────────────────────────────────────────
Write-Host ""
Write-Host "Pushing to Git..." -ForegroundColor Cyan

git add .
git commit -m "v$NEW_VERSION - $Message"
git push

git tag "v$NEW_VERSION"
git push origin "v$NEW_VERSION"

Write-Host "Git push + tag done" -ForegroundColor Green

# ─── 6. Create GitHub Release ─────────────────────────────────────────────────
Write-Host ""
Write-Host "Creating GitHub Release v$NEW_VERSION..." -ForegroundColor Cyan

if (-Not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: GitHub CLI (gh) not installed. Get it from: https://cli.github.com/" -ForegroundColor Red
    Write-Host "ZIP is available at: $ZipFile" -ForegroundColor Yellow
    exit 1
}

& gh auth status --hostname github.com 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Not logged in to GitHub CLI. Run: gh auth login" -ForegroundColor Red
    exit 1
}

# Write release notes to a temp file (avoids PowerShell string parsing issues)
$NotesFile = Join-Path ([System.IO.Path]::GetTempPath()) "ail_notes_$NEW_VERSION.md"
$ReleaseNotes = "$Message`n`n## Changes in v$NEW_VERSION`n- $Message"
[System.IO.File]::WriteAllText($NotesFile, $ReleaseNotes, $Utf8NoBom)

& gh release create "v$NEW_VERSION" "$ZipFile" `
    --repo "$OWNER/$REPO" `
    --title "v$NEW_VERSION - $Message" `
    --notes-file "$NotesFile"

Remove-Item $NotesFile -Force -ErrorAction SilentlyContinue

# ─── 7. Done ──────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  SUCCESS! Release v$NEW_VERSION created   " -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host "https://github.com/$OWNER/$REPO/releases/tag/v$NEW_VERSION" -ForegroundColor Cyan

Start-Process "https://github.com/$OWNER/$REPO/releases/tag/v$NEW_VERSION"
