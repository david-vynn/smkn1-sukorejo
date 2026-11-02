
# ==========================================
# AUTO PUSH GITHUB
# Interval dijadwalkan melalui Task Scheduler
# ==========================================

$ErrorActionPreference = "Stop"

# Lokasi project
$ProjectPath = "C:\xampp\htdocs\smkn1-sukorejo"

# Lokasi log
$LogPath = Join-Path $ProjectPath "auto-push.log"

function Write-Log($Message) {
    $Time = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogPath -Value "[$Time] $Message"
}

try {
    Set-Location $ProjectPath

    # Pastikan folder adalah repository Git
    git rev-parse --is-inside-work-tree | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Folder bukan repository Git."
    }

    # Ambil informasi terbaru dari GitHub
    git fetch
    if ($LASTEXITCODE -ne 0) {
        throw "Git fetch gagal."
    }

    # Periksa apakah branch lokal tertinggal
    $Behind = git rev-list --count 'HEAD..@{u}' 2>$null

    if ($LASTEXITCODE -ne 0) {
        throw "Branch belum memiliki upstream."
    }

    if ([int]$Behind -gt 0) {
        throw "Branch lokal tertinggal dari remote. Sinkronisasi manual diperlukan."
    }

    # Masukkan perubahan ke staging
    git add -A
    if ($LASTEXITCODE -ne 0) {
        throw "Git add gagal."
    }

    # Periksa apakah ada perubahan
    git diff --cached --quiet

    if ($LASTEXITCODE -eq 0) {
        Write-Log "Tidak ada perubahan."
        exit 0
    }

    if ($LASTEXITCODE -ne 1) {
        throw "Gagal memeriksa perubahan."
    }

    # Buat commit otomatis
    $Message = "Auto-save: $(Get-Date -Format 'yyyy-MM-dd HH:mm')"

    git commit -m $Message
    if ($LASTEXITCODE -ne 0) {
        throw "Git commit gagal."
    }

    # Push ke GitHub
    git push
    if ($LASTEXITCODE -ne 0) {
        throw "Git push gagal."
    }

    Write-Log "Berhasil commit dan push."
}
catch {
    Write-Log "ERROR: $($_.Exception.Message)"
    exit 1
}
