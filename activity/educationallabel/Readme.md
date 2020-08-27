Activité étiquette pédagogique
============================
Le module étiquette pédagogique fonctionne de la même manière que le module étiquette natif de Moodle.
Sa spécificité est l'insertion automatique d'un titre et d'un fond de couleur.
Les titres existants et les couleurs associées sont les suivants :
• 'Présentation de votre formation': #9a5bb6 / #f5edf8
• 'Comment réussir votre formation ?': #fbf3e8 / #e07d00
• 'Activité à réaliser': #3597e2 / #e6f5fc
• 'Note aux formateurs': #40bd9b / #e9f8f5
• 'Important': #cb303e / #fcf0f2

Il est possible de modifier le titre de chaque étiquette pédagogique dans les paramètres de celle-ci.

Cette activité a été validée pour la version 3.5 de Moodle. Son fonctionnement est expérimental sur les versions ultérieures.

Installation
============
1. Vérifiez que vous avez la bonne version de Moodle. Une autre version de moodle peut entraîner des comportements indésirables.
2. Passez Moodle en 'Maintenance Mode' (https://docs.moodle.org/35/en/Maintenance_mode)
3. Copiez le dossier 'educationallabel' dans '/mod/'.
4. Retirez le mode maintenance.

Précision sur les capacités
==============
Les capacités suivantes permettent de contrôler l’affichage des labels pédagogiques correspondants :
• 'mod/educationallabel:presentationblockview': label 'Présentation de votre formation'
• 'mod/educationallabel:succedblockview': label 'Comment réussir votre formation?'
• 'mod/educationallabel:activityblockview': label 'Activité à réaliser'
• 'mod/educationallabel:noteblockview': label 'Note aux formateurs'
• 'mod/educationallabel:importantblockview': label 'Important'

Les rôles disposant de la capacité standard 'moodle/course:ignoreavailabilityrestrictions' peuvent voir toutes les
activités pédagogiques sans avoir les capacités requises.

Le renommage du rôle pour les labels 'Note aux formateurs' n’est par contre visible que si l’utilisateur connecté a
bien la capacité 'mod/educationallabel:noteblockview'.