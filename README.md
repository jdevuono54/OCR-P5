# Blog PHP

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/ed0b00cd65804ad786d11115c1c8bd8e)](https://www.codacy.com/gh/jdevuono54/OCR-P5/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=jdevuono54/OCR-P5&amp;utm_campaign=Badge_Grade)

## Introduction

Le projet est donc de développer votre blog professionnel. Ce site web se décompose en deux grands groupes de pages :

* Les pages utiles à tous les visiteurs
* Les pages permettant d’administrer votre blog

Liste des pages minimum qui doivent être accessible sur le site :

* La page d'accueil
* La page listant l’ensemble des blog posts
* La page affichant un blog post
* La page permettant d’ajouter un blog post
* La page permettant de modifier un blog post
* Les pages permettant de modifier/supprimer un blog post
* Les pages de connexion/enregistrement des utilisateurs

## Installation

### Étape 1
* Cloner le projet sur votre serveur php

### Étape 2
Installer les dépendances avec la commande

```bash
composer i
```

### Étape 3
* Créer une base de données pour le projet, puis importer le script SQL présent dans

```bash
/bdd/ocr_p5.sql
```

Une liste de comptes test ainsi que les mots de passes sont disponible dans le fichier
```bash
/bdd/accounts.txt
```

### Étape 4
* Configurer la connexion la base de données ainsi que votre serveur smtp dans le fichier
```bash
/config/config.ini
```