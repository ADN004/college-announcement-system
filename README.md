# College Announcement System

A PHP + MySQL web application for colleges to manage and play audio announcements across campus locations.

## Stack
- PHP (procedural, MySQLi)
- MySQL
- Vanilla JS
- Custom dark-theme CSS
- XAMPP (local dev)

## Setup (Fresh Clone)

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/) with Apache + MySQL running
- PHP 8.0+

### 2. Clone & Place Files
```
Place the project folder in: C:\xampp\htdocs\college_announcement_system\
```

### 3. Database Setup
1. Open `http://localhost/phpmyadmin`
2. Click **Import** → select `setup.sql` → click **Go**

This creates the `college_announcement` database with all tables and seeds:
- Default admin account
- 3 default locations (Office, Block A, Library)

### 4. Configure Database
```bash
cp db.example.php db.php
```
Edit `db.php` with your MySQL credentials (default XAMPP: root / no password).

### 5. Run
Open: `http://localhost/college_announcement_system/`

---

## Default Credentials

| Role     | Username | Password   |
|----------|----------|------------|
| Admin    | `admin`  | `admin123` |

**Change the admin password immediately after first login via Admin → Manage Users.**

### Default Location Passwords

| Location | Password    |
|----------|-------------|
| Office   | `office123` |
| Block A  | `blocka123` |
| Library  | `library123` |

**Change location passwords via Admin → Manage Locations.**

---

## Features

- **Staff**: Create audio announcements (record / upload / text-to-speech), view status & notifications
- **Admin**: Approve/reject announcements, manage users, manage locations, view play logs
- **Locations**: Password-protected player that auto-plays approved announcements for their location
- **DB-driven locations**: Add new locations from the admin panel — no code changes needed

## Optional: Text-to-Speech (TTS)
TTS announcements require `espeak` installed on the server:
- **Windows**: Download from [espeak.sourceforge.net](http://espeak.sourceforge.net/download.html) and add to PATH
- **Linux**: `sudo apt install espeak`

---

## Git Notes

- `db.php` is gitignored — each developer keeps their own local copy
- `uploads/` contents are gitignored — only the empty folder structure (`.gitkeep` files) is tracked
- See `.gitignore` for full exclusion list
