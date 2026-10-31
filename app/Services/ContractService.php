<?php

namespace App\Services;

use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;

use App\Exceptions\Contract\UnsupportedTypeException;
use App\Exceptions\Contract\NumberAlreadyUsedException;
use App\Exceptions\Contract\NoUploadedFileException;

use App\Models\Contract;
use App\Models\File;
use App\Models\Publishing\Publishing;
use App\Models\User\Profile;


use App\Services\FileService;

use Dompdf\Dompdf;
use Dompdf\Options;

class ContractService
{
    private static function getTemplate(string $path): string
    {
        $html = app(CompanyIdentity::class)->tokenize(FileFacade::get($path));
        return preg_replace_callback('/Soundfresh(?:\.id)?/i', fn () => e(app_name()), $html);
    }

    public function store($data)
    {
        return \Illuminate\Support\Facades\DB::transaction(fn () => $this->createContract($data));
    }

    private function createContract($data)
    {
        $profileid = $data['profileid'];
        $type = $data['type'];
        $number = $data['number'];
        $month = $data['month'];
        $year = $data['year'];
        $contractDate = null;

        if ($type === 'aggregator') {
            $contractDate = \Illuminate\Support\Facades\Validator::make([
                'contract_date' => $data['contract_date'] ?? now()->toDateString(),
            ], ['contract_date' => ['required', 'date_format:Y-m-d']])->validate()['contract_date'];
            $profile = Profile::lockForUpdate()->findOrFail($profileid);
            if ($profile->status !== 'verified' || !$profile->feature_aggregator) {
                throw new \DomainException('Kontrak Aggregator hanya dapat dibuat untuk user terverifikasi dengan fitur Aggregator aktif.');
            }
            $rates = \Illuminate\Support\Facades\Validator::make([
                'aggregator_rate' => $data['aggregator_rate'] ?? $profile->aggregator_rate,
                'system_rate' => $data['system_rate'] ?? (100 - (int) $profile->aggregator_rate),
            ], [
                'aggregator_rate' => ['required', 'numeric', 'multiple_of:1', 'between:0,100'],
                'system_rate' => ['required', 'numeric', 'multiple_of:1', 'between:0,100'],
            ])->validate();
            if ((int) $rates['aggregator_rate'] + (int) $rates['system_rate'] !== 100) {
                throw \Illuminate\Validation\ValidationException::withMessages(['aggregator_rate' => 'Total Pembagian User dan Pembagian System harus 100%.']);
            }
        }

        // Additional validation
        if (!in_array($type, ['aggregator', 'publishing', 'publishing_statement'])) {
            throw new UnsupportedTypeException($type, ['aggregator', 'publishing', 'publishing_statement']);
        }

        // Check if contract already exists
        if (Contract::where('type', $type)->where('number', $number)->where('year', $year)->exists()) {
            throw new NumberAlreadyUsedException();
        }

        // Create contract
        $contract = Contract::create([
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'number' => $number,
            'sort' => $year . '-' . str_pad($number, 3, '0', STR_PAD_LEFT),
            'docnum' => self::prettyDocNum($type, $number, $month, $year),
            'profileid' => $profileid,
            'contract_date' => $contractDate,
        ]);

        // For aggregator, set owner morph to Profile (redundant with profileid, but consistent)
        if ($type === 'aggregator') {
            $profile->aggregator_rate = (int) $rates['aggregator_rate'];
            $profile->save();
            $contract->user_percentage = (int) $rates['aggregator_rate'];
            $contract->system_percentage = (int) $rates['system_rate'];
            $contract->setRelation('profile', $profile);
            $contract->owner()->associate($profile);
            $contract->generated_html = self::renderAggregatorHtml($contract);
            $contract->save();
        }

        return $contract;
    }

    public function show(Contract $contract)
    {
        // Check if contract is uploaded
        if (!$contract->file) {
            throw new NoUploadedFileException();
        }

        // Get file
        $file = Storage::disk($contract->file->disk)->get($contract->file->identifier);

        // Return response
        return response($file)
            ->header('Content-Type', $contract->file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $contract->docnum . '"');
    }

    public function upload(Contract $contract, $data, $advType = null)
    {
        // Store file
        FileService::store($data['file'], $contract, $contract->profile, $advType, 'files');
    }

    public function destroy(Contract $contract)
    {
        // Delete contract
        $contract->delete();
    }

    public function uploadDigitalStamp(Contract $contract, \Illuminate\Http\UploadedFile $upload): File
    {
        $path = Storage::disk('local')->putFile('contracts/digital-stamps', $upload);
        if (!$path) {
            throw new \RuntimeException('PDF bermaterai gagal disimpan. Silakan coba kembali.');
        }
        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($contract, $upload, $path) {
                $locked = Contract::lockForUpdate()->findOrFail($contract->id);
                $previous = $locked->digitalStamp;
                $file = $locked->digitalStamp()->create([
                    'type' => 'digital_stamp', 'profileid' => $locked->profileid,
                    'disk' => 'local', 'identifier' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => 'application/pdf', 'size' => $upload->getSize(), 'uploaded_at' => now(),
                ]);
                if ($previous) {
                    $previous->delete();
                }
                return $file;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    /**
     * Generate contract PDF
     */
    public static function generateAggregatorPdf(Contract $contract, bool $stream = true)
    {
        if ($contract->type !== 'aggregator') throw new \DomainException('Kontrak ini bukan kontrak Aggregator.');
        $html = $contract->generated_html ?: self::renderAggregatorHtml($contract, false);
        $date = self::prettyDate(($contract->contract_date ?? $contract->created_at ?? now())->format('Y-m-d'));
        $html = app(AggregatorContractTemplates::class)->formatSignatureLayout($html, $date);
        return self::prepareDomPdf($contract->id, $html, true, true, $stream);
    }

    public static function renderAggregatorHtml(Contract $contract, bool $configuredDraft = true): string
    {
        // Get contract type
        $type = null;

        if ($contract->profile->is_company) {
            $type = 'aggregator_company';
        } else {
            $type = 'aggregator';
        }

        // Set contract fields
        $fields = [
            'APP_NAME' => app_name(),
            'USER_PERCENT' => $contract->user_percentage ?? (int) $contract->profile->aggregator_rate,
            'SYSTEM_PERCENT' => $contract->system_percentage ?? (100 - (int) $contract->profile->aggregator_rate),
            'NPWPPERSONAL' => $contract->profile->npwppersonal_num,
            'DOCNUM' => $contract->docnum ?: self::prettyDocNum($contract->type, $contract->number, $contract->month, $contract->year),
            'DATE' => self::prettyDate(($contract->contract_date ?? $contract->created_at ?? now())->format('Y-m-d')),
            'FULLNAME' => strtoupper($contract->profile->full_name),
            'IDNUM' => $contract->profile->ktp_num,
            'BANKNAME' => $contract->profile->bankname,
            'ACCOUNTNUMBER' => $contract->profile->accountnumber,
            'ACCOUNTNAME' => $contract->profile->accountname,
            'FULLADDRESS' => $contract->profile->full_address,
            'PHONE' => $contract->profile->phone,
            'EMAIL' => $contract->profile->user->email,

            'COMPANYNAME' => strtoupper($contract->profile->companyname ?? ''),
            'NPWPCOMPANY' => $contract->profile->npwpcompany_num,
            'NIBCOMPANY' => $contract->profile->nibcompany_num,
            'POSITION' => $contract->profile->position == 'direktur_utama' ? 'Direktur Utama' : 'Direktur',
        ];

        // Get html template
        $html = $configuredDraft
            ? app(AggregatorContractTemplates::class)->get((bool) $contract->profile->is_company)
            : self::getTemplate(resource_path('contract/'.$type.'.html'));
        $html = app(AggregatorContractTemplates::class)->formatSignatureLayout($html);

        // Replace template with contract data (live data)
        foreach ($fields as $key => $value) {
            $html = str_replace('[' . $key . ']', e((string) $value), $html);
        }

        // Define images
        $images = [
            '[SIGNATURE1]' => [
                'path' => app(CompanyIdentity::class)->signaturePath(),
                'style' => 'width="200px" style="max-width:100%;max-height:115px;margin:0;"',
                'file' => null,
            ],
            '[SIGNATURE2]' => [
                'path' => null,
                'style' => 'width="200px" style="max-width:100%;max-height:115px;margin:0;"',
                'file' => $contract->profile->identities()->where('type', 'signature')->first(),
            ],
        ];

        // Inject images
        $html = self::injectImages($images, $html);

        // Generate pdf
        return $html;
    }

    public static function generatePublishingContractPdf(Contract $contract, $stream = true)
    {
        // Set initial header & footer options
        $header = true;
        $footer = true;

        // Get owner details (support both new Publishing owner and legacy Signee owner)
        $owner = $contract->owner;
        $images = [];

        if ($owner instanceof Publishing) {
            $publishing = $owner;
            $profile = $publishing->requestor;

            $fullName = $profile->full_name;
            $idNum = $profile->ktp_num;
            $bankName = $profile->bankname;
            $accountNumber = $profile->accountnumber;
            $accountName = $profile->accountname;
            $fullAddress = $profile->full_address;
            $phone = $profile->phone;
            $email = $profile->user->email;

            $signatureFile = $profile->identities()->where('type', 'signature')->first();
            $ktpFile = $profile->identities()->where('type', 'ktp')->first();
            $npwpFile = $profile->identities()->where('type', 'npwppersonal')->first();
        } else {
            // Legacy Signee owner
            $publishing = $owner->publishing;

            $fullName = $owner->full_name;
            $idNum = $owner->ktp_num;
            $bankName = $owner->bankname;
            $accountNumber = $owner->accountnumber;
            $accountName = $owner->accountname;
            $fullAddress = $owner->full_address;
            $phone = $owner->phone;
            $email = $owner->email;

            $signatureFile = $owner->signature;
            $ktpFile = $owner->ktp;
            $npwpFile = $owner->npwppersonal;
        }

        // Set contract fields
        $fields = [
            'DOCNUM' => self::prettyDocNum($contract->type, $contract->number, $contract->month, $contract->year),
            'DATE' => self::prettyDate(date('Y-m-d')),
            'FULLNAME' => strtoupper($fullName),
            'IDNUM' => $idNum,
            'BANKNAME' => $bankName,
            'ACCOUNTNUMBER' => $accountNumber,
            'ACCOUNTNAME' => $accountName,
            'FULLADDRESS' => $fullAddress,
            'PHONE' => $phone,
            'EMAIL' => $email,
            'TITLE' => $publishing->title,
        ];

        // Get html template
        $type = $publishing->sync_license_type === 'non-exclusive' ? 'publishing_non_exclusive' : 'publishing_exclusive';
        $html = SELF::getTemplate(base_path('resources' . DIRECTORY_SEPARATOR . 'contract' . DIRECTORY_SEPARATOR . $type . '.html'));

        // Replace template with contract data (live data)
        foreach ($fields as $key => $value) {
            $html = str_replace('[' . $key . ']', $value, $html);
        }

        // Define images
        $images = array_merge($images, [
            '[SIGNATURE1]' => [
                'path' => app(CompanyIdentity::class)->signaturePath(),
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -60px; margin-left: 30px;"',
                'file' => null,
            ],
            '[SIGNATURE2]' => [
                'path' => null,
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -40px; margin-right: -100px;"',
                'file' => $signatureFile,
            ],
            '[KTP]' => [
                'path' => null,
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -40px; margin-right: -100px;"',
                'file' => $ktpFile,
            ],
            '[NPWP]' => [
                'path' => null,
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -40px; margin-right: -100px;"',
                'file' => $npwpFile,
            ],
        ]);

        // Inject images
        $html = self::injectImages($images, $html);

        // Generate pdf
        return self::prepareDomPdf($contract->id, $html, $header, $footer, $stream);
    }

    public static function generatePublishingStatementPdf(Publishing $publishing, ?Contract $contract = null, $stream = true)
    {
        // Set initial header & footer options
        $header = false;
        $footer = true;
        $images = [];

        // Set contract fields
        $fields = [
            'DOCNUM' => $contract ? $contract->docnum : '-',
            'DATE' => self::prettyDate(date('Y-m-d')),
            'TITLE' => $publishing->title,
        ];

        $role = [
            'composer' => 'Composer',
            'author' => 'Author',
            'author_composer' => 'Author & Composer',
        ];

        $fields['SONGWRITERS'] = "";
        $fields['TBODY'] = "<tbody>";
        $fields['SIGNATURES_1'] = "";
        $fields['SIGNATURES_2'] = "";
        $fields['ATTACHMENTS'] = "";
        foreach ($publishing->signees as $index => $signee) {
            // Determine credentials (index 0 is requestor profile, index > 0 is signee)
            if ($index == 0) {
                $address = $publishing->requestor->full_address;
                $signatureFile = $publishing->requestor->identities()->where('type', 'signature')->first();
                $ktpFile = $publishing->requestor->identities()->where('type', 'ktp')->first();
                $npwpFile = $publishing->requestor->identities()->where('type', 'npwppersonal')->first();
            } else {
                $address = "-";
                $signatureFile = $signee->signature;
                $ktpFile = null;
                $npwpFile = null;
            }

            /**
             * Songwriter comparitions
             */
            $fields['SONGWRITERS'] .= "<li><strong>{$signee->full_name}</strong>, merupakan perorangan dengan Nomor Induk
            Kependudukan (NIK) {$signee->ktp_num} yang beralamat di {$address}";

            $end = ".</li>";
            if (isset($publishing->signees[$index + 1])) {

                if (isset($publishing->signees[$index + 2])) {
                    $end = ";</li>";
                } else {
                    $end = "; dan</li>";
                }
            }
            $fields['SONGWRITERS'] .= $end;

            /**
             * Sharing Table
             */
            $fields['TBODY'] .= "<tr>
                <td>" . ($index + 1) . "</td>
                <td>" . $publishing->title . "</td>
                <td>" . $signee->full_name . "</td>
                <td>" . $role[$signee->role] . "</td>
                <td style=\"text-align: right;\">" . $signee->share . "</td>
            </tr>";

            /**
             * Signatures 1
             */
            $fields['SIGNATURES_1'] .= "<div style=\"display: inline-block; width: 33%;\">
                <p style=\"margin: 0px; text-align: left;\"><strong>" . ($index == 0 ? "Pencipta" : "&nbsp;") . "</strong></p>
                <p style=\"margin: 0px; text-align: left;\">&nbsp;</p>
                <div style=\"height: 80px;\">[SIGNATURE_1" . "_" . ($index + 1) . "]</div>
                <p style=\"margin: 0px; text-align: left;\"><strong>" . strtoupper($signee->full_name) . "</strong></p>
                <p style=\"margin: 0px; text-align: left;\">&nbsp;</p>
            </div>";

            if ($index == 1) {
                $fields['SIGNATURES_1'] .= "<div style=\"display: inline-block; width: 33%;\">
                    <p style=\"margin: 0px; text-align: left;\"><strong>Publisher</strong></p>
                    <p style=\"margin: 0px; text-align: left;\">[PROVIDER_NAME]</p>
                    <div style=\"height: 80px;\">[SIGNATURE1]</div>
                    <p style=\"margin: 0px; text-align: left;\"><strong>[PROVIDER_DIRECTOR]</strong></p>
                    <p style=\"margin: 0px; text-align: left;\">[PROVIDER_POSITION]</p>
                </div>";
            } elseif ($index > 1 && $index % 2 == 1) {
                $fields['SIGNATURES_1'] .= "<div style=\"display: inline-block; width: 33%;\">
                    &nbsp;
                </div>";
            }

            $images['[SIGNATURE_1' . '_' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -60px;"',
                'file' => $signatureFile,
            ];

            /**
             * Signatures 2
             */
            $fields['SIGNATURES_2'] .= "<div style=\"display: inline-block; width: 35%;\">
                <p style=\"margin: 0px; text-align: left;\"><strong>" . ($index == 0 ? "Pencipta" : "&nbsp;") . "</strong></p>
                <p style=\"margin: 0px; text-align: left;\">&nbsp;</p>
                <div style=\"height: 80px;\">[SIGNATURE_2" . "_" . ($index + 1) . "]</div>
                <p style=\"margin: 0px; text-align: left;\"><strong>" . strtoupper($signee->full_name) . "</strong></p>
                <p style=\"margin: 0px; text-align: left;\">&nbsp;</p>
            </div>";

            if ($index % 2 == 0) {
                $fields['SIGNATURES_2'] .= "<div style=\"display: inline-block; width: 30%;\">&nbsp;</div>";
            }

            $images['[SIGNATURE_2' . '_' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -60px;"',
                'file' => $signatureFile,
            ];
            /**
             * Attachments
             */
            $fields['ATTACHMENTS'] .= "<tr>
                <td style=\"text-align: left;\"><div style=\"margin-bottom: 20px; min-height: 70px;\">[KTP" . ($index + 1) . "]</div></td>
                <td style=\"text-align: left;\"><div style=\"margin-bottom: 20px; min-height: 70px;\">[NPWP" . ($index + 1) . "]</div></td>
            </tr>";

            $images['[KTP' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'style="max-height: 120px; max-width: 200px"',
                'file' => $ktpFile,
            ];

            $images['[NPWP' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'style="max-height: 120px; max-width: 200px"',
                'file' => $npwpFile,
            ];
        }
        $fields['TBODY'] .= "</tbody>";

        // Songwriter after comparitions
        $fields['SONGWRITERS_AFTER'] = "Dalam hal ini bertindak selaku pencipta dari hak cipta atas lagu sebagaimana tercantum dalam Lampiran Surat Pernyataan ini (selanjutnya disebut sebagai <strong>\"Lagu\"</strong>).
        Untuk selanjutnya disebut sebagai <strong>\"Pencipta\"</strong>.";
        if ($publishing->signees->count() > 1) {
            $fields['SONGWRITERS_AFTER'] = "Dalam hal ini masing-masing bertindak selaku pencipta dari hak cipta atas lagu sebagaimana tercantum dalam Lampiran Surat Pernyataan ini (selanjutnya disebut sebagai <strong>\"Lagu\"</strong>).
            Untuk selanjutnya disebut sebagai <strong>\"Pencipta\"</strong>.";
        }

        // Get html template
        $html = SELF::getTemplate(base_path('resources' . DIRECTORY_SEPARATOR . 'contract' . DIRECTORY_SEPARATOR . 'publishing_statement.html'));

        // Replace template with contract data (live data)
        foreach ($fields as $key => $value) {
            $html = str_replace('[' . $key . ']', $value, $html);
        }

        // Define images
        $images = array_merge($images, [
            '[SIGNATURE1]' => [
                'path' => app(CompanyIdentity::class)->signaturePath(),
                'style' => 'width="180px" style="margin-top: 0px; margin-bottom: -60px; margin-left: 30px;"',
                'file' => null,
            ],
        ]);

        // Inject images
        $html = self::injectImages($images, $html);

        if (isset($_REQUEST['html'])) {
            print_r($html);
            die();
        }

        // Generate pdf
        return self::prepareDomPdf($publishing->id, $html, $header, $footer, $stream);
    }

    public static function generatePublishingNuonPdf(Publishing $publishing, $stream = true)
    {
        // Set initial header & footer options
        $header = false;
        $footer = false;
        $images = [];

        // Set contract fields
        $fields = [
            'DATE' => self::prettyDate(date('Y-m-d')),
            'TITLE' => $publishing->title,
        ];

        $fields['SONGWRITERS'] = "";
        $fields['SIGNATURES'] = "";
        $fields['ATTACHMENTS'] = "";

        foreach ($publishing->signees as $index => $signee) {
            // Determine credentials (index 0 is requestor profile, index > 0 is signee)
            if ($index == 0) {
                $address = $publishing->requestor->full_address;
                $signatureFile = $publishing->requestor->identities()->where('type', 'signature')->first();
                $ktpFile = $publishing->requestor->identities()->where('type', 'ktp')->first();
            } else {
                $address = "-";
                $signatureFile = $signee->signature;
                $ktpFile = null;
            }

            /**
             * Songwriter comparitions
             */

            $fields['SONGWRITERS'] .= "<li>{$signee->full_name}, bertempat tinggal di {$address}, sesuai dengan Kartu Tanda Penduduk (KTP) Nomor {$signee->ktp_num} yang dikeluarkan oleh _____________";
            if (isset($publishing->signees[$index + 1])) {

                if (isset($publishing->signees[$index + 2])) {
                    $end = ";</li>";
                } else {
                    $end = "; dan</li>";
                }
            } else {
                $end = " (untuk selanjutnya disebut sebagai \"<strong>Pencipta Lagu</strong>\") selaku pencipta lagu atas lagu sebagai berikut: <p></p> <p><em>{$publishing->title}</em></p> <p>(selanjutnya disebut \"Lagu-lagu\")</p> <p>dan</p>";
            }
            $fields['SONGWRITERS'] .= $end;

            /**
             * Signatures
             */
            $signatureCount = count($publishing->signees);

            $fields['SIGNATURES'] .= "<div style=\"display:inline-block;width:33%;\">
                [SIGNATURE_" . 1 . "_" . ($index + 1) . "]
                <p style=\"margin:0;text-align:left;\"><strong><u>" . strtoupper($signee->full_name) . "</u></strong></p>
                <p style=\"margin:0;text-align:left;\">Pencipta Lagu</p>
            </div>";

            if ($index == 1 || ($signatureCount == 1 && $index == 0)) {
                $fields['SIGNATURES'] .= "<div style=\"display:inline-block;width:33%;\">
                    [SIGNATURE1]
                    <p style=\"margin:0;text-align:left;\"><strong><u>[PROVIDER_DIRECTOR]</u></strong></p>
                    <p style=\"margin:0;text-align:left;\">Publisher</p>
                </div>";
            } elseif ($index > 0 && $index % 3 == 0) {
                $fields['SIGNATURES'] .= "<div style=\"display: inline-block; width: 33%;\"></div>";
            }

            $images['[SIGNATURE_' . 1 . '_' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'style="margin-top: 0px; margin-bottom: -15px; max-height: 65px; max-width: 180px;"',
                'file' => $signatureFile,
            ];

            /**
             * Attachments
             */
            $fields['ATTACHMENTS'] .= "<tr>
                <td style=\"text-align: left;\"><div style=\"margin-bottom: 20px; min-height: 70px;\">[KTP" . ($index + 1) . "]</div></td>
            </tr>";

            $images['[KTP' . ($index + 1) . ']'] = [
                'path' => null,
                'style' => 'style="max-height: 120px"',
                'file' => $ktpFile,
            ];
        }

        // Get html template
        $html = SELF::getTemplate(base_path('resources' . DIRECTORY_SEPARATOR . 'contract' . DIRECTORY_SEPARATOR . 'publishing_nuon.html'));

        // Replace template with contract data (live data)
        foreach ($fields as $key => $value) {
            $html = str_replace('[' . $key . ']', $value, $html);
        }

        // Define images
        $images = array_merge($images, [
            '[SIGNATURE1]' => [
                'path' => app(CompanyIdentity::class)->signaturePath(),
                'style' => 'style="margin-top: 0px; margin-bottom: -15px; max-height: 65px; max-width: 180px;"',
                'file' => null,
            ],
        ]);

        // Inject images
        $html = self::injectImages($images, $html);

        if (isset($_REQUEST['html'])) {
            print_r($html);
            die();
        }

        // Generate pdf
        return self::prepareDomPdf($publishing->id, $html, $header, $footer, $stream);
    }

    public function deletePdf(Contract $contract)
    {
        // Delete file
        FileService::delete($contract->file);
    }

    /**
     * Pretty document number methods
     */
    public static function prettyDocNum($type, $number, $month, $year)
    {
        $month = (int) $month;
        $romans = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        switch ($type) {
            case 'aggregator':
                return str_pad($number, 3, '0', STR_PAD_LEFT) . '/' . app(SystemSettings::class)->get('aggregator_contract_prefix') . '/' . $romans[$month] . '/' . $year;
                break;
            case 'publishing':
                return str_pad($number, 3, '0', STR_PAD_LEFT) . '/' . 'PKS-PUB' . '/DMSV/' . $romans[$month] . '/' . $year;
                break;
            case 'publishing_statement':
                return str_pad($number, 3, '0', STR_PAD_LEFT) . '/' . 'SP-PUB' . '/DMSV/' . $romans[$month] . '/' . $year;
                break;
            default:
                throw new UnsupportedTypeException($type, ['aggregator', 'publishing', 'publishing_statement']);
                break;
        }
    }

    private static function prettyDate($date)
    {
        $arr = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        return date('d', strtotime($date)) . ' ' . $arr[date('n', strtotime($date)) - 1] . ' ' . date('Y', strtotime($date));
    }

    /**
     * PDF processing methods
     */
    private static function injectImages(array $images, $html)
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $html = app(CompanyIdentity::class)->fill($html);
        if (isset($images['[SIGNATURE1]'])) $images['[PROVIDER_SIGNATURE]'] = $images['[SIGNATURE1]'];
        // Inject images
        foreach ($images as $placeholder => $image) {
            $file = null;
            $tag = '';

            // If file is static
            if ($image['path'] && FileFacade::exists($image['path'])) {
                $file = FileFacade::get($image['path']);
            }

            // If file is uploaded
            elseif ($image['file']) {

                if (strpos($image['file']->mime_type, 'image/') === 0 && Storage::disk($image['file']->disk)->exists($image['file']->identifier)) {
                    $file = Storage::disk($image['file']->disk)->get($image['file']->identifier);
                }
            }

            if ($file) {
                $encoded = base64_encode($file);
                $tag = "<img src=\"data:image/png;base64, {$encoded}\" {$image['style']}>";
            }

            $html = str_replace($placeholder, $tag, $html);
        }

        return $html;
    }

    private static function prepareDomPdf($name, $html, $header, $footer, $stream = true)
    {
        $headerLogo = resource_path('images/logo-soundfresh-vertical.png');
        $brandingLogo = app(BrandingAssets::class)->path(app(SystemSettings::class)->get('logo'));
        if ($header && $brandingLogo) {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $headerLogo = $manager->read($brandingLogo)->toPng()->toDataUri();
        }
        $logoWidth = 70;
        $logoHeight = 70;
        if ($header) {
            $dimensions = getimagesize($headerLogo);
            if ($dimensions && $dimensions[0] > 0 && $dimensions[1] > 0) {
                $scale = min(100 / $dimensions[0], 70 / $dimensions[1]);
                $logoWidth = $dimensions[0] * $scale;
                $logoHeight = $dimensions[1] * $scale;
            }
        }
        // Prepare DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        // Add header and footer
        $canvas = $dompdf->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($header, $footer, $headerLogo, $logoWidth, $logoHeight) {

            if ($footer) {
                $text = "Halaman $pageNumber dari $pageCount";
                $font_bold = $fontMetrics->getFont('helvetica', 'bold');
                $pageWidth = $canvas->get_width();
                $pageHeight = $canvas->get_height();
                $size = 10;
                $width = $fontMetrics->getTextWidth($text, $font_bold, $size);
                $x = ($pageWidth - $width) / 2;
                $canvas->text($x, $pageHeight - 30, $text, $font_bold, $size);
            }

            if ($header) {
                $canvas->image($headerLogo, 525 - $logoWidth, 15 + (70 - $logoHeight) / 2, $logoWidth, $logoHeight);
            }

        });

        if ($stream) {
            $dompdf->stream($name . '.pdf', ['Attachment' => false]);
            return null;
        }

        return $dompdf->output();
    }
}
