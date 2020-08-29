Cycle de vie (workflow) 
============================
Ce document a pour objectif de guider l’utilisateur dans l’installation du plugin local « workflow ».

Pré-requis
============
Le fonctionnement du Workflow se base sur une arborescence de catégories de cours précise. Elle est indispensable au bon fonctionnement du plugin. Elle est définie comme ci-dessous :
- Gabarit
- Parcours de formation
    - Parcours en démonstration
- Session de formation
    - Session en auto-inscription
    - Session locale en auto-inscription
- Archive
- Corbeille
Les catégories n’ayant que leur nom comme identifiant, il est crucial que les noms soient respectés à la lettre.
Si une des catégories est manquante, le workflow retournera une erreur.

Installation
============
1. Vérifiez que vous avez la bonne version de Moodle. Une autre version de moodle peut entraîner des comportements indésirables.
2. Passez Moodle en 'Maintenance Mode' (https://docs.moodle.org/35/en/Maintenance_mode)
3. Copiez le dossier “workflow“ dans le dossier “/local/“.
4. Retirez le mode maintenance.
Pendant l’installation du plugin, vous devrez choisir des rôles qui seront utilisés pour les inscriptions lors des différentes étapes de la vie d’un parcours dans le workflow. Ces rôles sont obligatoires, le workflow ne peut pas fonctionner sans. Un message d’erreur conduira l’utilisateur sur la page de configuration si cette dernière n’est pas correctement remplie.

Précision sur les capacités
==============
1.	Accès au plugin
- 'local/workflow:globalaccess'
2.	Accès à la configuration du plugin
- 'local/workflow:config'
- 'local/workflow:optimizeconfig'

4.3	Action sur les parcours

'local/workflow:duplicate'
'local/workflow:createparcours'
'local/workflow:createsession'
'local/workflow:recreatesession'
'local/workflow:opensession'
'local/workflow:closecourse'
'local/workflow:reopencourse'
'local/workflow:courseopening'
'local/workflow:openselfenrolement'
'local/workflow:trash'
4.4	Gestion des inscriptions des utilisateurs

'local/workflow:addformateur'
'local/workflow:addtuteur'
'local/workflow:addparticipant'
'local/workflow:addparticipantmanual'
'local/workflow:addparticipantcsv'
'local/workflow:addformateurmanual'
'local/workflow:addformateurcsv'
'local/workflow:addtuteurmanual'
'local/workflow:addtuteurcsv'

4.5	Plugin Optimiseur

'local/workflow:optimize'
4.6	Configuration du parcours

'local/workflow:setcoursedates'
4.7	Configuration de l’indexation du parcours

'local/workflow:index'

Dépendances et fonctionnalités supplémentaires
============
Le plugin Workflow peut intégrer d’autres plugins. Ces derniers doivent être installés et fonctionnels avec leurs propres dépendances.

- Intégration de Gaia
Si le plugin local_gaia est présent et fonctionnel, et qu’il est actif dans la configuration du workflow, il pourra être accessible directement dans le workflow.

- Intégration de l’indexation
Si le plugin local_indexation est présent et fonctionnel, et qu’il est actif dans la configuration du workflow, il pourra être accessible directement dans le workflow.

- Intégration de l’optimiseur
Si le plugin local_magisterelib est présent, et qu’il est actif dans la configuration du workflow, il pourra être accessible directement dans le workflow.

- Intégration du hub de publication
Si le plugin local_coursehub est présent et fonctionnel, et qu’il est actif dans la configuration du workflow, il pourra être accessible directement dans le workflow pour permettre la publication des parcours et session.
