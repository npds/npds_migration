<?php
/************************************************************************/
/* DUNE by NPDS                                                         */
/* ===========================                                          */
/*                                                                      */
/* NPDS Copyright (c) 2002-2020 by Philippe Brunier                     */
/* IZ-Xinstall version : 1.3                                            */
/*                                                                      */
/* Auteurs : v.0.1.0 EBH (plan.net@free.fr)                             */
/*         : v.1.1.1 jpb, phr                                           */
/*         : v.1.1.2 jpb, phr, dev, boris                               */
/*         : v.1.1.3 dev - 2013                                         */
/*         : v.1.2 phr, jpb - 2016                                      */
/*         : v.1.3 jpb - 2020                                           */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/

/*
if (version_compare(PHP_VERSION, '5.3.0') >= 0 and extension_loaded('mysqli')) {
   $file = file("config.php");
   $file[33] ="\$mysql_p = 1;\n";
   $file[34] ="\$mysql_i = 1;\n";
   $fic = fopen("config.php", "w");
   foreach($file as $n => $ligne) {
      fwrite($fic, $ligne);
   }
   fclose($fic);
   include_once('lib/mysqli.php');
} else
   include_once('lib/mysql.php');

settype($langue,'string');
if($langue) {
   $lang_symb = substr($langue, 0, 3);
   if(file_exists($fichier_lang = 'install/languages/'.$langue.'/install-'.$lang_symb.'.php')) {
      include_once $fichier_lang;
   }
   else
      include_once('install/languages/french/install-fre.php');
}
*/

function verif_php() {
   global $stopphp, $phpver;
   $stopphp = 0;
   if(phpversion() < "5.3.0") { 
      $phpver = phpversion();
      $stopphp = 1;
   }
   else
      $phpver = phpversion();
   return ($phpver);
}

function write_parameters($new_dbhost, $new_dbuname, $new_dbpass, $new_dbname, $new_NPDS_Prefix, $new_mysql_p, $new_system, $new_system_md5, $new_adminmail) {
   global $stage4_ok;
   $stage4_ok = 0;

   $file = file("config.php");
   $file[29] ="\$dbhost = \"$new_dbhost\";\n";
   $file[30] ="\$dbuname = \"$new_dbuname\";\n";
   $file[31] ="\$dbpass = \"$new_dbpass\";\n";
   $file[32] ="\$dbname = \"$new_dbname\";\n";
   $file[33] ="\$mysql_p = \"$new_mysql_p\";\n";
   $file[36] ="\$system = $new_system;\n";
   $file[37] ="\$system_md5 = $new_system_md5;\n";
   $file[214]="\$adminmail = \"$new_adminmail\";\n";
   $file[319]="\$NPDS_Prefix = \"$new_NPDS_Prefix\";\n";
   $NPDS_Key=uniqid("");
   $file[320]="\$NPDS_Key = \"$NPDS_Key\";\n";

   $fic = fopen("config.php", "w");
   foreach($file as $n => $ligne) {
      fwrite($fic, $ligne);
   }
   fclose($fic);

   $stage4_ok = 1;
   return($stage4_ok);
}

function write_others($new_nuke_url, $new_sitename, $new_Titlesitename, $new_slogan, $new_Default_Theme, $new_startdate) {
   global $stage5_ok;
   $stage5_ok = 0;

   // Par défaut $parse=1 dans le config.php
   $new_sitename =  htmlentities(stripslashes($new_sitename));
   $new_Titlesitename = htmlentities(stripslashes($new_Titlesitename));
   $new_slogan = htmlentities(stripslashes($new_slogan));
   $new_startdate = stripslashes($new_startdate);
   $new_nuke_url = FixQuotes($new_nuke_url);

   $file = file("config.php");
   $file[90] ="\$sitename = \"$new_sitename\";\n";
   $file[91] ="\$Titlesitename = \"$new_Titlesitename\";\n";
   $file[92] ="\$nuke_url = \"$new_nuke_url\";\n";
   $file[94] ="\$slogan = \"$new_slogan\";\n";
   $file[95] ="\$startdate = \"$new_startdate\";\n";
   $file[101] ="\$Default_Theme = \"$new_Default_Theme\";\n";

   $fic = fopen("config.php", "w");
   foreach($file as $n => $ligne) {
      fwrite($fic, $ligne);
   }
   fclose($fic);

   $stage5_ok = 1;
   return($stage5_ok);
}

function msg_erreur($message) {
   echo '<html>
   <body bgcolor="white"><br />
      <div style="text-align: center; font-weight: bold">
         <div style="font-face: arial; font-size: 22px; color: #ff0000">'.ins_translate($message).'</div>
      </div>
      </body>
</html>';
   die();
}
?>