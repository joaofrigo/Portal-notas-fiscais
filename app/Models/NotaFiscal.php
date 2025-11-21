<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    protected $table = 'notas_fiscais'; // nome tabela

    protected $fillable = [
        'nome_arquivo',
        'xml_conteudo',
    ];
}

