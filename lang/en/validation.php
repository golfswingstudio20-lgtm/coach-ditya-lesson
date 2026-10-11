<?php

// Pesan validasi password dalam Bahasa Indonesia (aplikasi memakai locale "en").
// Kunci lain tetap memakai pesan bawaan Laravel.
return [
    'confirmed' => 'Ulangi :attribute tidak sama.',
    'min' => [
        'string' => ':Attribute minimal :min karakter.',
    ],
    'password' => [
        'letters'       => 'Password harus berisi minimal satu huruf.',
        'mixed'         => 'Password harus berisi huruf besar dan huruf kecil.',
        'numbers'       => 'Password harus berisi minimal satu angka.',
        'symbols'       => 'Password harus berisi minimal satu simbol.',
        'uncompromised' => 'Password ini pernah bocor di internet dan mudah ditebak. Silakan pilih password lain.',
    ],
    'attributes' => [
        'password'         => 'password',
        'current_password' => 'password saat ini',
        'name'             => 'nama',
    ],
];
