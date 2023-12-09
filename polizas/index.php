<?php
/* Copyright (C) 2017 Leo Campos <leo@leonx.net>
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
 *	\file       
 *  \ingroup    
 *  \brief      
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/polizas/class/polizas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/contab.class.php';

$type=isset($_GET["type"])?$_GET["type"]:(isset($_POST["type"])?$_POST["type"]:'');
if ($type =='' && !$user->rights->produit->lire) $type='1';	// Force global page on service page only
if ($type =='' && !$user->rights->service->lire) $type='0';	// Force global page on prpduct page only

// Security check
/*
if ($type=='0') $result=restrictedArea($user,'produit');
else if ($type=='1') $result=restrictedArea($user,'service');
else $result=restrictedArea($user,'produit|service');
*/

$socid='';
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$langs->load("products");

$cuenta_static = new Polizas($db);  //llama a la clase del objeto


/*
 * View
 */

$transAreaType = $langs->trans("Area de Pólizas"); //titulo del nombre del modulo (area)
$helpurl='';
if (! isset($_GET["type"]))
{
	$transAreaType = $langs->trans("Area de Pólizas");
//	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 0) || empty($conf->service->enabled))
{
	$transAreaType = $langs->trans("Area de Pólizas");
//	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}

llxHeader("",$langs->trans("Contabilidad"),$helpurl);

print_fiche_titre($transAreaType);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zona de busqueda de polizas
 */
$rowspan=2;
print '<form method="post" action="'.DOL_URL_ROOT.'/custom/contab/polizas/list.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder nohover" width="100%">';

print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Folio").':</td><td><input class="flat" type="text" size="14" name="search_folio"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Concepto").':</td><td><input class="flat" type="text" size="14" name="search_concepto"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';
//print "<tr ".$bc[false]."><td>";
//print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="sall"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';

print "</table></form><br>";


print '<table class="noborder" width="100%">';

print '</table>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Last modified products
 */
$max=15;
$sql = "SELECT p.rowid, p.Ejercicio, p.Periodo, p.Folio, p.Concepto, p.TipoPol, p.Fecha";
$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas as p";
$sql.= " ORDER BY p.tms DESC";
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0)
	{
		$transRecordedType = $langs->trans("Las 15 últimas pólizas registradas",$max);

		print '<table class="noborder" width="100%">';

		$colnb=6;

		print '<tr class="liste_titre"><td colspan="'.$colnb.'">'.$transRecordedType.'</td></tr>';

		$var=True;

		while ($i < $num)
		{
			$objc = $db->fetch_object($result);

			//Multilangs

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td nowrap="nowrap">';
			$cuenta_static->id=$objc->rowid;
			$cuenta_static->folio=$objc->Folio;
			$cuenta_static->concepto=$objc->Concepto;
			
			print $cuenta_static->getNomUrl(1,'',16);
			print "</td>\n";
			print '<td>'.dol_trunc($objc->Concepto,32).'</td>';
			print "<td>";
			print dol_print_date($db->jdate($objc->Fecha),'day');
			print "</td>";
			print '<td align="right" nowrap="nowrap">';
			print $cuenta_static->getTipoPol($objc->TipoPol);
			print "</td>";
            print '<td align="right" nowrap="nowrap">';
			print $cuenta_static->getPeriodo($objc->Periodo);
            print "</td>";
            print '<td align="right" nowrap="nowrap">';
			//print $cuenta_static->getEjercicio($objc->Ejercicio);
			print $objc->Ejercicio;
            print "</td>";
			print "</tr>\n";
			$i++;
		}

		$db->free();

		print "</table>";
	}
}
else
{
	dol_print_error($db);
}

print '</td></tr></table>';

llxFooter();

$db->close();
?>
