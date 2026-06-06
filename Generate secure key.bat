# Generate secure key
[System.Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))