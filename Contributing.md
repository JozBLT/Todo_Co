   ### Contribuer au projet ToDo & Co ###

## 🚀 Pré-requis ##

PHP >= 8.2

Composer

Symfony CLI (optionnel mais recommandé)

Node.js et npm (si vous modifiez les assets front)

Xdebug ou PCOV pour générer les rapports de couverture

## 📝 Installation du projet ##

Clonez le dépôt GitHub :

   git clone https://github.com/JozBLT/Todo_Co.git

Installez les dépendances :

   -> composer install

Configurez l'environnement :

   Copiez le fichier .env en .env.local et modifiez-le si nécessaire (connexion BDD, etc.)

Lancez les migrations :

   -> php bin/console doctrine:migrations:migrate

Chargez les fixtures (pour peupler la base de données de tests) :

   -> php bin/console doctrine:fixtures:load

## 🔥 Lancer les tests ##

Exécutez les tests et générez le rapport de couverture :

   -> php bin/phpunit --coverage-html coverage/

## 🗂️ Workflow Git ##

Feature Branching : Créez une branche par fonctionnalité/correction.

   -> git checkout -b feature/nom-de-la-feature

Commits : Ecrivez des messages clairs et concis.

Pull Requests : Proposez une PR pour chaque fonctionnalité/correction et faites-la relire.

## ✅ Règles de qualité ##

Respectez les standards PSR (PSR-1, PSR-12).

Vérifiez la couverture de code (> 70%).

Utilisez Codacy ou un outil similaire pour vérifier la qualité du code.

Utilisez le profiler Symfony pour vérifier les performances.

## 📚 Documentation ##

Voir docs/authentication.pdf pour la documentation technique de l'authentification.

Voir coverage/index.html pour la couverture des tests.

## 🛡️ Sécurité et bonnes pratiques ##

Ne commitez pas de données sensibles.

Ecrivez des tests pour toute nouvelle fonctionnalité.

## 🤝 Contact ##

Pour toute question, contactez l'équipe de développement via GitHub Issues.