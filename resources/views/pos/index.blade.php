@extends('layouts.admin')

@section('content')
<div class="row h-100">
    <div class="col-md-8 h-100 d-flex flex-column">
        <h2 class="fw-bold mb-3"><i class="bi bi-cart-check-fill me-2"></i> POS Terminal</h2>
        
        @if(session('success'))
            <div class="alert alert-success fw-bold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger fw-bold">{{ session('error') }}</div>
        @endif

        <div class="row g-3 overflow-auto flex-grow-1 pb-3">
            @foreach($products as $product)
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 cursor-pointer" style="cursor: pointer;" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->retail_selling_price }})">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold mb-1">{{ $product->name }}</h6>
                        <small class="text-muted d-block mb-2">{{ $product->sku }}</small>
                        <span class="badge bg-success fs-6 mt-auto">{{ $product->formatted_retail_price }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-4 h-100 border-start bg-white shadow-sm p-0 d-flex flex-column">
        <div class="p-3 bg-dark text-white fw-bold">
            Current Order
        </div>
        
        <div class="flex-grow-1 p-3 overflow-auto" id="cart-items">
        </div>

        <div class="p-3 border-top bg-light">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold">Total</span>
                <span class="fw-bold fs-4 text-primary" id="cart-total">RM 0.00</span>
            </div>
            <form action="{{ route('pos.checkout') }}" method="POST">
                @csrf
                <input type="hidden" name="cart_data" id="cart_data_input">
                <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-2">Process Payment</button>
            </form>
            <button class="btn btn-outline-danger w-100 fw-bold" onclick="clearCart()">Clear Cart</button>
        </div>
    </div>
</div>

<script>
    let cart = [];

    function addToCart(id, name, price) {
        let existing = cart.find(item => item.id === id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({id: id, name: name, price: parseFloat(price), qty: 1});
        }
        updateCartUI();
    }

    function updateCartUI() {
        let container = document.getElementById('cart-items');
        let totalDisplay = document.getElementById('cart-total');
        let total = 0;
        
        container.innerHTML = '';
        
        cart.forEach((item, index) => {
            let subtotal = item.price * item.qty;
            total += subtotal;
            
            container.innerHTML += `
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div>
                        <h6 class="mb-0 fw-bold">${item.name}</h6>
                        <small class="text-muted">RM ${item.price.toFixed(2)} x ${item.qty}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold mb-1">RM ${subtotal.toFixed(2)}</div>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeFromCart(${index})">X</button>
                    </div>
                </div>
            `;
        });
        
        totalDisplay.innerText = 'RM ' + total.toFixed(2);
        document.getElementById('cart_data_input').value = JSON.stringify(cart);
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    function clearCart() {
        cart = [];
        updateCartUI();
    }
</script>
@endsection