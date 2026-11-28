# Deploying The Cozy Nook on Render

Render runs the app in a **Docker** container (it has no plain PHP option), and the
database lives on **Aiven for MySQL** (Render only offers PostgreSQL). Both have free plans.

```
Browser ──► Render (Docker: PHP 8.3 + Apache, this repo) ──SSL──► Aiven (MySQL 8, lms data)
```

Files in this repo that make it work:

| File | What it does |
|---|---|
| `Dockerfile` | PHP 8.3 + Apache with `intl` and `mysqli`, web root = `public/` |
| `docker/start.sh` | Listens on Render's port, prepares `writable/`, runs new migrations, starts Apache |
| `.dockerignore` | Keeps `.env`, `.git`, logs and database exports out of the image |
| `app/Config/Database.php` | Uses SSL when `DB_SSL_CA` is set |
| `app/Controllers/BaseController.php` | Applies `DB_SQL_MODE` so MySQL behaves like XAMPP's MariaDB |

---

## 1. Create the database on Aiven

1. Sign up at **https://aiven.io** and choose **Create service → MySQL → Free plan**.
   Pick a region close to you (e.g. Singapore), then **Create service** and wait until it says *Running*.
2. On the service's **Overview** page, note the connection details:
   **Host**, **Port**, **User** (`avnadmin`), **Password**, **Database name** (`defaultdb`).
3. Click **CA certificate → Download** (`ca.pem`). Keep it; Render needs its text.

## 2. Copy your data to Aiven

A ready export of your local database is in **`deploy/lms_db.sql`** (not in Git: it
contains password hashes). To make a fresh one later, run this from the project folder:

```powershell
C:\xampp\mysql\bin\mysqldump.exe -uroot --single-transaction --no-tablespaces --skip-comments lms_db --result-file=deploy\lms_db.sql
```

Then delete the first line of the file if it says `enable the sandbox mode` (MySQL can't read it).

Import it with **HeidiSQL** (or SQLyog):

1. New session → **Network type: MySQL (TCP/IP)**, Hostname/Port/User/Password from Aiven.
2. **SSL** tab → tick **Use SSL**, **SSL CA certificate** = the downloaded `ca.pem`.
3. Open → select the **`defaultdb`** database → **File → Run SQL file…** → `deploy\lms_db.sql`.
4. Refresh: you should see 12 tables (`books`, `users`, `borrowings`, …).

## 3. Put the code on GitHub

Render builds from the GitHub repository, so the deploy files above must be pushed
(`git push origin main`).

## 4. Create the web service on Render

1. Sign up at **https://render.com** with GitHub.
2. **New → Web Service** → pick the **flintlock392/LMS** repository.
3. Settings:
   - **Language / Runtime:** Docker (Render detects the `Dockerfile`)
   - **Branch:** `main`
   - **Instance type:** Free
4. **Environment variables** (Advanced → Add environment variable):

   | Key | Value |
   |---|---|
   | `CI_ENVIRONMENT` | `production` |
   | `app_baseURL` | `https://YOUR-SERVICE.onrender.com/` (the URL Render shows; keep the last `/`) |
   | `database_default_hostname` | Aiven **Host** |
   | `database_default_port` | Aiven **Port** |
   | `database_default_database` | `defaultdb` |
   | `database_default_username` | `avnadmin` |
   | `database_default_password` | Aiven **Password** |
   | `DB_SSL_CA` | the whole text of `ca.pem` (from `-----BEGIN CERTIFICATE-----` to `-----END CERTIFICATE-----`) |
   | `DB_SQL_MODE` | `NO_ENGINE_SUBSTITUTION,ERROR_FOR_DIVISION_BY_ZERO` |

5. **Create Web Service.** The first build takes a few minutes; watch the **Logs** tab.
   When it says *Your service is live*, open the URL.
6. If you only learn the URL after creating the service, set `app_baseURL` then and
   click **Manual Deploy → Deploy latest commit**.

Sign in with the usual accounts (e.g. `HL-0001` / `Head@12345`), then **change those
passwords** under My Account: the site is now public.

## 5. Updating the site

Every `git push origin main` redeploys automatically. New migrations run on start-up.

---

## Good to know (free plans)

- **Sleeps when idle.** A free Render service stops after ~15 minutes without visitors;
  the next visit takes about a minute to wake it up.
- **Uploaded files don't last.** Render's disk is reset on every deploy/restart, so book
  covers **uploaded** through the form disappear. Covers picked from Open Library (and the
  bundled Pragmatic Programmer cover) are fine. For permanent uploads you'd need a paid
  Render disk or external storage.
- **Sign-ins reset on restart.** Sessions are stored on that same disk, so users may need
  to sign in again after a deploy.
- **Aiven free MySQL** has limited storage, which is plenty for this app.

## If something goes wrong

| Symptom | Check |
|---|---|
| Build fails | Render **Logs**: usually a typo in the Dockerfile or a network hiccup; redeploy. |
| "Whoops! We seem to have hit a snag." | Database settings. Temporarily set `CI_ENVIRONMENT` = `development` to see the real error, then set it back. |
| `Access denied` / `SSL` errors | `database_default_*` values and that `DB_SSL_CA` holds the full certificate text. |
| Pages load but links/styles point to localhost | `app_baseURL` must be the Render URL with `https://` and the trailing `/`. |
| `Expression #1 of SELECT list is not in GROUP BY` | `DB_SQL_MODE` is missing. |
