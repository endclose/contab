<?php
/* Copyright (C) 2010     Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-204 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file      htdocs/core/ajax/prodserv.php
 *       \ingroup	core
 *       \brief     File to return Ajax response on zipcode or town request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contab/class/commoncontab.class.php';

/*$htmlname=GETPOST('htmlname','alpha');
//$socid=GETPOST('socid','int');
//$type=GETPOST('type','int');
//$mode=GETPOST('mode','int');
//$status=((GETPOST('status','int') >= 0) ? GETPOST('status','int') : -1);
$outjson=(GETPOST('outjson','int') ? GETPOST('outjson','int') : 0);
//$price_level=GETPOST('price_level','int');
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
//$price_by_qty_rowid=GETPOST('pbq', 'int');
//$idwarehouse = GETPOST('entrepot_id', 'int');*/

top_httphead();

$table = array_keys($_GET);  //Obtenemos el valor de la tabla que se va a consultar
//var_dump($_GET);

//var_dump($table[0]);

// Generation of list of zip-town
if (! empty($_GET[$table[0]])) {
	$return_arr = array();

	if ($table[0] == 'cuenta') {
		// Define filter on text typed
		$cuentas = $_GET['cuenta']?$_GET['cuenta']:'';
	
		$sql = "SELECT cc.rowid, cc.codigo as label, cc.nombre as description";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as cc";
		$sql.= " WHERE (active = 0 AND afectable = 1) AND";
		if ($cuentas) {
			$sql.= " cc.codigo LIKE '".$db->escape($cuentas)."%'";
			$sql.= " OR cc.nombre LIKE '%".$db->escape($cuentas)."%'";
		}
		$sql.= " ORDER BY cc.codigo";
	}
		
	$sql.= $db->plimit(100); // Avoid pb with bad criteria
 
	$resql=$db->query($sql);
	//var_dump($db);

    if ($resql)
	{
		while ($row = $db->fetch_array($resql))
		{
			$row_array['label'] = $row['label'].' '.$row['description']; //Informacion a mostrar en el control
			$row_array['id'] = $row['rowid']; //id del elemento seleccionado
			$row_array['value'] = $row['label']; //valor que pasara por el GET

			array_push($return_arr, $row_array);
		}
	}

	echo json_encode($return_arr);
}
else
{
 echo "";
}

$db->close();

/*	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	top_httphead();

	if (empty($htmlname)) return;

	$match = preg_grep('/('.$htmlname.'[0-9]+)/',array_keys($_GET));
	sort($match);
	$idprod = (! empty($match[0]) ? $match[0] : '');

	if (! GETPOST($htmlname) && ! GETPOST($idprod)) return;

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey=(GETPOST($idprod)?GETPOST($idprod):(GETPOST($htmlname)?GETPOST($htmlname):''));

	$form = new Form($db);
    $arrayresult=$form->select_cuentas_contab("",$htmlname,"",$searchkey,$outjson);


	$db->close();

	if ($outjson) print json_encode($arrayresult);*/


