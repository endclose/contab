<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Page accueil des produits et services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contab/cuentas/class/cuentas.class.php';

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

$cuenta_static = new Cuentas($db);  //llama a la clase del objeto


/*
 * View
 */

$transAreaType = $langs->trans("Area de Cuentas Contables"); //titulo del nombre del modulo (area)
$helpurl='';
if (! isset($_GET["type"]))
{
	$transAreaType = $langs->trans("Area de Cuentas Contables");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 0) || empty($conf->service->enabled))
{
	$transAreaType = $langs->trans("Area de Cuentas Contables");
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if ((isset($_GET["type"]) && $_GET["type"] == 1) || empty($conf->product->enabled))
{
	$transAreaType = $langs->trans("Area de Cuentas Contables");
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader("",$langs->trans("Contabilidad"),$helpurl);

print_fiche_titre($transAreaType);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche produit/service
 */
$rowspan=2;
if (! empty($conf->barcode->enabled)) $rowspan++;
print '<form method="post" action="'.DOL_URL_ROOT.'/contab/cuentas/list.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Codigo").':</td><td><input class="flat" type="text" size="14" name="search_codigo"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Nombre cuenta").':</td><td><input class="flat" type="text" size="14" name="search_nombre"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';
//print "<tr ".$bc[false]."><td>";
//print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="sall"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';

print "</table></form><br>";


/*
 * Nombre de produits et/ou services
 */
 /*
$prodser = array();
$prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

$sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.tosell, p.tobuy";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= ' WHERE p.entity IN ('.getEntity($product_static->element, 1).')';
$sql.= " GROUP BY p.fk_product_type, p.tosell, p.tobuy";
$result = $db->query($sql);
while ($objp = $db->fetch_object($result))
{
	$status=1;
	if (! $objp->tosell && ! $objp->tobuy) $status=0;
	$prodser[$objp->fk_product_type][$status]=$objp->total;
}
*/

print '<table class="noborder" width="100%">';
/*
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if (! empty($conf->product->enabled))
{
	$statProducts = "<tr $bc[0]>";
	$statProducts.= '<td><a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
	$statProducts.= "</tr>";
	$statProducts.= "<tr $bc[1]>";
	$statProducts.= '<td><a href="liste.php?type=0&amp;tosell=1">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
	$statProducts.= "</tr>";
}
if (! empty($conf->service->enabled))
{
	$statServices = "<tr $bc[0]>";
	$statServices.= '<td><a href="liste.php?type=1&amp;tosell=0&amp;tobuy=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
	$statServices.= "</tr>";
	$statServices.= "<tr $bc[1]>";
	$statServices.= '<td><a href="liste.php?type=1&amp;tosell=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
	$statServices.= "</tr>";
}
$total=0;
if ($type == '0')
{
	print $statProducts;
	$total=round($prodser[0][0])+round($prodser[0][1]);
}
else if ($type == '1')
{
	print $statServices;
	$total=round($prodser[1][0])+round($prodser[1][1]);
}
else
{
	print $statProducts.$statServices;
	$total=round($prodser[1][0])+round($prodser[1][1])+round($prodser[0][0])+round($prodser[0][1]);
}
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
print $total;
print '</td></tr>';
*/
print '</table>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Last modified products
 */
$max=15;
$sql = "SELECT c.rowid, c.codigo, c.nombre, c.FechaRegistro, c.Afectable, c.active";
$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as c";
$sql.= " WHERE c.active = 0";
$sql.= " ORDER BY c.FechaRegistro DESC";
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	if ($num > 0)
	{
		$transRecordedType = $langs->trans("Las 15 últimas cuentas contables registradas",$max);

		print '<table class="noborder" width="100%">';

		$colnb=5;

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
			$cuenta_static->ref=$objc->codigo;
			$cuenta_static->nombre=$objc->nombre;
			
			print $cuenta_static->getNomUrl(1,'',16);
			print "</td>\n";
			print '<td>'.dol_trunc($objc->nombre,32).'</td>';
			print "<td>";
			print dol_print_date($db->jdate($objc->FechaRegistro),'day');
			print "</td>";
			print '<td align="right" nowrap="nowrap">';
			print $cuenta_static->LibStatut($objc->Afectable,5,0);
			print "</td>";
            print '<td align="right" nowrap="nowrap">';
            if ($objc->active == 0) print $cuenta_static->LibStatut(1,5,1);
			else print $cuenta_static->LibStatut(0,5,1);
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
