<?php

namespace App\Services;

use Dompdf\Dompdf;

/** Print layout is part of the encrypted generated_html snapshot for new contracts. */
class ContractPdfLayout
{
    public const MARKER = 'contract-pdf-layout';
    public const SIDE_MARGIN = 46;

    public function identity(): array
    {
        $settings = app(SystemSettings::class);
        return [
            'system'=>(string) $settings->get('app_name'),
            'company'=>(string) $settings->get('company_name'),
            'address'=>(string) $settings->get('company_address'),
            'email'=>(string) $settings->get('company_email'),
            'phone'=>(string) $settings->get('company_phone'),
        ];
    }

    public function decorated(string $html): bool
    {
        return str_contains($html,'id="'.self::MARKER.'"');
    }

    public function footerHeight(string $html): float
    {
        if (preg_match('/data-footer-height="([0-9.]+)"/',$html,$match)) return (float) $match[1];
        return 48;
    }

    /** Read saved identities rather than current settings when rendering an existing contract. */
    public function pageData(string $html): array
    {
        $dom = $this->dom($html);
        $xpath = new \DOMXPath($dom);
        $text = fn ($class) => trim($xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," '.$class.' ")]')->item(0)?->textContent ?? '');
        $rows = [];
        foreach ($xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," contract-footer-row ")]') as $row) {
            $rows[] = ['text'=>trim($row->textContent),'bold'=>$row->getElementsByTagName('strong')->length > 0];
        }
        $logo = null;
        $image = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," contract-pdf-header ")]//img')->item(0);
        if ($image && preg_match('/width:([0-9.]+)pt;height:([0-9.]+)pt/',$image->getAttribute('style'),$size)) {
            $logo = ['uri'=>$image->getAttribute('src'),'width'=>(float) $size[1],'height'=>(float) $size[2]];
        }
        return ['system'=>$text('contract-brand'),'company'=>$text('contract-provider'),'number'=>$text('contract-document-number'),'rows'=>$rows,'logo'=>$logo,'height'=>$this->footerHeight($html)];
    }

    public function drawPage($canvas, $metrics, array $data, bool $header, bool $footer): void
    {
        $left = self::SIDE_MARGIN;
        $right = $canvas->get_width() - $left;
        $regular = $metrics->getFont('helvetica','normal');
        $bold = $metrics->getFont('helvetica','bold');
        $ink = [.09,.14,.22];
        $muted = [.28,.33,.41];
        $purple = [.43,.16,.85];
        if ($header) {
            $size = 18;
            $brandFont = $metrics->getFont('times','bold');
            while ($size > 8 && $metrics->getTextWidth($data['system'],$brandFont,$size) > ($right-$left)*.62) $size -= .5;
            $canvas->text($left,27,$data['system'],$brandFont,$size,$ink);
            $canvas->text($left,51,$data['company'],$regular,8,$muted);
            if ($data['logo']) {
                $logo = $data['logo'];
                $canvas->image($logo['uri'],$right-$logo['width'],21,$logo['width'],$logo['height']);
            }
            foreach (['DOKUMEN PERJANJIAN',$data['number']] as $index=>$line) {
                $canvas->text($right-$metrics->getTextWidth($line,$regular,7),65+$index*10,$line,$regular,7,$muted);
            }
            $canvas->line($left,88,$right,88,$purple,1.2);
        }
        if ($footer) {
            $top = $canvas->get_height() - $data['height'] - 30;
            $canvas->line($left,$top,$right,$top,$purple,1);
            foreach ($data['rows'] as $index=>$row) {
                $canvas->text($left,$top+8+$index*10,$row['text'],$row['bold'] ? $bold : $regular,7.5,$row['bold'] ? $ink : $muted);
            }
        }
    }

    public function decorate(string $html, array $summary = [], bool $header = true, bool $footer = true): string
    {
        // Remove the former presentation summary when viewing an existing snapshot.
        // The saved legal clauses and database snapshot remain untouched.
        if (str_contains($html,'class="contract-summary"')) {
            $dom = $this->dom($html);
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//table[@class="contract-summary"]') as $table) $table->parentNode->removeChild($table);
            $html = str_replace('<?xml encoding="UTF-8">','',$dom->saveHTML());
        }
        if ($this->decorated($html)) return $html;
        $identity = $this->identity();
        $fontMetrics = (new Dompdf)->getFontMetrics();
        $width = 595.28 - self::SIDE_MARGIN * 2;
        $rows = [];
        foreach ([[$identity['system'].'  |  '.$identity['company'],true,$width-90],[$identity['address'],false,$width],['Email: '.$identity['email'].'  |  Telp: '.$identity['phone'],false,$width]] as [$text,$bold,$available]) {
            foreach ($this->wrap($text,$fontMetrics,$available,$bold) as $line) $rows[] = ['text'=>$line,'bold'=>$bold];
        }
        $footerHeight = count($rows) * 10 + 12;
        $bottomMargin = $footer ? $footerHeight + 38 : 42;
        $logo = $header ? $this->logo() : null;
        $dom = $this->dom($html);
        $head = $dom->getElementsByTagName('head')->item(0);
        if (!$head) { $head=$dom->createElement('head');$dom->documentElement->insertBefore($head,$dom->documentElement->firstChild); }
        $body = $dom->getElementsByTagName('body')->item(0);
        $style=$dom->createElement('style');$style->setAttribute('id',self::MARKER);$style->setAttribute('data-footer-height',(string) $footerHeight);
        $style->appendChild($dom->createTextNode(view('contracts.pdf-styles',compact('bottomMargin','footerHeight','header'))->render()));
        $head->appendChild($style);

        // Improve title hierarchy without replacing a single word of the stored clauses.
        $paragraphs=[];
        foreach ($body->childNodes as $node) if ($node instanceof \DOMElement && $node->tagName === 'p') $paragraphs[]=$node;
        foreach ($paragraphs as $index=>$node) {
            if (preg_match('/^PASAL\s+\d+$/iu',trim($node->textContent))) {
                $node->setAttribute('class',trim($node->getAttribute('class').' contract-section-heading'));
                if (isset($paragraphs[$index+1])) $paragraphs[$index+1]->setAttribute('class',trim($paragraphs[$index+1]->getAttribute('class').' contract-section-heading'));
            }
        }
        if (isset($paragraphs[2]) && str_contains(mb_strtoupper($paragraphs[0]->textContent),'PERJANJIAN')) {
            foreach (array_slice($paragraphs,0,3) as $index=>$node) $node->setAttribute('class',trim($node->getAttribute('class').' contract-title-'.$index));
        }
        if ($footer) $body->insertBefore($this->element($dom,view('contracts.pdf-footer',compact('rows'))->render()),$body->firstChild);
        if ($header) $body->insertBefore($this->element($dom,view('contracts.pdf-header',compact('identity','logo','summary'))->render()),$body->firstChild);
        return str_replace('<?xml encoding="UTF-8">','',$dom->saveHTML());
    }

    private function logo(): ?array
    {
        $path=app(BrandingAssets::class)->path(app(SystemSettings::class)->get('logo'));
        if (!$path || !($size = @getimagesize($path))) return null;
        $scale=min(90/$size[0],40/$size[1]);
        return ['uri'=>(string) (new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver()))->read($path)->toPng()->toDataUri(),
            'width'=>$size[0]*$scale,'height'=>$size[1]*$scale];
    }

    private function wrap(string $text, $metrics, float $width, bool $bold): array
    {
        $font=$metrics->getFont('helvetica',$bold ? 'bold' : 'normal');
        $lines=[];$line='';
        foreach (preg_split('/\s+/u',trim($text),-1,PREG_SPLIT_NO_EMPTY) as $word) {
            if ($line !== '' && $metrics->getTextWidth($line.' '.$word,$font,7.5)>$width) { $lines[]=$line;$line=''; }
            // Also split very long email/address tokens instead of clipping them.
            foreach (mb_str_split($word) as $character) {
                $candidate=$line.$character;
                if ($line !== '' && $metrics->getTextWidth($candidate,$font,7.5)>$width) { $lines[]=rtrim($line);$line=''; }
                $line.=$character;
            }
            $line.=' ';
        }
        if (trim($line)!=='') $lines[]=trim($line);
        return $lines;
    }

    private function dom(string $html): \DOMDocument
    {
        $dom=new \DOMDocument();$previous=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);
        libxml_clear_errors();libxml_use_internal_errors($previous);
        return $dom;
    }

    private function element(\DOMDocument $target,string $html): \DOMNode
    {
        $source=$this->dom($html);
        return $target->importNode($source->getElementsByTagName('body')->item(0)->firstChild,true);
    }
}
