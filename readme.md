# Meteolia

## Description

Meteolia est un outil permettant de générer des visuels météorologiques. A l’aide d’un champ de recherche l’utilisateur pourra saisir la localisation souhaitée. La soumission du formulaire déclenchera le téléchargement d’un visuel auto-généré.

## Extentions PHP requises

- Extension php_gd
- Extension php_fileinfo

## Installation

1. Cloner le dépôt :

    `git clone https://github.com/geopchn/Meteolia.git`

3. Se rendre dans le dossier du projet :

    `cd Meteolia`

4. Installer les dépendances avec Composer :

    `composer install`

5. Copier le contenu du fichier `.env` dans un nouveau fichier `.env.local` :

    `APP_SECRET=your_secret`

    `WEATHER_API_KEY=your_api_key`

    Remplacez `your_secret` par la clé secrète de votre application et `your_api_key` par la clé API obtenue auprès de OpenWeatherMap.

5.  Lancez l'application en exécutant un serveur local ou en la configurant sur votre serveur web.