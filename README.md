# MAD Tracker — KEHITAA SARL

Application de suivi des Mises À Disposition (MAD) fournisseurs.

## Stack

- **Laravel 11** — Backend PHP
- **Livewire 3** — Composants réactifs
- **Tailwind CSS 3** — Styles
- **Alpine.js 3** — Interactions JS légères
- **Vite** — Build tool
- **ApexCharts** — Graphiques dashboard
- **Maatwebsite/Excel** — Export Excel
- **barryvdh/dompdf** — Export PDF

---

## Installation

### 1. Cloner / décompresser le projet

```bash
cd /var/www
# copier le dossier mad-tracker ici
cd mad-tracker
```

### 2. Dépendances PHP

```bash
composer install
```

### 3. Dépendances JS

```bash
npm install
```

### 4. Configuration environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` :

```env
DB_DATABASE=mad_tracker
DB_USERNAME=votre_user
DB_PASSWORD=votre_password
APP_URL=http://votre-domaine.com
```

### 5. Base de données

```bash
# Créer la base d'abord dans MySQL
mysql -u root -p -e "CREATE DATABASE mad_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrations
php artisan migrate

# Données initiales (utilisateurs, clients, fournisseurs)
php artisan db:seed
```

### 6. Build assets

```bash
# Développement
npm run dev

# Production
npm run build
```

### 7. Lancer le serveur (développement)

```bash
php artisan serve
```

Accéder à `http://localhost:8000`

---

## Comptes par défaut (après seed)

| Initiales | Email               | Mot de passe | Rôle        |
|-----------|---------------------|--------------|-------------|
| MSB       | msb@kehitaa.com     | password     | Admin       |
| CA        | ca@kehitaa.com      | password     | Gestionnaire|
| EB        | eb@kehitaa.com      | password     | Gestionnaire|
| NM        | nm@kehitaa.com      | password     | Gestionnaire|

---

## Structure du projet

```
app/
  Http/Livewire/
    Dashboard.php       — Tableau de bord
    DossierIndex.php    — Liste des dossiers
    DossierForm.php     — Formulaire création/édition
    DossierShow.php     — Détail + timeline + observations
    Analyse.php         — Analyses & performances
  Models/
    Dossier.php         — Modèle principal
    Client.php
    Fournisseur.php
    User.php
    EtapeMadFournisseur.php
    EtapeFacturation.php
    EtapeTransitaire.php
    EtapeLivraison.php
    EtapeCloture.php
    Observation.php
  Exports/
    DossiersExport.php  — Export Excel
  Http/Controllers/
    ExportController.php
    AuthController.php

database/migrations/    — 6 migrations
database/seeders/       — Données initiales

resources/
  css/app.css           — Styles Tailwind custom
  js/app.js             — Alpine + Flatpickr + ApexCharts
  views/
    layouts/app.blade.php
    auth/login.blade.php
    livewire/
      dashboard.blade.php
      dossier-index.blade.php
      dossier-form.blade.php
      dossier-show.blade.php
      analyse.blade.php
    pdf/dossier.blade.php
```

---

## Workflow des 5 étapes

```
1. MAD Fournisseur  → Usine met à dispo, docs/photos/COC reçus
2. Facturation      → Facture émise, paiement client
3. Transitaire      → Infos transitaire reçues, instructions, enlèvement
4. Livraison        → Date prévue vs réelle, AWB/BL (selon incoterm)
5. Clôture POD      → Proof of Delivery → clôture l'affaire
```

## Alertes automatiques

- 🔴 MAD en retard : date MAD prévue dépassée sans MAD réelle
- 🟡 Facture manquante : MAD faite mais pas encore facturé
- 🟡 Transitaire manquant : facturé mais transitaire non communiqué
- 🔴 Livraison dépassée : date prévue dépassée sans livraison réelle
- 🟡 POD manquante : enlevé mais POD non reçue

## Exports

- **Excel** : tous les dossiers avec toutes les colonnes (`/export/dossiers`)
- **PDF** : fiche individuelle d'un dossier avec timeline complète
