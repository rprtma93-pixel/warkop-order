<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Dashboard admin. Tabel (menu_items, orders, order_items) dibuat oleh
 * aplikasi Node.js; Laravel membaca/menulis ke database PostgreSQL yang sama.
 */
class AdminController extends Controller
{
    private const STATUSES = ['baru', 'diproses', 'siap', 'selesai', 'batal'];

    // ---------- Login ----------
    public function loginForm()
    {
        return session('admin') ? redirect('/admin') : view('admin.login');
    }

    public function login(Request $request)
    {
        $expected = (string) env('ADMIN_PASSWORD', '');
        $given = (string) $request->input('password', '');

        if ($expected !== '' && hash_equals($expected, $given)) {
            $request->session()->regenerate();
            $request->session()->put('admin', true);
            return redirect('/admin');
        }

        return back()->withErrors(['password' => 'Password salah']);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // ---------- Pesanan ----------
    public function orders()
    {
        $orders = DB::table('orders')->orderByDesc('created_at')->limit(100)->get();
        $items = DB::table('order_items')
            ->whereIn('order_id', $orders->pluck('id'))
            ->get()
            ->groupBy('order_id');

        $today = DB::selectOne(
            "SELECT COUNT(*)::int AS n,
                    COALESCE(SUM(total) FILTER (WHERE status <> 'batal'), 0)::bigint AS revenue
             FROM orders
             WHERE (created_at AT TIME ZONE 'Asia/Jakarta')::date = (now() AT TIME ZONE 'Asia/Jakarta')::date"
        );
        $active = DB::table('orders')->whereIn('status', ['baru', 'diproses'])->count();

        return view('admin.orders', [
            'orders' => $orders,
            'items' => $items,
            'statuses' => self::STATUSES,
            'today' => $today,
            'active' => $active,
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $affected = DB::table('orders')->where('id', $id)->update(['status' => $data['status']]);

        return back()->with('ok', $affected ? "Status pesanan #$id diubah menjadi {$data['status']}" : 'Pesanan tidak ditemukan');
    }

    // ---------- Menu ----------
    public function menu(Request $request)
    {
        $items = DB::table('menu_items')->orderBy('category')->orderBy('name')->get();
        $edit = $request->filled('edit') ? DB::table('menu_items')->where('id', (int) $request->query('edit'))->first() : null;

        return view('admin.menu', ['items' => $items, 'edit' => $edit]);
    }

    public function menuStore(Request $request)
    {
        DB::table('menu_items')->insert($this->menuData($request));
        return redirect('/admin/menu')->with('ok', 'Menu ditambahkan');
    }

    public function menuUpdate(Request $request, int $id)
    {
        DB::table('menu_items')->where('id', $id)->update($this->menuData($request));
        return redirect('/admin/menu')->with('ok', 'Menu diperbarui');
    }

    public function menuDelete(int $id)
    {
        DB::table('menu_items')->where('id', $id)->delete();
        return redirect('/admin/menu')->with('ok', 'Menu dihapus');
    }

    private function menuData(Request $request): array
    {
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string|max:50',
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        return [
            'name' => $v['name'],
            'category' => $v['category'],
            'description' => $v['description'] ?? '',
            'price' => (int) $v['price'],
            'available' => $request->boolean('available'),
        ];
    }
}
