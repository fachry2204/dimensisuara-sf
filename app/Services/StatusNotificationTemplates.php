<?php

namespace App\Services;

class StatusNotificationTemplates
{
    public const TOKENS = ['nama','aplikasi','status','status_sebelumnya','judul_rilis','artis','upc','tanggal_rilis','alasan','tautan'];

    public function events(): array
    {
        return [
            'user.pending'=>['label'=>'User: Menunggu Verifikasi','status'=>'Menunggu verifikasi','group'=>'Pengguna','body'=>"Halo {nama},\n\nPendaftaran akun {aplikasi} sudah diterima. Tim kami sedang memeriksa data dan dokumen Anda.\n\nSilakan pastikan profil dan dokumen sudah lengkap. Kami akan memberi kabar setelah pemeriksaan selesai."],
            'user.verified'=>['label'=>'User: Terverifikasi','status'=>'Terverifikasi','group'=>'Pengguna','body'=>"Halo {nama},\n\nAkun Anda di {aplikasi} telah terverifikasi. Anda dapat melanjutkan pengelolaan musik dan layanan yang tersedia di akun Anda.\n\nBuka akun untuk melihat langkah berikutnya."],
            'user.blocked'=>['label'=>'User: Diblokir','status'=>'Diblokir','group'=>'Pengguna','body'=>"Halo {nama},\n\nStatus akun Anda di {aplikasi} berubah menjadi diblokir.\n\nCatatan tim: {alasan}\n\nHubungi tim kami apabila Anda membutuhkan penjelasan lebih lanjut."],
            'user.email_verified'=>['label'=>'User: Email Terverifikasi','status'=>'Email terverifikasi','group'=>'Pengguna','body'=>"Halo {nama},\n\nAlamat email akun {aplikasi} Anda sudah terverifikasi. Status pemeriksaan profil dan dokumen dapat dilihat melalui akun Anda."],
            'release.draft'=>['label'=>'Rilis: Draft','status'=>'Draft','group'=>'Rilis','body'=>"Halo {nama},\n\nRilis {judul_rilis} tersimpan sebagai draft. Lengkapi metadata, cover art, dan audio sebelum mengajukannya untuk pemeriksaan."],
            'release.pending'=>['label'=>'Rilis: Menunggu Pemeriksaan','status'=>'Menunggu pemeriksaan','group'=>'Rilis','body'=>"Halo {nama},\n\nPengajuan rilis {judul_rilis} telah kami terima. Rilis Anda sedang menunggu pemeriksaan tim {aplikasi}.\n\nAnda dapat memantau perkembangannya melalui halaman detail rilis."],
            'release.revision'=>['label'=>'Rilis: Revisi','status'=>'Revisi','group'=>'Rilis','body'=>"Halo {nama},\n\nRilis {judul_rilis} berada pada tahap revisi.\n\nCatatan tim: {alasan}\n\nBuka detail rilis untuk memeriksa informasi revisi dan tindak lanjutnya."],
            'release.review'=>['label'=>'Rilis: Sedang Ditinjau','status'=>'Sedang ditinjau','group'=>'Rilis','body'=>"Halo {nama},\n\nTim {aplikasi} sedang meninjau rilis {judul_rilis}, termasuk metadata, cover art, dan audio.\n\nKami akan memberi kabar saat statusnya berubah."],
            'release.uploading'=>['label'=>'Rilis: Proses Distribusi','status'=>'Proses distribusi','group'=>'Rilis','body'=>"Halo {nama},\n\nRilis {judul_rilis} sedang diproses untuk distribusi ke platform musik.\n\nProses dan waktu ketersediaan dapat berbeda pada setiap platform. Pantau detail rilis untuk pembaruan selanjutnya."],
            'release.released'=>['label'=>'Rilis: Released','status'=>'Released','group'=>'Rilis','body'=>"Halo {nama},\n\nStatus rilis {judul_rilis} di {aplikasi} telah berubah menjadi Released.\n\nTanggal rilis: {tanggal_rilis}\nUPC: {upc}\n\nKetersediaan di setiap platform mengikuti jadwal dan proses masing-masing. Buka detail rilis untuk informasi terbaru."],
            'release.rejected'=>['label'=>'Rilis: Ditolak','status'=>'Ditolak','group'=>'Rilis','body'=>"Halo {nama},\n\nRilis {judul_rilis} belum dapat dilanjutkan.\n\nAlasan: {alasan}\n\nBuka detail rilis, tinjau catatan tim dan batas waktu revisi yang tertera, lalu lakukan perbaikan yang diperlukan."],
        ];
    }

    public function configuration(string $channel): array
    {
        $stored = app(SystemSettings::class)->get('status_notifications_'.$channel) ?? [];
        $defaults = ['enabled'=>$channel === 'email','accent'=>'#6d28d9','footer'=>'Terima kasih telah mempercayakan karya Anda kepada {aplikasi}.','templates'=>[]];
        foreach ($this->events() as $event=>$meta) {
            $defaults['templates'][$event] = ['enabled'=>$event !== 'release.draft','subject'=>'{aplikasi} · '.$meta['status'],
                'heading'=>$meta['status'],'body'=>$meta['body'],'button'=>'Buka '.($meta['group'] === 'Rilis' ? 'Detail Rilis' : 'Akun')];
        }
        return array_replace_recursive($defaults,is_array($stored) ? $stored : []);
    }

    public function render(string $channel, string $event, array $context, ?array $configuration = null): array
    {
        $configuration ??= $this->configuration($channel);
        $template = $configuration['templates'][$event];
        $replace = [];
        foreach (self::TOKENS as $token) $replace['{'.$token.'}'] = (string) ($context[$token] ?? '—');
        $render = static fn ($value) => strtr($value,$replace);
        $singleLine = static fn ($value) => trim(preg_replace('/[\r\n]+/',' ', $value));
        return [
            'subject'=>$singleLine($render($template['subject'])), 'heading'=>$singleLine($render($template['heading'])),
            'body'=>$render($template['body']), 'button'=>$singleLine($render($template['button'])),
            'footer'=>$render($configuration['footer']), 'accent'=>$configuration['accent'],
            'context'=>$context,
            'whatsapp'=>'*'.$singleLine($render($template['heading']))."*\n\n".$render($template['body'])."\n\n".$singleLine($render($template['button'])).': '.$context['tautan']."\n\n".$render($configuration['footer']),
        ];
    }

    public function sample(string $event): array
    {
        return ['nama'=>'Kreator Contoh','aplikasi'=>app_name(),'status'=>$this->events()[$event]['status'],
            'status_sebelumnya'=>str_starts_with($event,'release.') ? 'Menunggu pemeriksaan' : 'Menunggu verifikasi',
            'judul_rilis'=>str_starts_with($event,'release.') ? 'Cerita di Dalam Musik' : '',
            'artis'=>str_starts_with($event,'release.') ? 'Artis Contoh' : '', 'upc'=>'8991234567890',
            'tanggal_rilis'=>now()->addDays(14)->locale('id')->translatedFormat('d F Y'),
            'alasan'=>'Mohon periksa kembali informasi dan dokumen yang ditandai oleh tim.',
            'tautan'=>url('/login')];
    }
}
