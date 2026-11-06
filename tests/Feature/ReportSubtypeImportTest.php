<?php

namespace Tests\Feature;

use App\Services\ReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ReportSubtypeImportTest extends TestCase
{
    public function test_all_importers_preserve_dynamic_subtypes_separately_from_subscription(): void
    {
        foreach (['Believe','Soundon','Lokamusik'] as $source) {
            $sheet = (new Spreadsheet)->getActiveSheet();
            $values = ['DEFAULT','PGC','UGC','DUO','BUNDLE','INDIVIDUAL','NEW_VALUE','1E3',null];
            foreach ($values as $index=>$value) {
                $sheet->setCellValueExplicit('A'.($index+2), $value, $value === null ? 'null' : 's');
                $sheet->setCellValue('B'.($index+2), 'SUBSCRIPTION');
            }
            $reader = new \ReflectionMethod(ReportService::class, 'getExcelFileData'.$source);
            $rows = $reader->invoke(null,$sheet,count($values)+1,'B',['A'=>'sales_sub_type','B'=>'subscription_type']);
            $this->assertSame($values,array_column($rows,'sales_sub_type'),$source);
            $this->assertSame('SUBSCRIPTION',$rows[0]['subscription_type']);
        }
    }

    public function test_optional_header_alias_is_detected_in_all_xlsx_formats(): void
    {
        $headers = [
            'Believe'=>['Reporting month','Sales Month','Platform','Country / Region','Artist Name','Release title','Track title','UPC','ISRC','Quantity','Net Revenue'],
            'Soundon'=>['Reporting Period','Sales Period','Store Name','Sales Region','Track Artists','Album Title','Track Title','UPC Code','ISRC','Units of Sold','Final Royalty'],
            'Lokamusik'=>['period','sales_period','platform','country','artist','title','upc','isrc','sales_unit','client_net_share','sales_type'],
        ];
        foreach ($headers as $source=>$columns) {
            $sheet = (new Spreadsheet)->getActiveSheet();
            $sheet->fromArray([$columns]);
            $file = tempnam(sys_get_temp_dir(),'subtype-import-');
            try {
                $validator = new \ReflectionMethod(ReportService::class,'validateExcelFile'.$source);
                (new Xlsx($sheet->getParent()))->save($file);
                $this->assertNotContains('sales_sub_type',$validator->invoke(null,$file)['map']);
                $columns[] = $source === 'Lokamusik' ? 'sales_sub_type' : 'Sales Sub Type';
                $sheet->fromArray([$columns]);
                (new Xlsx($sheet->getParent()))->save($file);
                $this->assertContains('sales_sub_type',$validator->invoke(null,$file)['map']);
            } finally { unlink($file); }
        }
    }

    public function test_long_subtype_is_rejected_instead_of_truncated(): void
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setCellValue('A2',str_repeat('X',256));
        $this->expectException(\Exception::class);
        (new \ReflectionMethod(ReportService::class,'getExcelFileDataBelieve'))->invoke(null,$sheet,2,'A',['A'=>'sales_sub_type']);
    }
}
