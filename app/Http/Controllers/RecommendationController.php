<?php

namespace App\Http\Controllers;

use EasyRdf\Exception;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        // Define key
        define('KEY_TEMPAT', 'tempat_turun');
        define('KEY_AYAT', 'jumlah_ayat');
        define('KEY_BOBOT_AYAT', 'bobot_jumlah_ayat');
        // Note: untuk key bobot, tambahkan 'bobot_{nama_kriteria}' 
        // diikuti dengan nama kriteria, contoh: 'jumlah_ayat' -> 'bobot_jumlah_ayat' 

        // Get request
        $tempatTurun = $request->query(KEY_TEMPAT);
        $jumlahAyat = $request->query(KEY_AYAT);
        $bobotAyat = $request->query(KEY_BOBOT_AYAT);

        // Variables
        $kriteria = $this->getListKriteria();
        $listRekomendasi = [];
        $listRekomendasiSorted = [];

        if (!empty($request->all())) {
            // Recommendation process
            $listSurah = $this->getListSurah($tempatTurun);
            $bobot = $this->getBobot($bobotAyat);
            $normalisasi = $this->getNormalisasi($listSurah, [KEY_AYAT]);
            $listRekomendasi = $this->getHasil($normalisasi, $bobot);
            $listRekomendasiSorted = $this->getHasilSorted($listRekomendasi);
            dd($listRekomendasi, $listRekomendasiSorted);
        }

        return view('recommendation', [
            'listRekomendasi' => $listRekomendasi,
            'listRekomendasiSorted' => $listRekomendasiSorted,
            'kriteria' => $kriteria
        ]);
    }

    private function getListSurah($tempatTurun): array
    {
        try {
            $list = [];

            // Contruct query
            $query = "SELECT DISTINCT * WHERE { 
                ?surah a al:Surah ;
                    al:Memiliki_Tempat_Turun_Di ?tempat ;
                    al:Jumlah_Ayat ?ayat .
            ";

            if ($tempatTurun != null) {
                $query .= "?surah al:Memiliki_Tempat_Turun_Di al:$tempatTurun .";
            }

            $query .= " }";
            
            // Exceute query
            $result = $this->sparql->query($query);

            // Parse data
            foreach ($result as $item) {
                array_push($list, [
                    'id' => $this->parse($item->surah),
                    'nama' => $this->parse($item->surah, '_', ' '),
                    'tempat_turun' => $this->parse($item->surah, '_', ' '),
                    'jumlah_ayat' => $item->ayat->getValue()
                ]);
            }
        } catch (Exception $e) {
            dd($e);
        }

        return $list;
    }

    private function getBobot($bobotAyat): array
    {
        $bobot = [];

        $bobot[KEY_BOBOT_AYAT] = $bobotAyat ? (int) $bobotAyat : 1;

        return $bobot;
    }

    private function getNormalisasi($listSurah, $kriteria_keys): array
    {
        // Variables
        $result = [];
        $listMin = null;
        $listMax = null;

        // Nilai min max data surah
        foreach ($kriteria_keys as $key) {
            $max = 0;
            $min = PHP_INT_MAX;

            foreach ($listSurah as $surah) {
                // Min
                if ($surah[$key] < $min) {
                    $min = $surah[$key];
                }
                // Max
                if ($surah[$key] > $max) {
                    $max = $surah[$key];
                }
            }

            $listMin[$key] = $min;
            $listMax[$key] = $max;
        }

        // Normalisasi
        foreach ($listSurah as $surah) {
            $current = $surah;
            // $current['id'] = $surah['id'];
            // $current['nama'] = $surah['nama'];

            foreach ($kriteria_keys as $key) {
                if ($surah[$key] > 0) {
                    $current['normalisasi'][$key] = ($surah[$key] - $listMin[$key]) / ($listMax[$key] - $listMin[$key]);
                } else {
                    $current['normalisasi'][$key] = 0;
                }
            }

            array_push($result, $current);
        }
        
        return $result;
    }

    private function getHasil($normalisasi, $bobot): array
    {
        $result = [];

        foreach ($normalisasi as $item) {
            $totalBobot = 0;
            $current = $item;
            foreach ($bobot as $key => $value) {
                $totalBobot += $item['normalisasi'][str_replace('bobot_', '', $key)] * $value;
            }
            $current['bobot'] = $totalBobot;
            array_push($result, $current);
        }

        return $result;
    }

    private function getHasilSorted($listRekomendasi): array
    {
        for ($i = 0; $i < sizeof($listRekomendasi); $i++) {
            for ($j = 0; $j < sizeof($listRekomendasi) - $i - 1; $j++) {
                // Swap if element found is less
                if ($listRekomendasi[$j]['bobot'] < $listRekomendasi[$j + 1]['bobot']) {
                    $temp = $listRekomendasi[$j];
                    $listRekomendasi[$j] = $listRekomendasi[$j + 1];
                    $listRekomendasi[$j + 1] = $temp;
                }
            }
        }

        return $listRekomendasi;
    }

    private function getListKriteria(): array
    {
        return [
            KEY_TEMPAT => $this->getListTempatTurun(),
            KEY_AYAT => $this->getListAyatDropdown()
        ];
    }

    private function getListTempatTurun(): array
    {
        $list = [];

        try {
            $result = $this->sparql->query("SELECT ?tempat WHERE { ?tempat a al:Tempat_Turun }");
            foreach ($result as $item) {
                array_push($list, [
                    'nama' => $this->parse($item->tempat, '_', ' ')
                ]);
            }
        } catch (Exception $e) {
            dd($e);
        }

        return $list;
    }

    private function getListAyatDropdown(): array
    {
        return [
            [
                'id' => 1,
                'label' => 'Kurang dari 50'
            ],
            [
                'id' => 1,
                'label' => 'Kurang dari 100'
            ],
            [
                'id' => 1,
                'label' => 'Kurang dari 150'
            ],
            [
                'id' => 1,
                'label' => 'Kurang dari 200'
            ],
            [
                'id' => 1,
                'label' => 'Lebih dari 200'
            ]
        ];
    }
}
