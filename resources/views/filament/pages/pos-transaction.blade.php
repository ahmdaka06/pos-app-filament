<x-filament-panels::page
    full-height
    class="pos-page !gap-0
        [&_.fi-page-header]:hidden
        [&_.fi-page-header-main-ctn]:gap-0
        [&_.fi-page-header-main-ctn]:py-0
        [&_.fi-page-content]:min-h-0
        [&_.fi-page-content]:!flex
        [&_.fi-page-content]:!flex-col
        [&_.fi-page-content]:flex-1
        [&_.fi-page-content]:gap-0
        [&_.fi-page-content]:p-0
        [&_.fi-page-main]:min-h-0
        [&_.fi-page-main]:flex-1"
>
    <div
        class="pos-shell"
        x-data="{ paymentOpen: false }"
        @keydown.window.f8.prevent="if ({{ count($cart) > 0 ? 'true' : 'false' }}) paymentOpen = true"
        @keydown.window.escape="if (paymentOpen) paymentOpen = false; else $wire.searchQuery = ''"
        @keydown.window.ctrl-k.prevent="$el.querySelector('#pos-search')?.focus()"
    >
        {{-- Products Panel --}}
        <div class="pos-products-panel">
            {{-- Top Bar --}}
            <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-800 dark:bg-gray-900">
                <div class="relative min-w-0 flex-1">
                    <svg width="16" height="16" class="pos-icon pos-icon-sm pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                    <input
                        id="pos-search"
                        type="search"
                        wire:model.live.debounce.300ms="searchQuery"
                        autofocus
                        placeholder="Cari produk, scan barcode, atau SKU…"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:bg-gray-900"
                    />
                </div>
                <kbd class="hidden shrink-0 items-center gap-1 rounded border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-medium text-gray-400 md:inline-flex dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    <span class="font-semibold">Ctrl</span><span>+</span><span>K</span>
                </kbd>
                <kbd class="hidden shrink-0 items-center gap-1 rounded border border-gray-200 bg-gray-50 px-2 py-1 text-[10px] font-medium text-gray-400 md:inline-flex dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    <span class="font-semibold">F8</span> Bayar
                </kbd>
            </div>

            {{-- Category Pills --}}
            <div class="flex shrink-0 gap-1.5 overflow-x-auto border-b border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
                <button wire:click="$set('activeCategoryId', null)"
                    class="shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition {{ is_null($activeCategoryId) ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    Semua
                </button>
                @foreach($this->categories as $category)
                    <button wire:click="$set('activeCategoryId', {{ $category->id }})" wire:key="cat-{{ $category->id }}"
                        class="shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition {{ $activeCategoryId === $category->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            {{-- Product Grid + Pagination --}}
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div class="pos-product-grid min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-3">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @forelse($this->paginatedProducts as $product)
                        <button wire:click="addToCart({{ $product->id }})" wire:key="product-{{ $product->id }}"
                            class="group relative flex flex-col rounded-lg border border-gray-200 bg-white p-2.5 text-left shadow-sm transition hover:-translate-y-px hover:border-primary-400 hover:shadow-md active:scale-[0.98] dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-600">

                            <div class="mb-2 flex h-16 items-center justify-center overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                @if($product->image_path)
                                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                                @else
                                    <svg width="28" height="28" class="pos-icon text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                                        <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087zm6.163 3.75A.75.75 0 0110 12h4a.75.75 0 010 1.5h-4a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>

                            <span class="text-xs font-medium leading-snug text-gray-900 line-clamp-2 dark:text-gray-100">{{ $product->name }}</span>
                            <span class="mt-1 font-mono text-sm font-bold tabular-nums text-primary-600 dark:text-primary-400">Rp{{ number_format($product->price, 0, ',', '.') }}</span>

                            @if($product->track_stock)
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="inline-block size-1.5 rounded-full {{ $product->is_low_stock ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                                    <span class="text-[10px] font-medium tabular-nums {{ $product->is_low_stock ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $product->stock }} stok</span>
                                </div>
                            @endif

                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-lg bg-primary-600/80 opacity-0 transition-opacity group-hover:opacity-100">
                                <span class="rounded-md bg-white px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-700">+ Tambah</span>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
                            <svg width="48" height="48" class="pos-icon mb-3 text-gray-300 dark:text-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375z" />
                                <path fill-rule="evenodd" d="M3.087 9l.54 9.176A3 3 0 006.62 21h10.757a3 3 0 002.995-2.824L20.913 9H3.087z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Produk tidak ditemukan</p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Ubah kategori atau kata kunci pencarian</p>
                        </div>
                    @endforelse
                    </div>
                </div>

                @if($this->paginatedProducts->hasPages())
                    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
                        <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                            {{ $this->paginatedProducts->firstItem() }}–{{ $this->paginatedProducts->lastItem() }}
                            dari {{ $this->paginatedProducts->total() }} produk
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                wire:click="previousPage('productPage')"
                                @disabled($this->paginatedProducts->onFirstPage())
                                class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold transition enabled:hover:border-primary-400 enabled:hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:enabled:hover:text-primary-400"
                            >
                                ←
                            </button>
                            <span class="min-w-16 text-center text-xs font-semibold tabular-nums text-gray-600 dark:text-gray-300">
                                {{ $this->paginatedProducts->currentPage() }} / {{ $this->paginatedProducts->lastPage() }}
                            </span>
                            <button
                                wire:click="nextPage('productPage')"
                                @disabled(! $this->paginatedProducts->hasMorePages())
                                class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold transition enabled:hover:border-primary-400 enabled:hover:text-primary-600 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:enabled:hover:text-primary-400"
                            >
                                →
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Cart Panel --}}
        <div class="pos-cart-panel">
            {{-- Header --}}
            <div class="pos-cart-header flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <svg width="18" height="18" class="pos-icon text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.96A60.864 60.864 0 005.79 4.358l-.633-2.373a1.5 1.5 0 00-1.45-1.11H2.25z" />
                    </svg>
                    <span class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Pesanan</span>
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-white">{{ count($cart) }}</span>
                </div>
                @if(count($cart) > 0)
                    <button wire:click="clearCart" class="text-xs font-semibold uppercase tracking-wide text-gray-400 transition hover:text-red-500">
                        Hapus
                    </button>
                @endif
            </div>

            {{-- Body: scrollable items + sticky checkout --}}
            <div @class([
                'pos-cart-body min-h-0 flex-1 overflow-hidden',
                'grid grid-rows-[minmax(0,1fr)_auto]' => count($cart) > 0,
                'flex flex-col' => count($cart) === 0,
            ])>
                <div class="pos-cart-items min-h-0 overflow-y-auto overscroll-contain divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($cart as $index => $item)
                    <div wire:key="cart-{{ $item['product_id'] }}" class="group px-4 py-2.5 transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm font-medium text-gray-900 line-clamp-1 dark:text-white">{{ $item['name'] }}</span>
                            <button wire:click="removeFromCart({{ $index }})"
                                class="shrink-0 rounded p-0.5 text-gray-300 opacity-0 transition hover:text-red-500 group-hover:opacity-100 dark:text-gray-600">
                                <svg width="16" height="16" class="pos-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-2 flex items-center gap-1.5">
                            <button wire:click="updateCartItem({{ $index }}, 'quantity', {{ max(1, $item['quantity'] - 1) }})"
                                class="flex size-8 items-center justify-center rounded border border-gray-200 bg-white text-sm font-bold text-gray-600 transition hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">&minus;</button>
                            <span class="flex w-10 items-center justify-center font-mono text-sm font-bold tabular-nums text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                            <button wire:click="updateCartItem({{ $index }}, 'quantity', {{ $item['quantity'] + 1 }})"
                                class="flex size-8 items-center justify-center rounded border border-gray-200 bg-white text-sm font-bold text-gray-600 transition hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">+</button>

                            @php $lineTotal = max(0, $item['price'] * $item['quantity'] - $item['discount']); @endphp
                            <span class="ml-auto font-mono text-sm font-bold tabular-nums text-gray-900 dark:text-white">Rp{{ number_format($lineTotal, 0, ',', '.') }}</span>
                        </div>

                        <div class="mt-1.5 flex items-center gap-2">
                            <span class="font-mono text-[10px] tabular-nums text-gray-400">@Rp{{ number_format($item['price'], 0, ',', '.') }}</span>
                            @if($item['discount'] > 0)
                                <span class="font-mono text-[10px] tabular-nums text-red-500">&minus;Rp{{ number_format($item['discount'], 0, ',', '.') }}</span>
                            @endif
                            <input type="number" wire:change="updateCartItem({{ $index }}, 'discount', $event.target.value)"
                                value="{{ $item['discount'] }}" min="0" placeholder="diskon"
                                class="ml-auto w-16 rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-right font-mono text-[10px] tabular-nums outline-none focus:border-primary-400 focus:ring-1 focus:ring-primary-400/30 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center px-4 py-16 text-gray-300 dark:text-gray-600">
                        <svg width="56" height="56" class="pos-icon mb-3 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.96A60.864 60.864 0 005.79 4.358l-.633-2.373a1.5 1.5 0 00-1.45-1.11H2.25z" />
                        </svg>
                        <p class="text-sm font-semibold text-gray-400 dark:text-gray-500">Pesanan kosong</p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-600">Klik produk untuk menambahkan</p>
                    </div>
                @endforelse
                </div>

                @if(count($cart) > 0)
                    <div class="pos-cart-checkout shrink-0 border-t-2 border-gray-200 bg-white px-4 py-3 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] dark:border-gray-800 dark:bg-gray-900 dark:shadow-[0_-8px_24px_rgba(0,0,0,0.45)]" wire:loading.class="pointer-events-none opacity-60" wire:target="processPayment">
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Subtotal</span>
                            <span class="font-mono font-medium tabular-nums text-gray-700 dark:text-gray-300">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($discountTotal > 0)
                            <div class="flex justify-between text-xs text-red-500">
                                <span>Diskon</span>
                                <span class="font-mono font-medium tabular-nums">&minus;Rp{{ number_format($discountTotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($taxTotal > 0)
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Pajak</span>
                                <span class="font-mono font-medium tabular-nums text-gray-700 dark:text-gray-300">Rp{{ number_format($taxTotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-dashed border-gray-300 pt-2 dark:border-gray-700">
                            <span class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Total</span>
                            <span class="font-mono text-xl font-extrabold tabular-nums text-primary-600 dark:text-primary-400">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <select wire:model.live="selectedCustomerId"
                            class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Walk-in</option>
                            @foreach($this->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="paymentMethodId"
                            class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach($this->paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-2.5 grid grid-cols-6 gap-1">
                        @php
                            $quickAmounts = [
                                'Pas' => $grandTotal,
                                '+5rb' => $grandTotal + 5000,
                                '+10rb' => $grandTotal + 10000,
                                '+20rb' => $grandTotal + 20000,
                                '+50rb' => $grandTotal + 50000,
                                '+100rb' => $grandTotal + 100000,
                            ];
                        @endphp
                        @foreach($quickAmounts as $label => $amount)
                            <button wire:click="quickAmount({{ (int) $amount }})"
                                class="rounded border border-gray-200 bg-gray-50 px-0.5 py-1.5 text-[9px] font-semibold uppercase tracking-wide transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-primary-900/30 dark:hover:text-primary-400">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 font-mono text-sm font-semibold text-gray-400">Rp</span>
                        <input type="number" wire:model.live="paidAmount" step="1" placeholder="0"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-8 pr-3 text-right font-mono text-lg font-bold tabular-nums outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>

                    @if($paidAmount >= $grandTotal && $grandTotal > 0)
                        <div class="mt-2 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 dark:bg-emerald-950/40">
                            <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Kembali</span>
                            <span class="font-mono text-lg font-extrabold tabular-nums text-emerald-700 dark:text-emerald-400">Rp{{ number_format($changeAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <input type="text" wire:model.live="note" placeholder="Catatan (opsional)"
                        class="mt-2 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-400/30 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500" />

                    <div class="mt-2.5 grid grid-cols-5 gap-2">
                        <button wire:click="clearCart"
                            class="col-span-1 rounded-lg border border-gray-200 py-2.5 text-[10px] font-bold uppercase tracking-wide text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800">
                            Batal
                        </button>
                        <button wire:click="processPayment" @disabled($processing)
                            class="col-span-4 rounded-lg bg-primary-600 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-primary-500 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="processPayment">Bayar Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
                            <span wire:loading wire:target="processPayment" class="flex items-center justify-center gap-2">
                                <svg width="16" height="16" class="pos-icon animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memproses…
                            </span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* ── Viewport lock ── */
            .fi-main-ctn:has(.pos-page) {
                height: calc(100dvh - 4rem);
                max-height: calc(100dvh - 4rem);
                overflow: hidden;
            }

            .fi-main:has(.pos-page) {
                height: 100%;
                max-height: 100%;
                min-height: 0;
                max-width: none !important;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                padding: 0.5rem !important;
            }

            .pos-page,
            .pos-page .fi-page-header-main-ctn,
            .pos-page .fi-page-main,
            .pos-page .fi-page-content {
                height: 100%;
                min-height: 0;
                overflow: hidden;
            }

            /* ── Responsive split layout ── */
            .pos-page .pos-shell {
                display: grid;
                height: 100%;
                min-height: 0;
                max-height: 100%;
                overflow: hidden;
                gap: 0.625rem;
                /* Mobile / tablet: stack — products top, cart bottom */
                grid-template-columns: minmax(0, 1fr);
                grid-template-rows: minmax(0, 1fr) minmax(11rem, 38vh);
            }

            @media (min-width: 1024px) {
                .pos-page .pos-shell {
                    /* Desktop: side-by-side with fluid cart width */
                    grid-template-columns: minmax(0, 1fr) clamp(17rem, 26vw, 24rem);
                    grid-template-rows: minmax(0, 1fr);
                    gap: 0.75rem;
                }
            }

            @media (min-width: 1536px) {
                .pos-page .pos-shell {
                    grid-template-columns: minmax(0, 1fr) clamp(19rem, 22vw, 26rem);
                }
            }

            /* ── Panel shells (independent, no overlap) ── */
            .pos-page .pos-products-panel,
            .pos-page .pos-cart-panel {
                display: flex;
                flex-direction: column;
                min-width: 0;
                min-height: 0;
                max-width: 100%;
                overflow: hidden;
                border-radius: 0.75rem;
                border: 1px solid rgb(229 231 235);
                background: rgb(249 250 251);
            }

            .dark .pos-page .pos-products-panel,
            .dark .pos-page .pos-cart-panel {
                border-color: rgb(31 41 55);
                background: rgb(3 7 18);
            }

            .pos-page .pos-cart-panel {
                background: rgb(255 255 255);
            }

            .dark .pos-page .pos-cart-panel {
                background: rgb(17 24 39);
            }

            /* ── Products scroll area ── */
            .pos-page .pos-product-grid {
                flex: 1 1 0%;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
            }

            /* ── Cart internals ── */
            .pos-page .pos-cart-body {
                min-height: 0;
                flex: 1 1 0%;
            }

            .pos-page .pos-cart-items {
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior: contain;
                -webkit-overflow-scrolling: touch;
            }

            .pos-page .pos-cart-checkout {
                flex-shrink: 0;
                z-index: 10;
            }

            /* ── SVG safeguards ── */
            .pos-shell svg.pos-icon {
                flex-shrink: 0;
                max-width: none;
            }

            .pos-shell svg.pos-icon-sm {
                width: 1rem;
                height: 1rem;
            }

            .pos-shell svg.pos-icon:not([width]) {
                width: 1.25rem;
                height: 1.25rem;
            }
        </style>
    @endpush
</x-filament-panels::page>
