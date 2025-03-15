# Shape of You - Application d'aide vestimentaire avec IA

## Développeur
- Azat Harutyunyan https://github.com/azat44/shape-of-you-ah

## Description du projet
Shape of You est une application mobile-first qui aide les utilisateurs à mieux s'habiller grâce à l'intelligence artificielle. L'application détecte automatiquement les vêtements, propose des tenues, et offre des conseils personnalisés.


## Mise en production

https://violet-vulture-511538.hostingersite.com

## Fonctionnalités implémentées

### Partie Utilisateur
- Système d'authentification complet
- Module de prise de photo et détection des vêtements par IA
- Générateur de tenues basé sur l'IA
- Fonctionnalité de recherche avec filtres pour les vêtements et tenues
- Intégration avec des sites partenaires pour l'achat de vêtements
- Historique des tenues générées et sauvegardées
- Interface sociale pour le partage et la découverte de tenues

### Partie Administration
- Dashboard complet avec statistiques et analyses
- Système CRUD pour toutes les entités (utilisateurs, vêtements, catégories, partenaires)
- Système d'alertes IA pour les éléments non reconnus

## Technologies utilisées
- **Backend**: Symfony 6.x, PHP 8.1
- **Frontend**: Tailwind CSS, Lightning UI
- **IA**: API OpenAI, Claude, Mistral, HuggingFace
- **Base de données**: MySQL
- **Outils**: Composer, npm, Webpack Encore

## Installation et lancement en local

### Prérequis
- PHP 8.1 ou supérieur
- Composer
- Node.js 16+ et npm
- MySQL 8.0
- Symfony CLI

### Installation
1. Cloner le repository
```bash
git clone (https://github.com/azat44/shape-of-you-ah)
cd shape-of-you
```

2. Installer les dépendances PHP
```bash
composer install
```

3. Installer les dépendances JavaScript
```bash
npm install
```

4. Configurer la base de données
```bash
# Copier le fichier .env en .env.local et configurer les paramètres de base de données
cp .env .env.local
# Éditer .env.local avec vos paramètres

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Charger les fixtures (données de test)
```bash
php bin/console doctrine:fixtures:load
```

6. Compiler les assets
```bash
npm run build
```

7. Lancer le serveur de développement
```bash
symfony server:start
```
## Licence
@AH
