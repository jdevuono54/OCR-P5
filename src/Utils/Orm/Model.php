<?php

namespace App\Utils\Orm;

use App\Exception\AttributeNotExistException;
use App\Exception\EmptyPrimaryKeyException;
use App\Exception\EmptyTableNameException;

abstract class Model
{
    protected static ?string $table = null;
    protected array $attr = [];

    protected static string $primaryKey = "id";
    protected static bool $timestamps = false;
    protected static bool $softDelete = false;

    public function __construct(array $attr = [])
    {
        $this->attr = $attr;
    }

    /**
     * Permet de savoir si le model utilise les timestamp
     *
     * @return bool
     */
    public static function isTimestamps(): bool
    {
        return static::$timestamps;
    }

    /**
     * Permet de savoir si le model utilise le softdelete
     *
     * @return bool
     */
    public static function isSoftDelete(): bool
    {
        return static::$softDelete;
    }

    /**
     * Methode magique pour récupérer un attribut/une méthode
     *
     * @param $name
     * @return mixed
     *
     * @throws AttributeNotExistException
     */
    public function __get($name)
    {
        // Si c'est une méthode on la retourne
        if (method_exists(static::class, $name)) {
            return $this->$name();
        } else { // Si c'est un attribut, on le renvoi s'il existe sinon on soulève une erreur
            if (array_key_exists($name, $this->attr)) {
                return $this->attr[$name];
            } else {
                throw new AttributeNotExistException("L'attribut n'existe pas");
            }
        }
    }

    /**
     * Permet de set un attribut pour le model
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attr[$name] = $value;
    }

    public function delete()
    {
        if (static::$table != null) {
            if ($this->attr[static::$primaryKey] != null) {
                $query = Query::table(static::$table);
                $query->where(static::$primaryKey, "=", $this->attr[static::$primaryKey]);
                $query->delete();
            } else {
                throw new EmptyPrimaryKeyException("La clé primaire ne doit pas être vide pour supprimé une ligne");
            }
        } else {
            throw new EmptyTableNameException("Le nom de la table doit être renseigné");
        }
    }

    /**
     * Permet d'insérer une ligne en base
     *
     * @throws EmptyTableNameException
     */
    public function insert()
    {
        // Si la table est renseignée on fait l'insert
        if (static::$table != null) {
            $query = Query::table(static::$table);

            $this->attr[static::$primaryKey] = $query->insert($this->attr);
        } else { // Sinon on soulève une erreur
            throw new EmptyTableNameException("Le nom de la table doit être renseigné");
        }
    }

    /**
     * Permet de définir une relation belongsTo
     *
     * @param $modele
     * @param $foreign_key
     *
     * @return mixed
     *
     * @throws EmptyPrimaryKeyException
     */
    public function belongsTo($modele, $foreign_key)
    {
        // Si la clé primaire est renseignée
        if ($this->attr[static::$primaryKey] != null) {
            $query = Query::table($modele::$table);

            // On éxecute la requête, la clé primaire est = à la foreign key passer en param
            $query = $query->where($modele::$primaryKey, "=", $this->attr[$foreign_key])->get();

            return $modele::arrayToObject($query)[0];
        } else { // Sinon on soulève une erreur
            throw new EmptyPrimaryKeyException("La clé primaire ne doit pas être vide");
        }
    }

    /**
     * Permet de définir une relation hasMany
     *
     * @param $modele
     * @param $foreign_key
     *
     * @return mixed
     *
     * @throws EmptyPrimaryKeyException
     */
    public function hasMany($modele, $foreign_key)
    {
        // Si la clé primaire est renseignée
        if ($this->attr[static::$primaryKey] != null) {
            $query = Query::table($modele::$table);

            // On éxecute la requête, la foreign key doit avoir comme valeur la clé primaire du model sur lequel on execute le hasmany
            $query = $query->where($foreign_key, "=", $this->attr[static::$primaryKey])->get();

            return $modele::arrayToObject($query);
        } else { // Sinon on soulève une erreur
            throw new EmptyPrimaryKeyException("La clé primaire ne doit pas être vide");
        }
    }

    /**
     * Permet de récupérer toutes les données d'une table
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function all()
    {
        $query = Query::table(static::$table)->get();

        return self::arrayToObject($query);
    }

    /**
     * Permet de faire un find avec des critères
     *
     * @param null $criteria
     * @param array $colomns
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function find($criteria = null, array $colomns = [])
    {
        $query = Query::table(static::$table);

        // Si on veut que certains colonnes on construit la query
        if ($colomns != null) {
            $query = $query->select($colomns);
        }

        // Si y'a des critères on construit la query
        if($criteria){
            $query = self::constructQueryWithCriteria($query, $criteria);
        }

        $query = $query->get();

        return self::arrayToObject($query);
    }

    /**
     * Permet de faire un where
     *
     * @param $criteria
     * @return Query
     *
     * @throws EmptyTableNameException
     */
    public static function where($criteria)
    {
        // Si la table est renseigne on construit le where
        if (static::$table != null) {
            $query = Query::table(static::$table);

            return self::constructQueryWithCriteria($query, $criteria);
        } else { // Sinon on soulève une erreur
            throw new EmptyTableNameException("Le nom de la table doit être renseigné");
        }
    }

    /**
     * Permet de construire une requête avec des critères
     *
     * @param Query $query
     * @param $criteria
     *
     * @return Query
     */
    private static function constructQueryWithCriteria(Query $query, $criteria): Query
    {
        // Si le critère est un int c'est que c'est l'id
        if (is_int($criteria)) {
            $query = $query->where(static::$primaryKey, "=", $criteria);
        }

        // Si c'est un tableau
        if (is_array($criteria)) {
            // Si y'a qu'un seul critère on construit la requête avec le critère
            if (!is_array($criteria[0])) {
                $query = $query->where($criteria[0], $criteria[1], $criteria[2]);
            } else {
                // Si y'en a plusieurs on ajoute les critères au fur à mesure
                foreach ($criteria as $crit) {
                    $query = $query->where($crit[0], $crit[1], $crit[2]);
                }
            }

        }

        return $query;
    }

    /**
     * Permet de récupérer seulement le premier résultat d'une query
     *
     * @param null $criteria
     * @param array $colomns
     *
     * @return mixed|void
     */
    public static function first($criteria = null, array $colomns = [])
    {
        // On execute le find
        $query = self::find($criteria, $colomns);

        // Si y'a un résultat on renvoi le premier
        if ($query != null) {
            return $query[0];
        }
    }

    /**
     * Permet de transformer un tableau en objet
     *
     * @param $array
     *
     * @return array
     */
    private static function arrayToObject($array)
    {
        $result = [];

        // On parcours le tableau pour créer les objets
        foreach ($array as $row) {
            $objet = new static($row);
            $result[] = $objet;
        }

        return $result;
    }
}