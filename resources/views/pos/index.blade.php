@extends('layouts.app')
@section('title', 'POS / Transaksi')

@section('content')
<div class="eyebrow">01 / Point of Sales</div>
<h1 class="title">Transaksi Baru</h1>
<p class="sub">Pilih menu, periksa keranjang, lalu selesaikan transaksi. Stok bahan akan dihitung dari resep BOM setelah transaksi berhasil.</p>

<div class="menu-layout" style="margin-top: 20px">

    {{-- Menu Grid --}}
    <div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <input id="menuSearch" placeholder="Cari nama menu..." style="flex:1;min-width:180px">

            <select id="categoryFilter" style="min-width:150px">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="menuGrid" class="menu-grid">
            @forelse($menus as $menu)
                @php
                    $hasOutOfStock = $menu->hasOutOfStockMaterial();
                @endphp

                <article class="menu-card"
                    data-id="{{ $menu->id }}"
                    data-name="{{ $menu->name }}"
                    data-price="{{ (float) $menu->price }}"
                    data-category="{{ $menu->category->name }}"
                    data-desc="{{ $menu->description }}"
                    data-out="{{ $hasOutOfStock ? '1' : '0' }}">

                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:start">
                        <span class="tag">{{ $menu->category->name }}</span>

                        @if($hasOutOfStock)
                            <span class="status empty">Periksa stok</span>
                        @else
                            <span class="status safe">Menu aktif</span>
                        @endif
                    </div>

                    <h3 style="margin-top:12px">{{ $menu->name }}</h3>
                    <div class="sub">{{ $menu->description }}</div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px">
                        <span class="price">{{ $menu->price_formatted }}</span>

                        <button
                            type="button"
                            class="btn sm"
                            onclick="addToCart({{ $menu->id }})">
                            + Tambah
                        </button>
                    </div>
                </article>
            @empty
                <div class="sub" style="grid-column:span 2;padding:30px;text-align:center">
                    Belum ada menu aktif.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Keranjang Pesanan --}}
    <div class="panel sticky-cart">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-weight:800;font-size:16px">Keranjang Pesanan</div>
                <div class="sub" id="cartCount">Belum ada item</div>
            </div>

            <button type="button" class="btn secondary sm" id="clearCartBtn">
                Kosongkan
            </button>
        </div>

        <div id="cartItems" style="margin-top:12px;min-height:60px">
            <div class="sub" style="padding:22px 0;text-align:center">
                Pilih menu untuk memulai transaksi.
            </div>
        </div>

        <div style="border-top:1px solid var(--line);padding-top:14px;margin-top:8px">
            <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--muted)">
                <span>Subtotal</span>
                <b id="subtotal">Rp0</b>
            </div>

            <div style="display:flex;justify-content:space-between;font-size:19px;font-weight:800;margin:12px 0 16px">
                <span>Total</span>
                <span id="total">Rp0</span>
            </div>

            <label for="paymentMethod" style="display:block;margin-bottom:6px">
                Metode pembayaran
            </label>

            <select id="paymentMethod" style="width:100%;margin-bottom:12px">
                <option value="cash">Tunai</option>
                <option value="qris">QRIS</option>
            </select>

            <button type="button" id="checkoutBtn" class="btn" style="width:100%" disabled>
                <span class="material-symbols-outlined">check_circle</span>
                Selesaikan Transaksi
            </button>

            <p class="footer-note">Transaksi tidak terhubung ke payment gateway nyata.</p>
        </div>
    </div>
</div>

{{-- Pop-up Pembayaran Tunai --}}
<div id="cashPaymentModal" class="payment-modal" hidden>
    <div class="payment-modal-content" role="dialog" aria-modal="true" aria-labelledby="cashModalTitle">

        <div class="payment-modal-header">
            <div>
                <div class="eyebrow">PEMBAYARAN</div>
                <h2 id="cashModalTitle">Pembayaran Tunai</h2>
                <p class="sub">Masukkan nominal uang dari pelanggan.</p>
            </div>

            <button type="button" id="closeCashModal" class="btn secondary sm">
                Tutup
            </button>
        </div>

        <div class="payment-summary">
            <div class="payment-summary-row">
                <span>Total Pesanan</span>
                <strong id="cashOrderTotal">Rp0</strong>
            </div>
        </div>

        <label for="cashReceived">
            Total Uang yang Digunakan untuk Pembayaran
        </label>

        <input
            type="number"
            id="cashReceived"
            min="0"
            step="1"
            inputmode="numeric"
            placeholder="Contoh: 50000"
            style="width:100%;margin-top:8px">

        <div class="payment-change">
            <span>Kembalian</span>
            <strong id="cashChange">Rp0</strong>
        </div>

        <p id="cashPaymentError" class="payment-error" hidden></p>

        <div class="payment-modal-actions">
            <button type="button" id="cancelCashPayment" class="btn secondary">
                Batal
            </button>

            <button type="button" id="confirmCashPayment" class="btn" disabled>
                Konfirmasi Pembayaran
            </button>
        </div>
    </div>
</div>
@endsection

@php
    $menusJson = $menus->map(fn($m) => [
        'id'       => $m->id,
        'name'     => $m->name,
        'price'    => (float) $m->price,
        'category' => $m->category->name,
        'desc'     => $m->description,
        'out'      => $m->hasOutOfStockMaterial(),
    ])->values();
@endphp

@push('scripts')
<script>
'use strict';

// Data menu dari server
const MENUS = @json($menusJson);

// Keranjang: { menu_id: { name, price, qty } }
let cart = {};
let checkoutInProgress = false;

const checkoutUrl = @json(route('pos.checkout'));

const checkoutBtn = document.getElementById('checkoutBtn');
const paymentMethod = document.getElementById('paymentMethod');

const cashModal = document.getElementById('cashPaymentModal');
const cashInput = document.getElementById('cashReceived');
const cashOrderTotal = document.getElementById('cashOrderTotal');
const cashChange = document.getElementById('cashChange');
const cashError = document.getElementById('cashPaymentError');
const confirmCashBtn = document.getElementById('confirmCashPayment');

// Format rupiah
function formatRupiah(value) {
    return 'Rp' + Math.round(value).toLocaleString('id-ID');
}

// Menggunakan rupiah() dari layout jika tersedia
function money(value) {
    return typeof rupiah === 'function'
        ? rupiah(value)
        : formatRupiah(value);
}

// Total keranjang
function getCartTotal() {
    return Object.values(cart).reduce(
        (sum, item) => sum + item.price * item.qty,
        0
    );
}

// Pencarian dan filter kategori
function filterMenus() {
    const query = document.getElementById('menuSearch').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;

    document.querySelectorAll('#menuGrid article').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const desc = (card.dataset.desc || '').toLowerCase();
        const cardCategory = card.dataset.category;

        const matches =
            (name.includes(query) || desc.includes(query)) &&
            (!category || cardCategory === category);

        card.style.display = matches ? '' : 'none';
    });
}

document.getElementById('menuSearch')
    .addEventListener('input', filterMenus);

document.getElementById('categoryFilter')
    .addEventListener('change', filterMenus);

// Tambah menu ke keranjang
function addToCart(id) {
    if (checkoutInProgress) return;

    const menu = MENUS.find(item => Number(item.id) === Number(id));

    if (!menu) {
        showToast('Menu tidak ditemukan.', 'error');
        return;
    }

    if (menu.out) {
        showToast('Stok bahan menu ini perlu diperiksa.', 'error');
        return;
    }

    if (cart[id]) {
        cart[id].qty++;
    } else {
        cart[id] = {
            name: menu.name,
            price: Number(menu.price),
            qty: 1
        };
    }

    renderCart();
}

// Ubah kuantitas
function changeQty(id, delta) {
    if (checkoutInProgress || !cart[id]) return;

    cart[id].qty += delta;

    if (cart[id].qty <= 0) {
        delete cart[id];
    }

    renderCart();
}

// Hapus menu
function removeFromCart(id) {
    if (checkoutInProgress) return;

    delete cart[id];
    renderCart();
}

// Render keranjang
function renderCart() {
    const entries = Object.entries(cart);
    const itemsEl = document.getElementById('cartItems');
    const countEl = document.getElementById('cartCount');

    const totalQty = entries.reduce(
        (sum, [, item]) => sum + item.qty,
        0
    );

    countEl.textContent = totalQty > 0
        ? `${totalQty} item`
        : 'Belum ada item';

    if (entries.length === 0) {
        itemsEl.innerHTML = `
            <div class="sub" style="padding:22px 0;text-align:center">
                Pilih menu untuk memulai transaksi.
            </div>`;

        document.getElementById('subtotal').textContent = money(0);
        document.getElementById('total').textContent = money(0);
        checkoutBtn.disabled = true;

        updateCashPayment();
        return;
    }

    let total = 0;

    itemsEl.innerHTML = entries.map(([id, item]) => {
        const itemSubtotal = item.price * item.qty;
        total += itemSubtotal;

        // Nama menu dimasukkan sebagai teks, bukan HTML mentah.
        const row = document.createElement('div');
        row.className = 'cart-row';

        const details = document.createElement('div');

        const name = document.createElement('b');
        name.style.fontSize = '13px';
        name.textContent = item.name;

        const price = document.createElement('div');
        price.className = 'sub';
        price.textContent = `${money(item.price)} / porsi`;

        const controls = document.createElement('div');
        controls.className = 'qty-control';

        const minus = document.createElement('button');
        minus.type = 'button';
        minus.textContent = '−';
        minus.setAttribute('aria-label', 'Kurangi');
        minus.addEventListener('click', () => changeQty(id, -1));

        const quantity = document.createElement('span');
        quantity.className = 'qty-num';
        quantity.textContent = item.qty;

        const plus = document.createElement('button');
        plus.type = 'button';
        plus.textContent = '+';
        plus.setAttribute('aria-label', 'Tambah');
        plus.addEventListener('click', () => changeQty(id, 1));

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn danger sm';
        remove.style.marginLeft = '4px';
        remove.textContent = 'Hapus';
        remove.addEventListener('click', () => removeFromCart(id));

        controls.append(minus, quantity, plus, remove);
        details.append(name, price, controls);

        const itemTotal = document.createElement('b');
        itemTotal.style.fontSize = '13px';
        itemTotal.style.marginTop = '4px';
        itemTotal.textContent = money(itemSubtotal);

        row.append(details, itemTotal);

        return row;
    }).forEach(row => itemsEl.appendChild(row));

    document.getElementById('subtotal').textContent = money(total);
    document.getElementById('total').textContent = money(total);

    checkoutBtn.disabled = checkoutInProgress;

    updateCashPayment();
}

// Kosongkan keranjang
document.getElementById('clearCartBtn').addEventListener('click', () => {
    if (checkoutInProgress || Object.keys(cart).length === 0) return;

    if (confirm('Kosongkan keranjang?')) {
        cart = {};
        renderCart();
    }
});

// Buka pop-up pembayaran
function openCashPayment() {
    if (Object.keys(cart).length === 0 || checkoutInProgress) return;

    cashInput.value = '';
    cashModal.hidden = false;

    updateCashPayment();

    requestAnimationFrame(() => cashInput.focus());
}

// Tutup pop-up
function closeCashPayment() {
    if (checkoutInProgress) return;
    cashModal.hidden = true;
}

document.getElementById('closeCashModal')
    .addEventListener('click', closeCashPayment);

document.getElementById('cancelCashPayment')
    .addEventListener('click', closeCashPayment);

cashModal.addEventListener('click', event => {
    if (event.target === cashModal) {
        closeCashPayment();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !cashModal.hidden) {
        closeCashPayment();
    }
});

// Hitung kembalian secara otomatis
function updateCashPayment() {
    const total = getCartTotal();
    const raw = cashInput.value.trim();
    const paid = Number(raw);

    const validInput =
        raw !== '' &&
        Number.isFinite(paid) &&
        paid >= 0 &&
        Number.isSafeInteger(paid);

    const sufficient =
        validInput &&
        paid >= total &&
        total > 0;

    cashOrderTotal.textContent = money(total);
    cashChange.textContent = money(
        sufficient ? paid - total : 0
    );

    if (!validInput) {
        cashError.textContent = 'Masukkan nominal uang dalam angka bulat yang valid.';
        cashError.hidden = raw === '';
    } else if (paid < total) {
        cashError.textContent = 'Uang pembayaran kurang dari total pesanan.';
        cashError.hidden = false;
    } else {
        cashError.hidden = true;
    }

    confirmCashBtn.disabled = !sufficient || checkoutInProgress;
}

cashInput.addEventListener('input', updateCashPayment);

// Klik Selesaikan Transaksi
checkoutBtn.addEventListener('click', () => {
    if (checkoutInProgress || Object.keys(cart).length === 0) return;

    if (paymentMethod.value === 'cash') {
        openCashPayment();
    } else {
        // QRIS tetap memakai alur checkout sebelumnya.
        processCheckout();
    }
});

// Konfirmasi pembayaran tunai
confirmCashBtn.addEventListener('click', () => {
    const total = getCartTotal();
    const raw = cashInput.value.trim();
    const paid = Number(raw);

    if (
        raw === '' ||
        !Number.isSafeInteger(paid) ||
        paid < total ||
        total <= 0
    ) {
        updateCashPayment();
        return;
    }

    processCheckout({
        cash_received: paid,
        change_amount: paid - total
    });
});

// Kirim transaksi ke backend
async function processCheckout(cashPayment = null) {
    if (checkoutInProgress || Object.keys(cart).length === 0) return;

    const totalAtCheckout = getCartTotal();
    const selectedMethod = paymentMethod.value;

    if (
        selectedMethod === 'cash' &&
        (
            !cashPayment ||
            !Number.isSafeInteger(cashPayment.cash_received) ||
            cashPayment.cash_received < totalAtCheckout
        )
    ) {
        openCashPayment();
        updateCashPayment();
        return;
    }

    checkoutInProgress = true;

    const originalButton = checkoutBtn.innerHTML;

    checkoutBtn.disabled = true;
    confirmCashBtn.disabled = true;

    checkoutBtn.innerHTML = `
        <span class="material-symbols-outlined">progress_activity</span>
        Memproses...`;

    const items = Object.entries(cart).map(([id, item]) => ({
        menu_id: Number.parseInt(id, 10),
        quantity: item.qty
    }));

    const payload = {
        items,
        payment_method: selectedMethod
    };

    if (cashPayment) {
        payload.cash_received = cashPayment.cash_received;
    }

    try {
        const response = await fetch(checkoutUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            showToast(
                data.message || 'Transaksi gagal diproses.',
                'error'
            );
            return;
        }

        showToast(
            `✓ ${data.message || 'Transaksi berhasil.'}`,
            'success-toast'
        );

        cart = {};
        renderCart();

        cashModal.hidden = true;

        setTimeout(() => location.reload(), 1500);

    } catch (error) {
        console.error('Checkout error:', error);

        showToast(
            'Terjadi kesalahan koneksi. Coba lagi.',
            'error'
        );

    } finally {
        checkoutInProgress = false;
        checkoutBtn.innerHTML = originalButton;
        checkoutBtn.disabled = Object.keys(cart).length === 0;
        updateCashPayment();
    }
}
</script>

<style>
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.payment-modal[hidden] {
    display: none !important;
}

.payment-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(30, 17, 10, 0.55);
}

.payment-modal-content {
    width: 100%;
    max-width: 460px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 26px;
    border: 1px solid var(--line);
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.payment-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 24px;
}

.payment-modal-header h2 {
    margin: 8px 0;
    font-size: 23px;
}

.payment-summary {
    padding: 16px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: #fbf2eb;
}

.payment-summary-row,
.payment-change {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.payment-summary-row strong {
    font-size: 22px;
}

.payment-change {
    padding: 18px 0;
    margin-top: 12px;
    border-bottom: 1px solid var(--line);
}

.payment-change strong {
    font-size: 24px;
    color: #176b43;
}

.payment-error {
    margin: 12px 0;
    color: #b42318;
    font-size: 13px;
}

.payment-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.payment-modal-actions button {
    min-height: 42px;
}

@media (max-width: 480px) {
    .payment-modal-content {
        padding: 20px;
    }

    .payment-modal-actions {
        flex-direction: column-reverse;
    }

    .payment-modal-actions button {
        width: 100%;
    }
}
</style>
@endpush