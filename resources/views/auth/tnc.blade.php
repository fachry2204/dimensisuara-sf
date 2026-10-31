@extends('layouts.guest')
@section('title')
Terms and Conditions
@endsection
@section('content')
<div class="mx-3 mx-lg-0">
    <div class="card my-5 col-xl-9 col-xxl-8 mx-auto rounded-4 overflow-hidden border-3 p-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card-body text-center">
                    <a href="{{ url('/') }}">
                        <img src="{{ app_logo('build/images/logo-soundfresh-full-white.webp') }}" class="mb-4" width="145" alt="{{ app_name() }} Logo">
                    </a>
                    <div class="text-justify">
                        <h1 class="text-center mb-5">SYARAT DAN KETENTUAN PENGGUNAAN LAYANAN PENERBIT MUSIK (MUSIC PUBLISHER) {{ company_name() }} ({{ app_name() }})</h1>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PENGANTAR</h5>
                        <div>
                            <p>Selamat datang di layanan Penerbit Musik (Music Publisher) yang disediakan oleh {{ company_name() }} melalui platform {{ app_name() }}. Layanan ini dirancang untuk membantu para
                                pencipta, pemegang hak cipta, dan pihak terkait dalam mengelola, mendistribusikan, dan
                                memonetisasi karya musik mereka secara efektif dan profesional.</p>
                            <p>Sebagai penerbit musik, {{ company_name() }} bertanggung jawab untuk memastikan
                                bahwa karya musik Anda terlindungi hak ciptanya, serta memberikan peluang distribusi yang
                                luas dan pembayaran royalti yang adil. Layanan kami memungkinkan Anda untuk mengelola hak
                                cipta secara efisien melalui distribusi digital, pengumpulan royalti, serta pemberian
                                lisensi untuk penggunaan musik Anda di berbagai platform dan media.</p>
                            <p>Dengan mengakses dan menggunakan layanan Penerbit Musik kami, Anda setuju untuk terikat
                                dengan syarat dan ketentuan yang tercantum dalam dokumen ini. Penerimaan syarat dan
                                ketentuan ini berlaku sebagai kesepakatan dan perjanjian yang mengikat secara hukum antara
                                Anda (sebagai pengguna) dan {{ company_name() }}, berdasarkan peraturan
                                perundang-undangan yang berlaku di Republik Indonesia, termasuk tetapi tidak terbatas pada
                                Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan Data Pribadi, Undang-Undang Nomor 1
                                Tahun 2024 tentang Informasi dan Transaksi Elektronik, dan Peraturan Pemerintah Nomor 71
                                Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik</p>
                            <p>Layanan Penerbit Musik kami bertujuan untuk memberikan solusi menyeluruh bagi para pencipta
                                dan pemegang hak cipta musik. Tujuan utama layanan ini adalah:</p>
                            <ul>
                                <li>Pengelolaan Hak Cipta: Mengelola hak cipta Anda atas karya musik untuk memastikan bahwa
                                    hak-hak ekonomi dan moral terlindungi dengan baik.</li>
                                <li>Monetisasi Musik: Memastikan bahwa karya Anda dimonetisasi secara maksimal melalui
                                    royalti yang diperoleh dari berbagai saluran distribusi dan penggunaan lisensi.</li>
                                <li>Distribusi Musik Digital dan Agregasi: Mengoptimalkan pengelolaan dan distribusi musik
                                    Anda melalui layanan distribusi dan agregator yang tersedia di platform kami,
                                    {{ app_name() }}.</li>
                            </ul>
                            <p>Sebagai bagian dari layanan Penerbit Musik, kami menawarkan layanan distribusi dan agregator
                                yang memungkinkan Anda untuk mendistribusikan karya musik Anda dengan mudah. Layanan ini
                                meliputi: Distribusi Musik Digital melalui Agregator: Kami menyediakan platform distribusi
                                dan agregator yang memungkinkan Anda mendistribusikan karya musik ke berbagai platform
                                streaming digital global. Melalui {{ app_name() }}, Anda dapat mengelola dan mendistribusikan
                                musik Anda dengan cepat dan mudah ke ratusan platform streaming dan layanan musik lainnya.
                            </p>
                            <p>Dengan menggunakan layanan distribusi kami, Anda dapat memastikan bahwa musik Anda tersedia
                                di berbagai platform tanpa perlu melakukan perjanjian individual dengan masing-masing
                                layanan.</p>
                            <p>Pengelolaan Lisensi dan Sinkronisasi: Selain distribusi, kami juga mengelola hak-hak lisensi
                                Anda untuk penggunaan karya dalam film, iklan, video klip, dan media lainnya. Kami
                                memastikan Anda mendapatkan royalti yang pantas dari setiap penggunaan lisensi yang
                                dilakukan.</p>
                            <p>Pengumpulan Royalti: Kami mengumpulkan royalti dari berbagai saluran yang dihasilkan oleh
                                penggunaan karya Anda di platform digital, siaran, penggandaan fisik, dan penggunaan
                                lainnya. Semua royalti yang diterima akan didistribusikan secara adil dan transparan kepada
                                Anda.</p>
                            <p>Perlindungan Hak Cipta dan Penyelesaian Klaim: Kami berkomitmen untuk melindungi hak cipta
                                Anda dan menangani masalah pelanggaran hak cipta yang mungkin terjadi. Jika ada klaim
                                pelanggaran, kami akan bertindak sesuai prosedur hukum yang berlaku untuk melindungi karya
                                Anda.</p>
                            <p>Dengan menggunakan layanan Penerbit Musik kami, Anda menyetujui seluruh syarat dan ketentuan
                                yang tercantum dalam dokumen ini. Sebagai pengguna, Anda bertanggung jawab untuk memastikan
                                bahwa Anda memiliki hak atau izin untuk mengelola dan mendistribusikan musik yang diunggah
                                melalui platform kami.</p>
                            <p>Penerimaan syarat dan ketentuan ini juga berlaku sebagai kesepakatan dan perjanjian yang
                                mengikat secara hukum, sesuai dengan peraturan perundang-undangan yang berlaku di Republik
                                Indonesia, dan akan dilaksanakan sesuai dengan hukum yang berlaku di negara ini, termasuk
                                tetapi tidak terbatas pada ketentuan dalam Undang-Undang Hak Cipta dan Undang-Undang
                                Perlindungan Data Pribadi, serta regulasi yang berkaitan dengan transaksi elektronik dan
                                distribusi digital.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">DEFINISI</h5>
                        <div>
                            <p>Untuk keperluan Syarat dan Ketentuan Pengguna ini, istilah-istilah yang digunakan memiliki
                                arti sebagai berikut:</p>
                            <ol>
                                <li><strong>Platform</strong>: Situs web dan aplikasi yang dikelola oleh <strong>{{ company_name() }}</strong>, termasuk tetapi tidak terbatas pada
                                    <strong>{{ app_name() }}</strong>, yang menyediakan layanan distribusi digital dan
                                    penerbitan musik kepada pengguna.</li>
                                <li><strong>Pengguna</strong>: Individu atau entitas yang mendaftar untuk menggunakan
                                    layanan yang disediakan oleh <strong>{{ app_name() }}</strong> dan mengunggah karya cipta
                                    musik mereka untuk dikelola, didistribusikan, dan dimonetisasi oleh <strong>{{ company_name() }}</strong>.</li>
                                <li><strong>Penerbit Musik (Music Publisher)</strong>: Pihak yang bertanggung jawab untuk
                                    administrasi dan pengelolaan hak cipta atas karya musik yang dimiliki oleh
                                    <strong>Pencipta</strong> atau <strong>Pemegang Hak Cipta</strong>. Dalam hal ini,
                                    <strong>{{ company_name() }}</strong> bertindak sebagai <strong>Penerbit
                                        Musik</strong> untuk karya cipta yang dikelola melalui <strong>{{ app_name() }}</strong>.
                                </li>
                                <li><strong>Karya Cipta Lagu</strong>: Setiap ciptaan yang dihasilkan oleh
                                    <strong>Pencipta</strong> dalam bentuk komposisi musik, baik dalam bentuk lagu dengan
                                    atau tanpa lirik, yang dilindungi oleh <strong>Hak Cipta</strong>.</li>
                                <li><strong>Pencipta</strong>: Individu atau kelompok yang menghasilkan karya cipta lagu.
                                    Pencipta memiliki hak moral dan hak ekonomi atas karya cipta tersebut.</li>
                                <li><strong>Pemegang Hak Cipta</strong>: Pihak yang memiliki hak legal atas karya cipta,
                                    yang bisa merupakan <strong>Pencipta</strong> atau pihak yang memperoleh hak cipta
                                    tersebut melalui pengalihan hak atau transaksi lainnya.</li>
                                <li><strong>Hak Cipta</strong>: Hak eksklusif yang dimiliki oleh <strong>Pencipta</strong>
                                    atas karya cipta mereka, yang timbul otomatis setelah karya tersebut diwujudkan dalam
                                    bentuk nyata, sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</li>
                                <li><strong>Royalti</strong>: Pembayaran yang diterima oleh <strong>Pencipta</strong> atau
                                    <strong>Pemegang Hak Cipta</strong> atas penggunaan karya cipta mereka. Pembayaran
                                    royalti ini dihasilkan dari berbagai bentuk penggunaan karya, termasuk tetapi tidak
                                    terbatas pada distribusi digital, streaming, penggandaan fisik, dan penggunaan lisensi.
                                </li>
                                <li><strong>Hak Ekonomi</strong>: Hak eksklusif yang dimiliki oleh <strong>Pencipta</strong>
                                    atau <strong>Pemegang Hak Cipta</strong> untuk mendapatkan manfaat ekonomi dari karya
                                    cipta mereka. Hak ini mencakup:
                                    <ul>
                                        <li><strong>Mechanical Rights (Hak Menggandakan)</strong>: Hak untuk menggandakan dan
                                            mendistribusikan karya cipta dalam berbagai format, baik fisik (CD, kaset, vinyl) maupun
                                            digital (full track download, ringtone, dll).</li>
                                        <li><strong>Sheet Music Rights (Hak Terhadap Buku Musik)</strong>: Hak untuk menerbitkan dan
                                            mendistribusikan notasi musik dari karya cipta dalam format cetak dan digital.</li>
                                        <li><strong>Print Rights (Hak Cetak)</strong>: Hak untuk mengedarkan karya cipta dalam
                                            bentuk tulisan atau teks yang tercetak, baik untuk publikasi di media cetak maupun
                                            distribusi digital.</li>
                                    </ul>
                                </li>
                                <li><strong>Hak Derivatif</strong>: Hak yang terkait dengan pembuatan karya turunan dari
                                    karya cipta asli, termasuk:
                                    <ul>
                                        <li><strong>Hak Sinkronisasi (Synchronization Rights)</strong>: Hak untuk menggunakan karya
                                            cipta dalam gabungan dengan gambar bergerak, seperti dalam film, video klip, dan iklan.
                                        </li>
                                        <li><strong>Videogram Rights</strong>: Hak untuk memproduksi rekaman audiovisual, termasuk
                                            untuk format seperti DVD, VCD, dan lainnya.</li>
                                        <li><strong>Grand Rights</strong>: Hak untuk menggunakan karya cipta dalam pertunjukan
                                            teater, balet, opera, atau pertunjukan musik yang bersifat dramatis.</li>
                                    </ul>
                                </li>
                                <li><strong>Hak Distribusi</strong>: Hak untuk mendistribusikan karya cipta melalui berbagai
                                    medium, termasuk digital dan fisik. Ini termasuk:
                                    <ul>
                                        <li><strong>Fulltrack Download</strong>: Unduhan komplit karya cipta melalui internet ke
                                            perangkat digital.</li>
                                        <li><strong>Ring Back Tone (RBT)</strong>: Penggunaan karya cipta sebagai nada dering bagi
                                            pemanggil telepon.</li>
                                        <li><strong>Interactive Streaming Rights</strong>: Hak untuk mendengarkan karya cipta
                                            berdasarkan permintaan, seperti pada platform Spotify, YouTube, dll.</li>
                                        <li><strong>Non-Interactive Streaming Rights</strong>: Hak untuk mendengarkan karya cipta
                                            melalui platform tanpa interaksi pengguna, seperti pada radio online atau playlist.</li>
                                    </ul>
                                </li>
                                <li><strong>Performing Rights</strong>: Hak untuk melakukan pertunjukan publik atas karya
                                    cipta, baik dalam bentuk live performance maupun siaran melalui media seperti televisi,
                                    radio, dan platform digital.</li>
                                <li><strong>Hak Moral</strong>: Hak yang melekat pada <strong>Pencipta</strong> untuk
                                    melindungi integritas karya cipta mereka, termasuk hak untuk diakui sebagai pencipta dan
                                    hak untuk melarang perubahan atau modifikasi karya yang dapat merusak kehormatan atau
                                    reputasi mereka.</li>
                                <li><strong>Klaim Kepemilikan</strong>: Bagian kepemilikan atas hak cipta lagu yang dimiliki
                                    oleh <strong>Pencipta</strong> atau pihak terkait, yang bisa mencakup hak atas notasi
                                    musik, lirik, atau kombinasi keduanya.</li>
                                <li><strong>Pendapatan Bersih</strong>: Pendapatan yang diterima oleh <strong>{{ company_name() }}</strong> dari distribusi dan monetisasi karya cipta setelah
                                    dikurangi biaya yang terkait dengan distribusi, pengumpulan royalti, pajak, dan biaya
                                    administrasi lainnya.</li>
                                <li><strong>Pseudoname (Nama Alias)</strong>: Nama samaran atau nama lain yang digunakan
                                    oleh <strong>Pencipta</strong> untuk mengasosiasikan karya cipta mereka, selain nama
                                    asli mereka.</li>
                                <li><strong>Post Term Period</strong>: Periode 12 bulan setelah pengakhiran perjanjian, di
                                    mana <strong>{{ company_name() }}</strong> masih dapat mengumpulkan royalti
                                    yang dihasilkan dari penggunaan karya cipta yang dikelola sebelumnya.</li>
                                <li><strong>Force Majeure</strong>: Kejadian di luar kendali yang menghalangi atau
                                    memperlambat pelaksanaan kewajiban dalam perjanjian, seperti bencana alam, kebakaran,
                                    pandemi, atau kondisi tak terduga lainnya.</li>
                            </ol>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">KELAYAKAN</h5>
                        <div>
                            <p>Layanan Penerbit Musik yang disediakan oleh {{ company_name() }} melalui
                                {{ app_name() }} hanya dapat diakses dan digunakan oleh pihak yang memenuhi persyaratan
                                kelayakan yang ditetapkan dalam syarat dan ketentuan ini. Dengan mengakses dan menggunakan
                                layanan kami, Anda menyatakan dan menjamin bahwa Anda memenuhi semua kriteria kelayakan
                                berikut:</p>
                            <p><strong>Umur Pengguna</strong></p>
                            <p>Layanan kami hanya tersedia untuk individu yang berusia minimal 18 tahun. Jika Anda berusia
                                antara 13 hingga 17 tahun, Anda dapat menggunakan layanan ini hanya dengan izin tertulis
                                dari orang tua atau wali sah Anda yang bertanggung jawab. Dalam hal ini, kami menganggap
                                bahwa dengan pendaftaran layanan ini, orang tua atau wali sah Anda telah memberikan
                                persetujuan untuk penggunaan layanan ini oleh Anda.</p>
                            <p><strong>Kepemilikan Karya Cipta</strong></p>
                            <p>Pengguna yang mendaftar untuk layanan Penerbit Musik di {{ app_name() }} harus:</p>
                            <ul>
                                <li>Memiliki hak cipta atau izin penuh untuk mengelola, mendistribusikan, dan memonetisasi
                                    karya musik yang diunggah.</li>
                                <li>Menjamin bahwa karya musik yang diunggah tidak melanggar hak kekayaan intelektual pihak
                                    ketiga, termasuk hak cipta, hak moral, merek dagang, atau hak lainnya.</li>
                                <li>Jika karya cipta yang diunggah melibatkan lebih dari satu pihak, Pengguna harus
                                    memastikan bahwa semua pihak terkait (misalnya, co-pencipta, label, atau penerbit) telah
                                    memberikan izin tertulis untuk pengelolaan dan distribusi karya melalui {{ app_name() }}.</li>
                            </ul>
                            <p><strong>Keberadaan dan Kapasitas Hukum</strong></p>
                            <ul>
                                <li>Pengguna harus memiliki kapasitas hukum untuk mengikat diri dalam perjanjian ini.
                                    Artinya, jika Anda menggunakan layanan atas nama perusahaan atau entitas hukum lainnya,
                                    Anda menyatakan bahwa Anda memiliki wewenang untuk mengikat entitas tersebut dalam
                                    perjanjian ini.</li>
                                <li>Pengguna harus mendaftarkan diri secara akurat dan memberikan informasi yang benar serta
                                    lengkap dalam proses pendaftaran dan penggunaan layanan.</li>
                            </ul>
                            <p><strong>Penggunaan Layanan yang Sah</strong></p>
                            <p>Pengguna setuju untuk menggunakan layanan kami hanya untuk tujuan yang sah dan tidak
                                melanggar hukum, peraturan, atau ketentuan yang berlaku, baik secara lokal maupun
                                internasional. Ini termasuk, namun tidak terbatas pada:</p>
                            <ul>
                                <li>Menghindari distribusi konten yang melanggar hak cipta, pornografi, kebencian, atau
                                    konten yang merugikan pihak ketiga.</li>
                                <li>Menghindari pelanggaran terhadap kebijakan platform distribusi dan agregator pihak
                                    ketiga yang bekerja sama dengan <strong>{{ company_name() }}</strong>.</li>
                            </ul>
                            <p><strong>Pembatasan Penggunaan oleh Pihak Tertentu</strong></p>
                            <p>Layanan kami tidak tersedia untuk individu atau entitas yang:</p>
                            <ul>
                                <li>Telah dilarang atau dibatasi oleh hukum atau regulasi terkait hak cipta, hak kekayaan
                                    intelektual, atau ketentuan transaksi elektronik yang berlaku di Indonesia atau negara
                                    tempat tinggal Pengguna.</li>
                                <li>Tidak memenuhi standar kelayakan berdasarkan ketentuan yang ditetapkan dalam syarat dan
                                    ketentuan ini.</li>
                            </ul>
                            <p>Dengan mengakses dan menggunakan layanan Penerbit Musik melalui {{ app_name() }}, Pengguna
                                mengonfirmasi bahwa mereka memenuhi semua persyaratan kelayakan yang telah disebutkan di
                                atas dan bahwa mereka berhak untuk mengikatkan diri dalam perjanjian ini. {{ company_name() }} berhak untuk membatasi atau menghentikan akses Pengguna yang tidak memenuhi
                                persyaratan kelayakan ini tanpa pemberitahuan lebih lanjut.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PENERIMAAN SYARAT DAN KETENTUAN</h5>
                        <div>
                            <p>Dengan mengakses, mendaftar, atau menggunakan layanan Penerbit Musik yang disediakan oleh {{ company_name() }} melalui {{ app_name() }}, Anda sebagai Pengguna menyatakan dan
                                menyetujui untuk terikat dengan syarat dan ketentuan yang tercantum dalam dokumen ini.
                                Syarat dan ketentuan ini mengatur penggunaan layanan yang disediakan oleh {{ company_name() }} dan merupakan perjanjian yang sah secara hukum antara Pengguna dan {{ company_name() }}.</p>
                            <p>Dengan melanjutkan untuk mengakses atau menggunakan layanan Penerbit Musik melalui
                                {{ app_name() }}, Anda setuju bahwa tindakan tersebut adalah penerimaan penuh dan tidak terbatas
                                terhadap syarat dan ketentuan yang telah kami tentukan. Penerimaan syarat dan ketentuan ini
                                berlaku sebagai kesepakatan yang mengikat secara hukum antara Anda sebagai pengguna dan {{ company_name() }}.</p>
                            <p>Penerimaan ini juga mengikat Anda untuk mematuhi kewajiban yang tercantum dalam dokumen ini,
                                yang akan dilaksanakan sesuai dengan hukum yang berlaku di Republik Indonesia. Pengguna
                                menyetujui bahwa segala perselisihan atau sengketa yang timbul terkait penggunaan layanan
                                ini akan diselesaikan sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia,
                                termasuk namun tidak terbatas pada Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan
                                Data Pribadi, Undang-Undang Nomor 1 Tahun 2024 tentang Informasi dan Transaksi Elektronik,
                                dan Peraturan Pemerintah Nomor 71 Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi
                                Elektronik.</p>
                            <p>{{ company_name() }} berhak untuk mengubah, memperbarui, atau memperbaiki Syarat dan
                                Ketentuan Pengguna ini setiap saat sesuai dengan kebijakan perusahaan atau perubahan
                                peraturan perundang-undangan yang berlaku. Setiap perubahan atau pembaruan akan diumumkan di
                                Platform {{ app_name() }} dan akan berlaku segera setelah diumumkan, kecuali jika ditentukan lain.
                            </p>
                            <p>Jika Anda terus menggunakan layanan setelah perubahan atau pembaruan tersebut, hal itu
                                dianggap sebagai penerimaan terhadap perubahan syarat dan ketentuan yang berlaku. Oleh
                                karena itu, kami menyarankan Anda untuk secara berkala memeriksa Syarat dan Ketentuan ini
                                untuk memastikan Anda tetap memahami ketentuan yang berlaku.</p>
                            <p>Dengan menggunakan layanan Penerbit Musik melalui {{ app_name() }}, Anda juga menyatakan bahwa Anda
                                memiliki hak atau izin yang sah untuk menggunakan, mendistribusikan, dan memonetisasi karya
                                cipta yang diunggah, serta bahwa karya tersebut tidak melanggar hak kekayaan intelektual
                                pihak ketiga, dan sesuai dengan semua peraturan yang berlaku.</p>
                            <p>Jika Anda tidak setuju dengan Syarat dan Ketentuan Pengguna ini, Anda tidak diperkenankan
                                untuk menggunakan layanan kami. Anda harus segera menghentikan penggunaan platform dan
                                layanan {{ app_name() }}.</p>
                            <p>Segala perselisihan yang timbul akibat atau berkaitan dengan penggunaan layanan ini, termasuk
                                namun tidak terbatas pada masalah pengelolaan hak cipta, distribusi royalti, dan pengumpulan
                                royalti, akan diselesaikan secara musyawarah dalam waktu 60 (enam puluh) hari kalender sejak
                                salah satu pihak mengajukan penyelesaian masalah secara musyawarah.</p>
                            <p>Jika perselisihan tersebut tidak dapat diselesaikan dalam jangka waktu 60 (enam puluh) hari
                                kalender, maka perselisihan akan diselesaikan melalui Pengadilan Negeri Jakarta Selatan</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">AKUN PENGGUNA</h5>
                        <div>
                            <p>Untuk mengakses dan menggunakan layanan Penerbit Musik yang disediakan oleh {{ company_name() }} melalui {{ app_name() }}, Anda diwajibkan untuk membuat akun pengguna. Dalam hal
                                ini, Anda bertanggung jawab sepenuhnya untuk menjaga kerahasiaan akun dan kata sandi yang
                                digunakan untuk mengakses layanan kami. Berikut adalah ketentuan mengenai Akun Pengguna:</p>
                            <p>Pendaftaran Akun</p>
                            <ul>
                                <li>Proses Pendaftaran: Untuk membuat akun, Anda harus mengisi formulir pendaftaran yang
                                    disediakan oleh {{ app_name() }} dengan informasi yang akurat, lengkap, dan terbaru. Anda
                                    diwajibkan untuk memberikan data pribadi yang benar, seperti nama lengkap, alamat email,
                                    nomor telepon, informasi identitas lainnya, dan rincian yang relevan dengan karya musik
                                    yang ingin dikelola.</li>
                                <li>Hanya untuk Individu (Orang Pribadi): Layanan Penerbit Musik hanya tersedia untuk
                                    individu (orang pribadi), dan tidak dapat digunakan oleh perusahaan, badan hukum, atau
                                    entitas lainnya. Oleh karena itu, pendaftaran akun harus dilakukan oleh individu yang
                                    sah sebagai pemilik hak cipta atas karya yang diunggah. Pengguna harus menyatakan bahwa
                                    mereka adalah pemegang hak cipta yang sah atau memiliki izin penuh untuk
                                    mendistribusikan dan mengelola karya cipta tersebut.</li>
                                <li>Perjanjian Pengelolaan Hak Cipta: Sebelum menggunakan layanan Penerbit Musik, Anda
                                    sebagai Pencipta dan/atau Pemegang Hak Cipta harus menandatangani Perjanjian Pengelolaan
                                    Hak Cipta atas Lagu dan/atau Musik dengan {{ company_name() }}. Perjanjian ini
                                    mengatur pengelolaan hak cipta dan royalti atas karya yang akan dikelola.</li>
                                <li>Surat Kuasa: Sebagai bagian dari pendaftaran, Anda juga harus memberikan Surat Kuasa
                                    yang sah dari Pencipta dan/atau Pemegang Hak Cipta yang memberikan izin kepada {{ company_name() }} untuk bertindak sebagai penerbit musik dan mengelola hak
                                    cipta serta royalti terkait karya cipta yang diunggah.</li>
                            </ul>
                            <p>Tanggung Jawab Akun</p>
                            <ul>
                                <li>Kerahasiaan Akun dan Kata Sandi: Anda bertanggung jawab untuk menjaga kerahasiaan
                                    informasi akun, termasuk kata sandi yang digunakan untuk mengakses layanan {{ app_name() }}.
                                    Anda setuju untuk tidak mengungkapkan kata sandi Anda kepada pihak ketiga.</li>
                                <li>Penggunaan yang Sah: Anda bertanggung jawab sepenuhnya atas segala aktivitas yang
                                    terjadi dalam akun Anda, termasuk namun tidak terbatas pada penggunaan layanan Penerbit
                                    Musik, pengunggahan karya cipta, dan pengelolaan hak cipta.</li>
                                <li>Pemberitahuan Segera: Jika Anda mengetahui adanya penggunaan yang tidak sah atau
                                    pelanggaran terhadap akun Anda, Anda harus segera memberi tahu kami melalui informasi
                                    kontak yang tersedia di platform {{ app_name() }}. Kami akan mengambil langkah yang diperlukan
                                    untuk melindungi keamanan akun Anda.</li>
                            </ul>
                            <p>Akurasi Informasi Pengguna</p>
                            <ul>
                                <li>Verifikasi Informasi: Anda menyetujui untuk memberikan informasi yang akurat dan terkini
                                    saat pendaftaran dan selama penggunaan layanan. Anda juga setuju untuk memperbarui
                                    informasi tersebut apabila ada perubahan, seperti perubahan alamat email, nama kontak,
                                    atau rincian karya musik yang dikelola.</li>
                                <li>Pernyataan Kebenaran: Dengan mendaftar di {{ app_name() }}, Anda menyatakan bahwa informasi
                                    yang Anda berikan adalah benar dan bahwa Anda memiliki hak untuk menggunakan dan
                                    mendistribusikan karya cipta yang diunggah ke platform kami. Anda bertanggung jawab
                                    untuk memastikan bahwa karya yang diunggah tidak melanggar hak cipta pihak ketiga.</li>
                            </ul>
                            <p>Pembatasan Akses Akun</p>
                            <p>{{ company_name() }} berhak untuk:</p>
                            <ul>
                                <li>Membatasi, menangguhkan, atau menghentikan akses akun Anda jika kami menemukan bahwa
                                    informasi yang diberikan tidak akurat, tidak lengkap, atau melanggar ketentuan dalam
                                    Syarat dan Ketentuan Pengguna.</li>
                                <li>Membatasi akses ke fitur tertentu dalam platform {{ app_name() }} jika Anda tidak memenuhi
                                    persyaratan kelayakan yang ditetapkan dalam bagian Kelayakan, termasuk bahwa akun
                                    tersebut hanya dapat digunakan oleh individu (orang pribadi) dan tidak oleh entitas
                                    perusahaan atau badan hukum.</li>
                            </ul>
                            <p>Pengakhiran Akun</p>
                            <ul>
                                <li>Pengakhiran oleh Pengguna: Akun Penerbit Musik Anda tidak dapat dihentikan secara
                                    sepihak sebelum jangka waktu yang tercantum dalam Perjanjian Pengelolaan Hak Cipta atas
                                    Lagu dan/atau Musik berakhir, kecuali berdasarkan kesepakatan bersama atau alasan hukum
                                    yang sah. Jika Anda ingin mengakhiri akun setelah masa perjanjian berakhir, Anda dapat
                                    menghubungi tim dukungan pelanggan kami untuk prosedur lebih lanjut. Namun, setelah
                                    pengakhiran, Anda tetap berhak menerima royalti yang terhutang terkait karya cipta yang
                                    telah dikelola sebelum pengakhiran akun dilakukan.</li>
                                <li>Pengakhiran oleh {{ company_name() }}: Kami berhak untuk menangguhkan atau
                                    menghentikan akun Anda hanya jika terdapat pelanggaran terhadap Syarat dan Ketentuan
                                    Pengguna atau ketentuan yang ada dalam Perjanjian Pengelolaan Hak Cipta yang telah Anda
                                    tandatangani. Pengakhiran sepihak oleh kami tidak akan dilakukan sebelum jangka waktu
                                    yang diatur dalam Perjanjian Pengelolaan Hak Cipta berakhir, kecuali terdapat alasan
                                    yang sah sesuai dengan ketentuan peraturan perundang-undangan yang berlaku. Jika akun
                                    dihentikan oleh {{ company_name() }}, hak-hak Anda untuk memperoleh royalti
                                    atau pembayaran terkait karya yang sudah dikelola akan tetap dihormati dan diterima
                                    sesuai dengan ketentuan yang berlaku dalam Perjanjian Pengelolaan Hak Cipta.</li>
                            </ul>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">HAK KEPEMILIKAN DAN LISENSI</h5>
                        <div>
                            <p>Kepemilikan Karya Cipta</p>
                            <ul>
                                <li>Hak Cipta: Pengguna yang mengunggah karya cipta ke platform {{ app_name() }} menyatakan dan
                                    menjamin bahwa mereka adalah pemegang hak cipta yang sah atau telah memperoleh izin
                                    penuh dari pemegang hak cipta untuk mengelola, mendistribusikan, dan memonetisasi karya
                                    tersebut. Pengguna mengonfirmasi bahwa karya cipta yang diunggah tidak melanggar hak
                                    kekayaan intelektual pihak ketiga, termasuk hak cipta, merek dagang, atau hak lainnya.
                                </li>
                                <li>Kepemilikan atas Karya: Pengguna tetap mempertahankan hak cipta atas karya musik yang
                                    diunggah. {{ company_name() }} tidak akan mengklaim kepemilikan atas karya
                                    cipta yang diunggah oleh pengguna, namun {{ company_name() }} akan bertindak
                                    sebagai penerbit musik yang mengelola hak cipta tersebut sesuai dengan perjanjian yang
                                    disepakati antara pengguna dan perusahaan.</li>
                            </ul>
                            <p>Lisensi yang Diberikan kepada {{ company_name() }}</p>
                            <ul>
                                <li>Dengan mengunggah karya cipta melalui platform {{ app_name() }}, Pengguna memberikan kepada {{ company_name() }} hak lisensi global, non-eksklusif, bebas royalti, untuk:</li>
                                <ol>
                                    <li>Mendistribusikan karya musik di berbagai platform distribusi digital dan saluran
                                        media lainnya.</li>
                                    <li>Mengelola hak cipta karya musik, termasuk pengumpulan royalti dari penggunaan karya
                                        cipta di berbagai media, baik online maupun offline.</li>
                                    <li>Memberikan lisensi kepada pihak ketiga, seperti penyiar, penerbit, lembaga manajemen
                                        kolektif, atau distributor lain, untuk penggunaan karya cipta tersebut dalam konteks
                                        yang telah disetujui, seperti film, iklan, video klip, dan lainnya.</li>
                                    <li>Menggunakan karya cipta untuk tujuan promosi, marketing, dan distribusi terkait
                                        dengan layanan yang disediakan oleh {{ company_name() }}.</li>
                                </ol>
                                <li>Lisensi yang diberikan oleh Pengguna bersifat non-eksklusif, yang berarti pengguna
                                    berhak untuk memberikan lisensi yang sama kepada pihak lain atau mengelola hak cipta
                                    mereka secara terpisah di luar platform {{ app_name() }}.</li>
                            </ul>
                            <p>Penggunaan Karya oleh {{ company_name() }}</p>
                            <ul>
                                <li>Penggunaan Karya untuk Kepentingan Bisnis: {{ company_name() }} berhak untuk
                                    menggunakan karya yang diunggah oleh pengguna untuk tujuan promosi dan distribusi dalam
                                    kerangka bisnis {{ app_name() }}, yang termasuk namun tidak terbatas pada penggunaan karya
                                    dalam kampanye pemasaran digital, promosi platform, serta kegiatan lain yang bertujuan
                                    untuk meningkatkan eksposur karya pengguna.</li>
                                <li>Penggunaan dalam Media: {{ company_name() }} dapat memanfaatkan karya cipta
                                    yang diunggah oleh pengguna untuk penayangan di media seperti televisi, radio, internet,
                                    dan platform streaming tanpa memberikan kompensasi tambahan selain royalti yang
                                    terutang, sesuai dengan ketentuan dalam perjanjian yang berlaku.</li>
                            </ul>
                            <p>Pembatasan Penggunaan oleh Pengguna</p>
                            <ul>
                                <li>Pengguna tidak diperkenankan untuk memberikan lisensi atau hak lainnya terkait karya
                                    cipta yang diunggah kepada pihak ketiga tanpa persetujuan tertulis dari {{ company_name() }}, kecuali untuk penggunaan yang tidak berhubungan dengan layanan {{ app_name() }}.
                                </li>
                                <li>Pengguna setuju untuk tidak melakukan tindakan yang dapat merusak atau mengurangi nilai
                                    lisensi yang diberikan kepada {{ company_name() }}, termasuk tetapi tidak
                                    terbatas pada pelanggaran hak cipta atau distribusi ilegal dari karya yang diunggah.
                                </li>
                            </ul>
                            <p>Pengakhiran Lisensi</p>
                            <p>Lisensi yang diberikan oleh Pengguna kepada {{ company_name() }} akan berakhir
                                sesuai dengan ketentuan yang ada dalam Perjanjian Pengelolaan Hak Cipta yang telah
                                ditandatangani. Namun, hak untuk mengumpulkan royalti yang telah diperoleh sebelum
                                pengakhiran lisensi tetap berlaku meskipun akun pengguna telah dihentikan atau diakhiri.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">KEWAJIBAN PENGGUNA</h5>
                        <div>
                            <p>Sebagai pengguna layanan Penerbit Musik di {{ app_name() }}, Anda setuju untuk mematuhi semua
                                kewajiban yang tercantum dalam syarat dan ketentuan ini serta ketentuan yang berlaku dalam
                                Perjanjian Pengelolaan Hak Cipta atas Lagu dan/atau Musik. Berikut adalah kewajiban yang
                                harus dipenuhi oleh pengguna:</p>
                            <p>Pengunggahan Karya Cipta yang Sah</p>
                            <ul>
                                <li>Keaslian Karya Cipta: Anda bertanggung jawab untuk memastikan bahwa karya yang diunggah
                                    ke platform {{ app_name() }} adalah karya cipta asli yang Anda buat, atau Anda telah
                                    memperoleh izin yang sah dari pemegang hak cipta lainnya untuk mengunggah dan
                                    mendistribusikan karya tersebut.</li>
                                <li>Tidak Melanggar Hak Pihak Ketiga: Anda menyatakan dan menjamin bahwa karya cipta yang
                                    diunggah tidak melanggar hak kekayaan intelektual pihak ketiga, termasuk hak cipta, hak
                                    moral, hak merek dagang, atau hak lainnya. Anda harus memastikan bahwa karya tersebut
                                    tidak mengandung unsur yang melanggar ketentuan hukum yang berlaku.</li>
                            </ul>
                            <p>Mematuhi Hukum dan Peraturan yang Berlaku</p>
                            <ul>
                                <li>Kepatuhan terhadap Undang-Undang: Anda setuju untuk mematuhi seluruh hukum, peraturan,
                                    dan ketentuan yang berlaku di negara tempat tinggal Anda maupun peraturan yang berlaku
                                    di Indonesia terkait hak cipta, privasi, dan penggunaan media digital.</li>
                                <li>Konten yang Dilarang: Anda dilarang mengunggah atau mendistribusikan karya yang berisi
                                    konten yang bersifat:</li>
                                <ul>
                                    <li>Melanggar hukum, mengandung kebencian, diskriminasi, atau kekerasan.</li>
                                    <li>Mengandung unsur pornografi, kekerasan, atau ujaran kebencian yang dapat merugikan
                                        pihak lain.</li>
                                    <li>Melanggar kebijakan yang ditetapkan oleh platform distribusi dan agregator pihak
                                        ketiga yang digunakan oleh {{ app_name() }}.</li>
                                </ul>
                            </ul>
                            <p>Pengelolaan Akun dengan Bijak</p>
                            <ul>
                                <li>Keamanan Akun: Anda bertanggung jawab penuh untuk menjaga keamanan dan kerahasiaan akun
                                    serta kata sandi yang digunakan untuk mengakses layanan. Anda setuju untuk tidak
                                    membagikan kata sandi kepada pihak lain dan segera memberi tahu kami jika terjadi
                                    penggunaan akun yang tidak sah.</li>
                                <li>Informasi yang Akurat: Anda bertanggung jawab untuk memastikan bahwa informasi yang Anda
                                    berikan selama pendaftaran dan penggunaan layanan adalah akurat dan terkini. Anda harus
                                    segera memperbarui informasi tersebut jika terjadi perubahan.</li>
                            </ul>
                            <p>Kewajiban untuk Membayar Royalti dan Biaya yang Terkait</p>
                            <ul>
                                <li>Pembayaran Royalti: Anda wajib menerima pembayaran royalti yang dikumpulkan oleh {{ company_name() }} untuk penggunaan karya cipta Anda di berbagai platform
                                    distribusi dan media lain, sesuai dengan ketentuan dalam Perjanjian Pengelolaan Hak
                                    Cipta. Pembayaran royalti akan dilakukan sesuai dengan perhitungan yang telah disepakati
                                    antara pengguna dan perusahaan.</li>
                                <li>Biaya Layanan: Anda setuju untuk membayar biaya atau komisi yang terkait dengan layanan
                                    yang disediakan oleh {{ company_name() }} sesuai dengan ketentuan yang
                                    tercantum dalam perjanjian atau yang diinformasikan melalui platform {{ app_name() }}.</li>
                            </ul>
                            <p>Tanggung Jawab atas Penggunaan Karya oleh Pihak Ketiga</p>
                            <ul>
                                <li>Lisensi kepada Pihak Ketiga: Anda setuju bahwa {{ company_name() }} dapat
                                    memberikan lisensi kepada pihak ketiga untuk penggunaan karya cipta Anda dalam berbagai
                                    bentuk media atau platform, termasuk namun tidak terbatas pada film, iklan, video klip,
                                    atau platform digital lainnya, dan Anda berhak menerima royalti yang dihasilkan dari
                                    penggunaan tersebut sesuai dengan Perjanjian Pengelolaan Hak Cipta.</li>
                                <li>Penyelesaian Pelanggaran oleh Pihak Ketiga: Jika Anda mengetahui bahwa karya cipta yang
                                    telah Anda unggah digunakan oleh pihak ketiga tanpa izin, Anda setuju untuk segera
                                    memberitahukan kami dan memberikan dukungan yang diperlukan untuk menyelesaikan masalah
                                    tersebut, termasuk pengajuan klaim pelanggaran hak cipta kepada pihak berwenang.</li>
                            </ul>
                            <p>Penggunaan Layanan secara Etis dan Profesional</p>
                            <ul>
                                <li>Menghindari Penyalahgunaan Layanan: Anda setuju untuk tidak menggunakan layanan
                                    {{ app_name() }} untuk melakukan tindakan yang merugikan platform, pengguna lain, atau pihak
                                    ketiga, termasuk tetapi tidak terbatas pada:</li>
                                <ul>
                                    <li>Menggunakan perangkat lunak atau alat lain untuk meretas atau memperoleh akses tidak
                                        sah ke data pengguna atau platform.</li>
                                    <li>Mengunggah atau mendistribusikan malware, virus, atau perangkat lunak yang dapat
                                        merusak sistem atau data pengguna lain.</li>
                                </ul>
                                <li>Tanggung Jawab Sosial: Anda setuju untuk menggunakan layanan dengan cara yang etis,
                                    menjaga hubungan profesional dengan {{ company_name() }} serta pengguna dan
                                    pihak ketiga lainnya, dan tidak melakukan tindakan yang dapat merusak reputasi layanan
                                    {{ app_name() }} atau perusahaan.</li>
                            </ul>
                            <p>Kewajiban untuk Mematuhi Ketentuan yang Terkait dengan Pembayaran Royalti dan Hak Cipta</p>
                            <p>Anda setuju untuk mematuhi semua kewajiban yang diatur dalam Perjanjian Pengelolaan Hak Cipta
                                dan untuk tidak mengalihkan atau mencabut hak dan kewajiban yang tercantum dalam perjanjian
                                tersebut tanpa persetujuan tertulis dari {{ company_name() }}.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PEMROSESAN DATA PRIBADI</h5>
                        <div>
                            <p><strong>Pengumpulan Data Pribadi</strong></p>
                            <p>{{ company_name() }} menghargai privasi Anda dan berkomitmen untuk melindungi data
                                pribadi yang Anda berikan selama penggunaan layanan Penerbit Musik melalui {{ app_name() }}. Dalam
                                rangka menyediakan layanan ini, kami mengumpulkan berbagai jenis informasi pribadi yang
                                dapat mencakup namun tidak terbatas pada:</p>
                            <ul>
                                <li>Nama lengkap</li>
                                <li>Alamat email</li>
                                <li>Nomor telepon</li>
                                <li>Informasi identitas lainnya yang relevan dengan penggunaan platform</li>
                                <li>Informasi mengenai karya cipta yang diunggah dan dikelola melalui {{ app_name() }}</li>
                            </ul>
                            <p><strong>Tujuan Pemrosesan Data Pribadi</strong></p>
                            <p>Data pribadi yang kami kumpulkan akan diproses untuk tujuan sebagai berikut:</p>
                            <ul>
                                <li><strong>Pendaftaran Akun</strong>: Memastikan pendaftaran akun yang sah dan memberikan
                                    akses ke layanan yang disediakan oleh <strong>{{ app_name() }}</strong>.</li>
                                <li><strong>Pengelolaan Layanan Penerbit Musik</strong>: Memungkinkan kami untuk mengelola
                                    dan mendistribusikan karya cipta yang Anda unggah, serta mengumpulkan royalti dan
                                    membayar pengguna sesuai dengan <strong>Perjanjian Pengelolaan Hak Cipta</strong> yang
                                    telah disepakati.</li>
                                <li><strong>Pemasaran dan Promosi</strong>: Menggunakan data pribadi untuk mengirimkan
                                    informasi terkait dengan produk, layanan, atau promosi kami yang dapat relevan dengan
                                    Anda, dengan persetujuan Anda jika diperlukan.</li>
                                <li><strong>Kepatuhan Hukum</strong>: Untuk mematuhi kewajiban hukum yang berlaku, termasuk
                                    hukum yang terkait dengan hak cipta dan perlindungan data pribadi.</li>
                            </ul>
                            <p><strong>Penggunaan dan Pengungkapan Data Pribadi</strong></p>
                            <ul>
                                <li><strong>Keamanan Data</strong>: Kami berkomitmen untuk menjaga data pribadi Anda dengan
                                    standar keamanan yang memadai, guna melindungi informasi Anda dari akses, pengungkapan,
                                    perubahan, atau penghancuran yang tidak sah.</li>
                                <li><strong>Pihak Ketiga</strong>: Kami tidak akan mengungkapkan data pribadi Anda kepada
                                    pihak ketiga selain yang diperlukan untuk tujuan distribusi dan pengelolaan royalti,
                                    seperti kepada lembaga manajemen kolektif, platform distribusi musik, atau pihak ketiga
                                    yang terkait dengan pengelolaan hak cipta Anda. Semua pengungkapan ini akan dilakukan
                                    dengan sesuai dengan <strong>Perjanjian Pengelolaan Hak Cipta</strong> dan peraturan
                                    yang berlaku.</li>
                                <li><strong>Penyedia Layanan</strong>: Kami dapat berbagi data pribadi Anda dengan penyedia
                                    layanan pihak ketiga yang membantu kami dalam operasi bisnis, seperti platform
                                    pembayaran, hosting, atau sistem manajemen data. Penyedia layanan ini akan memproses
                                    data pribadi Anda hanya sesuai dengan instruksi kami dan dalam batasan yang diperlukan
                                    untuk menyediakan layanan yang diminta.</li>
                            </ul>
                            <p><strong>Penyimpanan Data Pribadi</strong></p>
                            <p>Data pribadi yang kami kumpulkan akan disimpan selama diperlukan untuk memenuhi tujuan
                                pengumpulan data tersebut, atau sesuai dengan ketentuan dalam perjanjian kami dengan Anda,
                                atau sesuai dengan kewajiban hukum yang berlaku. Data akan dihapus atau dianonimkan setelah
                                tidak lagi diperlukan untuk tujuan tersebut.</p>
                            <p><strong>Hak Pengguna Terhadap Data Pribadi</strong></p>
                            <p>Sebagai pengguna, Anda memiliki hak-hak tertentu terkait dengan data pribadi Anda, termasuk:
                            </p>
                            <ul>
                                <li><strong>Hak untuk Akses</strong>: Anda berhak untuk meminta salinan data pribadi yang
                                    kami simpan tentang Anda.</li>
                                <li><strong>Hak untuk Perbaikan</strong>: Anda dapat meminta kami untuk memperbaiki data
                                    pribadi yang tidak akurat atau tidak lengkap.</li>
                                <li><strong>Hak untuk Penghapusan</strong>: Anda dapat meminta kami untuk menghapus data
                                    pribadi Anda, dengan pengecualian yang sesuai dengan kewajiban hukum yang berlaku.</li>
                                <li><strong>Hak untuk Menarik Persetujuan</strong>: Jika pengolahan data pribadi Anda
                                    didasarkan pada persetujuan Anda, Anda berhak untuk menarik persetujuan tersebut kapan
                                    saja tanpa mempengaruhi keabsahan pemrosesan yang dilakukan sebelum penarikan
                                    persetujuan.</li>
                                <li><strong>Hak untuk Membatasi Pemrosesan</strong>: Anda dapat meminta pembatasan
                                    pemrosesan data pribadi Anda dalam situasi tertentu.</li>
                            </ul>
                            <p>Jika Anda ingin menggunakan hak-hak tersebut, atau jika Anda memiliki pertanyaan mengenai
                                pemrosesan data pribadi Anda, Anda dapat menghubungi kami melalui informasi kontak yang
                                tersedia di platform <strong>{{ app_name() }}</strong>.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PELANGGARAN HAK CIPTA</h5>
                        <div>
                            <p>Tanggung Jawab atas Pelanggaran Hak Cipta</p>
                            <p>Pengguna bertanggung jawab sepenuhnya untuk memastikan bahwa karya cipta yang diunggah ke
                                {{ app_name() }} tidak melanggar hak cipta atau hak kekayaan intelektual lainnya yang dimiliki
                                oleh pihak ketiga. Anda menjamin bahwa:</p>
                            <ul>
                                <li>Anda adalah pemegang hak cipta atau telah memperoleh izin yang sah dari pemegang hak
                                    cipta untuk mengunggah dan mendistribusikan karya cipta tersebut.</li>
                                <li>Karya cipta yang diunggah tidak mengandung unsur yang melanggar hak cipta, hak moral,
                                    hak merek dagang, atau hak kekayaan intelektual lainnya dari pihak ketiga.</li>
                                <li>Anda tidak akan mengunggah karya cipta yang melanggar hukum atau yang berpotensi merusak
                                    reputasi dan integritas {{ company_name() }} dan platform {{ app_name() }}.</li>
                            </ul>
                            <p>Prosedur Pengaduan Pelanggaran Hak Cipta</p>
                            <p>Jika Anda mengetahui adanya pelanggaran hak cipta terkait karya cipta yang diunggah oleh
                                pengguna lain di platform {{ app_name() }}, Anda dapat melaporkan pelanggaran tersebut kepada kami
                                dengan mengikuti prosedur yang telah ditentukan. Langkah-langkah pengaduan adalah sebagai
                                berikut:</p>
                            <ol>
                                <li>Pengajuan Pengaduan: Anda dapat mengirimkan laporan pelanggaran hak cipta melalui email
                                    atau formulir pengaduan yang tersedia di platform kami.</li>
                                <li>Informasi yang Diperlukan dalam Pengaduan: Laporan Anda harus mencakup informasi yang
                                    cukup jelas tentang karya cipta yang dilanggar, termasuk namun tidak terbatas pada:</li>
                            </ol>
                            <ul>
                                <li>Judul karya cipta.</li>
                                <li>Nama pemegang hak cipta atau pencipta.</li>
                                <li>Deskripsi pelanggaran yang terjadi dan bukti-bukti yang mendukung klaim pelanggaran.
                                </li>
                            </ul>
                            <ol>
                                <li>Penyelidikan dan Tindakan: Setelah menerima laporan, kami akan menyelidiki klaim
                                    pelanggaran hak cipta tersebut. Jika terbukti ada pelanggaran, kami akan mengambil
                                    tindakan yang sesuai, termasuk namun tidak terbatas pada penghapusan karya yang
                                    melanggar, suspend akun pengguna yang terlibat, atau langkah-langkah lain yang dianggap
                                    perlu sesuai dengan hukum yang berlaku.</li>
                            </ol>
                            <p>Tanggapan Terhadap Pengaduan Pelanggaran</p>
                            <p>Kami akan bertindak sesuai dengan Peraturan Pemerintah Nomor 71 Tahun 2019 tentang
                                Penyelenggaraan Sistem dan Transaksi Elektronik, yang mengatur mekanisme pengaduan
                                pelanggaran hak cipta dalam platform digital. Dalam hal ini:</p>
                            <ul>
                                <li>Jika kami menerima klaim pelanggaran hak cipta yang sah, kami akan segera menghapus atau
                                    menonaktifkan akses terhadap karya cipta yang dianggap melanggar.</li>
                                <li>Jika Anda adalah pihak yang dituduh melanggar hak cipta, Anda akan diberikan kesempatan
                                    untuk memberikan klarifikasi dan bukti-bukti yang membela klaim Anda sebelum tindakan
                                    lebih lanjut diambil.</li>
                            </ul>
                            <p>Penyelesaian Sengketa Hak Cipta</p>
                            <p>Jika terjadi sengketa terkait dengan pelanggaran hak cipta antara pengguna, kami akan
                                berupaya untuk menyelesaikan masalah ini melalui musyawarah dalam waktu 60 (enam puluh) hari
                                kalender sejak pihak yang mengajukan pengaduan pertama kali melaporkan pelanggaran. Jika
                                penyelesaian sengketa tidak tercapai dalam waktu tersebut, perselisihan akan diselesaikan
                                melalui Pengadilan Negeri Jakarta Selatan atau pengadilan yang berwenang berdasarkan hukum
                                yang berlaku.</p>
                            <p>Penangguhan atau Pengakhiran Akun Pengguna</p>
                            <p>Jika pengguna terbukti melakukan pelanggaran hak cipta atau pelanggaran lainnya yang
                                merugikan pihak ketiga atau platform {{ app_name() }}, {{ company_name() }} berhak untuk:
                            </p>
                            <ul>
                                <li>Menangguhkan akses akun pengguna yang melanggar untuk sementara waktu sampai masalah
                                    diselesaikan.</li>
                                <li>Mengakhiri akun pengguna jika pelanggaran yang terjadi terbukti serius atau berulang.
                                    Pengakhiran akun ini tidak membebaskan pengguna dari kewajiban untuk membayar royalti
                                    atau biaya yang masih terutang.</li>
                            </ul>
                            <p>Penghentian Distribusi dan Lisensi</p>
                            <p>Jika karya cipta yang diunggah dilaporkan dan terbukti melanggar hak cipta, {{ company_name() }} berhak untuk menghentikan distribusi karya tersebut ke platform streaming atau
                                media lain. Lisensi yang diberikan kepada {{ company_name() }} untuk distribusi
                                karya yang melanggar akan dibatalkan, dan karya tersebut akan dihapus dari sistem distribusi
                                kami, sesuai dengan ketentuan dalam Perjanjian Pengelolaan Hak Cipta.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PEMBAYARAN DAN ROYALTI</h5>
                        <div>
                            <p>Pengumpulan Royalti</p>
                            <p>{{ company_name() }} akan mengumpulkan royalti yang diperoleh dari penggunaan karya
                                cipta yang diunggah oleh pengguna ke {{ app_name() }} melalui berbagai saluran, termasuk tetapi
                                tidak terbatas pada:</p>
                            <ul>
                                <li>Platform streaming digital seperti Spotify, Apple Music, YouTube, dan lainnya.</li>
                                <li>Penggandaan fisik seperti CD, kaset, dan media lainnya.</li>
                                <li>Penggunaan dalam film, iklan, video klip, dan media lainnya yang melibatkan hak lisensi
                                    sinkronisasi.</li>
                            </ul>
                            <p>{{ company_name() }} berhak untuk memantau dan mengumpulkan royalti yang diperoleh
                                dari distribusi dan penggunaan karya cipta yang dikelola melalui platform {{ app_name() }}.</p>
                            <p>Pembayaran Royalti kepada Pengguna</p>
                            <ul>
                                <li>Pembayaran Royalti: Setelah royalti terkumpul, {{ company_name() }} akan
                                    menghitung dan membayar royalti kepada Pencipta dan/atau Pemegang Hak Cipta berdasarkan
                                    persentase yang tercantum dalam Perjanjian Pengelolaan Hak Cipta yang telah disepakati
                                    oleh kedua belah pihak.</li>
                                <li>Jadwal Pembayaran: Pembayaran royalti akan dilakukan secara berkala sesuai dengan
                                    ketentuan yang tercantum dalam Perjanjian Pengelolaan Hak Cipta. Pembayaran dapat
                                    dilakukan setiap kuartal atau sesuai dengan periode yang disepakati dalam perjanjian.
                                </li>
                                <li>Metode Pembayaran: Pembayaran royalti akan dilakukan melalui transfer bank atau metode
                                    pembayaran lain yang telah disepakati oleh kedua belah pihak. Pengguna wajib menyediakan
                                    informasi pembayaran yang akurat dan terkini untuk memfasilitasi proses pembayaran.</li>
                            </ul>
                            <p>Pemotongan dan Biaya Administrasi</p>
                            <ul>
                                <li>Biaya Administrasi: {{ company_name() }} berhak untuk memotong biaya
                                    administrasi yang wajar untuk pengelolaan royalti dan distribusi karya cipta. Biaya
                                    administrasi ini akan diumumkan dan disepakati dalam Perjanjian Pengelolaan Hak Cipta.
                                </li>
                                <li>Pajak: Pembayaran royalti dapat dikenakan pajak sesuai dengan peraturan perpajakan yang
                                    berlaku di negara tempat tinggal Pengguna. Pengguna bertanggung jawab untuk memenuhi
                                    kewajiban pajak yang relevan atas royalti yang diterima.</li>
                            </ul>
                            <p>Transparansi Laporan Royalti</p>
                            <ul>
                                <li>Laporan Penggunaan dan Pendapatan: {{ company_name() }} akan menyediakan
                                    laporan yang transparan mengenai penggunaan karya cipta dan pendapatan royalti yang
                                    dihasilkan. Laporan ini akan mencakup detail tentang jumlah streaming, penjualan, dan
                                    penggunaan lainnya yang berkontribusi pada pendapatan royalti.</li>
                                <li>Akses Laporan: Pengguna dapat mengakses laporan pendapatan royalti dan penggunaan karya
                                    cipta melalui akun mereka di platform {{ app_name() }}, yang akan diperbarui secara berkala.
                                </li>
                            </ul>
                            <p>Pembayaran Royalti untuk Karya Bersama</p>
                            <p>Jika karya cipta yang dikelola adalah hasil karya bersama (collaboration), royalti akan
                                dibagi sesuai dengan kesepakatan yang telah dibuat di antara para pihak yang terlibat, yang
                                tercantum dalam Perjanjian Pengelolaan Hak Cipta. Jika tidak ada kesepakatan khusus,
                                pembagian royalti akan mengikuti ketentuan standar yang berlaku di industri musik, dan {{ company_name() }} akan bertindak sebagai pihak yang mengelola dan menyalurkan
                                pembayaran tersebut.</p>
                            <p>Penangguhan Pembayaran Royalti</p>
                            <p>{{ company_name() }} berhak untuk menangguhkan pembayaran royalti kepada pengguna
                                dalam situasi tertentu, termasuk namun tidak terbatas pada:</p>
                            <ul>
                                <li>Pelanggaran terhadap Syarat dan Ketentuan: Jika pengguna melanggar ketentuan yang
                                    tercantum dalam Syarat dan Ketentuan Pengguna atau Perjanjian Pengelolaan Hak Cipta,
                                    pembayaran royalti dapat ditangguhkan sampai masalah tersebut diselesaikan.</li>
                                <li>Klaim Pihak Ketiga: Jika ada klaim atau sengketa mengenai kepemilikan atau penggunaan
                                    hak cipta yang diunggah, pembayaran royalti dapat ditangguhkan sampai klaim tersebut
                                    diselesaikan atau dibuktikan.</li>
                            </ul>
                            <p>Pengakhiran Akun dan Pembayaran Royalti</p>
                            <ul>
                                <li>Pengakhiran Akun: Jika akun Anda dihentikan atau diakhiri, {{ company_name() }}
                                    akan tetap memenuhi kewajibannya untuk membayar royalti yang terutang kepada pengguna
                                    atas karya cipta yang telah didistribusikan sebelum pengakhiran akun. Pembayaran royalti
                                    yang belum dibayar akan dilakukan sesuai dengan ketentuan yang tercantum dalam
                                    Perjanjian Pengelolaan Hak Cipta.</li>
                            </ul>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PERUBAHAN LAYANAN DAN SYARAT</h5>
                        <div>
                            <p><strong>Perubahan Layanan</strong></p>
                            <p><strong>{{ company_name() }}</strong> berhak untuk mengubah, memperbarui, atau
                                menghentikan layanan <strong>Penerbit Musik</strong> melalui <strong>{{ app_name() }}</strong>
                                atau bagian dari layanan tersebut, baik untuk seluruh pengguna atau sebagian pengguna,
                                dengan pemberitahuan sebelumnya kepada pengguna melalui platform <strong>{{ app_name() }}</strong>
                                atau email yang terdaftar pada akun pengguna. Pengguna setuju bahwa <strong>{{ company_name() }}</strong> tidak bertanggung jawab atas kerugian atau kerusakan yang timbul
                                akibat perubahan, penghentian, atau pembatasan layanan ini.</p>
                            <p>Jika perubahan layanan tersebut memengaruhi layanan yang telah diberikan kepada pengguna,
                                pengguna akan diberi kesempatan untuk meninjau dan menyetujui perubahan tersebut. Jika
                                pengguna tidak setuju dengan perubahan yang dilakukan, pengguna dapat mengakhiri penggunaan
                                layanan sesuai dengan ketentuan dalam perjanjian.</p>
                            <p><strong>11.2. Perubahan Syarat dan Ketentuan</strong></p>
                            <p><strong>{{ company_name() }}</strong> berhak untuk mengubah, memperbarui, atau
                                memperbaiki <strong>Syarat dan Ketentuan Pengguna</strong> ini dari waktu ke waktu.
                                Perubahan ini bisa disebabkan oleh perubahan peraturan perundang-undangan yang berlaku,
                                pembaruan fitur layanan, atau kebijakan internal perusahaan. <strong>Syarat dan Ketentuan
                                    Pengguna</strong> yang baru akan diberlakukan setelah diposting di platform
                                <strong>{{ app_name() }}</strong>, dan pengguna akan diberitahukan melalui email atau notifikasi
                                di akun mereka.</p>
                            <ul>
                                <li><strong>Pemberitahuan Perubahan</strong>: Pengguna akan diberi pemberitahuan tentang
                                    perubahan syarat dan ketentuan ini melalui email yang terdaftar atau notifikasi di
                                    platform <strong>{{ app_name() }}</strong>. Pengguna diwajibkan untuk memeriksa secara berkala
                                    syarat dan ketentuan yang berlaku untuk mengetahui pembaruan atau perubahan yang ada.
                                </li>
                                <li><strong>Penerimaan Perubahan</strong>: Dengan terus menggunakan layanan
                                    <strong>{{ app_name() }}</strong> setelah perubahan syarat dan ketentuan diberlakukan,
                                    pengguna dianggap telah menerima dan menyetujui perubahan tersebut. Jika pengguna tidak
                                    setuju dengan perubahan yang dibuat, pengguna berhak untuk menghentikan penggunaan
                                    layanan.</li>
                            </ul>
                            <p><strong>11.3. Pembaruan Syarat dan Ketentuan</strong></p>
                            <p>Setiap pembaruan atau perubahan terhadap syarat dan ketentuan ini akan dicatat pada halaman
                                ini dengan mencantumkan tanggal revisi terakhir. Pengguna dianjurkan untuk membaca dan
                                memahami Syarat dan Ketentuan Pengguna secara berkala untuk tetap mendapatkan informasi
                                terkini.</p>
                            <p>{{ company_name() }} berhak untuk mengakhiri atau membatasi akses pengguna ke
                                layanan {{ app_name() }} jika:</p>
                            <ul>
                                <li>Pengguna melanggar ketentuan dalam Syarat dan Ketentuan Pengguna atau peraturan hukum
                                    yang berlaku.</li>
                                <li>Layanan dihentikan atau dibatasi sesuai dengan kebijakan {{ company_name() }},
                                    yang mungkin terkait dengan perubahan regulasi atau keputusan bisnis internal.</li>
                            </ul>
                            <p>Jika penghentian atau pembatasan layanan berlaku, hak-hak pengguna untuk menerima royalti
                                yang terhutang atas karya cipta yang telah dikelola sebelumnya akan tetap dihormati sesuai
                                dengan ketentuan dalam Perjanjian Pengelolaan Hak Cipta yang telah disepakati.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PEMBEBASAN TANGGUNG JAWAB</h5>
                        <div>
                            <p>Tanggung Jawab Terhadap Penggunaan Layanan</p>
                            <ul>
                                <li>{{ company_name() }} tidak bertanggung jawab atas setiap kerugian, kerusakan,
                                    atau klaim yang timbul akibat penggunaan atau ketidakmampuan untuk menggunakan layanan
                                    {{ app_name() }}. Pengguna bertanggung jawab sepenuhnya atas keputusan dan tindakan yang
                                    diambil dalam menggunakan layanan, termasuk namun tidak terbatas pada distribusi karya
                                    cipta, pengumpulan royalti, dan pengelolaan hak cipta.</li>
                                <li>{{ company_name() }} tidak bertanggung jawab atas pelanggaran hak cipta yang
                                    dilakukan oleh pihak ketiga yang tidak terkait dengan layanan {{ app_name() }} atau distribusi
                                    yang dilakukan oleh platform pihak ketiga.</li>
                            </ul>
                            <p>Kerusakan atau Kehilangan Data</p>
                            <ul>
                                <li>Kami berusaha untuk memastikan bahwa layanan {{ app_name() }} berfungsi dengan baik dan data
                                    yang dikumpulkan aman. Namun, {{ company_name() }} tidak bertanggung jawab atas
                                    kerusakan, kehilangan data, atau kerugian lainnya yang disebabkan oleh kesalahan teknis,
                                    gangguan sistem, atau kehilangan data yang disebabkan oleh kelalaian atau masalah yang
                                    berada di luar kendali kami.</li>
                                <li>Kami tidak menjamin bahwa platform {{ app_name() }} bebas dari kesalahan atau bahwa akses ke
                                    layanan tidak akan terganggu. Pengguna setuju untuk menggunakan layanan ini dengan
                                    pemahaman bahwa gangguan atau kesalahan sistem bisa terjadi.</li>
                            </ul>
                            <p>Ketergantungan pada Konten Pihak Ketiga</p>
                            <p>Layanan {{ app_name() }} dapat menyediakan akses ke konten pihak ketiga, termasuk namun tidak
                                terbatas pada platform streaming digital atau media lain yang digunakan untuk
                                mendistribusikan karya cipta. {{ company_name() }} tidak bertanggung jawab atas
                                kualitas, ketersediaan, atau kelangsungan layanan yang disediakan oleh pihak ketiga ini.
                                Pengguna setuju untuk tidak menuntut {{ company_name() }} atas kerugian yang timbul
                                akibat ketergantungan pada atau penggunaan konten atau layanan pihak ketiga yang diakses
                                melalui platform {{ app_name() }}.</p>
                            <p>Tanggung Jawab atas Klaim Hak Cipta</p>
                            <p>Pengguna menyetujui untuk membebaskan {{ company_name() }} dari segala klaim atau
                                tuntutan hukum yang timbul akibat pelanggaran hak cipta atau hak kekayaan intelektual
                                lainnya yang terkait dengan karya cipta yang diunggah dan dikelola melalui platform
                                {{ app_name() }}. Jika terjadi pelanggaran hak cipta atau hak kekayaan intelektual lainnya terkait
                                karya cipta yang diunggah, pengguna bertanggung jawab penuh untuk menyelesaikan masalah
                                tersebut, termasuk biaya yang timbul dari klaim atau gugatan tersebut.</p>
                            <p>Pembebasan Tanggung Jawab atas Penghentian Layanan</p>
                            <p>{{ company_name() }} tidak bertanggung jawab atas kerugian atau kerusakan yang
                                timbul akibat penghentian atau pembatasan layanan yang disebabkan oleh perubahan kebijakan,
                                pembaruan teknis, atau karena alasan hukum atau administratif yang tidak dapat dihindari.
                                Pengguna setuju untuk tidak menuntut {{ company_name() }} atas penghentian layanan,
                                penghapusan akun, atau pembatasan akses yang dilakukan sesuai dengan ketentuan Syarat dan
                                Ketentuan Pengguna dan Perjanjian Pengelolaan Hak Cipta.</p>
                            <p>Tidak Ada Jaminan</p>
                            <p>Layanan {{ app_name() }} disediakan "sebagaimana adanya" tanpa jaminan apapun, baik secara
                                eksplisit maupun implisit, termasuk namun tidak terbatas pada jaminan kelayakan untuk tujuan
                                tertentu atau tidak adanya pelanggaran hak cipta. {{ company_name() }} tidak
                                memberikan jaminan bahwa layanan atau platform akan bebas dari kesalahan, gangguan, atau
                                bahwa hasil yang diperoleh melalui penggunaan layanan akan memenuhi harapan pengguna.</p>
                            <p>Pembebasan Tanggung Jawab atas Ketidakmampuan Mengakses Platform</p>
                            <p>{{ company_name() }} tidak bertanggung jawab atas ketidakmampuan pengguna untuk
                                mengakses platform {{ app_name() }} karena masalah teknis, termasuk kesalahan perangkat keras,
                                perangkat lunak, koneksi internet, atau masalah lain yang berada di luar kendali kami.
                                Pengguna setuju bahwa kami tidak akan bertanggung jawab atas keterlambatan atau kegagalan
                                dalam melakukan transaksi atau komunikasi yang terkait dengan layanan.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">HUKUM YANG BERLAKU</h5>
                        <div>
                            <p>Syarat dan Ketentuan Pengguna ini diatur oleh dan akan ditafsirkan sesuai dengan hukum yang
                                berlaku di Republik Indonesia. Semua perselisihan atau klaim yang timbul berdasarkan atau
                                sehubungan dengan penggunaan layanan {{ app_name() }} akan diselesaikan sesuai dengan hukum yang
                                berlaku di Indonesia.</p>
                            <p>Jika terjadi perselisihan yang tidak dapat diselesaikan melalui musyawarah dalam waktu 60
                                (enam puluh) hari kalender, para pihak sepakat untuk menyelesaikan sengketa tersebut melalui
                                Pengadilan Negeri Jakarta Selatan sebagai pengadilan yang berwenang untuk menyelesaikan
                                sengketa hukum yang timbul. Jika Pengadilan Negeri Jakarta Selatan tidak dipilih, maka
                                yurisdiksi yang berlaku adalah Pengadilan Negeri tempat kedudukan {{ company_name() }} berada di Jakarta, Republik Indonesia.</p>
                            <p>Syarat dan Ketentuan ini, serta semua komunikasi terkait dengan layanan {{ app_name() }}, akan
                                disusun dan diterjemahkan dalam Bahasa Indonesia. Dalam hal terjadi perbedaan penafsiran
                                antara versi Bahasa Indonesia dan terjemahan bahasa lainnya, versi Bahasa Indonesia yang
                                berlaku.</p>
                            <p>Jika ada ketentuan dalam Syarat dan Ketentuan Pengguna ini yang dianggap tidak sah, tidak
                                berlaku, atau tidak dapat dilaksanakan, maka ketentuan tersebut akan dianggap dipisahkan
                                dari ketentuan lainnya, dan sisanya tetap berlaku dan mengikat bagi kedua belah pihak.</p>
                            <p>Selain hukum yang berlaku di Indonesia, pengguna juga setuju untuk mematuhi peraturan
                                perundang-undangan yang berlaku terkait penggunaan layanan digital dan distribusi hak cipta,
                                termasuk namun tidak terbatas pada Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan
                                Data Pribadi, Undang-Undang Nomor 1 Tahun 2024 tentang Informasi dan Transaksi Elektronik,
                                dan Peraturan Pemerintah Nomor 71 Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi
                                Elektronik.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">KETENTUAN LAINNYA</h5>
                        <div>
                            <p>Force Majeure</p>
                            <p>Kami tidak akan bertanggung jawab atas keterlambatan atau kegagalan dalam pelaksanaan
                                kewajiban yang tercantum dalam Syarat dan Ketentuan Pengguna ini yang disebabkan oleh
                                kejadian-kejadian yang berada di luar kendali kami, termasuk namun tidak terbatas pada
                                bencana alam, perang, terorisme, kebakaran, banjir, pandemi, kegagalan sistem, kerusakan
                                perangkat keras atau perangkat lunak, atau kegagalan pihak ketiga dalam menyediakan layanan
                                yang diperlukan. Kejadian-kejadian tersebut dianggap sebagai Force Majeure, dan {{ company_name() }} akan segera memberitahukan pengguna tentang kejadian tersebut dan
                                mengambil langkah-langkah yang wajar untuk mengatasi dampaknya.</p>
                            <p>Keterpisahan (Severability)</p>
                            <p>Jika salah satu ketentuan dalam Syarat dan Ketentuan Pengguna ini dianggap tidak sah, ilegal,
                                atau tidak dapat diterapkan oleh pengadilan atau lembaga yang berwenang, ketentuan tersebut
                                akan dipisahkan dari syarat dan ketentuan ini tanpa mempengaruhi keberlakuan ketentuan
                                lainnya. Ketentuan yang tersisa tetap berlaku sepenuhnya dan memiliki kekuatan hukum.</p>
                            <p>Pengalihan (Assignment)</p>
                            <p>Pengguna tidak diperkenankan untuk mengalihkan hak atau kewajiban yang timbul dari Syarat dan
                                Ketentuan Pengguna ini kepada pihak lain tanpa persetujuan tertulis dari {{ company_name() }}. Sebaliknya, {{ company_name() }} berhak untuk mengalihkan hak atau
                                kewajibannya kepada pihak lain sesuai dengan kebutuhan operasional atau peraturan yang
                                berlaku, tanpa pemberitahuan lebih lanjut kepada pengguna.</p>
                            <p>Tidak Ada Hubungan Kemitraan atau Agensi</p>
                            <p>Tidak ada ketentuan dalam Syarat dan Ketentuan Pengguna ini yang mengarah pada pembentukan
                                hubungan kemitraan, joint venture, atau hubungan agensi antara pengguna dan {{ company_name() }}. Pengguna tidak berhak untuk bertindak atau mewakili diri mereka sebagai
                                perwakilan {{ company_name() }}, kecuali jika secara eksplisit ditentukan dalam
                                perjanjian tertulis antara kedua belah pihak.</p>
                            <p>Penggunaan Layanan oleh Pengguna yang Berusia di Bawah 18 Tahun</p>
                            <p>Layanan {{ app_name() }} hanya dapat digunakan oleh individu yang berusia 18 tahun ke atas. Jika
                                pengguna berusia antara 13 hingga 17 tahun, mereka dapat menggunakan layanan ini hanya
                                dengan izin tertulis dari orang tua atau wali sah mereka. Pengguna yang berusia di bawah 18
                                tahun harus memiliki persetujuan orang tua atau wali untuk dapat menggunakan layanan
                                Penerbit Musik.</p>
                            <p>Penggunaan oleh Entitas Hukum</p>
                            <p>Layanan Penerbit Musik hanya tersedia untuk individu (orang pribadi) yang memiliki hak cipta
                                atau izin yang sah atas karya cipta yang diunggah. {{ company_name() }} tidak
                                menyediakan layanan kepada badan hukum atau entitas lainnya untuk pengelolaan hak cipta.
                                Jika terdapat perubahan pada ketentuan ini, pengguna akan diberitahukan melalui platform.
                            </p>
                            <p>Ketentuan tentang Penyelesaian Perselisihan</p>
                            <p>Setiap sengketa atau perselisihan yang timbul antara pengguna dan {{ company_name() }} terkait dengan penggunaan layanan ini akan diselesaikan sesuai dengan ketentuan dalam
                                Syarat dan Ketentuan Pengguna ini dan melalui prosedur yang telah ditentukan, termasuk
                                melalui Pengadilan Negeri Jakarta Selatan atau pengadilan yang berwenang berdasarkan hukum
                                yang berlaku di Indonesia.</p>
                            <p>Persetujuan untuk Komunikasi Elektronik</p>
                            <p>Dengan menggunakan layanan {{ app_name() }}, pengguna menyetujui bahwa {{ company_name() }} dapat berkomunikasi dengan pengguna melalui email, notifikasi dalam aplikasi, atau
                                cara lain yang bersifat elektronik untuk tujuan pengelolaan akun, pemberitahuan mengenai
                                pembaruan layanan, atau untuk tujuan administratif lainnya.</p>
                            <p>Kebijakan Privasi</p>
                            <p>Penggunaan layanan ini juga tunduk pada Kebijakan Privasi yang terpisah yang menjelaskan
                                lebih lanjut tentang cara kami mengumpulkan, menggunakan, melindungi, dan mengelola data
                                pribadi pengguna.</p>
                        </div>
                        <p>&nbsp;</p>
                        <h5 class="text-center mb-3">PENUTUP</h5>
                        <div>
                            <p>Syarat dan Ketentuan Pengguna ini, bersama dengan Perjanjian Pengelolaan Hak Cipta atas Lagu
                                dan/atau Musik dan kebijakan lainnya yang berlaku, merupakan keseluruhan perjanjian antara
                                pengguna dan {{ company_name() }} terkait penggunaan layanan Penerbit Musik melalui
                                {{ app_name() }}. Perjanjian ini menggantikan semua komunikasi, diskusi, dan perjanjian
                                sebelumnya, baik lisan maupun tertulis, yang berkaitan dengan hal yang sama.</p>
                            <p>Dengan menggunakan layanan {{ app_name() }}, Anda mengakui bahwa Anda telah membaca, memahami, dan
                                setuju untuk terikat oleh Syarat dan Ketentuan Pengguna ini. Anda juga menyetujui bahwa {{ company_name() }} dapat memperbarui atau mengubah syarat dan ketentuan ini kapan
                                saja sesuai dengan ketentuan yang telah dijelaskan.</p>
                            <p>Jika Anda memiliki pertanyaan atau membutuhkan klarifikasi mengenai Syarat dan Ketentuan
                                Pengguna ini, Anda dapat menghubungi kami melalui informasi kontak yang tersedia di platform
                                {{ app_name() }}. Kami akan dengan senang hati membantu menjawab pertanyaan Anda terkait layanan
                                kami.</p>
                            <p>Meskipun Anda berhenti menggunakan layanan {{ app_name() }} atau akun Anda dihentikan,
                                ketentuan-ketentuan dalam Syarat dan Ketentuan Pengguna yang berhubungan dengan pembayaran
                                royalti, pelanggaran hak cipta, dan kewajiban hukum lainnya akan tetap berlaku. Hal ini
                                memastikan bahwa hak-hak dan kewajiban yang sudah terakumulasi tetap dihormati meskipun
                                hubungan antara pengguna dan {{ company_name() }} berakhir.</p>
                            <p>Syarat dan Ketentuan Pengguna ini mulai berlaku sejak tanggal yang tercantum pada bagian awal
                                dokumen ini dan akan terus berlaku sampai ada perubahan atau pembaruan yang diumumkan oleh
                                {{ company_name() }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection