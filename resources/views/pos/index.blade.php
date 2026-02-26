@extends('layouts.app')

@section('content')
    @push('styles')
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind = {
                config: {
                    theme: {
                        extend: {
                            colors: {
                                primary: '#4f46e5',
                            }
                        }
                    }
                }
            }
        </script>
    @endpush
    <div class="container mx-auto px-4 py-6 max-w-7xl h-screen flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Punto de Venta</h1>
                <p class="text-slate-500">Realiza ventas de forma rápida y eficiente</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-medium">
                    <i class="ri-time-line text-lg mr-2"></i>
                    <span id="clock">00:00:00</span>
                </span>
            </div>
        </div>

        <div
            class="flex-grow bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row border border-slate-200 mb-4">
            <!-- Left Column: Products -->
            <div class="w-full md:w-2/3 lg:w-3/4 flex flex-col h-full border-r border-slate-200 bg-slate-50">
                <!-- Products Toolbar -->
                <div class="p-4 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-grow">
                            <i
                                class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            <input type="text" id="product-search"
                                class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white text-slate-900 placeholder-slate-400 outline-none"
                                placeholder="Buscar producto..." autofocus>
                        </div>
                        <button
                            class="flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors font-medium bg-white"
                            onclick="filterCategory('all')">
                            <i class="ri-apps-line"></i> Todos
                        </button>
                    </div>

                    <!-- Category Filters (Horizontal Scroll) -->
                    <div class="flex gap-2 overflow-x-auto py-3 no-scrollbar mt-1">
                        @foreach($categories as $category)
                            <button
                                class="whitespace-nowrap px-4 py-1.5 rounded-full border border-slate-200 bg-white text-slate-600 hover:border-indigo-500 hover:text-indigo-600 transition-colors text-sm font-medium"
                                onclick="filterCategory({{ $category->id }})">{{ $category->nombre }}</button>
                        @endforeach
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-grow overflow-y-auto p-6" id="products-container">
                    <div id="products-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div class="col-span-full text-center py-12">
                            <div class="mb-4">
                                <div class="w-16 h-16 bg-slate-100 rounded-full mx-auto flex items-center justify-center">
                                    <svg width="32" height="32" fill="none" class="text-slate-400" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                </div>
                            </div>
                            <h5 class="text-lg font-medium text-slate-900">Listo para vender</h5>
                            <p class="text-slate-500">Utilice el buscador o seleccione una categoría.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Cart -->
            <div class="w-full md:w-1/3 lg:w-1/4 flex flex-col h-full bg-white border-l border-slate-200">
                <!-- Cart Header -->
                <div class="p-4 border-b border-slate-200">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-dashed border-slate-200">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="ri-shopping-cart-2-fill text-xl"></i>
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-center">
                                <h2 class="text-base font-semibold text-slate-800">Carrito</h2>
                                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold"
                                    id="cart-count">0 items</span>
                            </div>
                            <p class="text-xs text-slate-500">Productos seleccionados</p>
                        </div>
                    </div>

                    <!-- Client Selector -->
                    <div class="mb-0">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cliente</label>
                        <select id="client-select"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-slate-700">
                            <option value="">Cliente General (Público)</option>
                            @foreach(\App\Models\Client::all() as $client)
                                <option value="{{ $client->id }}">{{ $client->nombre_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-grow overflow-y-auto p-4 bg-slate-50" id="cart-items">
                    <!-- Items injected here -->
                </div>
            </div>
        </div>

        <!-- Bottom Card: Checkout & Payment -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-4 lg:p-6 mb-2">
            <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6 lg:gap-8">

                <!-- Section 1: Payment Methods & Inputs (Expanded) -->
                <div class="w-full lg:flex-grow grid grid-cols-1 md:grid-cols-12 gap-4 lg:gap-6">

                    <!-- Payment Method -->
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Método de Pago</label>
                        <div class="flex gap-2">
                            <input type="radio" class="peer/cash hidden" name="payment_method" id="pay-cash"
                                value="efectivo" checked onchange="toggleCashInput()">
                            <label
                                class="flex-1 border-2 border-slate-200 p-2.5 rounded-xl flex flex-col items-center justify-center gap-1 cursor-pointer transition-all hover:bg-slate-50 hover:border-slate-300 peer-checked/cash:border-emerald-500 peer-checked/cash:bg-emerald-50 peer-checked/cash:text-emerald-700"
                                for="pay-cash">
                                <i class="ri-money-dollar-circle-line text-xl"></i>
                                <span class="text-xs font-bold">Efectivo</span>
                            </label>

                            <input type="radio" class="peer/card hidden" name="payment_method" id="pay-card" value="tarjeta"
                                onchange="toggleCashInput()">
                            <label
                                class="flex-1 border-2 border-slate-200 p-2.5 rounded-xl flex flex-col items-center justify-center gap-1 cursor-pointer transition-all hover:bg-slate-50 hover:border-slate-300 peer-checked/card:border-blue-500 peer-checked/card:bg-blue-50 peer-checked/card:text-blue-700"
                                for="pay-card">
                                <i class="ri-bank-card-line text-xl"></i>
                                <span class="text-xs font-bold">Tarjeta</span>
                            </label>

                            <input type="radio" class="peer/transfer hidden" name="payment_method" id="pay-transfer"
                                value="transferencia" onchange="toggleCashInput()">
                            <label
                                class="flex-1 border-2 border-slate-200 p-2.5 rounded-xl flex flex-col items-center justify-center gap-1 cursor-pointer transition-all hover:bg-slate-50 hover:border-slate-300 peer-checked/transfer:border-indigo-500 peer-checked/transfer:bg-indigo-50 peer-checked/transfer:text-indigo-700"
                                for="pay-transfer">
                                <i class="ri-qr-code-line text-xl"></i>
                                <span class="text-xs font-bold">Transf.</span>
                            </label>
                        </div>
                    </div>

                    <!-- Amount Received & Change -->
                    <div class="md:col-span-8 flex gap-4 transition-all duration-300" id="cash-input-container">
                        <div class="w-1/2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Recibido</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">$</span>
                                <input type="number" id="amount-received"
                                    class="w-full pl-7 pr-4 py-2.5 border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-0 font-bold text-lg text-slate-800 outline-none transition-colors"
                                    placeholder="0.00" step="0.50">
                            </div>
                        </div>
                        <div class="w-1/2" id="change-container">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cambio</label>
                            <div class="bg-emerald-50 border border-emerald-100 p-2.5 rounded-xl text-center">
                                <span class="block font-extrabold text-emerald-600 text-xl" id="change-amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Totals & Actions -->
                <div
                    class="w-full lg:w-auto lg:min-w-[320px] flex flex-col gap-4 border-t lg:border-t-0 lg:border-l border-slate-100 pt-4 lg:pt-0 lg:pl-8">

                    <!-- Totals Display -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-slate-500 text-sm">
                            <span>Subtotal</span>
                            <span class="font-medium text-slate-700" id="cart-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between text-slate-500 text-sm">
                            <span>IVA (16%)</span>
                            <span class="font-medium text-slate-700" id="cart-tax">$0.00</span>
                        </div>
                        <div class="flex justify-between items-end mt-2 pt-2 border-t border-dashed border-slate-200">
                            <span class="text-sm font-bold text-slate-800 uppercase tracking-wide">Total a Pagar</span>
                            <span class="text-3xl font-black text-indigo-600 leading-none" id="cart-total">$0.00</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-3 gap-3 mt-2">
                        <button
                            class="col-span-1 py-2.5 rounded-xl border border-red-200 text-red-500 font-bold hover:bg-red-50 transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            onclick="cancelSale()" id="btn-cancel" disabled>
                            <i class="ri-delete-bin-line text-lg"></i>
                        </button>
                        <button
                            class="col-span-2 py-2.5 rounded-xl text-white font-bold shadow-lg shadow-indigo-200 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 transform active:scale-95 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            onclick="processCheckout()" id="btn-pay" disabled>
                            <span>Confirmar Venta</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>


    @push('scripts')

        <script>
            // Include existing JS logic here
            let currentCart = null;
            let searchTimeout = null;

            document.addEventListener('DOMContentLoaded', function () {
                loadCart();
                setInterval(updateClock, 1000); // Clock

                const searchInput = document.getElementById('product-search');
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 2) searchProducts(this.value);
                    }, 300);
                });

                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        if (this.value.length >= 1) searchProducts(this.value);
                    }
                });

                document.getElementById('client-select').addEventListener('change', function () {
                    updateClient(this.value);
                });

                document.getElementById('amount-received').addEventListener('input', calculateChange);

                // Initialize visibility based on default checked radio
                toggleCashInput();
            });

            function updateClock() {
                const now = new Date();
                document.getElementById('clock').innerText = now.toLocaleTimeString('es-MX', { hour12: false });
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(amount);
            }

            function toggleCashInput() {
                const method = document.querySelector('input[name="payment_method"]:checked').value;
                const cashContainer = document.getElementById('cash-input-container');
                const changeContainer = document.getElementById('change-container');
                const amountInput = document.getElementById('amount-received');

                if (method === 'efectivo') {
                    cashContainer.classList.remove('opacity-0', 'd-none');
                    changeContainer.classList.remove('opacity-0', 'd-none');
                    setTimeout(() => amountInput.focus(), 100);
                } else {
                    cashContainer.classList.add('opacity-0', 'd-none');
                    changeContainer.classList.add('opacity-0', 'd-none');
                    amountInput.value = ''; // Reset input
                    document.getElementById('change-amount').innerText = '$0.00';
                }
            }

            async function loadCart() {
                try {
                    const response = await fetch('{{ route("pos.cart") }}');
                    currentCart = await response.json();
                    renderCart();
                } catch (error) {
                    console.error('Error loading cart:', error);
                }
            }

            function renderCart() {
                const cartContainer = document.getElementById('cart-items');
                const cartCount = document.getElementById('cart-count');
                const cartSubtotal = document.getElementById('cart-subtotal');
                const cartTax = document.getElementById('cart-tax');
                const cartTotal = document.getElementById('cart-total');
                const btnPay = document.getElementById('btn-pay');
                const btnCancel = document.getElementById('btn-cancel');

                if (!currentCart || !currentCart.items || currentCart.items.length === 0) {
                    cartContainer.innerHTML = `
                                                <div class="h-full flex flex-col items-center justify-center text-slate-400 opacity-50">
                                                    <i class="ri-shopping-cart-line text-4xl mb-2"></i>
                                                    <p>Carrito vacío</p>
                                                </div>`;

                    cartCount.innerText = '0 items';
                    cartSubtotal.innerText = '$0.00';
                    cartTax.innerText = '$0.00';
                    cartTotal.innerText = '$0.00';
                    if (btnPay) btnPay.disabled = true;
                    if (btnCancel) btnCancel.disabled = true;
                    return;
                }

                const totalItems = currentCart.items.reduce((sum, item) => sum + item.cantidad, 0);
                cartCount.innerText = `${totalItems} items`;

                cartSubtotal.innerText = formatCurrency(currentCart.subtotal);
                cartTax.innerText = formatCurrency(currentCart.impuesto);
                cartTotal.innerText = formatCurrency(currentCart.total);
                if (btnPay) btnPay.disabled = false;
                if (btnCancel) btnCancel.disabled = false;

                document.getElementById('client-select').value = currentCart.client_id || '';

                let html = '';
                currentCart.items.forEach(item => {
                    const variantName = item.producto_nombre_snapshot || (item.variant ? item.variant.product.nombre : 'Producto');
                    html += `
                                                <div class="cart-item bg-white mb-2 border border-slate-200 rounded-lg shadow-sm">
                                                    <div class="p-3">
                                                        <div class="flex justify-between mb-1">
                                                            <span class="font-medium text-slate-800 text-sm truncate" title="${variantName}" style="max-width: 160px;">${variantName}</span>
                                                            <span class="font-bold text-indigo-600 text-sm">${formatCurrency(item.importe)}</span>
                                                        </div>
                                                        <div class="flex justify-between items-center">
                                                            <small class="text-slate-500 text-xs">${formatCurrency(item.precio_unitario)}</small>
                                                            <div class="flex items-center border border-slate-300 rounded-md overflow-hidden">
                                                                <button class="px-2 py-0.5 bg-slate-50 hover:bg-slate-100 text-slate-600 transition-colors" onclick="updateItem(${item.id}, ${item.cantidad - 1})">-</button>
                                                                <input type="text" class="w-8 text-center text-xs border-x border-slate-300 bg-white py-0.5 outline-none" value="${item.cantidad}" readonly>
                                                                <button class="px-2 py-0.5 bg-slate-50 hover:bg-slate-100 text-slate-600 transition-colors" onclick="updateItem(${item.id}, ${item.cantidad + 1})">+</button>
                                                            </div>
                                                            <button class="text-red-500 hover:text-red-700 transition-colors p-1" onclick="removeItem(${item.id})">
                                                                <i class="ri-delete-bin-line text-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                });

                cartContainer.innerHTML = html;
                cartContainer.scrollTop = cartContainer.scrollHeight;
            }

            async function searchProducts(query) {
                const grid = document.getElementById('products-grid');
                grid.innerHTML = '<div class="col-span-full text-center py-12"><div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent" role="status"></div></div>';

                try {
                    const response = await fetch(`{{ route("pos.search") }}?q=${encodeURIComponent(query)}`);
                    const products = await response.json();

                    if (products.length === 0) {
                        grid.innerHTML = `
                                                    <div class="col-span-full text-center text-slate-400 py-12">
                                                        <i class="ri-ghost-line text-4xl mb-2"></i>
                                                        <p>No se encontraron productos</p>
                                                    </div>`;
                        return;
                    }

                    let html = '';
                    products.forEach(p => {
                        const stockClass = p.stock > 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10';
                        const stockText = p.stock > 0 ? `Stock: ${p.stock}` : 'Agotado';
                        const opacity = p.stock > 0 ? '' : 'opacity-50 grayscale';

                        html += `
                                                    <div class="h-full">
                                                        <div class="h-full bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group ${opacity}" onclick="addToCart(${p.id})">
                                                            <div class="p-4 text-center">
                                                                <div class="bg-slate-50 rounded-lg mb-3 flex items-center justify-center border border-slate-100 h-24 group-hover:bg-indigo-50 transition-colors">
                                                                    ${p.image ? `<img src="${p.image}" class="img-fluid max-h-20">` : '<i class="ri-image-2-line text-4xl text-slate-300 group-hover:text-indigo-300 transition-colors"></i>'}
                                                                </div>
                                                                <h6 class="font-bold text-slate-800 truncate mb-1" title="${p.name}">${p.name}</h6>
                                                                <p class="text-slate-500 text-xs mb-3 truncate">SKU: ${p.sku}</p>
                                                                <div class="flex justify-between items-center">
                                                                    <h5 class="text-indigo-600 text-lg font-bold">${formatCurrency(p.price)}</h5>
                                                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${stockClass}">${stockText}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
                    });

                    grid.innerHTML = html;
                } catch (error) {
                    console.error('Search error:', error);
                    grid.innerHTML = '<div class="col-span-full text-center text-red-500"><p>Error al buscar productos</p></div>';
                }
            }

            async function addToCart(variantId) {
                try {
                    const response = await fetch('{{ route("pos.add") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ variant_id: variantId, quantity: 1 })
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        alert(data.error || 'Error al agregar producto');
                        return;
                    }

                    currentCart = await response.json();
                    renderCart();
                } catch (error) {
                    console.error('Error adding to cart:', error);
                }
            }

            async function updateItem(itemId, newQuantity) {
                if (newQuantity < 1) return;

                try {
                    const response = await fetch(`{{ url('pos/cart/update') }}/${itemId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ quantity: newQuantity })
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        alert(data.error || 'Stock insuficiente');
                        return;
                    }

                    currentCart = await response.json();
                    renderCart();
                } catch (error) {
                    console.error('Error updating item:', error);
                }
            }

            async function removeItem(itemId) {
                if (!confirm('¿Eliminar producto del carrito?')) return;

                try {
                    const response = await fetch(`{{ url('pos/cart/remove') }}/${itemId}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });

                    currentCart = await response.json();
                    renderCart();
                } catch (error) {
                    console.error('Error removing item:', error);
                }
            }

            async function updateClient(clientId) {
                try {
                    const response = await fetch('{{ route("pos.client") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ client_id: clientId })
                    });
                    currentCart = await response.json();
                } catch (error) {
                    console.error('Error updating client:', error);
                }
            }

            async function cancelSale() {
                if (!confirm('¿Está seguro de cancelar la venta actual?')) return;

                try {
                    const response = await fetch('{{ route("pos.cancel") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    currentCart = await response.json();
                    renderCart();
                    document.getElementById('product-search').value = '';
                    document.getElementById('products-grid').innerHTML = `
                                                <div class="col-span-full text-center py-12">
                                                    <div class="mb-4">
                                                        <div class="w-16 h-16 bg-slate-100 rounded-full mx-auto flex items-center justify-center">
                                                            <svg width="32" height="32" fill="none" class="text-slate-400" stroke="currentColor" stroke-width="1.5"
                                                                viewBox="0 0 24 24">
                                                                <circle cx="11" cy="11" r="8" />
                                                                <path d="m21 21-4.35-4.35" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <h5 class="text-lg font-medium text-slate-900">Listo para vender</h5>
                                                    <p class="text-slate-500">Utilice el buscador o seleccione una categoría.</p>
                                                </div>
                                            `;
                } catch (error) {
                    console.error('Error cancelling sale:', error);
                }
            }

            function calculateChange() {
                const total = parseFloat(currentCart.total);
                const received = parseFloat(this.value) || 0;
                const change = received - total;

                const changeAmount = document.getElementById('change-amount');
                changeAmount.innerText = formatCurrency(change > 0 ? change : 0);
            }

            async function processCheckout() {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                const amountReceived = parseFloat(document.getElementById('amount-received').value) || 0;

                if (paymentMethod === 'efectivo' && amountReceived < currentCart.total) {
                    alert('El monto recibido es menor al total.');
                    document.getElementById('amount-received').focus();
                    return;
                }

                if (!confirm('¿Confirmar venta por ' + formatCurrency(currentCart.total) + '?')) {
                    return;
                }

                try {
                    const response = await fetch('{{ route("pos.checkout") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            payment_method: paymentMethod,
                            amount_paid: amountReceived
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.error || 'Error al procesar la venta');
                    }
                } catch (error) {
                    console.error('Checkout error:', error);
                    alert('Error de red al procesar la venta');
                }
            }
        </script>
    @endpush
@endsection