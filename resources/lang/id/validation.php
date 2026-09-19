<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'              => ':attribute harus diterima.',
    'accepted_if'           => ':attribute harus diterima ketika :other berisi :value.',
    'active_url'            => ':attribute bukan URL yang valid.',
    'after'                 => ':attribute harus berisi tanggal setelah :date.',
    'after_or_equal'        => ':attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha'                 => ':attribute hanya boleh berisi huruf.',
    'alpha_dash'            => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
    'alpha_num'             => ':attribute hanya boleh berisi huruf dan angka.',
    'array'                 => ':attribute harus berisi sebuah array.',
    'ascii'                 => ':attribute hanya boleh berisi karakter alfanumerik dan simbol satu-byte.',
    'before'                => ':attribute harus berisi tanggal sebelum :date.',
    'before_or_equal'       => ':attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between'   => [
        'numeric'           => ':attribute harus bernilai antara :min sampai :max.',
        'file'              => ':attribute harus berukuran antara :min sampai :max kilobita.',
        'string'            => ':attribute harus berisi antara :min sampai :max karakter.',
        'array'             => ':attribute harus memiliki :min sampai :max anggota.',
    ],
    'boolean'               => ':attribute harus bernilai benar atau salah',
    'can'                   => ':attribute mengandung nilai yang tidak diizinkan.',
    'confirmed'             => 'Konfirmasi :attribute tidak cocok.',
    'contains'              => ':attribute harus berisi salah satu dari berikut: :values.',
    'current_password'      => 'Password salah.',
    'date'                  => ':attribute bukan tanggal yang valid.',
    'date_equals'           => ':attribute harus berisi tanggal yang sama dengan :date.',
    'date_format'           => ':attribute tidak cocok dengan format :format.',
    'decimal'               => ':attribute harus memiliki :decimal tempat desimal.',
    'declined'              => ':attribute harus ditolak.',
    'declined_if'           => ':attribute harus ditolak bila :other adalah :value.',
    'different'             => ':attribute dan :other harus berbeda.',
    'digits'                => ':attribute harus terdiri dari :digits angka.',
    'digits_between'        => ':attribute harus terdiri dari :min sampai :max angka.',
    'dimensions'            => ':attribute tidak memiliki dimensi gambar yang valid.',
    'distinct'              => ':attribute memiliki nilai yang duplikat.',
    'doesnt_end_with'       => ':attribute tidak boleh diakhiri dengan salah satu dari berikut: :values.',
    'doesnt_start_with'     => ':attribute tidak boleh diawali dengan salah satu dari berikut: :values.',
    'email'                 => ':attribute harus berupa alamat surel yang valid.',
    'ends_with'             => ':attribute harus diakhiri salah satu dari berikut: :values',
    'enum'                  => ':attribute yang dipilih tidak valid.',
    'exists'                => ':attribute yang dipilih tidak valid.',
    'extensions'            => ':attribute harus memiliki salah satu dari ekstensi berikut :values.',
    'file'                  => ':attribute harus berupa sebuah berkas.',
    'filled'                => ':attribute harus memiliki nilai.',
    'gt'    => [
        'numeric'           => ':attribute harus bernilai lebih besar dari :value.',
        'file'              => ':attribute harus berukuran lebih besar dari :value kilobita.',
        'string'            => ':attribute harus berisi lebih besar dari :value karakter.',
        'array'             => ':attribute harus memiliki lebih dari :value anggota.',
    ],
    'gte' => [
        'numeric'           => ':attribute harus bernilai lebih besar dari atau sama dengan :value.',
        'file'              => ':attribute harus berukuran lebih besar dari atau sama dengan :value kilobita.',
        'string'            => ':attribute harus berisi lebih besar dari atau sama dengan :value karakter.',
        'array'             => ':attribute harus terdiri dari :value anggota atau lebih.',
    ],
    'hex_color'             => ':attribute harus berupa warna heksadesimal.',
    'image'                 => ':attribute harus berupa gambar.',
    'in'                    => ':attribute yang dipilih tidak valid.',
    'in_array'              => ':attribute tidak ada di dalam :other.',
    'integer'               => ':attribute harus berupa bilangan bulat.',
    'ip'                    => ':attribute harus berupa alamat IP yang valid.',
    'ipv4'                  => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6'                  => ':attribute harus berupa alamat IPv6 yang valid.',
    'json'                  => ':attribute harus berupa JSON string yang valid.',
    'list'                  => ':attribute harus berupa daftar.',
    'lowercase'             => ':attribute harus berupa huruf kecil.',
    'lt'    => [
        'numeric'           => ':attribute harus bernilai kurang dari :value.',
        'file'              => ':attribute harus berukuran kurang dari :value kilobita.',
        'string'            => ':attribute harus berisi kurang dari :value karakter.',
        'array'             => ':attribute harus memiliki kurang dari :value anggota.',
    ],
    'lte' => [
        'numeric'           => ':attribute harus bernilai kurang dari atau sama dengan :value.',
        'file'              => ':attribute harus berukuran kurang dari atau sama dengan :value kilobita.',
        'string'            => ':attribute harus berisi kurang dari atau sama dengan :value karakter.',
        'array'             => ':attribute harus tidak lebih dari :value anggota.',
    ],
    'mac_address'           => ':attribute harus berupa MAC Address yang valid.',
    'max' => [
        'numeric'           => ':attribute maskimal berjumlah :max.',
        'file'              => ':attribute maksimal berukuran :max kilobita.',
        'string'            => ':attribute maskimal berisi :max karakter.',
        'array'             => ':attribute maksimal terdiri dari :max anggota.',
    ],
    'max_digits'            => ':attribute harus berisi maksimal :max angka.',
    'mimes'                 => ':attribute harus berupa berkas berjenis: :values.',
    'mimetypes'             => ':attribute harus berupa berkas berjenis: :values.',
    'min'       => [
        'numeric'           => ':attribute minimal berjumlah :min.',
        'file'              => ':attribute minimal berukuran :min kilobita.',
        'string'            => ':attribute minimal berisi :min karakter.',
        'array'             => ':attribute minimal terdiri dari :min anggota.',
    ],
    'min_digits'            => ':attribute harus berisi minimal :min angka.',
    'missing'               => ':attribute harus tidak ada.',
    'missing_if'            => ':attribute harus tidak ada bila :other adalah :value.',
    'missing_unless'        => ':attribute harus tidak ada kecuali :other memiliki nilai :values.',
    'missing_with'          => ':attribute harus tidak ada bila terdapat :values.',
    'missing_with_all'      => ':attribute harus tidak ada bila terdapat :values.',
    'multiple_of'           => ':attribute harus merupakan kelipatan dari :value',
    'not_in'                => ':attribute yang dipilih tidak valid.',
    'not_regex'             => 'Format :attribute tidak valid.',
    'numeric'               => ':attribute harus berupa angka.',
    'password' => [
        'letters'           => ':attribute harus mengandung setidaknya satu huruf.',
        'mixed'             => ':attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers'           => ':attribute harus mengandung setidaknya satu angka.',
        'symbols'           => ':attribute harus mengandung setidaknya satu simbol.',
        'uncompromised'     => ':attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'present'               => ':attribute wajib ada.',
    'present_if'            => ':attribute harus ada ketika :other adalah :value.',
    'present_unless'        => ':attribute harus ada kecuali :other adalah :value.',
    'present_with'          => ':attribute harus ada ketika :values ada.',
    'present_with_all'      => ':attribute harus ada ketika :values ada.',
    'prohibited'            => ':attribute dilarang.',
    'prohibited_if'         => ':attribute dilarang ketika :other adalah :value.',
    'prohibited_unless'     => ':attribute dilarang kecuali :other ada di :values.',
    'prohibits'             => ':attribute melarang :other untuk ada.',
    'regex'                 => 'Format :attribute tidak valid.',
    'required'              => ':attribute wajib diisi.',
    'required_array_keys'   => ':attribute harus berisi entri untuk: :values.',
    'required_if'           => ':attribute wajib diisi bila :other adalah :value.',
    'required_if_accepted'  => ':attribute harus diisi ketika :other diterima.',
    'required_if_declined'  => ':attribute harus diisi ketika :other ditolak.',
    'required_unless'       => ':attribute wajib diisi kecuali :other memiliki nilai :values.',
    'required_with'         => ':attribute wajib diisi bila terdapat :values.',
    'required_with_all'     => ':attribute wajib diisi bila terdapat :values.',
    'required_without'      => ':attribute wajib diisi bila tidak terdapat :values.',
    'required_without_all'  => ':attribute wajib diisi bila sama sekali tidak terdapat :values.',
    'same'                  => ':attribute dan :other harus sama.',
    'size'  => [
        'numeric'           => ':attribute harus berukuran :size.',
        'file'              => ':attribute harus berukuran :size kilobyte.',
        'string'            => ':attribute harus berukuran :size karakter.',
        'array'             => ':attribute harus mengandung :size anggota.',
    ],
    'starts_with'           => ':attribute harus diawali salah satu dari berikut: :values',
    'string'                => ':attribute harus berupa string.',
    'timezone'              => ':attribute harus berisi zona waktu yang valid.',
    'unique'                => ':attribute sudah ada sebelumnya.',
    'uploaded'              => ':attribute gagal diunggah.',
    'uppercase'             => ':attribute harus berupa huruf besar.',
    'url'                   => ':attribute harus berisi URL yang valid.',
    'ulid'                  => ':attribute harus berisi ULID yang valid.',
    'uuid'                  => ':attribute harus berisi UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'lyrics'    => [
            'required_if' => 'Lirik wajib diisi jika lagu bukan instrumental.',
        ],
        'lyriclang' => [
            'required_if' => 'Bahasa lirik wajib diisi jika lagu bukan instrumental.',
        ],
        'explicit'  => [
            'required_if' => 'Status explicit wajib diisi jika lagu bukan instrumental.',
        ],
        'ipl' => [
            'required_if' => 'Izin Penggunaan Lagu (IPL) wajib diunggah untuk lagu cover.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'companyname'           => 'Nama perusahaan',
        'firstname'             => 'Nama depan',
        'lastname'              => 'Nama belakang',
        'email'                 => 'Alamat email',
        'phone'                 => 'Nomor telepon',
        'address1'              => 'Alamat baris 1',
        'address2'              => 'Alamat baris 2',
        'city'                  => 'Kota',
        'state'                 => 'Provinsi',

        'provinceid'            => 'Provinsi',
        'cityid'                => 'Kabupaten/Kota',
        'districtid'            => 'Kecamatan',
        'villageid'             => 'Kelurahan',

        'zip'                   => 'Kode pos',
        'country'               => 'Negara',
        'bankname'              => 'Nama bank',
        'bankbranch'            => 'Cabang bank',
        'bankaccountname'       => 'Nama pemilik rekening',
        'bankaccountnumber'     => 'Nomor rekening',
        'password'              => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'ktp'                   => 'KTP',
        'nibcompany'            => 'NIB perusahaan',
        'npwpcompany'           => 'NPWP perusahaan',
        'npwppersonal'          => 'NPWP pribadi',
        'registrationtype'      => 'Tipe pendaftaran',
        'signature'             => 'Tanda tangan',

        'album_title'       => 'Judul album/single',
        'album_titlelang'   => 'Bahasa judul',
        'recordlabel'       => 'Label rekaman',
        'album_version'     => 'Versi album/single',
        'album_genre'       => 'Genre album/single',
        'album_subgenre'    => 'Sub-Genre album/single',
        'released'          => 'Keterangan rilis sebelumnya',
        'releaseddate'      => 'Tanggal rilis sebelumnya',
        'upc'               => 'UPC',
        'releaseon'         => 'Tanggal rilis',
        'tiktok_ytmusic_rerelease_date' => 'Tanggal Pra-Rilis TikTok & YouTube Music',
        'primaryartists'    => 'Artis utama',
        'tracknum'          => 'Jumlah lagu',
        'type'              => 'Tipe rilis',
        'cover'             => 'Cover',
        'cover_draft'       => 'Cover draft',
        'status'            => 'Status',
        'tracks'            => 'Tracks',

        'primaryartists.*.name'   => 'Nama Artis Utama ke-:position',
        'primaryartists.*.social'   => 'Media Sosial Artis Utama ke-:position',
        'primaryartists.*.spotify'   => 'Link Spotify Artis Utama ke-:position',

        'featuredartists.*.name'   => 'Nama Artis Unggulan ke-:position',
        'featuredartists.*.social'   => 'Media Sosial Artis Unggulan ke-:position',
        'featuredartists.*.spotify'   => 'Link Spotify Artis Unggulan ke-:position',

        'songwriters.*.name'       => 'Nama Penulis Lagu ke-:position',
        'songwriters.*.role'       => 'Peran Penulis Lagu ke-:position',

        'contributors.*.name'      => 'Nama Kontributor ke-:position',
        'contributors.*.role'      => 'Peran Kontributor ke-:position',

        'productions.*.name'       => 'Nama Tim Produksi ke-:position',
        'productions.*.role'       => 'Peran Tim Produksi ke-:position',

        'order'                 => 'Urutan',
        'track_title'           => 'Judul lagu',
        'track_titlelang'       => 'Bahasa judul lagu',
        'track_version'         => 'Versi',
        'track_genre'           => 'Genre',
        'track_subgenre'        => 'Sub-Genre',
        'songwriters'           => 'Penulis lagu',
        'composer'              => 'Komposer',
        'instrumental'          => 'Instrumental',
        'lyrics'                => 'Lirik',
        'lyriclang'             => 'Bahasa lirik',
        'explicit'              => 'Explicit',
        'songwriter'            => 'Penulis lagu',
        'isrc'                  => 'ISRC',
        'file_draft'            => 'Full song',
        'file_trimmed_draft'    => 'TikTok ref',
        'duration'              => 'Durasi',

        'primaryartists'        => 'Artis utama',
        'featuredartists'       => 'Artis unggulan/featured',
        'contributors'          => 'Kontributor',
        'productions'           => 'Produksi',
        'name'                  => 'Nama',
        'role'                  => 'Peran',
    ],

];
