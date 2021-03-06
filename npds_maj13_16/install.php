<?php
/************************************************************************/
/* DUNE by NPDS                                                         */
/* ===========================                                          */
/*                                                                      */
/* Mise à jour de NPDS REvolution 13 vers REvolution 16                 */
/*                                                                      */
/* NPDS Copyright (c) 2001-2020 by Philippe Brunier                     */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 3 of the License.       */
/************************************************************************/
if (!function_exists("Mysql_Connexion"))
   include ("mainfile.php");
   include("install/libraries/lib-inc.php");
   include("lib/archive.php");

// Fonctions de l'interface    
function mess_welcome($lang) {
   // include("install/libraries/lib-inc.php")
   echo '
   <div id="welcome" class="jumbotron my-4 mx-2">'; 
   $id_fr = fopen("install/languages/welcome-$lang.txt", "r");
   fpassthru($id_fr);
   echo '
      <div class="align-left mt-4">
         <form name="update_rev13" method="post" action="install.php">
            <input type="hidden" name="op" value="update" />
            <input type="submit" class="btn btn-primary btn-lg" value="';
    if ($lang=="french") echo "Etape suivante"; else echo "Next stage";
   echo '" />
         </form>
      </div>
   </div>';
}

function mess_update($lang) {
   echo '
   <div id="maj_deb" class="jumbotron my-4 mx-2">';
   $id_fr = fopen("install/languages/update-$lang.txt", "r");
   fpassthru($id_fr);
   echo '
      <div class="align-left mt-4">
         <form name="update_rev13" method="post" action="install.php">
            <input type="hidden" name="op" value="finish" />
            <input type="submit" class="btn btn-primary btn-lg" value="';
   if ($lang=="french") echo "Terminer la mise &agrave; jour"; else echo "End the update";
   echo '" />
         </form>
         <hr />
         <span class="">version php : '.verif_php().'</span>
      </div>
   </div>';
}

function mess_finish($lang) {
   echo '<div id="maj_fin" class="jumbotron my-4 mx-2">';
    $id_fr = fopen("install/languages/finish-$lang.txt", "r");
    fpassthru($id_fr);
   echo "</div><br />";
}

// Fonctions operationnelles
function maj_db() {
   global $NPDS_Prefix, $dbname;
   // hardcoded liste original des tables d'une version 13 [64 tables]
   $table13 = array("access", "adminblock", "appli_log", "authors", "autonews", "banner", "bannerclient", "bannerfinish", "catagories", "chatbox", "compatsujet", "config", "counter", "downloads", "ephem", "faqanswer", "faqcategories", "forums", "forumtopics", "forum_attachments", "forum_read", "groupes", "headlines", "lblocks", "links_categories", "links_editorials", "links_links", "links_modrequest", "links_newlink", "links_subcategories", "lnl_body", "lnl_head_foot", "lnl_outside_users", "lnl_send", "mainblock", "metalang", "modules", "optimy", "poll_data", "poll_desc", "posts", "priv_msgs", "publisujet", "queue", "rblocks", "referer", "related", "reviews", "reviews_add", "reviews_main", "rubriques", "seccont", "seccont_tempo", "sections", "session", "sform", "stories", "stories_cat", "subscribe", "topics", "users", "users_extend", "users_status", "wspad");

   // # mise à jour structure
//==> BASE : mise à jour du CHARSET et collation
   $sql="ALTER DATABASE '".$dbname."' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;";
   $result = sql_query($sql);
   echo '
   <div class="mx-2 my-3">
      <h4>
            <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#labase"><i class="toggle-icon fa fa-caret-down"></i></a>BASE : mise à jour du CHARSET et collation<span class="badge badge-pill badge-success float-right ml-2">1</span>
      </h4>
      <div id="labase" class="ml-4 collapse"><small class="text-success"><strong>'.$dbname.'</strong> : mise à jour du CHARSET et collation<i class="fa fa-check text-success ml-2"></i></small></div>
   </div>';
//<==

//==> TOUTES TABLES (13) : mise à jour du CHARSET utf8mb4 et collation utf8mb4_unicode_ci par defaut des tables ce qui ne signifie pas la conversion des données existante !!... must be 64 résultats
   $aff=''; $nbr=0;
   foreach($table13 as $v){
      $sql="ALTER TABLE ".$NPDS_Prefix.$v." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; //utf8mb4 pour la 16.2
      $result = sql_query($sql);
      $aff.= '<small class="text-success"><strong>'.$NPDS_Prefix.$v.'</strong> : mise à jour du CHARSET utf8mb4 et collation utf8mb4_unicode_ci</small><i class="fa fa-check text-success ml-2" title="ALTER TABLE '.$NPDS_Prefix.$v.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" data-toggle="tooltip"></i><br />';
      $nbr++;
   }
      echo '
      <div class="mx-2 mb-3">
         <h4>
            <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#lestables"><i class="toggle-icon fa fa-caret-down"></i></a>TABLES : mise à jour du CHARSET et collation<span class="badge badge-pill badge-success float-right ml-2">'.$nbr.'</span>
         </h4>
         <div id="lestables" class="ml-4 collapse">'.$aff.'</div>
      </div>';
//<==

//==>TABLES : création
   $aff=''; $nbr=0;
// droits : création 
   $t='droits';
   $sql="CREATE TABLE IF NOT EXISTS ".$NPDS_Prefix.$t." (
  d_aut_aid varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'id administrateur',
  d_fon_fid tinyint(3) unsigned NOT NULL COMMENT 'id fonction',
  d_droits varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dune_proto'";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.'</strong> : création</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// fonctions : création 
   $t='fonctions';
   $sql="CREATE TABLE IF NOT EXISTS ".$NPDS_Prefix.$t." (
  fid mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id unique auto incrémenté',
  fnom varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  fdroits1 tinyint(3) unsigned NOT NULL,
  fdroits1_descr varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  finterface tinyint(1) unsigned NOT NULL COMMENT '1 ou 0 : la fonction dispose ou non d''une interface',
  fetat tinyint(1) NOT NULL COMMENT '0 ou 1  9 : non active ou installé, installé',
  fretour varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'utiliser par les fonctions de categorie Alerte : nombre, ou ',
  fretour_h varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  fnom_affich varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  ficone varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  furlscript varchar(4000) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'attribut et contenu  de balise A : href=\"xxx\", onclick=\"xxx\"  etc',
  fcategorie tinyint(3) unsigned NOT NULL,
  fcategorie_nom varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  fordre tinyint(2) unsigned NOT NULL,
  PRIMARY KEY (fid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4  COLLATE=utf8mb4_unicode_ci COMMENT='Dune_proto'";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.'</strong> : création</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// fonctions : remplissage table (!! à voir le comportement de l'auto-increment !!)
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(1, 'edito', 1, '', 1, 1, '', '', 'Edito', 'edito', 'href=\"admin.php?op=Edito\"', 1, 'Contenu', 0);";
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(2, 'adminStory', 2, '', 1, 1, '', '', 'Nouvel Article', 'postnew', 'href=\"admin.php?op=adminStory\"', 1, 'Contenu', 1);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(3, 'sections', 3, '', 1, 1, '', '', 'Rubriques', 'sections', 'href=\"admin.php?op=sections\"', 1, 'Contenu', 2);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(4, 'topicsmanager', 4, '', 1, 1, '', '', 'Gestion des Sujets', 'topicsman', 'href=\"admin.php?op=topicsmanager\"', 1, 'Contenu', 3);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(5, 'links', 5, '', 1, 1, '', '', 'Liens Web', 'links', 'href=\"admin.php?op=links\"', 1, 'Contenu', 5);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(6, 'FaqAdmin', 6, '', 1, 1, '1', '', 'FAQ', 'faq', 'href=\"admin.php?op=FaqAdmin\"', 1, 'Contenu', 6);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(7, 'Ephemerids', 7, '', 1, 1, '1', '', 'Ephémérides', 'ephem', 'href=\"admin.php?op=Ephemerids\"', 1, 'Contenu', 7);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(8, 'HeadlinesAdmin', 8, '', 1, 1, '', '', 'News externes', 'headlines', 'href=\"admin.php?op=HeadlinesAdmin\"', 1, 'Contenu', 8);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(9, 'DownloadAdmin', 9, '', 1, 1, '', '', 'Téléchargements', 'download', 'href=\"admin.php?op=DownloadAdmin\"', 1, 'Contenu', 9);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(10, 'mod_users', 10, '', 1, 1, '', '', 'Utilisateurs', 'users', 'href=\"admin.php?op=mod_users\"', 2, 'Utilisateurs', 1);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(11, 'groupes', 11, '', 1, 1, '', '', 'Groupes', 'groupes', 'href=\"admin.php?op=groupes\"', 2, 'Utilisateurs', 2);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(12, 'mod_authors', 12, '', 1, 1, '', '', 'Administrateurs', 'authors', 'href=\"admin.php?op=mod_authors\"', 2, 'Utilisateurs', 3);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(13, 'MaintForumAdmin', 13, '', 1, 1, '', '', 'Maintenance Forums', 'forum', 'href=\"admin.php?op=MaintForumAdmin\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(14, 'ForumConfigAdmin', 14, '', 1, 1, '', '', 'Configuration Forums', 'forum', 'href=\"admin.php?op=ForumConfigAdmin\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(15, 'ForumAdmin', 15, '', 1, 1, '', '', 'Edition Forums', 'forum', 'href=\"admin.php?op=ForumAdmin\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(16, 'lnl', 16, '', 1, 1, '', '', 'Lettre D''info', 'lnl', 'href=\"admin.php?op=lnl\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(17, 'email_user', 17, '', 1, 1, '', '', 'Message Interne', 'email_user', 'href=\"admin.php?op=email_user\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(18, 'BannersAdmin', 18, '', 1, 1, '', '', 'Bannières', 'banner', 'href=\"admin.php?op=BannersAdmin\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(19, 'create', 19, '', 1, 1, '', '', 'Sondages', 'newpoll', 'href=\"admin.php?op=create\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(20, 'reviews', 20, '', 1, 1, '', '', 'Critiques', 'reviews', 'href=\"admin.php?op=reviews\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(21, 'hreferer', 21, '', 1, 1, '', '', 'Sites Référents', 'referer', 'href=\"admin.php?op=hreferer\"', 3, 'Communication', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(22, 'blocks', 22, '', 1, 1, '', '', 'Blocs', 'block', 'href=\"admin.php?op=blocks\"', 4, 'Interface', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(23, 'mblock', 23, '', 1, 1, '', '', 'Bloc Principal', 'blockmain', 'href=\"admin.php?op=mblock\"', 4, 'Interface', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(24, 'ablock', 24, '', 1, 1, '', '', 'Bloc Administration', 'blockadm', 'href=\"admin.php?op=ablock\"', 4, 'Interface', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(25, 'Configure', 25, '', 1, 1, '', '', 'Préférences', 'preferences', 'href=\"admin.php?op=Configure\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(26, 'ConfigFiles', 26, '', 1, 1, '', '', 'Fichiers configurations', 'preferences', 'href=\"admin.php?op=ConfigFiles\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(27, 'FileManager', 27, '', 1, 1, '', '', 'Gestionnaire Fichiers', 'filemanager', 'href=\"admin.php?op=FileManager\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(28, 'supercache', 28, '', 1, 1, '', '', 'SuperCache', 'overload', 'href=\"admin.php?op=supercache\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(29, 'OptimySQL', 29, '', 1, 1, '', '', 'OptimySQL', 'optimysql', 'href=\"admin.php?op=OptimySQL\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(30, 'SavemySQL', 30, '', 1, 1, '', '', 'SavemySQL', 'savemysql', 'href=\"admin.php?op=SavemySQL\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(31, 'MetaTagAdmin', 31, '', 1, 1, '', '', 'MétaTAGs', 'metatags', 'href=\"admin.php?op=MetaTagAdmin\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(32, 'MetaLangAdmin', 32, '', 1, 1, '', '', 'META-LANG', 'metalang', 'href=\"admin.php?op=Meta-LangAdmin\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(33, 'setban', 33, '', 1, 1, '', '', 'IP', 'ipban', 'href=\"admin.php?op=Extend-Admin-SubModule&amp;ModPath=ipban&amp;ModStart=setban\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(34, 'session_log', 34, '', 1, 1, '', '', 'Logs', 'logs', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=session-log&ModStart=session-log\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(35, 'reviews', 20, '', 1, 1, '0', 'Critique en atttente de validation.', 'Critiques', 'reviews', 'href=\"admin.php?op=reviews\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(36, 'mes_npds_versus', 36, '', 1, 1, '', 'Une nouvelle version est disponible ! Cliquez pour acc&#xE9;der &#xE0; la zone de t&#xE9;l&#xE9;chargement de NPDS.', '', 'message_npds', '', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(37, 'autoStory', 2, '', 1, 1, '1', 'articles sont programm&eacute;s pour la publication.', 'Auto-Articles', 'autonews', 'href=\"admin.php?op=autoStory\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(38, 'submissions', 2, '', 1, 1, '10', 'Article en attente de validation !', 'Articles', 'submissions', 'href=\"admin.php?op=submissions\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(39, 'hreferer_al', 21, '', 1, 1, '!!!', 'Limite des r&#xE9;f&#xE9;rants atteinte : pensez &#xE0; archiver vos r&#xE9;f&#xE9;rants.', 'Sites Référents', 'referer', 'href=\"admin.php?op=hreferer\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(40, 'abla', 40, '', 1, 1, '', '', 'Blackboard', 'abla', 'href=\"admin.php?op=abla\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(41, 'newlink', 5, '', 1, 1, '1', 'Lien &#xE0; valider', 'Lien', 'links', 'href=\"admin.php?op=links\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(42, 'brokenlink', 5, '', 1, 1, '6', 'Lien rompu &#xE0; valider', 'Lien rompu', 'links', 'href=\"admin.php?op=LinksListBrokenLinks\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(43, 'archive-stories', 43, '', 1, 1, '', '', 'Archives articles', 'archive-stories', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=archive-stories&ModStart=admin/archive-stories_set\"', 1, 'Contenu', 4);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(44, 'mod_users', 10, '', 1, 1, '', 'Utilisateur en attente de validation !', 'Utilisateurs', 'users', 'href=\"admin.php?op=nonallowed_users\"', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(49, 'npds_twi', 49, '', 1, 1, '', '', 'Npds_Twitter', 'npds_twi', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=npds_twi&ModStart=admin/npds_twi_set\"', 6, 'Modules', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(51, 'modules', 51, '', 1, 1, '', '', 'Gestion modules', 'modules', 'href=\"admin.php?op=modules\"', 5, 'Système', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(59, 'mes_npds_2', 0, '', 1, 1, '', 'Ceci est une note d''information provenant de NPDS.', '', 'flag_red', '', 9, 'Alerte', 0);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(74, 'reseaux-sociaux', 74, '', 1, 1, '', '', 'Réseaux sociaux', 'reseaux-sociaux', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=reseaux-sociaux&ModStart=admin/reseaux-sociaux_set\"', 2, 'Utilisateurs', 4);";
$result = sql_query($sql);
$sql="INSERT INTO ".$NPDS_Prefix.$t." (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(75, 'geoloc', 75, '', 1, 1, '', '', 'geoloc', 'geoloc', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=geoloc&ModStart=admin/geoloc_set\"', 6, 'Modules', 0);";
$result = sql_query($sql);

// ip_loc : création 
   $t='ip_loc';
   $sql="CREATE TABLE IF NOT EXISTS ".$NPDS_Prefix.$t." (
  ip_id smallint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_long float NOT NULL DEFAULT '0',
  ip_lat float NOT NULL DEFAULT '0',
  ip_visi_pag varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  ip_visite mediumint(9) UNSIGNED NOT NULL DEFAULT '0',
  ip_ip varchar(54) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  ip_country varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  ip_code_country varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  ip_city varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (ip_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.'</strong> : création</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   echo '
      <div class="mx-2 mb-3">
         <h4>
            <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#creatables"><i class="toggle-icon fa fa-caret-down"></i></a>TABLES : création<span class="badge badge-pill badge-success float-right ml-2">'.$nbr.'</span>
         </h4>
         <div id="creatables" class="ml-4 collapse">'.$aff.'</div>
      </div>';
//<==

//==> COLONNES de type char varchar text ...(249 résultats) : charset et collation <== A REVOIR !!!



   $aff='';$nbr=0;
   $sql="SELECT * FROM information_schema.columns
WHERE table_schema = '".$dbname."' AND data_type REGEXP 'char|text' 
ORDER BY table_name,ordinal_position";
   $resultcol = sql_query($sql);
   while($row = sql_fetch_row($resultcol)) {
      $nomtable= $row[2];
      $nomcol = $row[3];
//      $coltype= $row[7];
      $coltype=$row[15];//
/*

$sql="ALTER TABLE '".$nomtable."' MODIFY '".$nomcol."' '".$coltype."' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

*/


      $sql="ALTER TABLE '".$nomtable."' CHANGE '".$nomcol."' '".$nomcol."' '".$coltype."' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
      sql_query($sql);
      $aff.= '<small class="text-success"><strong>'.$nomtable.' : '.$nomcol.'</strong> : mise à jour du CHARSET utf8mb4 et collation utf8mb4_unicode_ci</small><i class="fa fa-check text-success ml-2"></i><br />';
      $nbr++;
   }
   echo '
   <div class="mx-2 mb-3">
      <h4>
         <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#lescolonnes"><i class="toggle-icon fa fa-caret-down"></i></a>COLONNES : mise à jour du CHARSET et collation<span class="badge badge-pill badge-success float-right ml-2">'.$nbr.'</span>
      </h4>
      <div id="lescolonnes" class="ml-4 collapse">'.$aff.'</div>
   </div>';
//<==

//==> COLONNES suppressions
   $aff='';$nbr=0;
   // authors : suppression des anciens droits ce qui signifie qu'il faudra réattribuer manuellement
   $colauthorssup=array('radminarticle','radmintopic','radminleft','radminright','radminuser','radminmain','radminsurvey','radminsection','radminlink','radminephem','radminhead','radminfaq','radmindownload','radminforum','radminreviews','radminsdv','radminlnl');
   $sql="ALTER TABLE ".$NPDS_Prefix."authors DROP COLUMN radminarticle, DROP COLUMN radmintopic, DROP COLUMN radminleft, DROP COLUMN radminright, DROP COLUMN radminuser, DROP COLUMN radminmain, DROP COLUMN radminsurvey, DROP COLUMN radminsection, DROP COLUMN radminlink, DROP COLUMN radminephem, DROP COLUMN radminhead, DROP COLUMN radminfaq, DROP COLUMN radmindownload, DROP COLUMN radminforum, DROP COLUMN radminreviews, DROP COLUMN radminsdv, DROP COLUMN radminlnl";
   $result = sql_query($sql);
   foreach($colauthorssup as $v) {
      $aff.= '<small class="text-success"><strong>authors : '.$v.'</strong> : supprimer</small><i class="fa fa-check text-success ml-2"></i><br />';
   }
   // seccont: suppression de colonnes inutiles
   $colseccontssup=array('crit1','crit2','crit3','crit4','crit5','crit6','crit7','crit8','crit9','crit10','crit11','crit12','crit13','crit14','crit15','crit16','crit17','crit18','crit19','crit20');
   $sql="ALTER TABLE ".$NPDS_Prefix."seccont DROP COLUMN crit1, DROP COLUMN crit2, DROP COLUMN crit3, DROP COLUMN crit4, DROP COLUMN crit5, DROP COLUMN crit6, DROP COLUMN crit7, DROP COLUMN crit8, DROP COLUMN crit9, DROP COLUMN crit10, DROP COLUMN crit11, DROP COLUMN crit12, DROP COLUMN crit13, DROP COLUMN crit14, DROP COLUMN crit15, DROP COLUMN crit16, DROP COLUMN crit17, DROP COLUMN crit18, DROP COLUMN crit19, DROP COLUMN crit20";
   $result = sql_query($sql);
   foreach($colseccontssup as $v) {
      $aff.= '<small class="text-success"><strong>seccont : '.$v.'</strong> : supprimer</small><i class="fa fa-check text-success ml-2"></i><br />';
   }
   // seccont_tempo: suppression de colonnes inutiles
   $sql="ALTER TABLE ".$NPDS_Prefix."seccont_tempo DROP COLUMN crit1, DROP COLUMN crit2, DROP COLUMN crit3, DROP COLUMN crit4, DROP COLUMN crit5, DROP COLUMN crit6, DROP COLUMN crit7, DROP COLUMN crit8, DROP COLUMN crit9, DROP COLUMN crit10, DROP COLUMN crit11, DROP COLUMN crit12, DROP COLUMN crit13, DROP COLUMN crit14, DROP COLUMN crit15, DROP COLUMN crit16, DROP COLUMN crit17, DROP COLUMN crit18, DROP COLUMN crit19, DROP COLUMN crit20";
   $result = sql_query($sql);
   foreach($colseccontssup as $v) {
      $aff.= '<small class="text-success"><strong>seccont_tempo : '.$v.'</strong> : supprimer</small><i class="fa fa-check text-success ml-2"></i><br />';
   }
   // users : suppression des "anciens réseaux sociaux"
   $coluserssup=array('user_icq','user_aim','user_yim','user_msnm');
   $sql="ALTER TABLE ".$NPDS_Prefix."users DROP COLUMN user_icq, DROP COLUMN user_aim, DROP COLUMN user_yim, DROP COLUMN user_msnm";
   $result = sql_query($sql);
   foreach($coluserssup as $v) {
      $aff.= '<small class="text-success"><strong>users : '.$v.'</strong> : supprimer</small><i class="fa fa-check text-success ml-2"></i><br />';
   }
   echo '
   <div class="mx-2 mb-3">
      <h4>
         <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#supcolonnes"><i class="toggle-icon fa fa-caret-down"></i></a>COLONNES : suppressions <span class="badge badge-pill badge-danger float-right ml-2">XXX</span>
      </h4>
      <div id="supcolonnes" class="ml-4 collapse">'.$aff.'</div>
   </div>';
//<==

//==> COLONNES modifications
   $aff='';$nbr=0;
// access : modif taille 
   $t='access'; $c='access_title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(80)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar(80))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// adminblock : modif taille 
   $t='adminblock'; $c='title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(1000)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar(1000))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// appli_log : modif taille IPV6 support
   $t='appli_log'; $c='al_ip';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(54)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar(54) IPV6 support)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// appli_log : modif valeur par défaut compat mysql 5.7
   $c='al_date';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>appli_log : al_date</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// authors : modif taille //à voir pour aid et name
   $t='authors'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// authors : modif taille
   $c='email'; 
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// banner : modif taille
   $t='banner'; $c='imageurl';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// banner : modif taille
   $c='clickurl';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// bannerclient : modif taille
   $t='bannerclient'; $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// chatbox : modif taille IPV6 support
   $t='chatbox'; $c='ip';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(54)";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("54"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// downloads : ddate modif valeur par defaut compat mysql 5.7
   $t='downloads'; $c='ddate';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATE NOT NULL DEFAULT '1000-01-01'";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// downloads : dfilesize no need signed value ...
   $c='dfilesize';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." BIGINT(15) UNSIGNED NULL DEFAULT NULL";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer attribut (signed non nécessaire)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// downloads : change type of value ...gestion des permissions pour les groupes ...
   $c='perms';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(480)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer type (varchar(480))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// downloads : modif taille
   $c='durl';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// headlines : modif taille
   $t='headlines'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// headlines : modif taille
   $c='headlinesurl';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// lblocks : modif taille
   $t='lblocks'; $c='title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(1000)";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("1000"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// links_editorials : modif valeur par defaut de editorialtimestamp compat mysql 5.7
   $t='links_editorials'; $c='editorialtimestamp';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.=  '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// links_links : modif taille
   $t='links_links'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// links_modrequest : modif taille
   $t='links_modrequest'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// links_newlink : modif taille
   $t='links_newlink'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// links_newlink : modif taille
   $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// lnl_outside_users : modif valeur par defaut de date compat mysql 5.7
   $t='lnl_outside_users'; $c='date';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// lnl_outside_users : modif taille
   $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// lnl_send : modif valeur par defaut de date compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."lnl_send CHANGE date date DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>lnl_send : date</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// mainblock : modif taille
   $t='mainblock'; $c='title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(1000)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("1000"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// posts : modif taille IPV6 support
   $t='posts'; $c='poster_ip';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(54)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar(54) IPV6 support)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// queue : modif valeur par defaut de 'timestamp' compat mysql 5.7
   $t='queue'; $c='timestamp';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// rblocks : modif taille
   $t='rblocks'; $c='title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(1000)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("1000"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// referer : modif taille
   $t='referer'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
   
// related : modif taille
   $t='related'; $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// reviews : modif valeur par defaut de 'date' compat mysql 5.7
   $t='reviews'; $c='date';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." DATE NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (compat mysql 5.7)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// reviews : modif taille
   $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// reviews : modif taille
   $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// reviews_add : modif taille
   $t='reviews_add'; $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// reviews_add : modif taille
   $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// reviews_main : modif valeur par defaut de 'title' the null default value for text columns is empty string '' et pas NULL
   $t='reviews_main'; $c='title';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." CHANGE ".$c." ".$c." text DEFAULT ''";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer valeur par défaut (the null default value for text columns is empty string \'\')</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// session : modif taille username support IPV6 (à voir si il faut rajouter NOT NULL)
   $t='session'; $c='username';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(54)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("54") IPV6 support)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// session : modif taille host_addr support IPV6 (à voir si il faut rajouter NOT NULL)
   $c='host_addr';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(54)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("54") IPV6 support)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

// users : modif taille
   $t='users'; $c='email';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<br />'.$t.'<br /><small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// users : modif taille
   $c='femail';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(254)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("254"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// users : modif taille
   $c='url';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(320)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer taille (varchar("320"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// users : type de user_theme (pour skin implémentation)
   $c='user_theme';
   $sql="ALTER TABLE ".$NPDS_Prefix.$t." MODIFY ".$c." varchar(255)";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : modifer type (varchar("255"))</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;
// users : mise à jour du thème (pour tous les utilisateurs)...
   $c='theme';
   $sql="UPDATE ".$NPDS_Prefix.$t." SET ".$c."=\'npds-boost_sk+cosmo\' WHERE 1=1 ;";
   $result = sql_query($sql);
   $aff.= '<small class="text-success"><strong>'.$t.' : '.$c.'</strong> : mise à jour du thème (pour tous les utilisateurs)</small><i class="fa fa-check text-success ml-2"></i><br />';
   $nbr++;

   echo '
   <div class="mx-2 mb-3">
      <h4>
         <a href="#" class="arrow-toggle text-primary mr-2" data-toggle="collapse" data-target="#modcolonnes"><i class="toggle-icon fa fa-caret-down"></i></a>COLONNES : modifications <span class="badge badge-pill badge-success float-right ml-2">'.$nbr.'</span>
      </h4>
      <div id="modcolonnes" class="ml-4 collapse">'.$aff.'</div>
   </div>';

/*

/// on progress and TO DO
   // counter à faire

   // links_categories : modif longueur title
   $sql="ALTER TABLE ".$NPDS_Prefix."links_categories MODIFY title varchar(255)";
   $result = sql_query($sql);


//==> to do (on chechr et effeace les metamots du core on touche pas au autres)
   // Mise à jour de la table metalang
   $sql="UPDATE ".$NPDS_Prefix."metalang SET description='[french]Fabrique un bloc R (droite) ou L (gauche) en s\'appuyant sur l\'ID (voir gestionnaire de blocs) pour incorporation / syntaxe : blocID(R1) ou blocID(L2)[/french]' WHERE def='blocID'";
   $result = sql_query($sql);
   
   $sql="DELETE FROM ".$NPDS_Prefix."metalang WHERE def='espace_groupe'";
   $result = sql_query($sql);
   $sql='INSERT INTO '.$NPDS_Prefix.'metalang VALUES (\'espace_groupe\', \'function MM_espace_groupe($gr, $t_gr, $i_gr) {\\r\\n$gr = arg_filter($gr);\\r\\n$t_gr = arg_filter($t_gr);\\r\\n$i_gr = arg_filter($i_gr);\\r\\n\\r\\nreturn (fab_espace_groupe($gr, $t_gr, $i_gr));\\r\\n}\', \'meta\', \'-\', NULL, \'[french]Fabrique un WorkSpace / syntaxe : espace_groupe(groupe_id, aff_name_groupe, aff_img_groupe) ou groupe_id est l\\\'ID du groupe - aff_name_groupe(0 ou 1) permet d\\\'afficher le nom du groupe - aff_img_groupe(0 ou 1) permet d\\\'afficher l\\\'image associ&eacute;e au groupe.[/french]\', \'1\');';
   $result = sql_query($sql);

   // Mise à jour des avatars
   $result = sql_query("SELECT uid, user_avatar from ".$NPDS_Prefix."users order by uid DESC");
   while($temp = sql_fetch_assoc($result) ) {
      if (substr($temp['user_avatar'],0,1)=="/") {
         sql_query("UPDATE ".$NPDS_Prefix."users set user_avatar='".substr($temp['user_avatar'],1)."' where uid='".$temp['uid']."'");
      }
   }
*/

echo '
<script type="text/javascript" src="lib/js/jquery.min.js"></script>
<script type="text/javascript" src="lib/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" src="lib/js/npds_adapt.js"></script>
';
}

// ==> export db
// à executer en premier
   function PrepareString($a_string = '') {
      $search       = array('\\','\'',"\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
      $replace      = array('\\\\','\\\'','\0', '\n', '\r', '\Z');
      return str_replace($search, $replace, $a_string);
   }
   function table_trunc($table) {
      global $dbname, $crlf, $crlf2;
      $schema_create = '';
      $schema_create .= "TRUNCATE TABLE $table;$crlf";
      return($schema_create);
   }
   function get_table_content($table) {
      global $dbname, $crlf, $crlf2;
      $no_needtable = array('bannerfinish','blocnotes','compatsujet','counter','forum_read','lnl_send','optimy','subscribe','users_status');
      $table_list = '';
      $schema_insert = '';
      $result = sql_query("SELECT * FROM $table");
      $count = sql_num_fields($result);
      while($row = sql_fetch_row($result)) {
         $schema_insert .= "INSERT INTO $table VALUES (";
         for($j = 0; $j < $count; $j++) {
            if(!isset($row[$j]))
               $schema_insert .= " NULL";
            else
               if ($row[$j] != "")
                  $schema_insert .= " '".PrepareString($row[$j])."'";
               else
                  $schema_insert .= " ''";
            if($j < ($count -1))
               $schema_insert .= ",";
         }
         $schema_insert .= ");$crlf";
      }
      if($schema_insert != "")
      {
         $schema_insert = trim($schema_insert);
         return($schema_insert);
      }
   }
   function export_db() {
      global $dbname, $MSos, $crlf;
      $no_needtable = array('bannerfinish','blocnotes','compatsujet','counter','forum_read','lnl_send','optimy','subscribe','users_status');
      @set_time_limit(600);
      $date_jour = date(adm_translate("dateforop"));
      $date_op = date("mdy");
      $filename = $dbname."-migration";
      $tables = sql_list_tables($dbname);
      $num_tables = sql_num_rows($tables);
      if($num_tables == 0)
         echo "&nbsp;".adm_translate("Aucune table n'a été trouvée")."\n";
      else {
         $heure_jour = date("H:i");
         $data = "# ========================================================$crlf"
            ."# $crlf"
            ."# ".adm_translate("Sauvegarde de la base de données")." : ".$dbname." $crlf"
            ."# ".adm_translate("Effectuée le")." ".$date_jour." : ".$heure_jour." $crlf"
            ."# $crlf"
            ."# ========================================================$crlf";
         while($row = sql_fetch_row($tables)) {
            $table = $row[0];
            if (!in_array($table, $no_needtable)) {
               $data .= "$crlf"
               $data .= table_trunc($table)
                  ."$crlf"
               $data .= get_table_content($table)
                  ."$crlf$crlf"
            }
         }
      }
      send_file($data,$filename,"sql",$MSos);
   }


function maj_files() {   
   global $nuke_url;
   
   @unlink("images/admin/ws/package_locked.gif");
   
   // Update config file//
   $file=file("config.php");
   $fic = fopen("config.php", "w");
   foreach($file as $n => $ligne) {
      if (trim($ligne)=="\$Version_Num = \"13\";") 
         $ligne="\$Version_Num = \"16.2\";\n";
      fwrite($fic, $ligne);
   }
   fclose($fic);

   // Update robot.txt for sitemap.xml
   $fic = fopen("robots.txt", "a");
   fwrite($fic,"\r\nSitemap: ".$nuke_url."/cache/sitemap.xml");
   fclose($fic);
   
   SC_clean();

   include ("admin/settings_save.php");
   $tab_tmp=GetMetaTags("meta/meta.php");
   $tab_tmp['doctype']=doctype;
   MetaTagSave("meta/meta.php", $tab_tmp);
}
   echo '
   <link id="bsth" rel="stylesheet" href="lib/bootstrap/dist/css/bootstrap.min.css" />
   <link rel="stylesheet" href="lib/font-awesome/css/all.min.css" />
   <div class="container-fluid">
      <div class="row bg-secondary py-2">
         <div class="col-md-2 d-none d-md-block text-center">
            <img class="img-fluid" src="themes/npds-boost_sk/images/header.png" alt="logo" align="center" class="mess" />
         </div>
         <div id="logo_header" class="col-md-10">
            <h1 class="my-4">NPDS<br /><small class="text-light">Mise à jour REvolution 13 à 16</small></h1>
         </div>
      </div>';

   if ($language=="french") $lang="french"; else $lang="english"; 

   // Check NPDS version et le fichier config !!!!
/*
   if ($Version_Sub != "REvolution13") {
      echo '
      <div id="welcome" class="alert alert-danger lead my-4 mx-2">
         <p class="mt-3 text-danger">
            <i class="fa fa-ban fa-2x mr-2 align-middle"></i>';
      if ($lang=="french") 
         echo "Vous n'avez pas la bonne version de NPDS - ce patch s'installe par dessus REvolution13.";
      else
         echo "You haven't the right NPDS version - this update will only work over REvolution 13.";
      echo '
         </p>
         <p>Installation avortée</p>
      </div>
   </div>';
      $op="bad_version";
   }
*/
//if(!isset($op)) $op='';//

   switch ($op) {
      case "update":
         mess_update($lang);
         break;
      case "export":
      $MSos=get_os();
         if ($MSos) {
            $crlf="\r\n";
            $crlf2="\\r\\n";
         } else {
            $crlf="\n";
            $crlf2="\\n";
         }
         export_db($lang)
      break;
         
      case "finish":
         maj_db();
//         maj_files();
         mess_finish($lang);

         // delete installation files
/*
           @unlink("install.php");
           @unlink ("install/languages/welcome-french.txt");
           @unlink ("install/languages/welcome-english.txt");
           @unlink ("install/languages/update-french.txt");
           @unlink ("install/languages/update-english.txt");
           @unlink ("install/languages/finish-french.txt");
           @unlink ("install/languages/finish-english.txt");
           @rmdir("install/languages");
           @rmdir("install");
*/
         break;
      
      case "bad_version":
         break;


      default:
      case '':
         @unlink("IZ-Xinstall.ok");
         mess_welcome($lang);
         break;
   }
//   include("footer.php");
?>