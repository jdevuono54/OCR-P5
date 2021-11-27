<?php

namespace App\Utils\Image;

use App\Exception\ImageValidationException;

class ImageValidator
{
    /**
     * Permet de valider une image
     *
     * @throws ImageValidationException
     */
    public static function validate($image){
        // Liste des formats acceptés
        $allowed_extension = ["png", "jpg", "jpeg", "gif", "webp"];

        // On récupère l'extension du fichier envoyé
        $file_extension = pathinfo($image["name"], PATHINFO_EXTENSION);

        // Si l'image est vide on renvoi throw une erreur
        if (!file_exists($image["tmp_name"])) {
            throw new ImageValidationException("L'image est vide");
        } // Si le format n'est pas valide on throw une erreur
        else if (!in_array($file_extension, $allowed_extension)) {
            throw new ImageValidationException("Format d'image incorrect");
        } // Si le poid de l'image est supérieur a 8mo on throw une erreur
        else if ($image["size"] > 8000000) {
            throw new ImageValidationException("Le poids de l'image est supérieur à 8mo");
        }

        return true;
    }
}