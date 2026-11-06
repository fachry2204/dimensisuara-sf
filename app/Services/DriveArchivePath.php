<?php

namespace App\Services;

use App\Models\File;
use App\Models\Release;
use App\Models\Track;
use App\Support\ArtistCredits;

class DriveArchivePath
{
    public const VERSION = 2;

    public function context(File $file): array
    {
        $owner = $file->owner;
        $release = $owner instanceof Release ? $owner : ($owner instanceof Track
            ? ($owner->relationLoaded('releases') ? $owner->releases->where('status','released')->sortBy('id')->first() : $owner->releases()->where('status','released')->reorder('releases.id')->first()) : null);
        if (!$release || $release->status !== 'released') throw new \RuntimeException('File belum terhubung ke rilisan Published.');
        $profile = $release->profile;
        $client = $profile ? ($profile->is_company && filled($profile->companyname) ? $profile->companyname : trim($profile->firstname.' '.$profile->lastname)) : '';
        $client = $client ?: 'Client '.$release->profileid;
        $artists = ArtistCredits::normalize($release->primaryartists);
        $artist = implode(' & ',array_map(fn ($credit) => $credit->name,$artists)) ?: 'Artis belum diisi';
        return ['release'=>$release,'client'=>$client,'artist'=>$artist,'title'=>$owner instanceof Track ? $owner->title : $release->title];
    }

    public function segment(string $name): string
    {
        $name = str_replace(['\\','/',':','*','?','[',']','{','}','..'], ' ', $name);
        $name = preg_replace('/[\x00-\x1f\x7f]+/u',' ',$name);
        $name = preg_replace('/\s+/u',' ',trim($name));
        return trim(mb_strcut($name,0,140,'UTF-8')," .") ?: 'Tanpa nama';
    }

    public function destination(File $file, string $md5): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/iD',$md5)) throw new \RuntimeException('Checksum sumber tidak valid untuk penamaan arsip.');
        $context = $this->context($file);
        $release = $context['release'];
        // IDs disambiguate clients/releases with identical names without numeric-only folders.
        $folders = [$this->segment($context['client']).' (C'.$release->profileid.')', $this->segment($context['artist']), $this->segment($release->title).' (R'.$release->id.')'];
        $label = ['cover'=>'Cover','thumbnail'=>'Thumbnail','ipl'=>'IPL','full'=>'Audio Penuh','trimmed'=>'Audio Potongan'][$file->type] ?? 'File';
        $extension = preg_replace('/[^a-z0-9]/','',strtolower(pathinfo($file->identifier,PATHINFO_EXTENSION)));
        $name = $this->segment($context['title']).' - '.$label.' (F'.$file->id.'-'.substr(strtolower($md5),0,12).')'.($extension ? '.'.$extension : '');
        return rtrim(app(RcloneDrive::class)->root(),'/').'/'.implode('/',$folders).'/'.$name;
    }
}
