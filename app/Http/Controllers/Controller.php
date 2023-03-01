<?php

namespace App\Http\Controllers;

use EasyRdf\RdfNamespace;
use EasyRdf\Sparql\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $sparql;

    function __construct()
    {
        RdfNamespace::set('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        RdfNamespace::set('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        RdfNamespace::set('owl', 'http://www.w3.org/2002/07/owl#');
        RdfNamespace::set('al', 'http://www.semanticweb.org/asus/ontologies/2023/1/alquran#');

        $this->sparql = new Client('http://localhost:3030/alquran/');
    }

    public function parse(String $content, String $replaceChar = null, String $replaceWith = null)
    {
        $str = explode('#', $content)[1];
        if ($replaceChar != null && $replaceWith != null) {
            $str = str_replace($replaceChar, $replaceWith, $str);
        }
        return $str;
    }
}
