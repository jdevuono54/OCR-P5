<?php

namespace App\Model;

use App\Utils\Orm\Model;

class Role extends Model
{
    protected static ?string $table = "role";
    protected static string $primaryKey = "id";

    /**
     * Permet de récupérer les utilisateurs possédant un rôle
     *
     * @return mixed
     *
     * @throws \App\Exception\EmptyPrimaryKeyException
     */
    public function users(){
        return $this->hasMany(User::class,"id_role");
    }
}