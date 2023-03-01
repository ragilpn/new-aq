<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function list()
    {
        $list = [];

        $result = $this->sparql->query("SELECT DISTINCT * WHERE { 
            ?surah a al:Surah ;
                al:Arti ?arti ;
                al:Memiliki_Tempat_Turun_Di ?tempat ;
                al:Jumlah_Ayat ?ayat ;
                al:Urutan_Pewahyuan ?urutan .
        }");

        foreach ($result as $item) {
            array_push($list, [
                'id' => $this->parse_data($item->surah),
                'nama' => $this->parse_data($item->surah, '_', ' '),
                'arti' => $item->arti->getValue(),
                'tempat_turun' => $this->parse_data($item->surah, '_', ' '),
                'jumlah_ayat' => $item->ayat->getValue(),
                'urutan' => $item->urutan->getValue(),
            ]);
        }

        dd($list);
        return view('list', [
            'list' => $list
        ]);
    }
}
