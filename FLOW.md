# SmartTask - Huong dan setup cho thanh vien

Tai lieu nay dung cho thanh vien moi clone project va ket noi database tren may
rieng. Moi nguoi dung database va file `.env` rieng, khong gui `.env` len GitHub.

Repository: `https://github.com/DHung154/SmartTask`

## 1. Yeu cau

- Git
- Laragon: Apache, MySQL va PHP 8.2 tro len
- Composer
- Hoac Docker Desktop neu chay bang Docker

## 2. Clone project

Mo PowerShell tai thu muc Laragon `www` hoac thu muc lam viec cua ban:

```powershell
git clone https://github.com/DHung154/SmartTask.git
cd SmartTask
```

Neu repository da co tren may, cap nhat truoc khi lam viec:

```powershell
git pull origin main
```

## 3. Cai PHP packages

Neu `php` va `composer` da co trong PATH:

```powershell
composer install
```

Neu dung Laragon va Composer chua co trong PATH:

```powershell
D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -d extension=zip D:\laragon\bin\composer\composer.phar install
```

## 4. Tao file .env

Windows:

```powershell
Copy-Item .env.example .env
```

Mo `.env` va sua phan database theo MySQL tren may cua ban:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=todo_schema
DB_USER=root
DB_PASS=
APP_DEBUG=true
APP_URL=http://smarttask.test
```

`DB_PASS` de trong neu MySQL Laragon cua ban dung user `root` khong co mat khau.
Khong commit file `.env` va khong chia se `MAIL_PASS` hoac API token.

## 5. Tao database

Chi can import mot file duy nhat: `database_setup.sql`.

### Cach 1: Laragon terminal

```powershell
D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -u root < database_setup.sql
```

### Cach 2: phpMyAdmin

1. Mo phpMyAdmin tu Laragon.
2. Chon tab `Import`.
3. Chon file `database_setup.sql`.
4. Bam `Go`.

Canh bao: file nay xoa va tao lai database `todo_schema`. Khong import no vao
database dang chua du lieu can giu.

## 6. Chay bang Laragon

1. Mo Laragon va bam `Start All`.
2. Dat project trong `C:\laragon\www\SmartTask` hoac tao virtual host tro den
   thu muc `public`.
3. Neu dung Auto Virtual Hosts cua Laragon, mo URL `http://smarttask.test`.
4. Kiem tra ket noi database tai `http://smarttask.test/health`.

Ket qua dung:

```json
{"status":"ok","database":"connected"}
```

Neu URL `.test` chua hoat dong, restart Laragon hoac dung URL ma Laragon hien thi
trong Menu > www.

## 7. Chay bang Docker (tuy chon)

Docker tu tao MySQL rieng, khong dung database Laragon:

```powershell
docker-compose up --build -d
```

Mo `http://localhost:8080/health`.

Kiem tra container:

```powershell
docker-compose ps
```

Dung Docker sau khi xong:

```powershell
docker-compose down
```

## 8. Kiem tra truoc khi push

Neu chay Docker, dung cac lenh sau:

```powershell
docker-compose exec -T app vendor/bin/phpunit
docker-compose exec -T app vendor/bin/phpcs
```

Hoac dung Composer tren may neu dependency da cai day du:

```powershell
composer test
composer lint
```

## 9. Quy tac lam viec team

1. Tao branch tu `main`: `git switch -c feature/ten-chuc-nang`.
2. Moi chuc nang/bug phai co GitHub Issue.
3. Khong commit `.env`, `storage/logs`, `storage/cache`, uploads hoac backup SQL.
4. Pull `main` truoc khi code va truoc khi tao Pull Request.
5. Test lai trang `/health`, login va chuc nang da sua truoc khi push.

## Xu ly loi thuong gap

| Loi | Cach xu ly |
| --- | --- |
| `Could not connect to database` | Kiem tra MySQL Laragon dang chay va `DB_*` trong `.env`. |
| `Class not found` | Chay `composer install`, sau do `composer dump-autoload`. |
| `Access denied for root` | Dien dung `DB_PASS` theo MySQL cua may ban. |
| `smarttask.test` khong mo duoc | Restart Laragon, kiem tra virtual host va thu muc `public`. |
| Docker khong ket noi duoc | Mo Docker Desktop, chay `docker-compose up --build -d` lai. |
