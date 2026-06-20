# ABNet WP-PostViews Addons - WordPress Plugin Packaging Script
# Creates a ready-to-install WordPress plugin archive

param(
    [string]$OutputPath = "./dist",
    [string]$PluginName = "abnet-wp-post-views-addons",
    [string]$VersionConstant = "ABNET_WP_POST_VIEWS_VERSION",
    [string]$PluginHeaderFile = "constants.php",
    [string]$Version = "1.0.1",
    [switch]$IncludeDevFiles = $false,
    [switch]$ExportUnarchived = $false,
    [switch]$Verbose = $false
)

# Set strict mode for better error handling
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Configuration
$ScriptPath = if ($PSScriptRoot) {
    $PSScriptRoot
} else {
    Split-Path -Parent $MyInvocation.MyCommand.Path
}

$PluginPath = Split-Path $ScriptPath -Parent

if (-not [System.IO.Path]::IsPathRooted($OutputPath)) {
    $OutputPath = Join-Path $PluginPath $OutputPath
}

$TempPath = Join-Path $env:TEMP "abnet-plugin-build"
$ArchiveName = "$PluginName.zip"
$ArchiveDirectoryName = [System.IO.Path]::GetFileNameWithoutExtension($ArchiveName)

# Files and directories to exclude from the package
$ExcludePatterns = @(
    "*.log",
    "*.tmp",
    ".git*",
	"*build*",
	"*dist*",
    "*screenshots*",
    "*bin*",
    "*.code-workspace",
    ".vscode",
	".gitignore",
    "*.bak",
    "*~"
)

# Development files to exclude unless explicitly included
$DevFiles = @(
    "bin\*",
    "bin/*",
    "*examples*",
    "support\*",
    "support/*",
    "*tests*",
    "*docs*",
    "*.md",
    "webpack.config.js",
    "package.json",
    "composer.json",
    ".editorconfig",
    ".phpcs.xml",
    "phpunit.xml"
)

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    
    if ($Verbose -or $Level -eq "ERROR") {
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $color = switch ($Level) {
            "ERROR" { "Red" }
            "WARNING" { "Yellow" }
            "SUCCESS" { "Green" }
            default { "White" }
        }
        Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
    }
}

function Test-Prerequisites {
    Write-Log "Checking prerequisites..."
    
    # Check if plugin directory exists
    if (-not (Test-Path $PluginPath)) {
        Write-Log "Plugin directory not found: $PluginPath" "ERROR"
        throw "Plugin directory not found"
    }
    
    # Check if main plugin file exists
    $mainFile = Join-Path $PluginPath "$PluginName.php"
    if (-not (Test-Path $mainFile)) {
        Write-Log "Main plugin file not found: $mainFile" "ERROR"
        throw "Main plugin file not found"
    }
    
    # Check for 7-Zip or PowerShell 5.0+ for compression
    $canCompress = $false
    
    if (Get-Command "7z" -ErrorAction SilentlyContinue) {
        $canCompress = $true
        Write-Log "Found 7-Zip for compression"
    } elseif ($PSVersionTable.PSVersion.Major -ge 5) {
        $canCompress = $true
        Write-Log "Using PowerShell built-in compression"
    }
    
    if (-not $canCompress) {
        Write-Log "No compression method available. Install 7-Zip or upgrade to PowerShell 5.0+" "ERROR"
        throw "No compression method available"
    }
    
    Write-Log "Prerequisites check passed" "SUCCESS"
}

function New-CleanDirectory {
    param([string]$Path)
    
    if (Test-Path $Path) {
        Write-Log "Cleaning existing directory: $Path"
        Remove-Item $Path -Recurse -Force
    }
    
    New-Item -ItemType Directory -Path $Path -Force | Out-Null
    Write-Log "Created directory: $Path"
}

function Copy-PluginFiles {
    Write-Log "Copying plugin files..."
    
    $destinationPath = Join-Path $TempPath $PluginName
    New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
    
    # Build exclude patterns
    $allExcludePatterns = $ExcludePatterns
    if (-not $IncludeDevFiles) {
        $allExcludePatterns += $DevFiles
    }
    
    # Get all files and filter out excluded ones
    $sourceFiles = Get-ChildItem -Path $PluginPath -Recurse -File
    $filesToCopy = @()
    
    foreach ($file in $sourceFiles) {
        $relativePath = $file.FullName.Substring($PluginPath.Length + 1)
        $shouldExclude = $false
        
        foreach ($pattern in $allExcludePatterns) {
            if ($relativePath -like $pattern -or $file.Name -like $pattern) {
                $shouldExclude = $true
                break
            }
        }
        
        if (-not $shouldExclude) {
            $filesToCopy += $file
        } else {
            Write-Log "Excluding: $relativePath" "WARNING"
        }
    }
    
    # Copy filtered files
    foreach ($file in $filesToCopy) {
        $relativePath = $file.FullName.Substring($PluginPath.Length + 1)
        $destFile = Join-Path $destinationPath $relativePath
        $destDir = Split-Path $destFile -Parent
        
        if (-not (Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        
        Copy-Item $file.FullName $destFile -Force
    }
    
    Write-Log "Copied $($filesToCopy.Count) files" "SUCCESS"
    return $destinationPath
}

function Update-VersionInfo {
    param([string]$PluginDir)
    
    Write-Log "Updating version information..."
    
    $mainFile = Join-Path $PluginDir "$PluginName.php"
    if (Test-Path $mainFile) {
        $content = Get-Content $mainFile -Raw
        
        # Update version in plugin header
        $content = $content -replace '(\* Version:\s+)[\d\.]+', "`${1}$Version"

        Set-Content $mainFile $content -Encoding UTF8
        Write-Log "Updated version to $Version in main plugin file" "SUCCESS"
    }

    # Update version constant
    if ($PluginHeaderFile -ne $null -and $PluginHeaderFile -ne "" -and $VersionConstant -ne $null -and $VersionConstant -ne "") {
        $constantsFile = Join-Path $PluginDir $PluginHeaderFile
        if (Test-Path $constantsFile) {
            $content = Get-Content $constantsFile -Raw
            $content = $content -replace "(define\('$VersionConstant',\s+')[^']*(')", "`${1}$Version`${2}"
            Set-Content $constantsFile $content -Encoding UTF8
            Write-Log "Updated version in constants.php" "SUCCESS"
        }
    }
    
    # Update readme.txt if it exists
    $readmeFile = Join-Path $PluginDir "readme.txt"
    if (Test-Path $readmeFile) {
        $content = Get-Content $readmeFile -Raw
        $content = $content -replace '(Stable tag:\s+)[\d\.]+', "`${1}$Version"
        Set-Content $readmeFile $content -Encoding UTF8
        Write-Log "Updated version in readme.txt" "SUCCESS"
    }
}

function New-PluginArchive {
    param([string]$SourcePath, [string]$OutputFile)
    
    Write-Log "Creating plugin archive: $OutputFile"
    
    if (Get-Command "7z" -ErrorAction SilentlyContinue) {
        # Use 7-Zip for better compression
        $7zPath = (Get-Command "7z").Source
        & $7zPath a -tzip -mx9 "$OutputFile" "$SourcePath\*" | Out-Null
        
        if ($LASTEXITCODE -ne 0) {
            throw "7-Zip compression failed"
        }
    } else {
        # Use PowerShell built-in compression
        Compress-Archive -Path "$SourcePath\*" -DestinationPath $OutputFile -CompressionLevel Optimal -Force
    }
    
    $archiveSize = (Get-Item $OutputFile).Length
    $sizeKB = [math]::Round($archiveSize / 1KB, 2)
    Write-Log "Archive created successfully ($sizeKB KB)" "SUCCESS"
}

function Export-UnarchivedPlugin {
    param(
        [string]$SourcePath,
        [string]$OutputDirectory
    )

    Write-Log "Exporting unarchived plugin structure to: $OutputDirectory"
    New-CleanDirectory $OutputDirectory

    $destinationPath = Join-Path $OutputDirectory $PluginName
    Copy-Item -Path $SourcePath -Destination $destinationPath -Recurse -Force

    Write-Log "Unarchived plugin export completed" "SUCCESS"
}

function Test-PluginArchive {
    param([string]$ArchivePath)
    
    Write-Log "Validating plugin archive..."
    
    try {
        # Test if archive can be opened
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        $archive = [System.IO.Compression.ZipFile]::OpenRead($ArchivePath)
        
        # Check for required files
        $requiredFiles = @(
            "$PluginName/$PluginName.php",
            "$PluginName/readme.txt"
        )
        
        $archiveEntries = $archive.Entries | ForEach-Object { $_.FullName }
        
        foreach ($required in $requiredFiles) {
            if ($required -notin $archiveEntries) {
                throw "Required file missing from archive: $required"
            }
        }
        
        $archive.Dispose()
        Write-Log "Archive validation passed" "SUCCESS"
        return $true
    }
    catch {
        Write-Log "Archive validation failed: $($_.Exception.Message)" "ERROR"
        return $false
    }
}

function Show-Summary {
    param([string]$ArchivePath, [string]$BuildTime)
    
    $archiveInfo = Get-Item $ArchivePath
    $sizeKB = [math]::Round($archiveInfo.Length / 1KB, 2)
    
    Write-Host "`n" -NoNewline
    Write-Host "================================================" -ForegroundColor Green
    Write-Host "           PLUGIN PACKAGING COMPLETE" -ForegroundColor Green
    Write-Host "================================================" -ForegroundColor Green
    Write-Host "Plugin Name : $PluginName" -ForegroundColor White
    Write-Host "Version : $Version" -ForegroundColor White
    Write-Host "Archive : $($archiveInfo.Name)" -ForegroundColor White
    Write-Host "Size : $sizeKB KB" -ForegroundColor White
    Write-Host "Location : $($archiveInfo.FullName)" -ForegroundColor White
    Write-Host "Build Time : $BuildTime" -ForegroundColor White
    Write-Host "================================================" -ForegroundColor Green
    Write-Host "`nArchive is ready for WordPress installation!" -ForegroundColor Yellow
}

# Main execution
try {
    $startTime = Get-Date
	$OutputPath = [System.IO.Path]::GetFullPath($OutputPath)

    # Check prerequisites
    Test-Prerequisites
    
    # Create output directory
    New-CleanDirectory $OutputPath
    New-CleanDirectory $TempPath
    
    # Copy and filter plugin files
    $pluginDir = Copy-PluginFiles
    
    # Update version information
    Update-VersionInfo $pluginDir
    
    # Create the archive
    $outputFile = Join-Path $OutputPath $ArchiveName
    New-PluginArchive $TempPath $outputFile

    # Optionally export an unarchived copy for direct FTP upload
    if ($ExportUnarchived) {
        $unarchivedOutputPath = Join-Path $OutputPath $ArchiveDirectoryName
        Export-UnarchivedPlugin -SourcePath $pluginDir -OutputDirectory $unarchivedOutputPath
    }
    
    # Validate the archive
    if (-not (Test-PluginArchive $outputFile)) {
        throw "Archive validation failed"
    }
    
    # Clean up temporary files
    Remove-Item $TempPath -Recurse -Force
    
    # Show summary
    $endTime = Get-Date
    $buildTime = "{0:mm\:ss}" -f ($endTime - $startTime)
    Show-Summary $outputFile $buildTime
    
} catch {
    Write-Log "Packaging failed: $($_.Exception.Message)" "ERROR"
    
    # Clean up on error
    if (Test-Path $TempPath) {
        Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue
    }
    
    exit 1
}
