<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Akun Test Siswa CBT Julia

Seeder `StudentSeeder` menyediakan akun siswa yang bisa langsung digunakan di `/student/login` setelah menjalankan `php artisan db:seed`:

| Username | Password |
| --- | --- |
| siswa001 | password |
| siswa002 | password |
| siswa003 | password |

## Perbaikan Cache Laravel di Windows/XAMPP

Project ini menggunakan Laravel 12 dan membutuhkan **PHP 8.2 atau lebih baru**. Jika XAMPP masih memakai PHP lama, Laravel dapat gagal saat merender exception dan menampilkan error seperti `syntax error, unexpected token "="` dari file vendor Laravel. Jangan edit file `vendor`; ganti PHP XAMPP/CLI ke PHP 8.2+.

Jika muncul error seperti berikut:

```text
rename(C:\xampp\htdocs\cbtespero\bootstrap\cache\ser4B1F.tmp,C:\xampp\htdocs\cbtespero\bootstrap\cache/services.php): Access is denied (code: 5)
```

lakukan langkah berikut dari Windows:

1. Stop semua proses `php artisan serve`.
2. Stop Apache dari XAMPP Control Panel.
3. Pastikan folder berikut ada dan bisa dibaca/ditulis oleh user Windows yang menjalankan PHP/Apache:
   - `bootstrap/cache`
   - `storage`
   - `storage/framework/views`
   - `storage/framework/cache`
   - `storage/framework/sessions`
4. Hapus semua file `bootstrap/cache/*.php`.
5. Hapus compiled views di `storage/framework/views` kecuali file `.gitignore`.
6. Jalankan:

```bat
php scripts\repair-windows-xampp.php
php artisan optimize:clear
```

Script `scripts/repair-windows-xampp.php` sengaja tidak memuat Laravel/vendor, sehingga bisa dipakai untuk membersihkan cache rusak terlebih dahulu. Script ini juga memeriksa versi PHP CLI, membuat folder cache/storage yang hilang, memeriksa permission baca/tulis, menghapus cache bootstrap dan compiled views, lalu menjalankan `php artisan optimize:clear` jika `vendor/autoload.php` tersedia.

Jika script melaporkan PHP lebih lama dari 8.2, samakan versi PHP CLI dan Apache XAMPP ke **PHP 8.2+** sebelum menjalankan ulang perintah di atas.
