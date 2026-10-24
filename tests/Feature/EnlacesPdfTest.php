<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * R-48: los PDF de exportar y de reportes se abren en una pestaña nueva, para que el
 * usuario no salga de la pantalla en la que estaba.
 */
class EnlacesPdfTest extends TestCase
{
    public function test_every_link_to_a_pdf_opens_in_a_new_tab(): void
    {
        preg_match_all("/->name\('([^']+)'\)/", File::get(base_path('routes/reportes.php')), $rutas);
        $this->assertNotEmpty($rutas[1]);

        $enlaces = 0;
        $fallos = [];

        foreach (File::allFiles(resource_path('views')) as $vista) {
            $html = $vista->getContents();

            foreach ($rutas[1] as $ruta) {
                preg_match_all("/route\('" . preg_quote($ruta, '/') . "'([^)]*)\)/", $html, $usos, PREG_OFFSET_CAPTURE);

                foreach ($usos[0] as $i => [$uso, $posicion]) {
                    // Los reportes con tipo 2 descargan el archivo sin salir de la página
                    if (trim($usos[1][$i][0]) === ', 2') {
                        continue;
                    }

                    $inicio = strrpos(substr($html, 0, $posicion), '<a');
                    $etiqueta = substr($html, $inicio, $posicion - $inicio + 250);
                    $enlaces++;

                    if (! str_contains($etiqueta, 'target="_blank"') || ! str_contains($etiqueta, 'rel="noopener"')) {
                        $fallos[] = $vista->getRelativePathname() . ": $uso";
                    }
                }
            }
        }

        $this->assertGreaterThanOrEqual(17, $enlaces);
        $this->assertSame([], $fallos, 'Enlaces a PDF que no abren en una pestaña nueva');
    }
}
