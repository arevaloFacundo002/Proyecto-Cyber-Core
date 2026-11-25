<?php

class XlsxWriter
{
    public const S_DEF = 0, S_TITULO = 1, S_META_ETQ = 2, S_META_VAL = 3, S_CABECERA = 4,
        S_KPI_ETQ = 17, S_KPI_VAL = 18, S_SECCION = 19, S_PARRAFO = 20, S_NOTA = 21;

    private const TIPOS = ['txt' => 5, 'int' => 7, 'plata' => 9, 'pct' => 11, 'fecha' => 13, 'dec' => 15];

    private array $hojas = [];
    private array $graficos = [];

    public function hoja(string $nombre): int
    {
        $this->hojas[] = [
            'nombre' => $nombre, 'filas' => [], 'anchos' => [], 'merges' => [], 'altos' => [],
            'filtro' => null, 'congelar' => null, 'graficos' => [],
        ];
        return count($this->hojas) - 1;
    }

    public static function estiloCelda(string $tipo, bool $cebra): int
    {
        return (self::TIPOS[$tipo] ?? 5) + ($cebra ? 1 : 0);
    }

    public function celda(int $h, int $fila, int $col, $valor, int $estilo = 0, string $tipo = 'txt'): void
    {
        $this->hojas[$h]['filas'][$fila][$col] = [$valor, $estilo, $tipo];
    }

    public function ancho(int $h, int $col, float $ancho): void
    {
        $this->hojas[$h]['anchos'][$col] = $ancho;
    }

    public function alto(int $h, int $fila, float $alto): void
    {
        $this->hojas[$h]['altos'][$fila] = $alto;
    }

    public function unir(int $h, int $f1, int $c1, int $f2, int $c2): void
    {
        $this->hojas[$h]['merges'][] = self::ref($c1, $f1) . ':' . self::ref($c2, $f2);
    }

    public function filtro(int $h, int $fila, int $c1, int $c2, int $ultimaFila): void
    {
        $this->hojas[$h]['filtro'] = [$c1, $fila, $c2, $ultimaFila];
    }

    public function congelar(int $h, int $filasArriba): void
    {
        $this->hojas[$h]['congelar'] = $filasArriba;
    }

    public function grafico(int $h, array $g): void
    {
        $this->hojas[$h]['graficos'][] = $g;
    }

    public function anchosAuto(int $h, int $desdeFila, int $min = 10, int $max = 55): void
    {
        $w = [];
        foreach ($this->hojas[$h]['filas'] as $f => $cols) {
            if ($f < $desdeFila) {
                continue;
            }
            foreach ($cols as $c => [$v, $e, $t]) {
                $len = is_string($v) ? mb_strlen($v) : (is_float($v) || is_int($v) ? strlen(number_format((float) $v, 2)) + 2 : 0);
                $w[$c] = max($w[$c] ?? 0, $len);
            }
        }
        foreach ($w as $c => $len) {
            $this->hojas[$h]['anchos'][$c] = max($min, min($max, $len + 3));
        }
    }

    public static function letra(int $col): string
    {
        $s = '';
        $col++;
        while ($col > 0) {
            $m = ($col - 1) % 26;
            $s = chr(65 + $m) . $s;
            $col = intdiv($col - 1, 26);
        }
        return $s;
    }

    public static function ref(int $col, int $fila): string
    {
        return self::letra($col) . ($fila + 1);
    }

    private static function x($t): string
    {
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $t);
        return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function serialFecha($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $ts = strtotime((string) $v);
        if ($ts === false) {
            return null;
        }
        $d = new DateTime(date('Y-m-d', $ts));
        $base = new DateTime('1899-12-30');
        return (float) $base->diff($d)->days * ($d < $base ? -1 : 1);
    }

    private function estilos(): string
    {
        $fonts = [
            '<font><sz val="11"/><name val="Calibri"/></font>',
            '<font><b/><sz val="16"/><color rgb="FF000521"/><name val="Calibri"/></font>',
            '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>',
            '<font><b/><sz val="12"/><color rgb="FF000521"/><name val="Calibri"/></font>',
            '<font><i/><sz val="10"/><color rgb="FF6B7A90"/><name val="Calibri"/></font>',
            '<font><b/><sz val="14"/><color rgb="FF0E4EB2"/><name val="Calibri"/></font>',
            '<font><b/><sz val="11"/><name val="Calibri"/></font>',
            '<font><sz val="10"/><color rgb="FF6B7A90"/><name val="Calibri"/></font>',
        ];
        $fills = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0E4EB2"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF4F7FB"/><bgColor indexed="64"/></patternFill></fill>',
            '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF3FB"/><bgColor indexed="64"/></patternFill></fill>',
        ];
        $lado = fn($n) => "<$n style=\"thin\"><color rgb=\"FFD3DEEC\"/></$n>";
        $borders = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border>' . $lado('left') . $lado('right') . $lado('top') . $lado('bottom') . '<diagonal/></border>',
            '<border><left/><right/><top/><bottom style="medium"><color rgb="FF00BED7"/></bottom><diagonal/></border>',
        ];
        // [numFmt, font, fill, border, alineación]
        $xf = [
            0  => [0, 0, 0, 0, ''],
            1  => [0, 1, 0, 0, '<alignment vertical="center"/>'],
            2  => [0, 6, 0, 0, ''],
            3  => [0, 0, 0, 0, '<alignment horizontal="left"/>'],
            4  => [0, 2, 2, 1, '<alignment horizontal="center" vertical="center" wrapText="1"/>'],
            5  => [0, 0, 0, 1, '<alignment vertical="center"/>'],
            6  => [0, 0, 3, 1, '<alignment vertical="center"/>'],
            7  => [3, 0, 0, 1, '<alignment horizontal="right"/>'],
            8  => [3, 0, 3, 1, '<alignment horizontal="right"/>'],
            9  => [164, 0, 0, 1, '<alignment horizontal="right"/>'],
            10 => [164, 0, 3, 1, '<alignment horizontal="right"/>'],
            11 => [165, 0, 0, 1, '<alignment horizontal="right"/>'],
            12 => [165, 0, 3, 1, '<alignment horizontal="right"/>'],
            13 => [14, 0, 0, 1, '<alignment horizontal="center"/>'],
            14 => [14, 0, 3, 1, '<alignment horizontal="center"/>'],
            15 => [166, 0, 0, 1, '<alignment horizontal="right"/>'],
            16 => [166, 0, 3, 1, '<alignment horizontal="right"/>'],
            17 => [0, 7, 4, 1, '<alignment vertical="center"/>'],
            18 => [0, 5, 4, 1, '<alignment horizontal="right" vertical="center"/>'],
            19 => [0, 3, 0, 2, ''],
            20 => [0, 0, 0, 0, '<alignment vertical="top" wrapText="1"/>'],
            21 => [0, 4, 0, 0, '<alignment vertical="top" wrapText="1"/>'],
        ];
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3"><numFmt numFmtId="164" formatCode="&quot;$&quot;\ #,##0.00"/>'
            . '<numFmt numFmtId="165" formatCode="0.0&quot;%&quot;"/><numFmt numFmtId="166" formatCode="#,##0.0"/></numFmts>'
            . '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts>'
            . '<fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="' . count($borders) . '">' . implode('', $borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xf) . '">';
        foreach ($xf as [$nf, $fo, $fi, $bo, $al]) {
            $x .= '<xf numFmtId="' . $nf . '" fontId="' . $fo . '" fillId="' . $fi . '" borderId="' . $bo . '" xfId="0"'
                . ($nf ? ' applyNumberFormat="1"' : '') . ' applyFont="1" applyFill="1" applyBorder="1"'
                . ($al !== '' ? ' applyAlignment="1">' . $al . '</xf>' : '/>');
        }
        return $x . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function xmlHoja(int $i): string
    {
        $h = $this->hojas[$i];
        $maxCol = 0;
        foreach ($h['filas'] as $cols) {
            if ($cols) {
                $maxCol = max($maxCol, max(array_keys($cols)));
            }
        }
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';
        $x .= '<sheetViews><sheetView workbookViewId="0" showGridLines="' . ($i === 0 ? '0' : '1') . '"'
            . ($i === 0 ? ' tabSelected="1"' : '') . '>';
        if ($h['congelar']) {
            $n = (int) $h['congelar'];
            $x .= '<pane ySplit="' . $n . '" topLeftCell="A' . ($n + 1) . '" activePane="bottomLeft" state="frozen"/>'
                . '<selection pane="bottomLeft" activeCell="A' . ($n + 1) . '" sqref="A' . ($n + 1) . '"/>';
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        if ($h['anchos']) {
            ksort($h['anchos']);
            $x .= '<cols>';
            foreach ($h['anchos'] as $c => $w) {
                $x .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . round($w, 2) . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        ksort($h['filas']);
        foreach ($h['filas'] as $f => $cols) {
            ksort($cols);
            $x .= '<row r="' . ($f + 1) . '"' . (isset($h['altos'][$f]) ? ' ht="' . $h['altos'][$f] . '" customHeight="1"' : '') . '>';
            foreach ($cols as $c => [$v, $e, $t]) {
                $r = self::ref($c, $f);
                if ($v === null || $v === '') {
                    $x .= '<c r="' . $r . '" s="' . $e . '"/>';
                } elseif ($t === 'fecha') {
                    $s = self::serialFecha($v);
                    $x .= $s === null ? '<c r="' . $r . '" s="' . $e . '"/>' : '<c r="' . $r . '" s="' . $e . '"><v>' . $s . '</v></c>';
                } elseif (in_array($t, ['int', 'plata', 'pct', 'dec'], true) && is_numeric($v)) {
                    $x .= '<c r="' . $r . '" s="' . $e . '"><v>' . (0 + $v) . '</v></c>';
                } else {
                    $x .= '<c r="' . $r . '" s="' . $e . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($h['filtro']) {
            [$a, $b, $c, $d] = $h['filtro'];
            $x .= '<autoFilter ref="' . self::ref($a, $b) . ':' . self::ref($c, $d) . '"/>';
        }
        if ($h['merges']) {
            $x .= '<mergeCells count="' . count($h['merges']) . '">';
            foreach ($h['merges'] as $m) {
                $x .= '<mergeCell ref="' . $m . '"/>';
            }
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="' . ($maxCol > 6 ? 'landscape' : 'portrait') . '" fitToWidth="1" fitToHeight="0"/>';
        if ($h['graficos']) {
            $x .= '<drawing r:id="rId1"/>';
        }
        return $x . '</worksheet>';
    }

    private function xmlGrafico(array $g, string $hoja): string
    {
        $c1 = $g['cat'];
        $n = count($g['etiquetas']);
        $ref = fn($col, $a, $b) => "'" . str_replace("'", "''", $hoja) . "'!\$" . $col . '$' . $a . ':$' . $col . '$' . $b;
        $cat = '<c:cat><c:strRef><c:f>' . self::x($ref($c1['col_cat'], $c1['f1'], $c1['f2'])) . '</c:f><c:strCache><c:ptCount val="' . $n . '"/>';
        foreach ($g['etiquetas'] as $i => $e) {
            $cat .= '<c:pt idx="' . $i . '"><c:v>' . self::x($e) . '</c:v></c:pt>';
        }
        $cat .= '</c:strCache></c:strRef></c:cat>';
        $val = '<c:val><c:numRef><c:f>' . self::x($ref($c1['col_val'], $c1['f1'], $c1['f2'])) . '</c:f><c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="' . $n . '"/>';
        foreach ($g['valores'] as $i => $v) {
            $val .= '<c:pt idx="' . $i . '"><c:v>' . (0 + $v) . '</c:v></c:pt>';
        }
        $val .= '</c:numCache></c:numRef></c:val>';
        $tx = '<c:tx><c:v>' . self::x($g['serie']) . '</c:v></c:tx>';
        $fmt = !empty($g['dinero']) ? '&quot;$&quot;\ #,##0' : '#,##0';
        $paleta = ['0E4EB2', '00BED7', '5B8DEF', '7A5AF8', '12B76A', 'F79009', 'F04438', '98A2B3'];

        if ($g['tipo'] === 'torta') {
            $dpt = '';
            foreach ($g['etiquetas'] as $i => $_) {
                $dpt .= '<c:dPt><c:idx val="' . $i . '"/><c:bubble3D val="0"/><c:spPr><a:solidFill><a:srgbClr val="' . $paleta[$i % 8] . '"/></a:solidFill></c:spPr></c:dPt>';
            }
            $plot = '<c:pieChart><c:varyColors val="1"/><c:ser><c:idx val="0"/><c:order val="0"/>' . $tx . $dpt
                . '<c:dLbls><c:numFmt formatCode="0.0%" sourceLinked="0"/><c:spPr><a:noFill/></c:spPr><c:showLegendKey val="0"/><c:showVal val="0"/><c:showCatName val="0"/><c:showSerName val="0"/><c:showPercent val="1"/><c:showBubbleSize val="0"/><c:showLeaderLines val="1"/></c:dLbls>'
                . $cat . $val . '</c:ser><c:firstSliceAng val="0"/></c:pieChart>';
            $ejes = '';
            $leyenda = '<c:legend><c:legendPos val="r"/><c:overlay val="0"/></c:legend>';
        } else {
            $ax = '<c:catAx><c:axId val="111"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="b"/><c:numFmt formatCode="General" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:crossAx val="222"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/><c:noMultiLvlLbl val="0"/></c:catAx>'
                . '<c:valAx><c:axId val="222"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="l"/><c:majorGridlines><c:spPr><a:ln w="6350"><a:solidFill><a:srgbClr val="E3E8EF"/></a:solidFill></a:ln></c:spPr></c:majorGridlines><c:numFmt formatCode="' . $fmt . '" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:crossAx val="111"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx>';
            if ($g['tipo'] === 'lineas') {
                $plot = '<c:lineChart><c:grouping val="standard"/><c:varyColors val="0"/><c:ser><c:idx val="0"/><c:order val="0"/>' . $tx
                    . '<c:spPr><a:ln w="28575" cap="rnd"><a:solidFill><a:srgbClr val="0E4EB2"/></a:solidFill><a:round/></a:ln></c:spPr>'
                    . '<c:marker><c:symbol val="circle"/><c:size val="6"/><c:spPr><a:solidFill><a:srgbClr val="00BED7"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="0E4EB2"/></a:solidFill></a:ln></c:spPr></c:marker>'
                    . $cat . $val . '<c:smooth val="0"/></c:ser><c:marker val="1"/><c:axId val="111"/><c:axId val="222"/></c:lineChart>';
            } else {
                $plot = '<c:barChart><c:barDir val="col"/><c:grouping val="clustered"/><c:varyColors val="0"/><c:ser><c:idx val="0"/><c:order val="0"/>' . $tx
                    . '<c:spPr><a:solidFill><a:srgbClr val="0E4EB2"/></a:solidFill></c:spPr><c:invertIfNegative val="0"/>'
                    . $cat . $val . '</c:ser><c:gapWidth val="60"/><c:axId val="111"/><c:axId val="222"/></c:barChart>';
            }
            $ejes = $ax;
            $leyenda = '';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<c:roundedCorners val="0"/><c:chart><c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="1200" b="1"/></a:pPr><a:r><a:rPr lang="es-AR" sz="1200" b="1"/><a:t>'
            . self::x($g['titulo']) . '</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title><c:autoTitleDeleted val="0"/>'
            . '<c:plotArea><c:layout/>' . $plot . $ejes . '</c:plotArea>' . $leyenda
            . '<c:plotVisOnly val="1"/><c:dispBlanksAs val="gap"/></c:chart>'
            . '<c:spPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln w="9525"><a:solidFill><a:srgbClr val="D3DEEC"/></a:solidFill></a:ln></c:spPr></c:chartSpace>';
    }

    private function xmlDibujo(array $graficos): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart">';
        foreach ($graficos as $i => $g) {
            $a = $g['ancla'];
            $x .= '<xdr:twoCellAnchor><xdr:from><xdr:col>' . $a['c1'] . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . $a['f1'] . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
                . '<xdr:to><xdr:col>' . $a['c2'] . '</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . $a['f2'] . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                . '<xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="' . ($i + 2) . '" name="Grafico ' . ($i + 1) . '"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr>'
                . '<xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart"><c:chart r:id="rId' . ($i + 1) . '"/></a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>';
        }
        return $x . '</xdr:wsDr>';
    }

    public function guardar(string $ruta): bool
    {
        $zip = new XlsxZip();
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets>';
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $nChart = 0;
        $nDraw = 0;
        $filtros = '';
        foreach ($this->hojas as $i => $h) {
            $n = $i + 1;
            $nombre = self::x(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $h['nombre']), 0, 31));
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wb .= '<sheet name="' . $nombre . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $wbRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            if ($h['filtro']) {
                [$a, $b, $c, $d] = $h['filtro'];
                $abs = "'" . $nombre . "'!$" . self::letra($a) . '$' . ($b + 1) . ':$' . self::letra($c) . '$' . ($d + 1);
                $filtros .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">' . $abs . '</definedName>';
            }
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', $this->xmlHoja($i));
            if ($h['graficos']) {
                $nDraw++;
                $zip->addFromString('xl/worksheets/_rels/sheet' . $n . '.xml.rels',
                    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing' . $nDraw . '.xml"/></Relationships>');
                $ct .= '<Override PartName="/xl/drawings/drawing' . $nDraw . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
                $zip->addFromString('xl/drawings/drawing' . $nDraw . '.xml', $this->xmlDibujo($h['graficos']));
                $dr = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
                foreach ($h['graficos'] as $k => $g) {
                    $nChart++;
                    $dr .= '<Relationship Id="rId' . ($k + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart' . $nChart . '.xml"/>';
                    $ct .= '<Override PartName="/xl/charts/chart' . $nChart . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>';
                    $zip->addFromString('xl/charts/chart' . $nChart . '.xml', $this->xmlGrafico($g, $h['nombre']));
                }
                $zip->addFromString('xl/drawings/_rels/drawing' . $nDraw . '.xml.rels', $dr . '</Relationships>');
            }
        }
        $n = count($this->hojas) + 1;
        $wb .= '</sheets>' . ($filtros !== '' ? '<definedNames>' . $filtros . '</definedNames>' : '') . '</workbook>';
        $wbRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('[Content_Types].xml', $ct . '</Types>', true);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/styles.xml', $this->estilos());
        return file_put_contents($ruta, $zip->contenido()) !== false;
    }
}

class XlsxZip
{
    private array $entradas = [];

    public function addFromString(string $nombre, string $datos, bool $primero = false): void
    {
        if ($primero) {
            array_unshift($this->entradas, [$nombre, $datos]);
        } else {
            $this->entradas[] = [$nombre, $datos];
        }
    }

    public function contenido(): string
    {
        $out = '';
        $central = '';
        $hora = 0;
        $fecha = ((2026 - 1980) << 9) | (1 << 5) | 1;
        foreach ($this->entradas as [$nombre, $datos]) {
            $crc = crc32($datos) & 0xFFFFFFFF;
            $usize = strlen($datos);
            $comp = function_exists('gzdeflate') ? gzdeflate($datos, 6) : false;
            if ($comp !== false && strlen($comp) < $usize) {
                $metodo = 8;
            } else {
                $metodo = 0;
                $comp = $datos;
            }
            $csize = strlen($comp);
            $offset = strlen($out);
            $nl = strlen($nombre);
            $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $metodo, $hora, $fecha, $crc, $csize, $usize, $nl, 0) . $nombre . $comp;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $metodo, $hora, $fecha, $crc, $csize, $usize, $nl, 0, 0, 0, 0, 0, $offset) . $nombre;
        }
        $n = count($this->entradas);
        return $out . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($central), strlen($out), 0);
    }
}