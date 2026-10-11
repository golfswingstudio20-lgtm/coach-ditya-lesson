@extends('admin.layouts.panel')

@section('title', 'Tambah customer')

@section('content')
    <section class="page-head">
        <div>
            <h1>Tambah customer</h1>
            <p>Buatkan akun online untuk customer. Setelah dibuat, customer bisa login dan booking sendiri.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.customers.index', ['tab' => 'member']) }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    @if ($errors->any())
        <div class="error-box">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.customers.store') }}" class="form-card" style="max-width: 720px">
        @csrf

        <div class="field-row">
            <div class="field">
                <label for="cName">Nama lengkap</label>
                <input type="text" name="name" id="cName" class="input" required maxlength="255" value="{{ old('name') }}" autocomplete="off">
            </div>
            <div class="field">
                <label for="cEmail">Email</label>
                <input type="email" name="email" id="cEmail" class="input" required maxlength="255" value="{{ old('email') }}" autocomplete="off">
                <p class="field-hint">Dipakai customer untuk login.</p>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="cPassword">Password</label>
                <input type="text" name="password" id="cPassword" class="input" required minlength="8" autocomplete="new-password"
                       style="font-family: Consolas, Menlo, monospace">
                <p class="field-hint">Minimal 8 karakter, berisi huruf dan angka. Password akan ditampilkan sekali setelah akun dibuat.</p>
            </div>
            <div class="field">
                <label for="cPassword2">Ulangi password</label>
                <input type="text" name="password_confirmation" id="cPassword2" class="input" required minlength="8" autocomplete="new-password"
                       style="font-family: Consolas, Menlo, monospace">
                <button type="button" class="btn btn-outline" id="generatePassword" style="margin-top: 8px">⟳ Buat password otomatis</button>
            </div>
        </div>

        <input type="hidden" name="verified" value="0">
        <label class="checkbox" style="height: auto; padding: 12px; align-items: flex-start">
            <input type="checkbox" name="verified" value="1" @checked(old('verified', '1') === '1') style="margin-top: 2px">
            <span>
                <strong style="display: block; color: var(--d-text, #17261d)">Tandai email sudah terverifikasi</strong>
                <small style="color: var(--text-muted)">Customer bisa langsung booking. Jika tidak dicentang, customer harus membuka link verifikasi yang dikirim ke emailnya.</small>
            </span>
        </label>

        <div class="form-actions">
            <a href="{{ route('admin.customers.index', ['tab' => 'member']) }}" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-primary">Buat akun customer</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Password acak 10 karakter (tanpa huruf yang mirip seperti O/0, l/1)
        document.getElementById('generatePassword').addEventListener('click', () => {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            const random = new Uint32Array(10);
            crypto.getRandomValues(random);
            const password = Array.from(random, (n) => chars[n % chars.length]).join('');

            document.getElementById('cPassword').value = password;
            document.getElementById('cPassword2').value = password;
        });
    </script>
@endpush
