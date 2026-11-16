const express = require('express');
const fs = require('fs');
const path = require('path');
const { Pool } = require('pg');

const PORT = process.env.PORT || 3000;
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'admin123';

const pool = new Pool(
  process.env.DATABASE_URL
    ? { connectionString: process.env.DATABASE_URL }
    : {
        host: process.env.DB_HOST || 'localhost',
        port: Number(process.env.DB_PORT || 5432),
        user: process.env.DB_USER || 'postgres',
        password: process.env.DB_PASSWORD || 'postgres',
        database: process.env.DB_NAME || 'warkop',
      }
);

const app = express();
app.use(express.json({ limit: '100kb' }));
app.use(express.static(path.join(__dirname, 'public')));

const wrap = (fn) => (req, res) =>
  fn(req, res).catch((err) => {
    console.error(err);
    res.status(500).json({ error: 'Terjadi kesalahan pada server' });
  });

const adminOnly = (req, res, next) => {
  if (req.get('x-admin-key') !== ADMIN_PASSWORD) {
    return res.status(401).json({ error: 'Tidak diizinkan' });
  }
  next();
};

// ---------- Health check (dipakai Coolify) ----------
app.get('/api/health', wrap(async (req, res) => {
  await pool.query('SELECT 1');
  res.json({ status: 'ok' });
}));

// ---------- Menu ----------
app.get('/api/menu', wrap(async (req, res) => {
  const { rows } = await pool.query(
    'SELECT id, name, category, description, price FROM menu_items WHERE available = TRUE ORDER BY category, name'
  );
  res.json(rows);
}));

app.get('/api/admin/menu', adminOnly, wrap(async (req, res) => {
  const { rows } = await pool.query('SELECT * FROM menu_items ORDER BY category, name');
  res.json(rows);
}));

app.post('/api/admin/menu', adminOnly, wrap(async (req, res) => {
  const { name, category, description, price, available } = req.body;
  if (!name || !Number.isInteger(price) || price < 0) {
    return res.status(400).json({ error: 'Nama dan harga (angka) wajib diisi' });
  }
  const { rows } = await pool.query(
    `INSERT INTO menu_items (name, category, description, price, available)
     VALUES ($1,$2,$3,$4,$5) RETURNING *`,
    [name, category || 'Minuman', description || '', price, available !== false]
  );
  res.status(201).json(rows[0]);
}));

app.put('/api/admin/menu/:id', adminOnly, wrap(async (req, res) => {
  const { name, category, description, price, available } = req.body;
  if (!name || !Number.isInteger(price) || price < 0) {
    return res.status(400).json({ error: 'Nama dan harga (angka) wajib diisi' });
  }
  const { rows } = await pool.query(
    `UPDATE menu_items SET name=$1, category=$2, description=$3, price=$4, available=$5
     WHERE id=$6 RETURNING *`,
    [name, category || 'Minuman', description || '', price, available !== false, req.params.id]
  );
  if (!rows.length) return res.status(404).json({ error: 'Menu tidak ditemukan' });
  res.json(rows[0]);
}));

app.delete('/api/admin/menu/:id', adminOnly, wrap(async (req, res) => {
  await pool.query('DELETE FROM menu_items WHERE id=$1', [req.params.id]);
  res.json({ ok: true });
}));

// ---------- Pesanan ----------
app.post('/api/orders', wrap(async (req, res) => {
  const { customer_name, table_no, notes, items } = req.body;
  if (!customer_name || !customer_name.trim()) {
    return res.status(400).json({ error: 'Nama pemesan wajib diisi' });
  }
  if (!Array.isArray(items) || !items.length) {
    return res.status(400).json({ error: 'Keranjang masih kosong' });
  }

  const client = await pool.connect();
  try {
    await client.query('BEGIN');
    let total = 0;
    const lines = [];
    for (const it of items) {
      const qty = Number(it.qty);
      if (!Number.isInteger(qty) || qty < 1 || qty > 50) throw new Error('Jumlah tidak valid');
      const { rows } = await client.query(
        'SELECT id, name, price FROM menu_items WHERE id=$1 AND available=TRUE',
        [it.menu_id]
      );
      if (!rows.length) throw new Error('Ada menu yang sudah tidak tersedia');
      total += rows[0].price * qty;
      lines.push({ ...rows[0], qty });
    }
    const order = await client.query(
      `INSERT INTO orders (customer_name, table_no, notes, total)
       VALUES ($1,$2,$3,$4) RETURNING id, status, total, created_at`,
      [customer_name.trim(), (table_no || '').trim(), (notes || '').trim(), total]
    );
    for (const l of lines) {
      await client.query(
        'INSERT INTO order_items (order_id, menu_id, name, price, qty) VALUES ($1,$2,$3,$4,$5)',
        [order.rows[0].id, l.id, l.name, l.price, l.qty]
      );
    }
    await client.query('COMMIT');
    res.status(201).json(order.rows[0]);
  } catch (err) {
    await client.query('ROLLBACK');
    res.status(400).json({ error: err.message });
  } finally {
    client.release();
  }
}));

// Pelanggan memantau status pesanan
app.get('/api/orders/:id', wrap(async (req, res) => {
  const { rows } = await pool.query(
    'SELECT id, customer_name, status, total, created_at FROM orders WHERE id=$1',
    [req.params.id]
  );
  if (!rows.length) return res.status(404).json({ error: 'Pesanan tidak ditemukan' });
  res.json(rows[0]);
}));

app.get('/api/admin/orders', adminOnly, wrap(async (req, res) => {
  const { rows } = await pool.query(
    `SELECT o.*, COALESCE(json_agg(json_build_object('name', i.name, 'qty', i.qty, 'price', i.price))
            FILTER (WHERE i.id IS NOT NULL), '[]') AS items
     FROM orders o LEFT JOIN order_items i ON i.order_id = o.id
     GROUP BY o.id ORDER BY o.created_at DESC LIMIT 100`
  );
  res.json(rows);
}));

app.patch('/api/admin/orders/:id/status', adminOnly, wrap(async (req, res) => {
  const { status } = req.body;
  if (!['baru', 'diproses', 'siap', 'selesai', 'batal'].includes(status)) {
    return res.status(400).json({ error: 'Status tidak valid' });
  }
  const { rows } = await pool.query(
    'UPDATE orders SET status=$1 WHERE id=$2 RETURNING *', [status, req.params.id]
  );
  if (!rows.length) return res.status(404).json({ error: 'Pesanan tidak ditemukan' });
  res.json(rows[0]);
}));

// ---------- Inisialisasi DB ----------
async function initDb() {
  await pool.query(fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8'));
  const { rows } = await pool.query('SELECT COUNT(*)::int AS n FROM menu_items');
  if (rows[0].n === 0) {
    const seed = [
      ['Kopi Susu Gula Aren', 'Kopi', 'Espresso, susu segar, gula aren', 18000],
      ['Americano', 'Kopi', 'Espresso dengan air panas/es', 15000],
      ['Latte', 'Kopi', 'Espresso dengan susu steam', 20000],
      ['Teh Tarik', 'Non-Kopi', 'Teh susu khas', 14000],
      ['Matcha Latte', 'Non-Kopi', 'Matcha dengan susu', 22000],
      ['Roti Bakar Coklat Keju', 'Makanan', 'Roti tebal dengan coklat & keju', 16000],
      ['Pisang Goreng', 'Makanan', '5 potong, renyah', 12000],
      ['Mie Goreng Spesial', 'Makanan', 'Dengan telur dan sayur', 20000],
    ];
    for (const s of seed) {
      await pool.query(
        'INSERT INTO menu_items (name, category, description, price) VALUES ($1,$2,$3,$4)', s
      );
    }
    console.log('Data menu contoh ditambahkan');
  }
}

async function start() {
  for (let i = 1; i <= 10; i++) {
    try { await initDb(); break; }
    catch (e) {
      console.log(`Menunggu database... (${i}/10): ${e.message}`);
      if (i === 10) process.exit(1);
      await new Promise((r) => setTimeout(r, 3000));
    }
  }
  app.listen(PORT, '0.0.0.0', () => console.log(`Server berjalan di port ${PORT}`));
}
start();
