@echo off
cd /d "%~dp0.."
"D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "scripts\send_deadline_reminders.php"
"D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" "scripts\work_queue.php"
