<?php

namespace App\Utils\Orm;

use PDO;

class Query
{
    private $table;
    private $fields = '*';
    private $where = null;
    private $args = [];
    private $sql = null;

    private $createdAtFied = 'created_at';
    private $updateAtFied = 'updated_at';
    private $deletedAtFied = 'deleted_at';

    /**
     * Permet de définir la table visée par la query
     *
     * @param $table
     *
     * @return Query
     */
    public static function table($table){
        $query = new Query;

        $query->table = $table;

        return $query;
    }

    /**
     * Permet de stocker les champs à récupérer pour la query
     *
     * @param array $fields
     *
     * @return $this
     */
    public function select(array $fields){
        $this->fields = implode(",", $fields);

        return $this;
    }

    /**
     * Permet de construire la partie where de la requête
     *
     * @param $field
     * @param $operator
     * @param $val
     *
     * @return $this
     */
    public function where($field,$operator,$val){
        // Si c'est la première fois qu'on utilise le where pour la query on ajoute where
        if($this->where == null){
            $this->where .= " where ";
        }
        else{ // sinon on remplace where par AND
            $this->where .= " AND ";
        }

        // On concat + on stock les args
        $this->where .= $field." ".$operator." ?";
        $this->args[] = $val;

        return $this;
    }

    /**
     * Permet d'éxecuter un select
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function get(){
        // On construit le select avec le where (si y'en a un)
        $this->sql = "SELECT ".$this->fields." FROM ".$this->table.$this->where;

        // On récup la connexion & prépare la requête
        $pdo = ConnectionFactory::getConnection();
        $stmt = $pdo->prepare($this->sql);

        // Si y'a un where, on ajoute les arguments
        if($this->where != null){
            $stmt->execute($this->args);
        }
        else{
            $stmt->execute();
        }

        // On retourne les résultats
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Permet de supprimer des données en base
     *
     * @return string
     *
     * @throws \Exception
     */
    public function delete(){
        // Si le model utilise le softDelete on ajoute simplement la date
        if($this->isModelSoftDelete()){
            return $this->update([$this->deletedAtFied => date('Y-m-d H:i:s')]);
        }

        // Si on est pas en mode soft delete on construit la requête
        $this->sql = "DELETE FROM ".$this->table.$this->where;

        // On récup la connexion
        $pdo = ConnectionFactory::getConnection();
        $stmt = $pdo->prepare($this->sql);

        // Si y'a un where on ajoute les arguments
        if($this->where != null){
            $stmt->execute($this->args);
        }
        else{
            $stmt->execute();
        }

        return $this->sql;
    }

    /**
     * Permet d'ajouter des données en base
     *
     * @param array $args
     * @return mixed
     *
     * @throws \Exception
     */
    public function insert(array $args = []){
        // On récup la connexion
        $pdo = ConnectionFactory::getConnection();

        // On construit la query
        $this->sql = "INSERT INTO `".$this->table."` (";

        // Pour chaque argument on récup la clé
        foreach ($args as $key => $val){
            $this->sql .= "`".$key."`,";
        }

        // Si la model est en mode timestamp & que createAt n'est pas présent, on l'ajoute automatiquement
        if($this->isModelTimestamp() && !array_key_exists($this->createdAtFied, $args)){
            $this->sql .= "`".$this->createdAtFied."`";
        } else {
            $this->sql = substr($this->sql, 0, -1);
        }

        // On concat la chaine
        $this->sql .= ") VALUES (";

        // On récup les arguments
        foreach ($args as $arg){
            $this->sql .= "?,";
        }

        // On ajoute la valeur pour le champ createdAt si on doit l'ajouter automatiquement
        if($this->isModelTimestamp() && !array_key_exists($this->createdAtFied, $args)){
            $this->sql .= "NOW()";
        } else {
            $this->sql = substr($this->sql, 0, -1);
        }

        // On concat la chaine
        $this->sql .= ");";

        // On prépare la requête
        $stmt = $pdo->prepare($this->sql);

        // On execute
        $stmt->execute(array_values($args));

        // On renvoi l'id créer
        return $pdo->lastInsertId();
    }

    /**
     * Permet d'update une ligne en base
     *
     * @param array $args
     * @return string
     *
     * @throws \Exception
     */
    public function update(array $args = []){
        // On construit la requête
        $this->sql = "UPDATE " . $this->table . " SET ";

        // On récup la connexion
        $pdo = ConnectionFactory::getConnection();

        // On parcours les arguments & on les concat
        foreach ($args as $key => $val){
            $this->sql .= "`".$key."` = ?,";
        }

        // Si on est en mode timestamp et qu'on veut pas softdelete on ajoute la date de modif
        if($this->isModelTimestamp() && !array_key_exists($this->deletedAtFied, $args)){
            $this->sql .= "`".$this->updateAtFied."` = NOW() ";
        } else {
            $this->sql = substr($this->sql,0,-1);
        }

        // On ajoute le where & on prepare la requête
        $this->sql .= ''.$this->where;
        $stmt = $pdo->prepare($this->sql);

        // On execute en récupérent les args pour l'update et ceux pour le where
        $stmt->execute(array_merge(array_values($args), array_values($this->args)));

        return $this->sql;
    }

    /**
     * Permet de savoir si le model utilise timestamp
     *
     * @return mixed
     */
    private function isModelTimestamp(){
        return $this->getModeWithName()::isTimestamps();
    }

    /**
     * Permet de savoir si le model utilise softDelete
     *
     * @return mixed
     */
    private function isModelSoftDelete(){
        return $this->getModeWithName()::isSoftDelete();
    }

    /**
     * Permet de récupèrer un model avec son nom
     *
     * @return string
     */
    private function getModeWithName(){
        return 'App\Model\\' . ucfirst($this->table);
    }
}