@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
        background-color: #E9F1FA !important;
        color: #0F172A;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-shell-card {
        background: #FFFFFF;
        border: 4px solid rgba(255, 255, 255, 0.7);
        border-radius: 26px;
        padding: 2.75rem 2.5rem;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06);
    }

    .login-brand-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%);
        color: #FFFFFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
    }

    .login-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: #64748B;
        margin-bottom: 0.45rem;
    }

    .login-input-wrap {
        position: relative;
    }

    .login-input-wrap i {
        position: absolute;
        top: 50%;
        left: 1rem;
        transform: translateY(-50%);
        color: #94A3B8;
        font-size: 1.05rem;
    }

    .login-input {
        background-color: #F8FAFC !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 14px !important;
        padding: 0.8rem 1rem 0.8rem 2.75rem !important;
        font-size: 0.92rem !important;
        color: #0F172A !important;
        box-shadow: none !important;
        transition: all 0.15s ease;
    }

    .login-input:focus {
        background-color: #FFFFFF !important;
        border-color: #38BDF8 !important;
    }

    .btn-login-submit {
        background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        border: none;
        border-radius: 14px;
        padding: 0.85rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: #FFFFFF;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.22);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .btn-login-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.3);
        color: #FFFFFF;
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4" style="max-width: 430px;">
            <div class="login-shell-card">
                <div class="text-center mb-4">
                    <div class="login-brand-icon mb-3">
                        <i class="bi bi-grid-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-1">SME Hardware POS</h4>
                    <small class="text-muted">Sign in to access your terminal</small>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger border-0 rounded-4 py-2 px-3 small fw-semibold mb-3 text-center">
                        <ul class="mb-0 list-unstyled">
                            @foreach ($errors->all() as $error)
                                <li><i class="bi bi-exclamation-circle-fill me-1"></i>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label login-label">USERNAME</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" name="username" class="form-control login-input" placeholder="Enter your username" value="{{ old('username') }}" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label login-label">PASSWORD</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" class="form-control login-input" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login-submit w-100">
                        Secure Login <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection