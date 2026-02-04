#!/bin/bash
# Script untuk force pull di hosting (backup dulu)

echo "=== Backup local changes ==="
# Backup file-file yang akan di-overwrite
mkdir -p ~/backup_$(date +%Y%m%d_%H%M%S)
cp -r ~/public_html ~/backup_$(date +%Y%m%d_%H%M%S)/

echo "=== Stash/Reset local changes ==="
cd ~/public_html

# Stash semua local changes
git stash

# Atau kalau mau force reset (hati-hati, ini akan hapus semua local changes):
# git reset --hard HEAD

echo "=== Remove/backup untracked files ==="
# Backup untracked dashboard.html
if [ -f "frontend/pages/dashboard.html" ]; then
    mv frontend/pages/dashboard.html frontend/pages/dashboard.html.backup
fi

echo "=== Pull from GitHub ==="
git pull origin main

echo "=== Done! ==="
git status
