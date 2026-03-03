# Copy FAQ plugin files from "FAQ Management Plugin" folder into Tool-Installer/plugin-assets.
# Run from Tool-Installer: .\build-plugin-assets.ps1

$ErrorActionPreference = "Stop"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$faqFolder = Join-Path (Split-Path -Parent $scriptDir) "FAQ Management Plugin"
$repoRoot = if (Test-Path (Join-Path $faqFolder "admin")) { (Resolve-Path $faqFolder).Path } else { $scriptDir }
$outDir = Join-Path $scriptDir "plugin-assets"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

$sources = @(
    @{ src = "admin\FAQ Management Plugin.php"; dest = "admin.php" },
    @{ src = "admin\FAQ Management Plugin.css"; dest = "admin.css" },
    @{ src = "frontend\FAQ Global Renderer.php"; dest = "global-renderer.php" }
)
foreach ($m in $sources) {
    $srcPath = Join-Path $repoRoot $m.src
    if (Test-Path $srcPath) {
        Copy-Item -Path $srcPath -Destination (Join-Path $outDir $m.dest) -Force
        Write-Host "Copied $($m.src) -> plugin-assets/$($m.dest)"
    } else {
        Write-Warning "Source not found: $srcPath"
    }
}
Write-Host "Done. Plugin assets in $outDir"
