<?php

namespace App\Model;

use App\Utils\Orm\Model;

class Post extends Model
{
    protected static ?string $table = "post";
    protected static string $primaryKey = "id";
    protected static bool $timestamps = true;

    /**
     * Permet de récupérer l'auteur d'un post
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function author(){
        return $this->belongsTo(User::class,"id_user");
    }

    /**
     * Permet de récupérer les commentaires d'un post
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function comments($hydrate = true){
        return $this->hasMany(Comment::class,"id_post", $hydrate);
    }
}