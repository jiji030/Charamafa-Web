# Reset meters script
$date = Get-Date
if ($date.Day -eq 10) {
    $url = "http://localhost:8000/api/reset-meters"
    Invoke-RestMethod -Uri $url
    Write-Host "Meter reset check completed for $date"
}
