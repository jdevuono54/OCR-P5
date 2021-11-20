<?php

namespace App\Model;

use App\Utils\Orm\Model;

class User extends Model
{
    protected static ?string $table = "user";
    protected static string $primaryKey = "id";
    protected static bool $timestamps = true;

    /**
     * Permet de récupérer le rôle d'un user
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function role(){
        return $this->belongsTo(Role::class,"id_role");
    }

    /**
     * Permet de récupérer les posts d'un user
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function posts(){
        return $this->hasMany(Post::class,"id_user");
    }

    /**
     * Permet de récupérer les commentaires d'un user
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function comments(){
        return $this->hasMany(Comment::class,"id_user");
    }
}