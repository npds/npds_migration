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
if (!function_exists("Mysql_Connexion")) {
   include ("mainfile.php");
}

// Fonctions de l'interface    
function mess_welcome($lang) {
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
      </div>
   </div>';
}

function mess_finish($lang) {
   echo "<div id=\"maj_deb\" class=\"mess\">";
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
   // BASE : mise à jour du CHARSET et collation
   $sql="ALTER DATABASE '".$dbname."' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
   $result = sql_query($sql);

   // TOUTES TABLES : mise à jour du CHARSET et collation par defaut des tables ce qui ne signifie pas la conversion des données existante !!...*/
   foreach($table13 as $v){
      $sql="ALTER TABLE ".$NPDS_Prefix.$v." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"; //utf8mb4 pour la 16.2
      $result = sql_query($sql);
   }

   // COLONNES de type char varchar text ...(274 résultats) : charset et collation
   $sql="select * from information_schema.columns
where table_schema = 'rev13' and DATA_TYPE REGEXP 'char|text'  
order by table_name,ordinal_position";
   $resultcol = sql_query($sql);
   while($row = sql_fetch_row($resultcol)) {
      $nomtable= $row[2];
      $nomcol = $row[3];
      $sql="ALTER TABLE '".$nomtable."' CHANGE '".$nomcol."' '".$nomcol."' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
      sql_query($sql);
   }


   // appli_log : modif des valeur par defaut compat mysql 5.7 
   $sql="ALTER TABLE ".$NPDS_Prefix."appli_log CHANGE al_date al_date DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);

   // authors : suppression des anciens droits ce qui signifie qu'il faudra réattribuer manuellement
   $sql="ALTER TABLE ".$NPDS_Prefix."authors DROP COLUMN radminarticle, DROP COLUMN radmintopic, DROP COLUMN radminleft, DROP COLUMN radminright, DROP COLUMN radminuser, DROP COLUMN radminmain, DROP COLUMN radminsurvey, DROP COLUMN radminsection, DROP COLUMN radminlink, DROP COLUMN radminephem, DROP COLUMN radminhead, DROP COLUMN radminfaq, DROP COLUMN radmindownload, DROP COLUMN radminforum, DROP COLUMN radminreviews, DROP COLUMN radminsdv, DROP COLUMN radminlnl";
   $result = sql_query($sql);

   // counter

   // downloads : modif des valeur par defaut compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."downloads CHANGE ddate ddate DATE NOT NULL DEFAULT '1000-01-01'";
   $result = sql_query($sql);
   // downloads : dfilesize no need signed value ...
   $sql="ALTER TABLE ".$NPDS_Prefix."downloads CHANGE dfilesize dfilesize BIGINT(15) UNSIGNED NULL DEFAULT NULL";
   // downloads : change type of value ...gestion des permissions pour les groupes ...
   $sql="ALTER TABLE ".$NPDS_Prefix."downloads MODIFY perms varchar(480)";

   // links_categories : modif longueur title
   $sql="ALTER TABLE ".$NPDS_Prefix."links_categories MODIFY title varchar(255)";
   $result = sql_query($sql);

   // links_editorials : modif valeur par defaut de editorialtimestamp compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."links_editorials CHANGE editorialtimestamp editorialtimestamp DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);

   // links_links : modif longueur url
   $sql="ALTER TABLE ".$NPDS_Prefix."links_links MODIFY url varchar(255)";
   $result = sql_query($sql);

   // lnl_outside_users : modif valeur par defaut de date compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."lnl_outside_users CHANGE date date DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);

   // lnl_send : modif valeur par defaut de date compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."lnl_send CHANGE date date DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);

   // posts : modif longueur poster_ip pour support IPV6
   $sql="ALTER TABLE ".$NPDS_Prefix."posts MODIFY poster_ip varchar(54)";
   $result = sql_query($sql);

   // queue : modif valeur par defaut de 'timestamp' compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."queue CHANGE timestamp timestamp DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);

   // reviews : modif valeur par defaut de 'date' compat mysql 5.7
   $sql="ALTER TABLE ".$NPDS_Prefix."reviews CHANGE date date DATE NOT NULL DEFAULT '1000-01-01 00:00:00'";
   $result = sql_query($sql);
   // reviews : modif valeur par defaut de 'title' the null default value for text columns is empty string '' et pas NULL
   $sql="ALTER TABLE ".$NPDS_Prefix."reviews_main CHANGE title title text DEFAULT ''";
   $result = sql_query($sql);

   // seccont: suppression de colonne inutile
   $sql="ALTER TABLE ".$NPDS_Prefix."seccont DROP COLUMN crit1, DROP COLUMN crit2, DROP COLUMN crit3, DROP COLUMN crit4, DROP COLUMN crit5, DROP COLUMN crit6, DROP COLUMN crit7, DROP COLUMN crit8, DROP COLUMN crit9, DROP COLUMN crit10, DROP COLUMN crit11, DROP COLUMN crit12, DROP COLUMN crit13, DROP COLUMN crit14, DROP COLUMN crit15, DROP COLUMN crit16, DROP COLUMN crit17, DROP COLUMN crit18, DROP COLUMN crit19, DROP COLUMN crit20";
   $result = sql_query($sql);
   // seccont_tempo: suppression de colonne inutile
   $sql="ALTER TABLE ".$NPDS_Prefix."seccont_tempo DROP COLUMN crit1, DROP COLUMN crit2, DROP COLUMN crit3, DROP COLUMN crit4, DROP COLUMN crit5, DROP COLUMN crit6, DROP COLUMN crit7, DROP COLUMN crit8, DROP COLUMN crit9, DROP COLUMN crit10, DROP COLUMN crit11, DROP COLUMN crit12, DROP COLUMN crit13, DROP COLUMN crit14, DROP COLUMN crit15, DROP COLUMN crit16, DROP COLUMN crit17, DROP COLUMN crit18, DROP COLUMN crit19, DROP COLUMN crit20";
   $result = sql_query($sql);

   // session : modif longueur username, host_addr pour support IPV6 (à voir si il faut rajouter NOT NULL)
   $sql="ALTER TABLE ".$NPDS_Prefix."session MODIFY username varchar(54)";
   $result = sql_query($sql);
   $sql="ALTER TABLE ".$NPDS_Prefix."session MODIFY host_addr varchar(54)";
   $result = sql_query($sql);

   // users : suppression des "anciens réseaux sociaux" et type de user_theme (pour skin implémentation)
   $sql="ALTER TABLE ".$NPDS_Prefix."users DROP COLUMN user_icq, DROP COLUMN user_aim, DROP COLUMN user_yim, DROP COLUMN user_msnm";
   $result = sql_query($sql);
   $sql="ALTER TABLE ".$NPDS_Prefix."users MODIFY user_theme varchar(255)";
   $result = sql_query($sql);

   // création : 3 tables droits, fonctions, IP_loc
   $sql="CREATE TABLE ".$NPDS_Prefix."droits (
  d_aut_aid varchar(40) NOT NULL COMMENT 'id administrateur',
  d_fon_fid tinyint(3) unsigned NOT NULL COMMENT 'id fonction',
  d_droits varchar(5) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dune_proto'";
   $result = sql_query($sql);

   $sql="CREATE TABLE ".$NPDS_Prefix."fonctions (
  fid mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id unique auto incrémenté',
  fnom varchar(40) NOT NULL,
  fdroits1 tinyint(3) unsigned NOT NULL,
  fdroits1_descr varchar(40) NOT NULL,
  finterface tinyint(1) unsigned NOT NULL COMMENT '1 ou 0 : la fonction dispose ou non d''une interface',
  fetat tinyint(1) NOT NULL COMMENT '0 ou 1  9 : non active ou installé, installé',
  fretour varchar(500) NOT NULL COMMENT 'utiliser par les fonctions de categorie Alerte : nombre, ou ',
  fretour_h varchar(500) NOT NULL,
  fnom_affich varchar(200) NOT NULL,
  ficone varchar(40) NOT NULL,
  furlscript varchar(4000) NOT NULL COMMENT 'attribut et contenu  de balise A : href=\"xxx\", onclick=\"xxx\"  etc',
  fcategorie tinyint(3) unsigned NOT NULL,
  fcategorie_nom varchar(200) NOT NULL,
  fordre tinyint(2) unsigned NOT NULL,
  PRIMARY KEY (fid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4  COLLATE=utf8mb4_unicode_ci COMMENT='Dune_proto'";
   $result = sql_query($sql);

   $sql="CREATE TABLE ".$NPDS_Prefix."ip_loc (
  ip_id smallint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_long float NOT NULL DEFAULT '0',
  ip_lat float NOT NULL DEFAULT '0',
  ip_visi_pag varchar(100) NOT NULL DEFAULT '',
  ip_visite mediumint(9) UNSIGNED NOT NULL DEFAULT '0',
  ip_ip varchar(54) NOT NULL DEFAULT '',
  ip_country varchar(100) NOT NULL DEFAULT '0',
  ip_code_country varchar(4) NOT NULL,
  ip_city varchar(150) NOT NULL DEFAULT '0',
  PRIMARY KEY (ip_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
   $result = sql_query($sql);

// fonctions : remplissage table (!! à voir le comportement de l'auto-increment !!)
$sql="INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(1, 'edito', 1, '', 1, 1, '', '', 'Edito', 'edito', 'href=\"admin.php?op=Edito\"', 1, 'Contenu', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(2, 'adminStory', 2, '', 1, 1, '', '', 'Nouvel Article', 'postnew', 'href=\"admin.php?op=adminStory\"', 1, 'Contenu', 1);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(3, 'sections', 3, '', 1, 1, '', '', 'Rubriques', 'sections', 'href=\"admin.php?op=sections\"', 1, 'Contenu', 2);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(4, 'topicsmanager', 4, '', 1, 1, '', '', 'Gestion des Sujets', 'topicsman', 'href=\"admin.php?op=topicsmanager\"', 1, 'Contenu', 3);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(5, 'links', 5, '', 1, 1, '', '', 'Liens Web', 'links', 'href=\"admin.php?op=links\"', 1, 'Contenu', 5);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(6, 'FaqAdmin', 6, '', 1, 1, '1', '', 'FAQ', 'faq', 'href=\"admin.php?op=FaqAdmin\"', 1, 'Contenu', 6);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(7, 'Ephemerids', 7, '', 1, 1, '1', '', 'Ephémérides', 'ephem', 'href=\"admin.php?op=Ephemerids\"', 1, 'Contenu', 7);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(8, 'HeadlinesAdmin', 8, '', 1, 1, '', '', 'News externes', 'headlines', 'href=\"admin.php?op=HeadlinesAdmin\"', 1, 'Contenu', 8);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(9, 'DownloadAdmin', 9, '', 1, 1, '', '', 'Téléchargements', 'download', 'href=\"admin.php?op=DownloadAdmin\"', 1, 'Contenu', 9);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(10, 'mod_users', 10, '', 1, 1, '', '', 'Utilisateurs', 'users', 'href=\"admin.php?op=mod_users\"', 2, 'Utilisateurs', 1);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(11, 'groupes', 11, '', 1, 1, '', '', 'Groupes', 'groupes', 'href=\"admin.php?op=groupes\"', 2, 'Utilisateurs', 2);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(12, 'mod_authors', 12, '', 1, 1, '', '', 'Administrateurs', 'authors', 'href=\"admin.php?op=mod_authors\"', 2, 'Utilisateurs', 3);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(13, 'MaintForumAdmin', 13, '', 1, 1, '', '', 'Maintenance Forums', 'forum', 'href=\"admin.php?op=MaintForumAdmin\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(14, 'ForumConfigAdmin', 14, '', 1, 1, '', '', 'Configuration Forums', 'forum', 'href=\"admin.php?op=ForumConfigAdmin\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(15, 'ForumAdmin', 15, '', 1, 1, '', '', 'Edition Forums', 'forum', 'href=\"admin.php?op=ForumAdmin\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(16, 'lnl', 16, '', 1, 1, '', '', 'Lettre D''info', 'lnl', 'href=\"admin.php?op=lnl\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(17, 'email_user', 17, '', 1, 1, '', '', 'Message Interne', 'email_user', 'href=\"admin.php?op=email_user\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(18, 'BannersAdmin', 18, '', 1, 1, '', '', 'Bannières', 'banner', 'href=\"admin.php?op=BannersAdmin\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(19, 'create', 19, '', 1, 1, '', '', 'Sondages', 'newpoll', 'href=\"admin.php?op=create\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(20, 'reviews', 20, '', 1, 1, '', '', 'Critiques', 'reviews', 'href=\"admin.php?op=reviews\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(21, 'hreferer', 21, '', 1, 1, '', '', 'Sites Référents', 'referer', 'href=\"admin.php?op=hreferer\"', 3, 'Communication', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(22, 'blocks', 22, '', 1, 1, '', '', 'Blocs', 'block', 'href=\"admin.php?op=blocks\"', 4, 'Interface', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(23, 'mblock', 23, '', 1, 1, '', '', 'Bloc Principal', 'blockmain', 'href=\"admin.php?op=mblock\"', 4, 'Interface', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(24, 'ablock', 24, '', 1, 1, '', '', 'Bloc Administration', 'blockadm', 'href=\"admin.php?op=ablock\"', 4, 'Interface', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(25, 'Configure', 25, '', 1, 1, '', '', 'Préférences', 'preferences', 'href=\"admin.php?op=Configure\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(26, 'ConfigFiles', 26, '', 1, 1, '', '', 'Fichiers configurations', 'preferences', 'href=\"admin.php?op=ConfigFiles\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(27, 'FileManager', 27, '', 1, 1, '', '', 'Gestionnaire Fichiers', 'filemanager', 'href=\"admin.php?op=FileManager\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(28, 'supercache', 28, '', 1, 1, '', '', 'SuperCache', 'overload', 'href=\"admin.php?op=supercache\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(29, 'OptimySQL', 29, '', 1, 1, '', '', 'OptimySQL', 'optimysql', 'href=\"admin.php?op=OptimySQL\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(30, 'SavemySQL', 30, '', 1, 1, '', '', 'SavemySQL', 'savemysql', 'href=\"admin.php?op=SavemySQL\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(31, 'MetaTagAdmin', 31, '', 1, 1, '', '', 'MétaTAGs', 'metatags', 'href=\"admin.php?op=MetaTagAdmin\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(32, 'MetaLangAdmin', 32, '', 1, 1, '', '', 'META-LANG', 'metalang', 'href=\"admin.php?op=Meta-LangAdmin\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(33, 'setban', 33, '', 1, 1, '', '', 'IP', 'ipban', 'href=\"admin.php?op=Extend-Admin-SubModule&amp;ModPath=ipban&amp;ModStart=setban\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(34, 'session_log', 34, '', 1, 1, '', '', 'Logs', 'logs', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=session-log&ModStart=session-log\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(35, 'reviews', 20, '', 1, 1, '0', 'Critique en atttente de validation.', 'Critiques', 'reviews', 'href=\"admin.php?op=reviews\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(36, 'mes_npds_versus', 36, '', 1, 1, '', 'Une nouvelle version est disponible ! Cliquez pour acc&#xE9;der &#xE0; la zone de t&#xE9;l&#xE9;chargement de NPDS.', '', 'message_npds', '', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(37, 'autoStory', 2, '', 1, 1, '1', 'articles sont programm&eacute;s pour la publication.', 'Auto-Articles', 'autonews', 'href=\"admin.php?op=autoStory\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(38, 'submissions', 2, '', 1, 1, '10', 'Article en attente de validation !', 'Articles', 'submissions', 'href=\"admin.php?op=submissions\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(39, 'hreferer_al', 21, '', 1, 1, '!!!', 'Limite des r&#xE9;f&#xE9;rants atteinte : pensez &#xE0; archiver vos r&#xE9;f&#xE9;rants.', 'Sites Référents', 'referer', 'href=\"admin.php?op=hreferer\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(40, 'abla', 40, '', 1, 1, '', '', 'Blackboard', 'abla', 'href=\"admin.php?op=abla\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(41, 'newlink', 5, '', 1, 1, '1', 'Lien &#xE0; valider', 'Lien', 'links', 'href=\"admin.php?op=links\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(42, 'brokenlink', 5, '', 1, 1, '6', 'Lien rompu &#xE0; valider', 'Lien rompu', 'links', 'href=\"admin.php?op=LinksListBrokenLinks\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(43, 'archive-stories', 43, '', 1, 1, '', '', 'Archives articles', 'archive-stories', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=archive-stories&ModStart=admin/archive-stories_set\"', 1, 'Contenu', 4);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(44, 'mod_users', 10, '', 1, 1, '', 'Utilisateur en attente de validation !', 'Utilisateurs', 'users', 'href=\"admin.php?op=nonallowed_users\"', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(49, 'npds_twi', 49, '', 1, 1, '', '', 'Npds_Twitter', 'npds_twi', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=npds_twi&ModStart=admin/npds_twi_set\"', 6, 'Modules', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(51, 'modules', 51, '', 1, 1, '', '', 'Gestion modules', 'modules', 'href=\"admin.php?op=modules\"', 5, 'Système', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(59, 'mes_npds_2', 0, '', 1, 1, '', 'Ceci est une note d''information provenant de NPDS.', '', 'flag_red', '', 9, 'Alerte', 0);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(74, 'reseaux-sociaux', 74, '', 1, 1, '', '', 'Réseaux sociaux', 'reseaux-sociaux', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=reseaux-sociaux&ModStart=admin/reseaux-sociaux_set\"', 2, 'Utilisateurs', 4);
INSERT INTO fonctions (fid, fnom, fdroits1, fdroits1_descr, finterface, fetat, fretour, fretour_h, fnom_affich, ficone, furlscript, fcategorie, fcategorie_nom, fordre) VALUES(75, 'geoloc', 75, '', 1, 1, '', '', 'geoloc', 'geoloc', 'href=\"admin.php?op=Extend-Admin-SubModule&ModPath=geoloc&ModStart=admin/geoloc_set\"', 6, 'Modules', 0);";
$result = sql_query($sql);

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

   // Rajout d'options pour WS
   $sql="ALTER TABLE ".$NPDS_Prefix."groupes ADD groupe_blocnote INT(1) UNSIGNED NOT NULL DEFAULT '0'";
   $result = sql_query($sql);
   $sql="ALTER TABLE ".$NPDS_Prefix."groupes ADD groupe_pad INT(1) UNSIGNED NOT NULL DEFAULT '0'";
   $result = sql_query($sql);
   
   // Converti en nouveau WS
   $result = sql_query("SELECT id, content from ".$NPDS_Prefix."lblocks where content like '%espace_groupe(%'");
   while($temp = sql_fetch_assoc($result) ) {
      $ibid=str_replace("espace_groupe(","",$temp['content']);
      $ibid=trim(str_replace(",1) ","",$ibid));
      sql_query("UPDATE ".$NPDS_Prefix."lblocks set content='function#bloc_espace_groupe\r\nparams#$ibid,1' where id='".$temp['id']."'");
      sql_query("UPDATE ".$NPDS_Prefix."groupes set groupe_blocnote='1', groupe_pad='1' where groupe_id='$ibid'");
   }
   $result = sql_query("SELECT id, content from ".$NPDS_Prefix."rblocks where content like '%espace_groupe(%'");
   while($temp = sql_fetch_assoc($result) ) {
      $ibid=str_replace("espace_groupe(","",$temp['content']);
      $ibid=str_replace(",1) ","",$ibid);
      sql_query("UPDATE ".$NPDS_Prefix."rblocks set content='function#bloc_espace_groupe\r\nparams#$ibid,1' where id='".$temp['id']."'");
      sql_query("UPDATE ".$NPDS_Prefix."groupes set groupe_blocnote='1', groupe_pad='1' where groupe_id='$ibid'");
   }
}
function maj_files() {   
   global $nuke_url;
   
   @unlink("images/admin/ws/package_locked.gif");
   
   // Update NPDS Version// ==> à revoir
   $file=file("config.php");
   $fic = fopen("config.php", "w");
      while(list($n,$ligne) = each($file)) {
         if (trim($ligne)=="\$Version_Sub = \"REvolution WS\";") 
            $ligne="\$Version_Sub = \"REvolution\";\n";
         if (trim($ligne)=="\$Version_Sub = \"REvolution WS P1\";") 
            $ligne="\$Version_Sub = \"REvolution\";\n";
         if (trim($ligne)=="\$Version_Num = \"11\";") 
            $ligne="\$Version_Num = \"13\";\n";         
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

//   include("header.php");
   echo '
   
   <link id="bsth" rel="stylesheet" href="lib/bootstrap/dist/css/bootstrap.min.css" />
   <link rel="stylesheet" href="lib/font-awesome/css/font-awesome.min.css" />';
   echo '
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

   // Check NPDS version
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
      </div>
   </div>';
      $op="bad_version";
   }

   switch ($op) {
      case "update":
         mess_update($lang);
         break;
         
      case "finish":
//         maj_db();
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