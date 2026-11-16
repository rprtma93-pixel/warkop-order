# Warkop Order — Self-Hosted PaaS untuk Sistem Online Ordering UMKM Kuliner (Coolify)

Aplikasi pemesanan online untuk warung kopi / UMKM kuliner. Menu digital selalu *up-to-date*
(dikelola admin), pelanggan memesan dari HP, dan seluruhnya berjalan di server sendiri
(VPS murah) menggunakan **Coolify** — tanpa biaya hosting cloud komersial.

**Tech stack:** Coolify · Node.js (Express) · Laravel · PostgreSQL

## Arsitektur

```
                  ┌──────────────────────────── Coolify (Docker + Traefik + HTTPS) ───────────────────────────┐
 Pelanggan ─────► │  app    : Node.js (Express)  — halaman pemesanan + REST API   ──┐                        │
 (HP/browser)     │                                                                  ├──► db : PostgreSQL     │
 Admin/Kasir ───► │  admin  : Laravel            — dashboard pesanan & menu       ──┘     (volume pgdata)    │
                  └───────────────────────────────────────────────────────────────────────────────────────────┘
```

| Komponen | Peran |
|---|---|
| **Node.js (Express)** | Halaman pelanggan (`/`), API menu & pesanan, pembuat skema database, health check |
| **Laravel** | Dashboard admin: login, statistik harian, kelola pesanan (ubah status), kelola menu (CRUD) |
| **PostgreSQL** | Database bersama kedua aplikasi |
| **Coolify** | Platform self-hosted yang membangun & menjalankan ketiganya, mengatur domain + HTTPS + auto-deploy |

## Fitur

- **Pelanggan** (Node.js): lihat menu per kategori, keranjang, kirim pesanan (nama, no. meja, catatan), pantau status pesanan otomatis.
- **Admin** (Laravel): login password, ringkasan pesanan & omzet hari ini, ubah status pesanan (baru → diproses → siap → selesai / batal), tambah/ubah/hapus menu & tandai habis.
- Total harga dihitung di **server** (harga dari browser tidak dipercaya); pesanan disimpan dalam transaksi DB.
- Skema database + data menu contoh dibuat otomatis saat pertama kali jalan.

## Struktur

```
├── server.js            # Node.js: API + inisialisasi DB
├── schema.sql           # skema PostgreSQL
├── public/index.html    # halaman pelanggan
├── Dockerfile           # image Node.js
├── admin-laravel/       # dashboard admin Laravel
│   ├── Dockerfile       # membuat skeleton Laravel + menimpa dengan overlay/
│   ├── entrypoint.sh
│   └── overlay/         # kode kita: routes, controller, middleware, views
├── docker-compose.yml   # app + admin + db
└── .env.example
```

## Uji coba lokal

```bash
cp .env.example .env       # Windows: copy .env.example .env
docker compose up --build  # build pertama agak lama (mengunduh Laravel via Composer)
```

- Pelanggan: http://localhost:3000
- Admin Laravel: http://localhost:8000 (password = `ADMIN_PASSWORD` di `.env`)

## Deploy ke Coolify

### 1. Siapkan server
1. VPS Ubuntu 22.04/24.04 (min. 2 GB RAM), atau VM/PC lama.
2. Install Coolify: `curl -fsSL https://cdn.coollabs.io/coolify/install.sh | sudo bash`
3. Buka `http://IP-SERVER:8000`, buat akun admin Coolify.

> Port 8000 server dipakai dashboard Coolify. Port 8000 pada `docker-compose.yml` hanya untuk uji lokal; di Coolify aplikasi diakses lewat domain, bukan port.

### 2. Push kode ke Git
```bash
git init && git add . && git commit -m "Warkop order"
git remote add origin <url-repo-anda> && git push -u origin main
```

### 3. Buat resource di Coolify
1. *Project → New Resource → Public/Private Repository* → pilih repo.
2. *Build Pack*: **Docker Compose**, file: `docker-compose.yml`.
3. **Environment Variables**: `ADMIN_PASSWORD`, `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB`, `APP_KEY` (opsional, lihat `.env.example`), dan `FORCE_HTTPS=true`.
4. Atur **domain per service**:
   - `app` → `https://pesan.domainanda.com` (port 3000)
   - `admin` → `https://admin.domainanda.com` (port 8000)
5. **Deploy**.

### 4. Domain & HTTPS
Arahkan DNS A-record kedua subdomain ke IP server. Coolify (Traefik) menerbitkan sertifikat Let's Encrypt otomatis.

### 5. Auto-deploy & backup
- Aktifkan **Webhook / Auto Deploy** agar setiap `git push` otomatis ter-deploy.
- Aktifkan backup terjadwal untuk database (Coolify mendukung backup ke S3/MinIO) bila memakai database terpisah.

## Variabel lingkungan

| Variabel | Dipakai oleh | Keterangan |
|---|---|---|
| `ADMIN_PASSWORD` | Laravel (+ Node.js) | Password dashboard admin (**wajib diganti**) |
| `POSTGRES_USER/PASSWORD/DB` | semua | Kredensial database |
| `APP_KEY` | Laravel | Kunci enkripsi/sesi; kosong = dibuat otomatis (sesi hilang tiap restart) |
| `FORCE_HTTPS` | Laravel | `true` bila di belakang proxy HTTPS (Coolify) |

## API Node.js (ringkas)

| Method | Endpoint | Akses |
|---|---|---|
| GET | `/api/menu` | publik |
| POST | `/api/orders` | publik |
| GET | `/api/orders/:id` | publik (status) |
| GET | `/api/health` | publik |

Endpoint `/api/admin/*` di Node.js tetap ada (header `x-admin-key`) tetapi tidak dipakai UI;
pengelolaan admin dilakukan lewat dashboard Laravel.

## Catatan keamanan

- Ganti `ADMIN_PASSWORD` dengan yang kuat; selalu pakai HTTPS (otomatis via Coolify).
- Login Laravel dibatasi 10 percobaan/menit dan dilindungi CSRF token.
- Pengembangan lanjut: user admin berbasis tabel (Laravel Auth), notifikasi pesanan baru (Telegram/WebSocket), laporan penjualan.
