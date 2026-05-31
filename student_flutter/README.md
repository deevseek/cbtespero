# Espero CBT Student Flutter

Frontend Android khusus siswa. Aplikasi ini memakai API Laravel `/api/v1`, menyimpan token Bearer secara lokal, melakukan auto-save jawaban ke server, mengantre jawaban saat offline, dan mengirim cheating log untuk lifecycle/background/focus/fullscreen events. Proteksi Android native menyalakan `FLAG_SECURE`, fullscreen immersive, orientasi terkunci, serta hook lock-task opsional untuk perangkat sekolah/MDM.

Jalankan dengan `flutter pub get`, set `--dart-define=API_BASE_URL=https://domain-sekolah.test`, lalu `flutter build apk`.
