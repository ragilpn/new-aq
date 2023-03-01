<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetailController extends Controller
{
    public function index($id)
    {
        $surah = [];
        
        $result = $this->sparql->query("SELECT ?surah ?arti ?tempat ?ayat ?urutan ?isi WHERE { 
            VALUES ?surah { al:Surah_Al-Anfal }
                ?surah al:Arti ?arti ;
                al:Memiliki_Tempat_Turun_Di ?tempat ;
                al:Jumlah_Ayat ?ayat ;
                al:Urutan_Pewahyuan ?urutan ;
                al:Isi_Surah ?isi .
        } LIMIT 1");

        if ($result->numRows() > 0) {
            $surah['id'] = $this->parse($result[0]->surah);
            $surah['nama'] = $this->parse($result[0]->surah, '_', ' ');
            $surah['arti'] = $result[0]->arti->getValue();
            $surah['tempat_turun'] = $this->parse($result[0]->tempat, '_', ' ');
            $surah['jumlah_ayat'] = $result[0]->ayat->getValue();
            $surah['urutan'] = $result[0]->urutan->getValue();
            $surah['isi'] = $result[0]->isi->getValue();
        }

        dd($surah);
        return view('detail', [
            'surah' => $surah
        ]);
    }
}
