<?php

return [
    'attributes' => [
        'companyname'           => 'Company Name',
        'firstname'             => 'First Name',
        'lastname'              => 'Last Name',
        'email'                 => 'Email',
        'phone'                 => 'Phone',
        'address1'              => 'Address 1',
        'address2'              => 'Address 2',
        'city'                  => 'City',
        'state'                 => 'State',
        'zip'                   => 'Zip',
        'country'               => 'Country',
        'bankname'              => 'Bank Name',
        'bankbranch'            => 'Bank Branch',
        'bankaccountname'       => 'Bank Account Name',
        'bankaccountnumber'     => 'Bank Account Number',
        'password'              => 'Password',
        'password_confirmation' => 'Password Confirmation',
        'ktp'                   => 'Personal ID Card (KTP)',
        'ktp_num'               => 'Personal ID Number (NIK) (NIK)',
        'nibcompany'            => 'Company ID (NIB)',
        'npwpcompany'           => 'Company Tax ID (NPWP)',
        'npwppersonal'          => 'Personal Tax ID (NPWP)',
        'registrationtype'      => 'Registration Type',
        'signature'             => 'Signature',

        'nibcompany_num'        => 'Company ID Number (NIB)',
        'npwpcompany_num'       => 'Company Tax ID Number (NPWP)',
        'npwppersonal_num'      => 'Personal Tax ID Number (NPWP)',
        'position'              => 'Position',

        'deed'                  => 'Company Deed (Akta Perusahaan)',
        'establishment'         => 'Company Decree of Establishment (SK Kementerian)',

        'title'                 => 'Title',
        'titlelang'             => 'Title Language',
        'recordlabel'           => 'Record Label',
        'version'               => 'Version',
        'ipl'                   => 'Music Usage License (IPL)',
        'genre'                 => 'Genre',
        'subgenre'              => 'Sub-Genre',
        'album_title'           => 'Title',
        'album_titlelang'       => 'Title Language',
        'album_version'         => 'Version',
        'album_genre'           => 'Genre',
        'album_subgenre'        => 'Sub-Genre',
        'track_title'           => 'Title',
        'track_titlelang'       => 'Title Language',
        'track_version'         => 'Version',
        'track_genre'           => 'Genre',
        'track_subgenre'        => 'Sub-Genre',
        'songwriters'           => 'Songwriters',
        'released'              => 'Released',
        'releaseddate'          => 'Released Date',
        'upc'                   => 'UPC',
        'releaseon'             => 'Release Date',
        'tiktok_ytmusic_rerelease_date' => 'TikTok & YouTube Music Pre-Release Date',

        'primaryartists'        => 'Primary Artists',
        'primaryartists.*'      => 'Primary Artists',
        'primaryartists.*.name'   => 'Primary Artist :position Name',
        'primaryartists.*.social'   => 'Primary Artist :position Social Media Link',
        'primaryartists.*.spotify'   => 'Primary Artist :position Spotify Link',

        'featuredartists'        => 'Featured Artists',
        'featuredartists.*'      => 'Featured Artists',
        'featuredartists.*.name'   => 'Featured Artist :position Name',
        'featuredartists.*.social'   => 'Featured Artist :position Social Media Link',
        'featuredartists.*.spotify'   => 'Featured Artist :position Spotify Link',

        'tracknum'              => 'Track Number',
        'type'                  => 'Type',
        'cover'                 => 'Cover',
        'cover_draft'           => 'Cover Draft',

        'status'                => 'Status',

        'tracks'                => 'Tracks',

        'songwriters.*.name'       => 'Songwriter #:position Name',
        'songwriters.*.role'       => 'Songwriter #:position Role',

        'contributors.*.name'      => 'Contributor #:position Name',
        'contributors.*.role'      => 'Contributor #:position Role',

        'productions.*.name'       => 'Production Team #:position Name',
        'productions.*.role'       => 'Production Team #:position Role',
    ],
    'custom' => [
        'lyrics'    => [
            'required_if' => 'The lyrics field is required when track type is not instrumental.',
        ],
        'lyriclang' => [
            'required_if' => 'The lyrics language field is required when track type is not instrumental.',
        ],
        'explicit'  => [
            'required_if' => 'The explicit field is required when track type is not instrumental.',
        ],
        'songwriter'    => [
            'required_if' => 'The songwriter field is required when track type is not instrumental.',
        ],
        'ipl.required_if' => 'Music Usage License (IPL) is required if version is cover.',
    ],
];