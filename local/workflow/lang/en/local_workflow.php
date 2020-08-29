<?php
/**
 * workflow local plugin
 *
 * Language file.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 */

defined('MOODLE_INTERNAL') || die();

$string['ajax_error_email_entry'] = 'Erreur sur la saisie des emails.';
$string['ajax_error_format_file'] = 'Ce fichier n\'a pas un format reconnu.';
$string['ajax_error_no_csv_file'] = 'Ce fichier n\'est pas un fichier CSV.';
$string['ajax_error_no_group_entry'] = 'Un ou plusieurs groupes présent dans le fichier csv n\'existent pas.
Ils seront donc créés après la validation du formulaire.';
$string['ajax_error_role_entry'] = 'Erreur sur la saisie des rôles.';
$string['ajax_gaia_bind'] = 'La session GAIA a bien été liée.';
$string['ajax_gaia_unlink'] = 'La session GAIA a bien été dissociée.';
$string['ajax_no_encountered_problem'] = 'Le fichier csv ne contient aucune erreur.';

$string['banner_gabarit_title'] = 'Gabarit';
$string['banner_gabarit_description'] = 'Ce gabarit est un modèle sur lequel s’appuyer pour créer des nouveaux parcours.';
$string['banner_parcours_title'] = 'Parcours en construction';
$string['banner_parcours_description'] = 'A cette étape, le parcours est en construction dans lequel les concepteurs peuvent concevoir leur dispositif de formation.
A cette étape, il est nécessaire de les inscrire. Vous pouvez également renseigner les champs d’indexation si vous possédez déjà ces informations.';
$string['banner_session_preparation_title'] = 'Session en préparation';
$string['banner_session_preparation_description'] = 'A ce stade, la session n’est encore pas ouverte aux {$a->participant}s.
Les {$a->formateur}s inscrits à cette session peuvent préparer leur formation.
Avant d’ouvrir la session, il doivent inscrire les {$a->participant}s et renseigner les champs d’indexation indispensables.';
$string['banner_session_en_cours_title'] = 'Session en cours';
$string['banner_session_en_cours_description'] = 'A cette étape, la session est ouverte et les {$a->participant}s inscrits peuvent y accéder.
A la date de fin de formation, excepté certains types de sessions (collections « auto-formation », « réseau »,
« volet distant » et « espace collaboratif »), le ou les {$a->formateur}s recevront une notification
par messagerie pour attester la participation et clôturer cette session.';
$string['banner_session_archive_title'] = 'Session archivée';
$string['banner_session_archive_description'] = 'La formation est terminée. Les {$a}s peuvent toujours accéder
au contenu mais toutes les activités sont verrouillées en lecture.';

$string['course_management_link_announce_open_session'] = 'Annoncer l\'ouverture de la formation';
$string['course_management_link_announce_open_session_help'] = 'Annoncer l\'ouverture de la formation est désactivé
car les conditions ne sont pas remplies pour pouvoir y accéder. Veuillez vérifier que le parcours possède bien un forum des annonces.';
$string['course_management_link_attest_participation'] = 'Attester de la participation';
$string['course_management_link_attest_participation_help'] = 'L\'attestation est désactivée car les conditions ne sont pas remplies
pour pouvoir y accéder. Veuillez vérifier que le suivi d\'achèvement y est bien activé sur le parcours et qu\'il contient un bloc "Suivi de mes activités"';
$string['course_management_link_close_session'] = 'Clôturer la session';
$string['course_management_link_create_parcours'] = 'Créer un parcours';
$string['course_management_link_create_session'] = 'Créer une session';
$string['course_management_link_recreate_session'] = 'Créer une nouvelle session';
$string['course_management_link_discard'] = 'Mettre à la corbeille';
$string['course_management_link_duplicate_parcours'] = 'Dupliquer ce parcours';
$string['course_management_link_optimize_course'] = 'Optimiser ce parcours (Beta)';
$string['course_management_link_index_parcours'] = 'Indexer ce parcours';
$string['course_management_link_open_session'] = 'Ouvrir la session';
$string['course_management_link_open_session_help'] = 'L\'ouverture de session est désactivée car les conditions ne sont pas remplies
pour pouvoir y accéder. Veuillez vérifier les informations suivantes :
<ul>
    <li>La session contient des Formateurs.</li>
    <li>La session contient des Participants.</li>
    <li>Les champs requis dans l\'indexation sont correctement remplis.</li>
    <li>L\'indexation doit être remplie dans son intégralité.</li>
</ul>';
$string['course_management_link_open_session_auto_formation'] = 'Ouvrir la session en auto-inscription';
$string['course_management_link_open_session_auto_formation_help'] = 'L\'ouverture de session en auto-inscription est désactivée
car les conditions ne sont pas remplies pour pouvoir y accéder. Veuillez vérifier les informations suivantes :
<ul>
    <li>La session contient des Formateurs.</li>
    <li>Les champs requis dans l\'indexation sont correctement remplis.</li>
    <li>L\'indexation doit être remplie dans son intégralité.</li>
</ul>';
$string['course_management_link_reopen_session'] = 'Ré-ouvrir la session';
$string['course_management_link_restore_parcours'] = 'Restaurer le parcours';
$string['course_management_open_auto_formation'] = 'La session en auto-formation est maintenant ouverte.';
$string['course_management_open_auto_formation_failed'] = 'Un problème a été détecté à l\'ouverture de la session en auto-formation.';
$string['course_management_open_session'] = 'La session de formation est maintenant ouverte.';
$string['course_management_open_session_failed'] = 'Un problème a été détecté à l\'ouverture de la session.';
$string['course_management_session_archived'] = 'La session a bien été archivée.';
$string['course_management_session_unarchived'] = 'La session a bien été restaurée.';

$string['error_access_denied'] = 'Vous n\'avez pas les autorisations requises pour afficher cette page et/ou le mode édition n\'a pas été activé.';

$string['field_collection'] = 'Collection';
$string['field_collection_help'] = 'La collection permet de préciser le type de formation envisagée.
Les différentes actions qui vous seront proposées pour gérer le cycle de vie de votre parcours/formation dépendront du choix effectué sur la collection.
Par exemple, la collection « autoformation » ou « e-reseau » n\'impose pas de date de fin contrairement à une formation accompagnée par des {$a}s.';
$string['field_date_debut'] = 'Date de début';
$string['field_date_debut_help'] = 'Champs obligatoire pour ouvrir une session';
$string['field_date_fin'] = 'Date de fin';
$string['field_date_fin_help'] = 'Champs obligatoire si la collection est différente de Espace collaboratif et de Réseau';
$string['field_enrol_self_custom_welcome_message'] = 'Bonjour,
    
Votre inscription à la session "{$a}" a bien été prise en compte. La session apparaît désormais dans votre onglet "Se former".
    
ASTUCE : Il est possible de l\'ajouter dans vos favoris afin qu\'il apparaisse dès la page d\'accueil en cliquant sur la petite étoile.
    
Nous vous souhaitons une excellente formation.';
$string['field_email_enrol'] = 'Coller les adresses email des {$a}s';
$string['field_email_enrol_help'] = 'Coller les adresses email des Utilisateurs en utilisant les séparateurs "," ";" ou "|"';
$string['field_email_concepteur_enrol'] = 'Coller les adresses email des {$a->concepteur}s';
$string['field_email_concepteur_enrol_help'] = 'Coller les adresses email des Concepteurs en utilisant les séparateurs "," ";" ou "|"';
$string['field_email_formateur_enrol'] = 'Coller les adresses email des {$a->formateur}s';
$string['field_email_formateur_enrol_help'] = 'Coller les adresses email des Formateurs en utilisant les séparateurs "," ";" ou "|"';
$string['field_email_participant_enrol'] = 'Coller les adresses email des {$a->participant}s';
$string['field_email_participant_enrol_help'] = 'Coller les adresses email des Participants en utilisant les séparateurs "," ";" ou "|"';
$string['field_email_tuteur_enrol'] = 'Coller les adresses email des {$a->tuteur}s';
$string['field_email_tuteur_enrol_help'] = 'Coller les adresses email des Tuteurs en utilisant les séparateurs "," ";" ou "|"';
$string['field_temps_en_presence'] = 'Temps en présence';
$string['field_temps_en_presence_help'] = 'Champs obligatoire si la collection choisie est différente de "Espace Collaboratif et de "Réseau".';
$string['field_temps_a_distance'] = 'Temps à distance';
$string['field_temps_a_distance_help'] = 'Champs obligatoire si la collection choisie est différente de "Espace Collaboratif et de "Réseau".';

$string['inscription_manuelle'] = 'Inscription manuelle';

$string['label_actions_session'] = 'Actions possibles';
$string['label_collection'] = 'Choisir la collection';
$string['label_date'] = 'Dates de la formation';
$string['label_date_help'] = 'Dans le cas d’une formation accompagnée, la date de début comme la date de fin sont utilisées pour l\'attestation de présence générée à la fin de la formation';
$string['label_duree'] = 'Durées';
$string['label_duree_help'] = 'La durée a plusieurs impacts selon le type de formation.
Pour une formation accompagnée par un ou plusieurs {$a}s, la durée est utilisée pour générer les attestations de présence.
Pour une formation publiée dans l\'offre de formation de m@gistère, la durée permet aux personnes intéressées d\'évaluer le niveau d\'engagement';
$string['label_gaia'] = 'Gaia';
$string['label_indexation'] = 'Gérer l\'indexation';
$string['label_indexation_help'] = 'L\'identifiant du parcours permet de lier un parcours avec ses différentes sessions.
Il permet d\'assurer un suivi de la fréquentation et de l\'usage du parcours et de ses différentes sessions.';
$string['label_inscription_concepteur'] = 'Inscrire les {$a}s';
$string['label_inscription_formateur'] = 'Inscrire les {$a}s';
$string['label_inscription_formateur_help'] = 'Dans le cas d’une session de formation accompagnée, l\'inscription d’au moins un Formateur est nécessaire avant de pouvoir ouvrir la session.';
$string['label_inscription_participant'] = 'Inscrire les {$a}s';
$string['label_inscription_participant_help'] = 'L\'ouverture de la session n’est possible que si des Participants sont inscrits.
A ce stade, vous disposez de plusieurs méthodes pour inscrire les {$a}s accessible dans cette interface.';
$string['label_inscription_tuteur'] = 'Inscrire les {$a}s';
$string['label_publication'] = 'Publication';

$string['alert_close'] = 'Il est nécessaire d\'attester la participation des inscrits à votre formation avant de la clôturer car cela n\'est plus possible une fois la session archivée.';


$string['lead_time_hour_attendance'] = '{$a} en présence';
$string['lead_time_hour_remote'] = '{$a} à distance';

$string['link_createparcoursfromgabarit'] = 'Créer un parcours';
$string['link_discard'] = 'Mettre le parcours à la corbeille';
$string['link_duplicate'] = 'Dupliquer le parcours';
$string['link_gaia'] = 'Lier une session à Gaïa';
$string['link_indexation'] = 'Accéder à l\'indexation';
$string['link_publish'] = 'Publier sur l’offre de formation';
$string['link_publish_cancel'] = 'Retirer la publication';
$string['link_publish_update'] = 'Mettre à jour la publication';
$string['link_share'] = 'Publier sur l’offre de parcours';
$string['link_share_cancel'] = 'Retirer la publication';
$string['link_share_update'] = 'Mettre à jour la publication';

$string['link_publish_disable'] = 'Publier une session dans l\'offre de formation';
$string['link_publish_disable_help'] = 'La publication dans l\'offre de formation est désactivée car les conditions ne sont pas remplies
pour pouvoir y accéder. Veuillez vérifier les informations suivantes :
<ul>
    <li> La session doit être située dans une des sous-catégories de "Sessions en auto-inscription", elle même sous catégorie de "Session de formation".</li>
    <li> La session doit avoir été indexé correctement et être dans l\'état "Session ouverte" dans le cycle de vie.</li>
    <li> Les champs "Durée en présence" et "Durée à distance" doivent être complétés (même avec si la valeur est 0).</li>
    <li> Au niveau de cette indexation, il faut choisir une collection :
        <ul>
            <li>Autoformation : la session apparait dans "Autoformation"</li>
            <li>Toute autre collection : la session apparait dans "Parcours accompagné".</li>
        </ul>
    </li>
    <li> Une méthode d’inscription « Auto-inscription » doit être paramétrée (avec ou sans clé).</li>
    <li> Le nombre maximum d’inscrits (s\'il existe) paramétré dans la méthode d’inscription ne doit pas avoir été dépassé.</li>
    <li> La date du jour est située dans l’intervalle des dates paramétrées avec la méthode d’auto-inscription. Le parcours n’apparaîtra pas dans le cas contraire.</li>
    <li> Le rôle paramétré dans la méthode d’auto-inscription est « Participant ».</li>
    <li> Les valeurs des champs « Permettre les nouvelles inscriptions » et « Permettre l\'auto-inscription » doivent être paramétrées à « Oui ».</li>
</ul>
';

$string['link_local_publish'] = 'Publier sur l\'offre de formation locale';
$string['link_local_publish_disable'] = 'Publier une session dans l\'offre de formation locale';
$string['link_local_publish_disable_help'] = 'La publication dans l\'offre de formation locale est désactivée car les conditions ne sont pas remplies
pour pouvoir y accéder. Veuillez vérifier les informations suivantes :
<ul>
    <li> La session doit être située dans une des sous-catégories de "Session locale en auto-inscription", elle-même sous-catégorie de "Sessions de formation".</li>
    <li> La session doit avoir été indexé correctement et être dans l\'état "Session ouverte" dans le cycle de vie.</li>
    <li> Les champs "Durée en présence" et "Durée à distance" doivent être complétés (même avec si la valeur est 0).</li>
    <li> Une méthode d\'inscription « Auto-inscription » doit être paramétrée (avec ou sans clé).</li>
    <li> Le nombre maximum d\'inscrits (s\'il existe) paramétré dans la méthode d\'inscription ne doit pas avoir été dépassé.</li>
    <li> La date du jour est située dans l\'intervalle des dates paramétrées avec la méthode d\'auto-inscription. Le parcours n\'apparaîtra pas dans le cas contraire.</li>
    <li> Le rôle paramétré dans la méthode d\'auto-inscription est « Participant ».</li>
    <li> Les valeurs des champs « Permettre les nouvelles inscriptions » et « Permettre l\'auto-inscription » doivent être paramétrées à « Oui ».</li>
</ul>
';

$string['link_share_disable'] = 'Publier un parcours dans l\'offre de parcours';
$string['link_share_disable_help'] = 'La publication dans l\'offre de parcours est désactivée car les conditions ne sont pas remplies
pour pouvoir y accéder. Veuillez vérifier les informations suivantes :
<ul>
    <li> Le parcours doit être situé dans une des sous-catégories de "Parcours en démonstration".</li>
    <li> Le parcours doit avoir été indexé correctement et être consultable (non caché).</li>
    <li> Les champs "Durée en présence" et "Durée à distance" doivent être complétés (même avec si la valeur est 0).</li>
    <li> Une méthode d’inscription « Auto-inscription » doit être paramétrée (sans clé).</li>
    <li> Le nombre maximum d’inscrits (s\'il existe) paramétré dans la méthode d’inscription ne doit pas avoir été dépassé.</li>
    <li> La date du jour est située dans l’intervalle des dates paramétrées avec la méthode d’auto-inscription. Le parcours n’apparaîtra pas dans le cas contraire.</li>
    <li> Le rôle paramétré dans la méthode d’auto-inscription est « {$a} ».</li>
    <li> Les valeurs des champs « Permettre les nouvelles inscriptions » et « Permettre l\'auto-inscription » doivent être paramétrées à « Oui ».</li>
</ul>';

$string['local_workflow:addconcepteur'] = 'Ajouter des concepteurs sur un parcours à partir du cycle de travail';
$string['local_workflow:addformateur'] = 'Ajouter des formateurs sur un parcours à partir du cycle de travail';
$string['local_workflow:addparticipantmanual'] = 'Ajouter des participants manuellement sur un parcours à partir du cycle de travail';
$string['local_workflow:addparticipantgaia'] = 'Ajouter des participants avec le bloc gaia sur un parcours à partir du cycle de travail';
$string['local_workflow:addparticipantcsv'] = 'Ajouter des participants avec le bloc csv sur un parcours à partir du cycle de travail';
$string['local_workflow:addtuteur'] = 'Ajouter des tuteurs sur un parcours à partir du cycle de travail';
$string['local_workflow:confirmparticipation'] = 'Ajouter/Modifier une date de début et fin de parcours à partir du cycle de travail';
$string['local_workflow:closesession'] = 'Archiver une session à partir du cycle de travail';
$string['local_workflow:courseopening'] = 'Annoncer l\'ouverture d\'une session de formation à partir du cycle de travail';
$string['local_workflow:createsession'] = 'Créer une session à partir du cycle de travail';
$string['local_workflow:createparcours'] = 'Créer un parcours à partir d\'un gabarit au travers du cycle de travail';
$string['local_workflow:duplicate'] = 'Dupliquer un parcours à partir du cycle de travail';
$string['local_workflow:index'] = 'Voir le cycle de travail d\'un parcours';
$string['local_workflow:openselfenrolement'] = 'Activer l\'autoinscription sur une session à partir du cycle de travail';
$string['local_workflow:recreatesession'] = 'Créer une session à partir d\'une session au travers du cycle de travail';
$string['local_workflow:reopensession'] = 'Créer une session à partir d\'une session archivée au travers du cycle de travail';
$string['local_workflow:setcoursedates'] = 'Ajouter/Modifier une date de début et fin de parcours à partir du cycle de travail';
$string['local_workflow:setcourseduration'] = 'Ajouter/Modifier une date de début et fin de parcours à partir du cycle de travail';
$string['local_workflow:trash'] = 'Mettre un parcours à la corbeille à partir du cycle de travail';

$string['msg_local_publish_discard_success'] = 'La session locale a bien été dépubliée de l\'offre de formation';
$string['msg_local_publish_success'] = 'La session locale a bien été publiée dans l\'offre de formation';
$string['msg_publish_discard_success'] = 'La session a bien été dépubliée de l\'offre de formation';
$string['msg_publish_success'] = 'La session a bien été publiée dans l\'offre de formation';
$string['msg_share_discard_success'] = 'Le parcours a bien été dépublié de l\'offre de parcours';
$string['msg_share_success'] = 'Le parcours a bien été publié dans l\'offre de parcours';

$string['notification_indexation_created'] = 'L\'indexation a bien été créé.';
$string['notification_indexation_updated'] = 'L\'indexation a bien été modifiée.';

$string['notification_send_email_archive_session_subject'] = 'Archivage de la session "{$a}".';
$string['notification_send_email_archive_session_message'] = '<p>Bonjour {$a->userto},</p>
<p>Ce message vous informe que la session "{$a->sessionname}" a été clôturée et donc archivée.<br/>
Cependant, toutes les activités qui la composent sont accessibles, mais en lecture seulement.<br/>
Vous pouvez vous rendre directement sur cette session en cliquant sur <a href="{$a->linksession}">ce lien</a>.</p>';
$string['notification_send_email_open_session_subject'] = 'Ouverture de la session "{$a}".';
$string['notification_send_email_open_session_message'] = '<p>Bonjour {$a->userto},</p>
<p>Ce message concerne la session de formation m@gistère "{$a->sessionname}" dans laquelle vous êtes inscrit comme {$a->formateur}. <br/>
C’est aujourd\'hui la date d\'ouverture de la formation telle qu\'elle a été paramétrée. Avant que les {$a->participant}s puissent commencer leur formation, vous devez ouvrir la session.<br/>
Vous pouvez vous rendre directement sur l\'interface vous permettant de faire cette manipulation en cliquant sur <a href="{$a->linkworkflow}">ce lien</a>.
Veuillez vérifier toutefois que les conditions requises sont bien remplies pour ouvrir cette session. <br/>
Dans le cas contraire, des notifications vous informeront sur les données manquantes.<br/>
Si vous souhaitez annoncer l\'ouverture de cette même session, veuillez cliquer sur <a href="{$a->linkannonce}">ce lien</a></p>.';
$string['notification_send_email_close_session_subject'] = 'Fermeture de la session "{$a}".';
$string['notification_send_email_close_session_message'] = '<p>Bonjour {$a->userto},</p>
<p>Ce message vous informe que la session "{$a->sessionname}" est arrivée à échéance.<br/>
Vous pouvez vous rendre directement sur l\'interface vous permettant de fermer cette session en cliquant sur <a href="{$a->linkworkflow}">ce lien</a>.<br/>
Si vous souhaitez vérifier les attestations de chaque {$a->participant}, veuillez cliquer sur <a href="{$a->linkattest}">ce lien</a>.<br/>
A noter qu\'une nouvelle notification vous sera envoyée la semaine suivante si vous ne fermez pas cette session.</p>';

$string['pluginname'] = 'Cycle de vie du parcours';

$string['status_course_not_published'] = 'Parcours non publié';
$string['status_local_publish'] = 'Session locale publiée';
$string['status_local_publish_course'] = 'Session locale publiée sur l’offre de formation';
$string['status_publish'] = 'Session publiée';
$string['status_publish_course'] = 'Session publiée sur l’offre de formation';
$string['status_share'] = 'Parcours publié';
$string['status_share_course'] = 'Parcours publié sur l’offre de parcours';

$string['workflow:addformateur'] = 'Ajouter un ou plusieurs formateurs';
$string['workflow:addformateurcsv'] = 'Ajouter un ou plusieurs formateurs à partir d\'un fichier CSV';
$string['workflow:addformateurmanual'] = 'Ajouter un ou plusieurs formateurs à partir de la méthode manuelle';
$string['workflow:addparticipant'] = 'Ajouter un ou plusieurs participants';
$string['workflow:addparticipantcsv'] = 'Ajouter un ou plusieurs participants à partir d\'un fichier CSV';
$string['workflow:addparticipantmanual'] = 'Ajouter un ou plusieurs participants à partir de la méthode manuelle';
$string['workflow:addtuteur'] = 'Ajouter un ou plusieurs tuteurs';
$string['workflow:addtuteurcsv'] = 'Ajouter un ou plusieurs tuteurs à partir d\'un fichier CSV';
$string['workflow:addtuteurmanual'] = 'Ajouter un ou plusieurs tuteurs à partir de la méthode manuelle';
$string['workflow:createparcours'] = 'Créer un parcours';
$string['workflow:createsession'] = 'Créer une session';
$string['workflow:closecourse'] = 'Fermer une session';
$string['workflow:confirmparticipation'] = 'Attester de la participation';
$string['workflow:courseopening'] = 'Annoncer l\'ouverture de la formation';
$string['workflow:duplicate'] = 'Dupliquer un parcours';
$string['workflow:globalaccess'] = 'Autoriser l\'accés global au workflow';
$string['workflow:index'] = 'Indexer un parcours';
$string['workflow:openselfenrolement'] = 'Ouvrir une session en autoinscription';
$string['workflow:opensession'] = 'Ouvrir une session';
$string['workflow:optimize'] = 'Optimiser un parcours ou une session';
$string['workflow:optimizeconfig'] = 'Autoriser à accéder à la configuration du système d\'optimisation';
$string['workflow:recreatesession'] = 'Créer une session à partir d\'une autre';
$string['workflow:reopencourse'] = 'Ré-ouvrir une session';
$string['workflow:setcoursecollection'] = 'Ajouter une collection à la formation';
$string['workflow:setcoursedates'] = 'Ajouter les dates de formation';
$string['workflow:setcourseduration'] = 'Ajouter les durées de formation';
$string['workflow:setgaiasession'] = 'Ajouter une session GAIA à la formation';
$string['workflow:trash'] = 'Mettre un parcours à la corbeille';

$string['local_workflow_optimizer_settings_head'] = 'Configuration de l\'optimiseur';
$string['local_workflow_optimizer_settings_label'] = 'Workflow';
$string['local_workflow_optimizer_settings_config'] = 'Optimiseur';

$string['local_workflow_optimizer_settings_centralizeminsize_label'] = 'Taille min des fichiers centralisables';
$string['local_workflow_optimizer_settings_centralizeminsize_description'] = 'Taille minimale des fichiers centralisables en octets (1Mo=1048576 octets)';

$string['local_workflow_course_settings'] = 'Workflow';
$string['local_workflow_course_settings_label'] = 'Workflow';

$string['local_workflow_settings_config'] = 'Configuration du Workflow';
$string['local_workflow_settings_enrol_head'] = 'Correspondance des roles';
$string['local_workflow_settings_module_head'] = 'Plugins additionnels';

$string['settings_role_enrol_participant_label'] = 'Role Participant';
$string['settings_role_enrol_participant_description'] = '';
$string['settings_role_enrol_tuteur_label'] = 'Role Tuteur';
$string['settings_role_enrol_tuteur_description'] = '';
$string['settings_role_enrol_formateur_label'] = 'Role Formateur';
$string['settings_role_enrol_formateur_description'] = '';
$string['settings_role_enrol_concepteur_label'] = 'Role Concepteur';
$string['settings_role_enrol_concepteur_description'] = '';


$string['settings_module_gaia_label'] = 'Activer Gaia';
$string['settings_module_gaia_description'] = 'Active l\'accès aux inscriptions Gaia. Cet accès ne sera disponible que si le plugin local_gaia est installé!';
$string['settings_module_indexation_label'] = 'Activer l\'indexation';
$string['settings_module_indexation_description'] = 'Active l\'accès à l\'indexation. Cet accès ne sera disponible que si le plugin local_indexation est installé!';
$string['settings_module_coursehub_label'] = 'Activer la publication';
$string['settings_module_coursehub_description'] = 'Active l\'accès à la publication. Cet accès ne sera disponible que si le plugin local_coursehub est installé!';;
$string['settings_module_optimizer_label'] = 'Activer l\'optimiseur';
$string['settings_module_optimizer_description'] = 'Active l\'accès à l\'optimiseur. Cet accès ne sera disponible que si le plugin local_magisterelib est installé!';;

$string['alreadyenrolled'] = 'L\'utilisateur {$a} est déjà inscrit à ce cours.';
$string['description'] = 'Vous pouvez uploader votre fichier CSV contenant des adresses email ou des users moodle ici,
afin qu\'ils soient inscrits au cours "{$a}".';
$string['done'] = 'Inscription terminée.';
$string['enrolmentlog'] = 'Log d\'inscription:';
$string['enrolling'] = 'Inscriptions en cours...';
$string['enrollinguser'] = 'Enregistre de l\'utilisateur {$a}.';
$string['resultfiles'] = 'Résultat de vos inscriptions par CSV:';
$string['rolenotfound'] = 'Le rôle pour l\'adresse email {$a} n\'a pas été trouvée.';
$string['status'] = 'Inscription terminée. Résultat: {$a->success} inscrit ou déjà inscrit, {$a->failed} a échoué.';
$string['uploadcsv'] = 'Uploadez votre CSV ici:';


$string['createsession'] = "Créer la session";
$string['coursename'] = "Nom : *";
$string['courseshortname'] = "Nom abrégé : *";
$string['courseshortnamehelp'] = "Le nom abrégé doit être unique, indiquez y le groupe, l'année";
$string['subcategory'] = "Sous-Catégorie :";
$string['nosubcategory'] = "Pas de sous-catégorie";
$string['startdatecourse'] = "Date de début du parcours :";
$string['createsessiontitle'] = "Créer une session de formation";
$string['createsessiondesc'] = "Merci de renseigner les champs suivants afin de réaliser la création du parcours";

$string['mailadress'] = "Adresse email";
$string['statuslabel'] = "Statut";
$string['valid'] = "Valide";
$string['not valid'] = "Invalide";
$string['errors_found'] = '{$a} erreur(s) détectée(s)';
$string['ignored_found'] = '{$a} email(s) ignoré(s)';
$string['ac-normandie_not_allow'] = "Les adresses en @ac-normandie.fr ne sont pas encore acceptées sur m@gistère. Il convient d'utiliser les adresses @ac-caen.fr et @ac-rouen.fr pour les utilisateurs concernés.";
