@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4 fw-bold">SME Hardware POS</h3>
                    
                    @if ($errors->any())
                        <div class="alert alert-danger p-2">
                            <ul class="mb-0 list-unstyled text-center">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">USERNAME</label>
                            <input type="text" name="username" class="form-control form-control-lg" value="{{ old('username') }}" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">PASSWORD</label>
                            <input type="password" name="password" class="form-control form-control-lg" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Secure Login</button>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection