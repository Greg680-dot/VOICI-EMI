# Guide de Déploiement sur Render.com — VoiCI-EMI

Ce guide explique comment déployer l'intégralité de la plateforme **VoiCI-EMI** (API Laravel 13 + Frontend Vue 3 + Base de Données) sur **[Render.com](https://render.com)** en quelques clics.

---

## 🚀 Méthode 1 : Déploiement Automatique via Blueprint (Recommandé - 1 Clic)

Render détecte automatiquement le fichier `render.yaml` situé à la racine du dépôt GitHub.

1. Connectez-vous sur votre compte **[dashboard.render.com](https://dashboard.render.com/)**.
2. Cliquez sur le bouton bleu **« New + »** en haut à droite, puis sélectionnez **« Blueprint »**.
3. Sélectionnez votre dépôt GitHub : **`Greg680-dot/VOICI-EMI`**.
4. Render lit le fichier `render.yaml` et prépare automatiquement :
   - Le service web Docker unifié (PHP 8.3 FPM + Nginx + Vue 3).
   - Les variables d'environnement de production.
   - La base de données SQLite auto-provisionnée et initialisée.
5. Cliquez sur **« Apply »**.
6. Render lance la compilation et vous fournit votre URL publique en direct (ex: `https://voici-emi.onrender.com`).

---

## 🛠️ Méthode 2 : Déploiement Manuel via Web Service

Si vous préférez créer le service manuellement :

1. Sur **[dashboard.render.com](https://dashboard.render.com/)**, cliquez sur **« New + »** → **« Web Service »**.
2. Choisissez **« Build and deploy from a Git repository »** et sélectionnez `Greg680-dot/VOICI-EMI`.
3. Renseignez les paramètres suivants :
   - **Name** : `voici-emi`
   - **Region** : `Frankfurt (EU Central)` *(ou la plus proche)*
   - **Branch** : `main`
   - **Runtime** : **`Docker`**
   - **Dockerfile Path** : `./Dockerfile`
   - **Instance Type** : `Free`
4. Dans la section **Environment Variables**, ajoutez :
   | Clé | Valeur |
   | :--- | :--- |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `DB_CONNECTION` | `sqlite` |
   | `LOG_CHANNEL` | `stderr` |
5. Cliquez sur **« Create Web Service »**.

---

## 🔑 Identifiants Pré-configurés dans la Base de Démonstration

Dès le premier lancement sur Render, la base de données est automatiquement migrée et pré-remplie avec tous les acteurs, articles et cours certifiants :

| Rôle | Adresse Email | Mot de passe |
| :--- | :--- | :--- |
| **👑 Super Admin (Root)** | `sa@voici-emi.bj` | `Admin12345!` |
| **⭐ Administrateur** | `admin@voici-emi.bj` | `Password123!` |
| **📚 Formateur** | `formateur@voici-emi.bj` | `Password123!` |
| **🎓 Apprenant** | `apprenant@voici-emi.bj` | `Password123!` |
| **🇧🇯 Citoyen** | `citoyen@voici-emi.bj` | `Password123!` |

---

## 🗄️ Utiliser une Base de Données MySQL ou PostgreSQL Externe (Optionnel)

Par défaut, l'application fonctionne immédiatement et sans frais avec SQLite.  
Si vous souhaitez connecter une base de données MySQL ou PostgreSQL hébergée (sur Render, Aiven, Supabase, Neon, etc.), ajoutez simplement ces variables dans Render :

```env
DB_CONNECTION=mysql
DB_HOST=votre-hote-mysql.com
DB_PORT=3306
DB_DATABASE=nom_de_la_base
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```
Le conteneur exécutera automatiquement les migrations et les seeders sur cette base lors du démarrage.
