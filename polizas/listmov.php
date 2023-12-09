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
 *	    \file       
 *      \ingroup    
 *		\brief      
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/polizas/class/polizas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/contab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/nxus/core/lib/nxos.excel.lib.php';

$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
//recojo los elementos seleccionados con el checkbox en el arreglo $selected
$selected=GETPOST('selected');
$miarray = unserialize($_GET['matriz']);

$search_folio=GETPOST("search_folio", 'int');
$search_codigo=GETPOST("search_codigo", 'alpha');
$search_cuenta=GETPOST("search_cuenta", 'alpha');
$search_concepto=GETPOST("search_concepto", 'alpha');
$search_tipopol=GETPOST("search_tipopol", 'int');
$search_periodo=GETPOST("search_periodo", 'int');
$search_ejercicio=GETPOST("search_ejercicio", 'int');
if ($_POST['reimonth'] && $_POST['reiday'] && $_POST['reiyear'] && $_POST['reimonth'] && $_POST['reiday'] && $_POST['reiyear']) {
	$search_datei=dol_mktime(12,0,0,$_POST['reimonth'],$_POST['reiday'],$_POST['reiyear']);
	$search_datef=dol_mktime(12,0,0,$_POST['refmonth'],$_POST['refday'],$_POST['refyear']);
	$search_datei=strftime("%F",strtotime($db->idate($search_datei)));
	$search_datef=strftime("%F",strtotime($db->idate($search_datef)));
}
if (GETPOST("search_datei") && GETPOST("search_datef")) {
	$search_datei = GETPOST("search_datei");
	$search_datef = GETPOST("search_datef");
}
$mode = GETPOST('mode','int');		//valor del boton de accion seleccionado
$idcuenta = GETPOST('idcuenta','int');

$type=GETPOST("type");
$view=GETPOST("view");

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid','int');
$begin=GETPOST('begin');

$cancel_filter=GETPOST('cancel_filter');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.Ejercicio, p.Periodo, p.TipoPol, p.Folio";  //campo de ordenacion por default
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

$pageprev = $page - 1;
$pagenext = $page + 1;

$titre = $langs->trans("Listado de Movimientos de Pólizas");

if (! empty($text)) $titre.= " $text";

if ($cancel_filter)
{
    $search_folio="";
    $search_codigo="";
    $search_cuenta="";
    $search_concepto="";
	$search_referencia="";		
    $search_tipopol="";
    $search_datei="";
    $search_datef="";	
    $search_periodo="";
    $search_ejercicio="";		
}
if ($search_priv < 0) $search_priv='';

if ($mode==2) {
	header("Location: ".DOL_URL_ROOT."/custom/contab/polizas/card.php?mode=2");
	exit;
}

	//eliminamos las polizas seleccionadas
/*con este codigo obtenemos los id de los checkbox seleccionado en la lista */
if($selected){	//guardamos en un arreglo los id seleccionados
	$idsel = array();
	foreach($selected as $id) {
		$idsel[$id] = $id;
	}
}
if($action=='confirm_delete' && $confirm='yes'){	//comenzamos a eliminar los id seleccionados
	$contab = new Contabilidad($db);
	$res_eliminar = 0;
	foreach($miarray as $id) {
		$ejecutar = $contab->borrarPoliza($id);	
		if($ejecutar>0) $res_eliminar++;
	}
	if ($res_eliminar>0) { 
		header("Location: ".DOL_URL_ROOT."/custom/contab/polizas/list.php?leftmenu=contab&type=0");
		setEventMessage('Poliza(s) eliminada(s) correctamente');
		exit;		
	}
	
}

$idsel_del = serialize($idsel);
$idsel_del = urlencode($idsel_del);

/*
 * View
 */
//				0			1		2			3			4
$sql = "SELECT p.Folio, tp.label, p.Fecha, pe.month, p.Ejercicio";
//				5			6		7			8				9			10			11
$sql.= ", mp.NumMovto, c.codigo, c.nombre, mp.Concepto, mp.Referencia, mp.Importe, mp.TipoMovto";
//				12				13			14		15			16			17
$sql.= ", p.rowid as idpol, p.TipoPol, p.Periodo, mp.rowid, mp.IdPoliza, mp.IdCuenta"; //columnas a ocultarse en la exportacion a excel
$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_polizas as p ON mp.IdPoliza = p.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_cuentas as c ON mp.IdCuenta = c.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_tipospol as tp ON tp.rowid = p.TipoPol";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_periodos as pe ON pe.rowid = p.Periodo";
$sql.= " WHERE 1";
$sql.= " AND p.Ejercicio = '".$conf->global->FISCAL_YEAR."'"; 

if ($search_folio) $sql .= " AND p.folio LIKE '%".$db->escape($search_folio)."%'";
if ($search_codigo) $sql .= " AND c.codigo LIKE '%".$db->escape($search_codigo)."%'";
if ($search_cuenta) $sql .= " AND c.nombre LIKE '%".$db->escape($search_cuenta)."%'";
if ($search_datei && $search_datef) $sql .= " AND p.Fecha >= '".$db->escape($search_datei)."' AND p.Fecha <= '".$db->escape($search_datef)."'";
if ($search_concepto) $sql .= " AND p.Concepto LIKE '%".$db->escape($search_concepto)."%'";
if ($search_tipopol) $sql .= " AND p.TipoPol LIKE '%".$db->escape($search_tipopol)."%'";
if ($search_ejercicio) $sql .= " AND p.Ejercicio LIKE '%".$db->escape($search_ejercicio)."%'";
if ($search_periodo) $sql .= " AND p.Periodo LIKE '%".$db->escape($search_periodo)."%'";
if ($idcuenta) $sql .= " AND mp.IdCuenta ='".$db->escape($idcuenta)."'";


// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}
// Add order and limit
if($view == "recent")
{
    $sql.= " ORDER BY p.Fecha DESC ";
	$sql2 = $sql;		//esta consulta no tiene limites es la completa	
	$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
}
else
{
    $sql.= " ORDER BY $sortfield $sortorder ";
	$sql2 = $sql;		//esta consulta no tiene limites es la completa
	$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
}
//print $sql;

if($mode==10) {		//se va a exportar a excel la consulta
	$mode==2;
	$titles_col = array('Folio','Tipo','Fecha','Periodo','Ejercicio','Mov','Codigo','Cuenta','Concepto','Referencia','Cargo','Abono');
	$fields_hide = array(0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,1);
	$report_name = "Auxiliar de Movimientos";
	$filename = "Aux_movimientos";
	$sep_ca = array(11,10);	//separa ek cargo y abono dependiendo del tipo de movimiento (tipomovto, importe)
	$stat_cols = array(12,13);	//columnas donde se sacarn las estadisticas
	$exportaxls = exportSQLXls($sql2,$fields_hide,$titles_col,$report_name,$filename,$sep_ca, $stat_cols);
	//print $sql2;
}

$title = $langs->trans("Listado de Movimientos de Pólizas");
llxHeader("",$langs->trans("Movimientos"),$helpurl);

$form=new Form($db);

if ($mode==3) {
	$aviso = 'Confirme la eliminación de póliza';
	$question = '¿Está seguro de eliminar la(s) póliza(s) seleccionada(s)?';
	$formconfirm=$form->form_confirm($_SERVER['PHP_SELF'].'?matriz='.$idsel_del,$aviso,$question,'confirm_delete','',0,1);	
}

$result = $db->query($sql);

if ($result)
{
	$poliza = new Polizas($db);  //llama a la clase del objeto

	$num = $db->num_rows($result);
    $i = 0;
	
	//Parametros para las busquedas de mas de una pagina
    $param='';
    if ($search_folio)       	$param.='&search_folio=' .$search_folio;
    if ($search_tipopol)     	$param.='&search_tipopol=' .$search_tipopol;
    if ($search_periodo)     	$param.='&search_periodo=' .$search_periodo;
    if ($search_ejercicio)   	$param.='&search_ejercicio='.$search_ejercicio;
	if ($search_datei)			$param.='&search_datei='.$search_datei;
	if ($search_datef)			$param.='&search_datef='.$search_datef;
	if ($search_codigo)			$param.='&search_codigo='.$search_codigo;
	if ($search_cuenta)			$param.='&search_cuenta='.$search_cuenta;
	if ($search_concepto)		$param.='&search_concepto='.$search_concepto;
	if ($search_referencia)		$param.='&search_referencia='.$search_referencia;			
	if ($idcuenta)				$param.='&idcuenta='.$idcuenta;				
	
    print '<form method="post" name="formlist" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="view" value="'.$view.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" id="cancel_filter" name="cancel_filter" value="">';	
	print '<input type="hidden" id="mode" name="mode" value="">';
//botones de accion
/*	print "\n".'<div>'."\n";
    print '<input type="image" value="add" onClick="sub_mode(1)" src="'.DOL_URL_ROOT.'/media/imgs/interface/add-1b.png" name="add" id="add" title="'.dol_escape_htmltag($langs->trans("Agregar Póliza")).'">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="image" value="delete" onClick="sub_mode(2)" src="'.DOL_URL_ROOT.'/media/imgs/interface/garbage-2c.png" name="button_delete" id="delete" title="'.dol_escape_htmltag($langs->trans("Eliminar Póliza")).'">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
	print '<input type="image" value="button_search" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-2c.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';
    print '<input type="image" value="button_removefilter"onClick="sub_mode(3)" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-cancel-2a.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
    print '<input type="image" value="xls" onClick="sub_mode(10)" src="'.DOL_URL_ROOT.'/media/imgs/interface/xls.png" name="xls" id="xls" title="Exportar a Excel"))">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
//    print '<input type="image" value="saldos" onClick="sub_mode(12)" src="'.DOL_URL_ROOT.'/media/imgs/interface/price-tag-16.png" name="saldos" id="saldos" title="Actualizar saldos de cuentas"))">';	
	print "</div>";*/
//
    print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
    print "\n".'<div>'."\n"; //botones de accion
    print '<input type="button" class="butAction" name="add" id="add" value="Agregar" onClick="sub_mode(1)">';
    print '<input type="button" class="butActionDelete" name="button_delete" id="delete" value="Eliminar" onClick="sub_mode(2)">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="submit" class="butAction" name="button_search" id="button_search" value="Filtrar">';
    print '<input type="button" class="butAction" name="button_removefilter" id="button_removefilter" value="Quitar Filtro" onClick="sub_mode(3)">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="button" class="butAction" name="xls" id="xls" value="Exportar a Excel" onClick="sub_mode(10)">';
    print "</div>";
    print '<table class="liste" width="100%">';

    // Ligne des titres
    print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
    print_liste_field_titre($langs->trans("Folio"),$_SERVER["PHP_SELF"],"p.folio", $begin, $param, '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Tipo"),$_SERVER["PHP_SELF"],"p.TipoPol", $begin, $param, 'align="center"', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Fecha"),$_SERVER["PHP_SELF"],"p.Fecha", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Periodo"),$_SERVER["PHP_SELF"],"p.Periodo", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Ejercicio"),$_SERVER["PHP_SELF"],"p.Ejercicio", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print '<td class="liste_titre" align="center">'.'Mov'.'</td>';
	print_liste_field_titre($langs->trans("Codigo"),$_SERVER["PHP_SELF"],"c.codigo", $begin, $param, '', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Cuenta"),$_SERVER["PHP_SELF"],"c.nombre", $begin, $param, '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Concepto"),$_SERVER["PHP_SELF"],"p.Concepto", $begin, $param, '', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Referencia"),$_SERVER["PHP_SELF"],"mp.Referencia", $begin, $param, '', $sortfield,$sortorder);
	print '<td class="liste_titre" align="center">'.'Cargos'.'</td>';
	print '<td class="liste_titre" align="center">'.'Abonos'.'</td>';
//	print '<td class="liste_titre">&nbsp;</td>';
    print "</tr>\n";

    // Ligne des champs de filtres
    print '<tr class="liste_titre">';
	//ALL/NONE
	print '<td class="liste_titre" align="center">';
	print '<input type="checkbox" name="checkall" id="checkall">';
	print '</td>';
    print '<td class="liste_titre"><input class="flat" type="text" name="search_folio" size="4" value="'.$search_folio.'"></td>';
	print '<td class="liste_titre" align="center">';
	selectTipoPol($search_tipopol,'search_tipopol');
	print '</td>';
	print '<td class="liste_titre" align="center">De ';	
	print $form->select_date($search_datei,'rei','','',1,'',1);
	print 'Al ';
	print $form->select_date($search_datef,'ref','','',1,'',1);	
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print selectPeriodo($search_periodo,'search_periodo');
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print selectEjercicio($search_ejercicio,'search_ejercicio');
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre"><input class="flat" type="text" name="search_codigo" value="'.$search_codigo.'"></td>';
	print '<td class="liste_titre"><input class="flat" type="text" name="search_cuenta" size="40" value="'.$search_cuenta.'"></td>';
    print '<td class="liste_titre"><input class="flat" type="text" name="search_concepto" size="40" value="'.$search_concepto.'"></td>';
	print '<td class="liste_titre"><input class="flat" type="text" name="search_referencia" size="40" value="'.$search_referencia.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" colspan="2">&nbsp;</td>';
    print '</tr>';

	$debe = 0;
	$haber = 0;
	
    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);

        $var=!$var;

        print "<tr $bc[$var]>";

		// Checkbox
		print '<td align="center">';
		if ($idsel[$obj->rowid] == $obj->rowid) print '<input class="flat checkformerge" checked onClick="mostrarVentana" type="checkbox" name="selected[]" value="'.$obj->rowid.'">';
		else print '<input class="flat checkformerge" onClick="mostrarVentana" type="checkbox" name="selected[]" value="'.$obj->rowid.'">';
		print '</td>' ;
		print '<td valign="middle" nowrap="nowrap" width="50">';
		$poliza->id=$obj->idpol;
		$poliza->folio=$obj->Folio;
		$poliza->concepto=$obj->Concepto;
		print $poliza->getNomUrl(1,'',10);
		print '</td>';
		print '<td align="center" nowrap="nowrap">'.$obj->label."</td>";
		print '<td align="center">'.$obj->Fecha.'</td>';
        print '<td align="center" nowrap="nowrap">'.$poliza->getPeriodo($obj->Periodo)."</td>";
		print '<td align="center" nowrap="nowrap">'.$obj->Ejercicio."</td>";
		print '<td align="center" nowrap="nowrap" width="70">'.$obj->NumMovto.'</td>';
		print '<td align="center" nowrap="nowrap" width="70">'.$obj->codigo.'</td>';
		print '<td>'.dol_trunc($obj->nombre,35).'</td>';
        print '<td>'.dol_trunc($obj->Concepto,35).'</td>';
        print '<td>'.dol_trunc($obj->Referencia,20).'</td>';		
		if ($obj->TipoMovto==0) print '<td align="right" nowrap="nowrap">'."$".price($obj->Importe)."</td>";		
		else print '<td align="right" nowrap="nowrap">&nbsp;</td>';
		if ($obj->TipoMovto==1) print '<td align="right" nowrap="nowrap">'.price($obj->Importe).'</td>';		
		else print '<td align="right" nowrap="nowrap">&nbsp;</td>';	
        print "</tr>\n";
		if ($obj->TipoMovto==0) $debe = $debe + $obj->Importe;
		else $haber = $haber + $obj->Importe;
        $i++;
    }
	
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="10">&nbsp;</td>';
	print '<td class="liste_total" align="right">'.'TOTAL PÁGINA'.'</td>';
	print '<td class="liste_total" align="right">'.'$ '.price($debe).'</td>';
	print '<td class="liste_total" align="right">'.'$ '.price($haber).'</td>';	
	print "</tr>\n";

    print "</table>";

    print '</form>';
	
	
	print '<br>';

    print print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

    $db->free($result);
	
}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
?>

</script>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#checkall").click(function() {
			if (document.getElementById("checkall").checked) {
				jQuery(".checkformerge").attr('checked', true);				
			}
			else {
				jQuery(".checkformerge").attr('checked', false);
			}
		});
	}); 
	
	function sub_mode(mode)
	{
		if (mode == '1') {
			document.getElementById("mode").value = 2;
			document.formlist.submit();
		}
		if (mode == '2') {
			document.getElementById("mode").value = 3;
			document.formlist.submit();
		}
		if (mode == '3') {
			document.getElementById("cancel_filter").value = -1;
			document.formlist.submit();
		}
		if (mode == '10') {
			document.getElementById("mode").value = 10;
			document.formlist.submit();
		}						
	}	
   
</script>