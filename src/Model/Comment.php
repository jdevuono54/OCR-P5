<?php

namespace App\Model;

use App\Utils\Orm\Model;

class Comment extends Model
{
    protected static ?string $table = "comment";
    protected static string $primaryKey = "id";

    protected static bool $timestamps = true;

    /**
     * Permet de récupérer l'auteur d'un commentaire
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function author($hydrate = true){
        return $this->belongsTo(User::class,"id_user", $hydrate);
    }

    /**
     * Permet de récupérer le post d'un commentaire
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function post(){
        return $this->belongsTo(Post::class,"id_post");
    }
}