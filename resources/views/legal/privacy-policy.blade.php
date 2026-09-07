@extends('layouts.base')

@section('title', 'Kebijakan Privasi Ta\'lim')

@section('body')
<div class="login-shell py-4 py-md-5">
    <div class="container" style="max-width: 760px;">
        <div class="madani-card p-4 p-md-5">
            <p class="stat-label mb-1">Ta'lim · MTsN 11 Majalengka</p>
            <h1 class="h3 mb-2">Kebijakan Privasi</h1>
            <p class="text-secondary small mb-4">Terakhir diperbarui: {{ $updatedAt }}</p>

            <section class="mb-4">
                <p class="mb-0">
                    Ta'lim adalah aplikasi administrasi dan layanan informasi madrasah untuk
                    <strong>guru/pegawai</strong> dan <strong>siswa</strong> MTsN 11 Majalengka.
                    Aplikasi dikembangkan oleh ATA DevLabs. Backend utama layanan adalah
                    <strong>MADANI</strong> (<a href="https://madani.mtsn11majalengka.sch.id">madani.mtsn11majalengka.sch.id</a>).
                    Akses diberikan melalui akun resmi yang dikelola admin madrasah.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Data yang dikumpulkan atau diproses</h2>
                <ul class="mb-0">
                    <li>Identitas guru: nama, NIP/username, jabatan, data kepegawaian, foto profil (jika diunggah).</li>
                    <li>Identitas siswa: nama, NISN/NIS, data biodata EMIS, orang tua/wali, alamat, koordinat lokasi rumah (jika diisi), foto yang dikelola madrasah.</li>
                    <li>Kontak: nomor telepon, email, alamat.</li>
                    <li>Data akademik/administratif: jadwal, rombel, jurnal, kelengkapan biodata, pernyataan, kartu e-pelajar, pengumuman.</li>
                    <li>Data perangkat/aplikasi: token sesi, token notifikasi (FCM), preferensi lokal, catatan/pengingat kalender di perangkat.</li>
                    <li>Izin perangkat yang diminta saat dibutuhkan: notifikasi, lokasi (untuk pin alamat), biometrik (opsional untuk mempermudah login di perangkat pengguna).</li>
                </ul>
            </section>

            <section class="mb-4">
                <h2 class="h5">Penggunaan data</h2>
                <ul class="mb-0">
                    <li>Autentikasi, keamanan akun, dan pembatasan akses sesuai peran (guru/siswa/admin).</li>
                    <li>Menampilkan dan memperbarui profil, jadwal, biodata, pengumuman, serta layanan administrasi terkait.</li>
                    <li>Mengirim notifikasi push/pengingat yang relevan (jadwal, pengumuman, pengingat).</li>
                    <li>Membuka pintasan ke layanan pendukung madrasah atau layanan eksternal yang dipilih pengguna.</li>
                    <li>Meningkatkan stabilitas, keamanan, dan kualitas layanan.</li>
                </ul>
            </section>

            <section class="mb-4">
                <h2 class="h5">Penyimpanan dan keamanan</h2>
                <p class="mb-0">
                    Data akun dan administrasi disimpan pada server yang digunakan madrasah (MADANI dan layanan terkait).
                    Komunikasi memakai HTTPS. Token sesi dan preferensi tertentu dapat disimpan lokal di perangkat.
                    Kami menerapkan langkah teknis yang wajar untuk melindungi data. Pengguna wajib menjaga kerahasiaan akun, perangkat, dan kata sandi.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Berbagi data</h2>
                <p class="mb-2">
                    Kami tidak menjual data pribadi. Data dapat diproses oleh layanan yang diperlukan agar fitur berjalan, misalnya:
                </p>
                <ul class="mb-2">
                    <li>MADANI sebagai sistem inti data madrasah.</li>
                    <li>Layanan pendukung (misalnya SimpatiSans untuk sebagian fitur guru, e-Lapkin, RDM, CBT, atau menu dinamis lain).</li>
                    <li>Penyedia notifikasi push (Firebase Cloud Messaging) untuk pengiriman notifikasi.</li>
                    <li>Layanan peta/geocoding pihak ketiga saat pengguna mengatur titik alamat.</li>
                </ul>
                <p class="mb-0">
                    Saat pengguna membuka layanan eksternal (WebView/browser/aplikasi lain), kebijakan privasi penyedia tersebut juga dapat berlaku.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Notifikasi, lokasi, biometrik, foto</h2>
                <ul class="mb-0">
                    <li><strong>Notifikasi:</strong> dapat diminta untuk pengumuman dan pengingat; dapat dinonaktifkan di pengaturan perangkat/aplikasi.</li>
                    <li><strong>Lokasi:</strong> diminta hanya untuk membantu menandai alamat (GPS/pin); bukan pelacakan berkelanjutan.</li>
                    <li><strong>Biometrik:</strong> opsional, diproses di perangkat untuk mempermudah login; tidak dikirim sebagai template biometrik ke server kami.</li>
                    <li><strong>Foto siswa:</strong> dikelola madrasah melalui MADANI; siswa tidak mengunggah/mengganti foto melalui API Ta'lim.</li>
                </ul>
            </section>

            <section class="mb-4">
                <h2 class="h5">Retensi dan penghapusan</h2>
                <p class="mb-0">
                    Data disimpan selama diperlukan untuk administrasi, keamanan, dan operasional madrasah.
                    Permintaan koreksi atau penghapusan dapat diajukan kepada admin madrasah atau pengembang melalui kontak di bawah.
                    Sebagian data dapat tetap disimpan jika diwajibkan kebijakan sekolah, audit, atau hukum yang berlaku.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Pengguna siswa</h2>
                <p class="mb-0">
                    Fitur siswa ditujukan bagi peserta didik MTsN 11 Majalengka dengan akun yang dibuat/dikelola sekolah,
                    bukan sebagai layanan konsumen umum untuk anak di luar konteks sekolah.
                    Penggunaan mengikuti tata kelola data pendidikan madrasah.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Perubahan kebijakan</h2>
                <p class="mb-0">
                    Kebijakan ini dapat diperbarui seiring perubahan fitur atau ketentuan hukum.
                    Versi terbaru selalu ditampilkan di halaman ini beserta tanggal pembaruan.
                </p>
            </section>

            <section class="mb-4">
                <h2 class="h5">Kontak</h2>
                <ul class="mb-0">
                    <li>Email pengembang: <a href="mailto:atadevlabs@gmail.com">atadevlabs@gmail.com</a></li>
                    <li>Madrasah: MTsN 11 Majalengka</li>
                    <li>Website: <a href="https://mtsn11majalengka.sch.id">https://mtsn11majalengka.sch.id</a></li>
                    <li>MADANI: <a href="https://madani.mtsn11majalengka.sch.id">https://madani.mtsn11majalengka.sch.id</a></li>
                </ul>
            </section>

            <p class="form-text mb-0">
                © {{ now()->year }} MTsN 11 Majalengka · Dikembangkan oleh ATA DevLabs
            </p>
        </div>
    </div>
</div>
@endsection
