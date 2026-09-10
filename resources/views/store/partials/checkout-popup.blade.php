@php
    $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
    $inside = (float) ($siteSettings['shipping_inside_dhaka'] ?? 60);
    $outside = (float) ($siteSettings['shipping_outside_dhaka'] ?? 120);
@endphp

<div class="quick-checkout-sheet-inner">
    <div class="quick-checkout-head">
        <div>
            <h2>অর্ডার করতে আপনার তথ্য দিন</h2>
            <p>কার্টের পণ্য যাচাই করে তথ্য পূরণ করুন</p>
        </div>
        <button type="button" class="quick-checkout-close" data-quick-checkout-close aria-label="বন্ধ করুন">×</button>
    </div>

    <div class="quick-checkout-errors" data-checkout-errors hidden></div>

    <form action="{{ route('checkout.store') }}" method="post" class="quick-checkout-form" data-quick-checkout-form data-subtotal="{{ $subtotal }}">
        @csrf
        <input type="hidden" name="event_id" value="">

        <div class="quick-checkout-layout">
            <div class="quick-checkout-customer">
                <div class="quick-panel-title">
                    <h3>আপনার তথ্য</h3>
                    <span>সব প্রয়োজনীয় তথ্য দিন</span>
                </div>

                <div class="quick-checkout-fields">
                    <label class="form-icon-row">
                        <span>👤</span>
                        <input name="customer_name" value="{{ old('customer_name') }}" placeholder="আপনার নাম" autocomplete="name" required>
                    </label>
                    <label class="form-icon-row">
                        <span>☎</span>
                        <input name="phone" value="{{ old('phone') }}" placeholder="আপনার ফোন নম্বর" inputmode="tel" autocomplete="tel" required>
                    </label>
                    <label class="form-icon-row">
                        <span>⌖</span>
                        <textarea name="address" placeholder="আপনার সম্পূর্ণ ঠিকানা" required>{{ old('address') }}</textarea>
                    </label>
                    <label class="form-icon-row optional-field">
                        <span>✉</span>
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="ইমেইল (ঐচ্ছিক)" autocomplete="email">
                    </label>
                </div>

                <h3 class="shipping-title">ডেলিভারি এলাকা</h3>
                <label class="shipping-choice">
                    <span><input type="radio" name="shipping_area" value="inside" checked data-charge="{{ $inside }}"> ঢাকা সিটির মধ্যে</span>
                    <b>৳{{ number_format($inside, 2) }}</b>
                </label>
                <label class="shipping-choice">
                    <span><input type="radio" name="shipping_area" value="outside" data-charge="{{ $outside }}"> ঢাকা সিটির বাইরে</span>
                    <b>৳{{ number_format($outside, 2) }}</b>
                </label>

                <textarea class="field quick-note" name="note" placeholder="অর্ডার নোট (ঐচ্ছিক)">{{ old('note') }}</textarea>
            </div>

            <div class="quick-checkout-order-summary">
                <div class="quick-cart-title">
                    <h3>আপনার কার্ট</h3>
                    <span>{{ collect($cart)->sum('quantity') }} টি পণ্য</span>
                </div>

                <div class="quick-cart-items">
                    @foreach($cart as $line)
                        <div class="quick-cart-item">
                            <div class="quick-cart-image">
                                @if(!empty($line['image']))
                                    <img src="{{ asset('storage/'.$line['image']) }}" alt="{{ $line['name'] }}">
                                @else
                                    <div class="image-placeholder">ছবি</div>
                                @endif
                            </div>
                            <div class="quick-cart-copy">
                                <b>{{ $line['name'] }}</b>
                                <small>{{ $line['sku'] }}</small>
                                <span>৳{{ number_format((float) $line['price'], 2) }} × {{ $line['quantity'] }}</span>
                            </div>
                            <strong>৳{{ number_format($line['price'] * $line['quantity'], 2) }}</strong>
                        </div>
                    @endforeach
                </div>

                <div class="quick-checkout-totals">
                    <div><span>সাবটোটাল</span><strong>৳{{ number_format($subtotal, 2) }}</strong></div>
                    <div><span>ডেলিভারি</span><strong data-shipping-amount>৳{{ number_format($inside, 2) }}</strong></div>
                    <div class="grand"><span>মোট</span><strong data-grand-total>৳{{ number_format($subtotal + $inside, 2) }}</strong></div>
                </div>

                <button class="btn primary full quick-checkout-submit" type="submit">
                    🛒 অর্ডার সম্পন্ন করুন - ৳<span data-button-total>{{ number_format($subtotal + $inside, 0) }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
