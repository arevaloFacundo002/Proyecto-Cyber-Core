<?php

require_once __DIR__ . '/FPDF/fpdf.php';

class ReportePDF extends FPDF
{
    const AZUL_OSC    = [0, 5, 33];
    const CELESTE     = [0, 190, 215];
    const AZUL        = [14, 78, 178];
    const GRIS        = [91, 107, 133];
    const GRIS_CLARO  = [244, 247, 251];
    const BORDE       = [220, 227, 238];
    const TEXTO       = [11, 18, 32];
    const PALETA      = [[14, 78, 178], [0, 190, 215], [32, 120, 207], [124, 141, 181], [0, 140, 160], [80, 110, 160]];

    private string $tituloReporte;
    private string $generadoPor = '';

    public function __construct(string $tituloReporte)
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        parent::__construct('P', 'mm', 'A4');
        $this->tituloReporte = $tituloReporte;
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $nombre = trim((string) ($_SESSION['usuario'] ?? ''));
        $rol    = trim((string) ($_SESSION['rol'] ?? ''));
        if ($nombre !== '') {
            $this->generadoPor = $nombre . ($rol !== '' ? ' (' . ucfirst($rol) . ')' : '');
        }
        $this->SetMargins(15, 24, 15);
        $this->SetAutoPageBreak(true, 22);
        $this->AliasNbPages();
        $this->SetTitle($tituloReporte . ' - CyberCore', true);
        $this->SetAuthor('CyberCore', true);
        $this->AddPage();
    }

    public static function t($texto): string
    {
        return mb_convert_encoding((string) $texto, 'Windows-1252', 'UTF-8');
    }

    private function color(array $c, string $tipo = 'text'): void
    {
        if ($tipo === 'fill') {
            $this->SetFillColor($c[0], $c[1], $c[2]);
        } elseif ($tipo === 'draw') {
            $this->SetDrawColor($c[0], $c[1], $c[2]);
        } else {
            $this->SetTextColor($c[0], $c[1], $c[2]);
        }
    }

    public function Header()
    {
        $this->color(self::AZUL_OSC, 'fill');
        $this->Rect(0, 0, 210, 16, 'F');
        $this->SetFont('Arial', 'B', 14);
        $ancho = $this->GetStringWidth('CYBER');
        $this->SetTextColor(255, 255, 255);
        $this->Text(15, 10.5, 'CYBER');
        $this->SetTextColor(0, 234, 255);
        $this->Text(15 + $ancho, 10.5, 'CORE');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(200, 210, 230);
        $txt = self::t($this->tituloReporte);
        $this->Text(195 - $this->GetStringWidth($txt), 10.2, $txt);
        $this->SetY(24);
    }

    public function Footer()
    {
        $this->SetY(-14);
        $this->color(self::BORDE, 'draw');
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Arial', '', 8);
        $this->color(self::GRIS);
        $this->SetX(15);
        $quien = $this->generadoPor !== '' ? ' por ' . $this->generadoPor : '';
        $this->Cell(140, 5, self::t('CyberCore · Generado' . $quien . ' el ' . date('d/m/Y H:i')), 0, 0, 'L');
        $this->Cell(40, 5, self::t('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    private function espacio(float $alto): void
    {
        if ($this->GetY() + $alto > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    public function portada(string $titulo, string $subtitulo = '', string $codigo = ''): void
    {
        $this->SetFont('Arial', 'B', 22);
        $this->color(self::AZUL_OSC);
        $this->Cell(0, 11, self::t($titulo), 0, 1);
        if ($subtitulo !== '') {
            $this->SetFont('Arial', '', 11);
            $this->color(self::GRIS);
            $this->MultiCell(0, 6, self::t($subtitulo));
            $this->Ln(1);
        }
        $this->SetFont('Arial', '', 9);
        $this->color(self::GRIS);
        if ($this->generadoPor !== '') {
            $this->Cell(0, 5, self::t('Generado por: ' . $this->generadoPor . ' · ' . date('d/m/Y H:i')), 0, 1);
        }
        if ($codigo !== '') {
            $this->Cell(0, 5, self::t('Código de reporte: ' . $codigo), 0, 1);
        }
        $this->color(self::CELESTE, 'draw');
        $this->SetLineWidth(0.9);
        $this->Line(15, $this->GetY() + 2, 50, $this->GetY() + 2);
        $this->SetLineWidth(0.2);
        $this->Ln(8);
    }

    public function seccion(string $titulo): void
    {
        $this->espacio(40);
        $this->Ln(4);
        $y = $this->GetY();
        $this->color(self::CELESTE, 'fill');
        $this->Rect(15, $y, 2.2, 6.5, 'F');
        $this->SetXY(20, $y);
        $this->SetFont('Arial', 'B', 13);
        $this->color(self::TEXTO);
        $this->Cell(0, 6.5, self::t($titulo), 0, 1);
        $this->Ln(3);
    }

    public function nota(string $texto): void
    {
        $this->SetFont('Arial', '', 9);
        $this->color(self::GRIS);
        $this->MultiCell(0, 5, self::t($texto));
        $this->Ln(2);
    }

    public function analisis(array $lineas): void
    {
        foreach ($lineas as $linea) {
            $this->espacio(10);
            $y = $this->GetY();
            $this->color(self::CELESTE, 'fill');
            $this->Rect(17, $y + 2.2, 1.8, 1.8, 'F');
            $this->SetFont('Arial', '', 10);
            $this->color(self::TEXTO);
            $this->SetXY(22, $y);
            $this->MultiCell(173, 5.5, self::t($linea));
            $this->Ln(1);
        }
        $this->Ln(2);
    }

    public function kpis(array $items, int $columnas = 3): void
    {
        $sep   = 4;
        $ancho = (180 - $sep * ($columnas - 1)) / $columnas;
        $alto  = 20;
        $filas = array_chunk($items, $columnas);

        foreach ($filas as $fila) {
            $this->espacio($alto + 4);
            $y = $this->GetY();
            foreach ($fila as $i => $item) {
                $x = 15 + $i * ($ancho + $sep);
                $this->color(self::GRIS_CLARO, 'fill');
                $this->color(self::BORDE, 'draw');
                $this->Rect($x, $y, $ancho, $alto, 'DF');
                $this->color(self::CELESTE, 'fill');
                $this->Rect($x, $y, 1.6, $alto, 'F');

                $tam = 18;
                $this->SetFont('Arial', 'B', $tam);
                while ($tam > 9 && $this->GetStringWidth(self::t($item[1])) > $ancho - 8) {
                    $tam--;
                    $this->SetFont('Arial', 'B', $tam);
                }
                $this->color(self::AZUL);
                $this->SetXY($x + 5, $y + 3);
                $this->Cell($ancho - 7, 9, self::t($item[1]), 0, 1);

                $this->SetFont('Arial', '', 9);
                $this->color(self::GRIS);
                $this->SetXY($x + 5, $y + 12.5);
                $this->Cell($ancho - 7, 5, self::t($item[0]), 0, 0);
            }
            $this->SetY($y + $alto + 4);
        }
    }

    private function ajustar(string $texto, float $ancho): string
    {
        $limite = $ancho - 3;
        if ($this->GetStringWidth(self::t($texto)) <= $limite) {
            return $texto;
        }
        while (mb_strlen($texto) > 1 && $this->GetStringWidth(self::t($texto . '...')) > $limite) {
            $texto = mb_substr($texto, 0, -1);
        }
        return $texto . '...';
    }

    public function tabla(array $cabeceras, array $filas, array $anchos, array $alineacion = []): void
    {
        $alto = 7;
        $dibujarCabecera = function () use ($cabeceras, $anchos, $alineacion) {
            $this->SetFont('Arial', 'B', 9);
            $this->color(self::AZUL, 'fill');
            $this->SetTextColor(255, 255, 255);
            foreach ($cabeceras as $i => $c) {
                $this->Cell($anchos[$i], 8, self::t($this->ajustar($c, $anchos[$i])), 0, 0, $alineacion[$i] ?? 'L', true);
            }
            $this->Ln();
        };

        $this->espacio(8 + $alto * 2);
        $dibujarCabecera();

        $this->SetFont('Arial', '', 9);
        if (!$filas) {
            $this->color(self::GRIS_CLARO, 'fill');
            $this->color(self::GRIS);
            $this->Cell(array_sum($anchos), $alto, self::t('Sin datos para mostrar'), 0, 1, 'C', true);
        }

        foreach ($filas as $n => $fila) {
            if ($this->GetY() + $alto > $this->PageBreakTrigger) {
                $this->AddPage();
                $dibujarCabecera();
                $this->SetFont('Arial', '', 9);
            }
            $this->color($n % 2 ? self::GRIS_CLARO : [255, 255, 255], 'fill');
            $this->color(self::TEXTO);
            foreach ($fila as $i => $valor) {
                $this->Cell($anchos[$i], $alto, self::t($this->ajustar((string) $valor, $anchos[$i])), 0, 0, $alineacion[$i] ?? 'L', true);
            }
            $this->Ln();
        }

        $this->color(self::BORDE, 'draw');
        $this->Line(15, $this->GetY(), 15 + array_sum($anchos), $this->GetY());
        $this->Ln(5);
    }

    private function tituloGrafico(string $titulo): void
    {
        if ($titulo !== '') {
            $this->SetFont('Arial', 'B', 10);
            $this->color(self::TEXTO);
            $this->Cell(0, 6, self::t($titulo), 0, 1);
            $this->Ln(1);
        }
    }

    private function vacio(string $titulo): void
    {
        $this->espacio(24);
        $this->tituloGrafico($titulo);
        $this->color(self::GRIS_CLARO, 'fill');
        $this->color(self::GRIS);
        $this->SetFont('Arial', '', 9);
        $this->Cell(180, 12, self::t('Sin datos para mostrar'), 0, 1, 'C', true);
        $this->Ln(4);
    }

    private function formato($v, bool $dinero = false): string
    {
        if ($dinero) {
            return '$' . number_format((float) $v, 2, ',', '.');
        }
        return is_float($v) && floor($v) != $v ? number_format($v, 2, ',', '.') : number_format((float) $v, 0, ',', '.');
    }

    public function barras(array $datos, string $titulo = '', bool $dinero = false): void
    {
        if (!$datos) {
            $this->vacio($titulo);
            return;
        }
        $alto = 8;
        $this->espacio(count($datos) * $alto + 14);
        $this->tituloGrafico($titulo);

        $anchoEtiqueta = 52;
        $anchoValor    = $dinero ? 30 : 16;
        $anchoMax      = 180 - $anchoEtiqueta - $anchoValor - 4;
        $maximo        = max(max($datos), 1);

        foreach ($datos as $etiqueta => $valor) {
            $y = $this->GetY();
            $this->SetFont('Arial', '', 9);
            $this->color(self::TEXTO);
            $this->SetXY(15, $y);
            $this->Cell($anchoEtiqueta, $alto, self::t($this->ajustar((string) $etiqueta, $anchoEtiqueta)), 0, 0, 'L');

            $this->color(self::GRIS_CLARO, 'fill');
            $this->Rect(15 + $anchoEtiqueta, $y + 1.5, $anchoMax, 5, 'F');
            $w = max($valor / $maximo * $anchoMax, $valor > 0 ? 0.8 : 0);
            $this->color(self::AZUL, 'fill');
            $this->Rect(15 + $anchoEtiqueta, $y + 1.5, $w, 5, 'F');

            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(15 + $anchoEtiqueta + $anchoMax + 2, $y);
            $this->Cell($anchoValor, $alto, self::t($this->formato($valor, $dinero)), 0, 0, 'L');
            $this->SetY($y + $alto);
        }
        $this->Ln(4);
    }

    public function lineas(array $datos, string $titulo = '', bool $dinero = false): void
    {
        if (!$datos) {
            $this->vacio($titulo);
            return;
        }
        $alto = 58;
        $this->espacio($alto + 14);
        $this->tituloGrafico($titulo);

        $y0 = $this->GetY();
        $x1 = 30;
        $x2 = 192;
        $yTop = $y0 + 6;
        $yBase = $y0 + $alto - 10;
        $maximo = max(max($datos), 1);
        $maximo = (int) ceil($maximo / 4) * 4;

        $this->SetFont('Arial', '', 8);
        for ($g = 0; $g <= 4; $g++) {
            $yy = $yBase - ($yBase - $yTop) * $g / 4;
            $this->color(self::BORDE, 'draw');
            $this->Line($x1, $yy, $x2, $yy);
            $this->color(self::GRIS);
            $this->Text(15, $yy + 1, number_format($maximo * $g / 4, 0, ',', '.'));
        }

        $n = count($datos);
        $puntos = [];
        $i = 0;
        foreach ($datos as $etiqueta => $valor) {
            $x = $n === 1 ? ($x1 + $x2) / 2 : $x1 + ($x2 - $x1) * $i / ($n - 1);
            $y = $yBase - ($yBase - $yTop) * $valor / $maximo;
            $puntos[] = [$x, $y, $etiqueta, $valor];
            $i++;
        }

        $this->color(self::AZUL, 'draw');
        $this->SetLineWidth(0.7);
        for ($k = 1; $k < $n; $k++) {
            $this->Line($puntos[$k - 1][0], $puntos[$k - 1][1], $puntos[$k][0], $puntos[$k][1]);
        }
        $this->SetLineWidth(0.2);

        $salto = max(1, (int) ceil($n / 12));
        $conValores = $n <= 16;
        foreach ($puntos as $k => $p) {
            $this->color(self::AZUL, 'fill');
            $this->circulo($p[0], $p[1], 1.2, 'F');
            if ($conValores) {
                $this->SetFont('Arial', 'B', 8);
                $this->color(self::TEXTO);
                $txt = self::t($this->formato($p[3], $dinero));
                $this->Text($p[0] - $this->GetStringWidth($txt) / 2, $p[1] - 2.5, $txt);
            }
            if ($k % $salto === 0) {
                $this->SetFont('Arial', '', 8);
                $this->color(self::GRIS);
                $et = self::t((string) $p[2]);
                $this->Text($p[0] - $this->GetStringWidth($et) / 2, $yBase + 5, $et);
            }
        }
        $this->SetY($y0 + $alto);
        $this->Ln(2);
    }

    public function torta(array $datos, string $titulo = '', bool $dinero = false): void
    {
        $total = array_sum($datos);
        if (!$datos || $total <= 0) {
            $this->vacio($titulo);
            return;
        }
        $this->espacio(56);
        $this->tituloGrafico($titulo);

        $y0 = $this->GetY();
        $cx = 40;
        $cy = $y0 + 24;
        $r  = 22;
        $angulo = -90.0;
        $i = 0;
        foreach ($datos as $valor) {
            if ($valor <= 0) {
                $i++;
                continue;
            }
            $barrido = 360 * $valor / $total;
            $puntos = [[$cx, $cy]];
            $pasos = max((int) ceil($barrido / 3), 2);
            for ($s = 0; $s <= $pasos; $s++) {
                $a = deg2rad($angulo + $barrido * $s / $pasos);
                $puntos[] = [$cx + $r * cos($a), $cy + $r * sin($a)];
            }
            $this->color(self::PALETA[$i % count(self::PALETA)], 'fill');
            $this->SetDrawColor(255, 255, 255);
            $this->SetLineWidth(0.5);
            $this->poligono($puntos, 'FD');
            $angulo += $barrido;
            $i++;
        }
        $this->SetLineWidth(0.2);

        $this->SetFillColor(255, 255, 255);
        $this->circulo($cx, $cy, 11, 'F');
        $this->SetFont('Arial', 'B', 13);
        $this->color(self::AZUL_OSC);
        $txt = self::t($dinero ? '$' . number_format(round($total), 0, ',', '.') : (string) $total);
        $this->Text($cx - $this->GetStringWidth($txt) / 2, $cy + 0.5, $txt);
        $this->SetFont('Arial', '', 7);
        $this->color(self::GRIS);
        $this->Text($cx - $this->GetStringWidth('total') / 2, $cy + 4.5, 'total');

        $paso = count($datos) > 6 ? 6 : 8;
        $yy = $y0 + 24 - ($paso * count($datos)) / 2 + 1;
        $i = 0;
        foreach ($datos as $etiqueta => $valor) {
            $this->color(self::PALETA[$i % count(self::PALETA)], 'fill');
            $this->Rect(78, $yy + 1, 4, 4, 'F');
            $this->SetFont('Arial', '', 10);
            $this->color(self::TEXTO);
            $this->SetXY(85, $yy);
            $pct = number_format($valor / $total * 100, 1, ',', '.');
            $this->Cell(105, 6, self::t($this->ajustar($etiqueta, 60) . ': ' . $this->formato($valor, $dinero) . '  (' . $pct . '%)'), 0, 0, 'L');
            $yy += $paso;
            $i++;
        }
        $this->SetY($y0 + 52);
    }

    private function poligono(array $puntos, string $estilo = 'F'): void
    {
        $op = $estilo === 'F' ? 'f' : ($estilo === 'FD' || $estilo === 'DF' ? 'B' : 'S');
        $s = '';
        foreach ($puntos as $i => $p) {
            $s .= sprintf('%.2F %.2F %s ', $p[0] * $this->k, ($this->h - $p[1]) * $this->k, $i ? 'l' : 'm');
        }
        $this->_out($s . 'h ' . $op);
    }

    private function circulo(float $cx, float $cy, float $r, string $estilo = 'F'): void
    {
        $puntos = [];
        for ($a = 0; $a < 360; $a += 6) {
            $puntos[] = [$cx + $r * cos(deg2rad($a)), $cy + $r * sin(deg2rad($a))];
        }
        $this->poligono($puntos, $estilo);
    }
}