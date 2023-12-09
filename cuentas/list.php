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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/nxus/core/lib/nxos.lib.php';

$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
//recojo los elementos seleccionados con el checkbox en el arreglo $selected
$selected=GETPOST('selected');
$miarray = unserialize($_GET['matriz']);

$search_codigo=GETPOST("search_codigo", 'alpha');
$search_nombre=GETPOST("search_nombre", 'alpha');
$search_tipocta=GETPOST("search_tipocta", 'int');
$search_nat=GETPOST("search_nat", 'int');
$search_nivel=GETPOST("search_nivel", 'int');
$search_afecta=GETPOST("search_afecta", 'int');
$search_padre=GETPOST("search_padre", 'alpha');
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

$type=GETPOST("type");
$view=GETPOST("view");

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid','int');
$begin=GETPOST('begin');

$cancel_filter=GETPOST('cancel_filter');

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="c.codigo";  //campo de ordenacion por default
if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

$pageprev = $page - 1;
$pagenext = $page + 1;

if ($cancel_filter)
{
    $search_codigo="";
    $search_nombre="";
    $search_tipocta="";
    $search_datei="";
    $search_datef="";	
    $search_nat="";
    $search_nivel="";
    $search_afecta="";		
    $search_padre="";	
	$sortorder="ASC";
	$sortfield="c.codigo";
	$cuadre = 0;
}
if ($search_priv < 0) $search_priv='';

if ($mode==2) {
	header("Location: ".DOL_URL_ROOT."/custom/contab/cuentas/card.php?mode=2");
	exit;
}

	//eliminamos las cuentas seleccionadas
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
		header("Location: ".DOL_URL_ROOT."/contab/polizas/list.php?leftmenu=contab&type=0");
		setEventMessage('Poliza(s) eliminada(s) correctamente');
		exit;		
	}
	
}

$idsel_del = serialize($idsel);
$idsel_del = urlencode($idsel_del);

/*
 * View
 */

$sql = "SELECT c.rowid, c.codigo, c.nombre, c.saldo, tc.label as tipocta, nc.abrev as nivelcta, c.nat, c.FechaRegistro, c.afectable";
$sql.= ", sc.codigo as codpadre, sc.nombre as nompadre";
$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_tiposcta as tc ON tc.rowid = c.fk_tipocta";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_nivelcta as nc ON nc.rowid = c.fk_nivel";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_ctanxus as cn ON cn.fk_hijo = c.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_cuentas as sc ON sc.rowid = cn.fk_padre";
$sql.= " WHERE c.rowid IS NOT NULL";

if ($search_codigo) $sql .= " AND c.codigo LIKE '%".$db->escape($search_codigo)."%'";
if ($search_nombre) $sql .= " AND c.nombre LIKE '%".$db->escape($search_nombre)."%'";
if ($search_tipocta) $sql .= " AND tc.rowid LIKE '%".$db->escape($search_tipocta)."%'";
if ($search_nivel) $sql .= " AND nc.rowid LIKE '%".$db->escape($search_nivel)."%'";
if ($search_nat) $sql .= " AND c.nat LIKE '%".$db->escape($search_nat)."%'";
if ($search_afecta==1) $sql .= " AND c.afectable LIKE '1'";
if ($search_afecta==-1) $sql .= " AND c.afectable LIKE '0'";
if ($search_datei && $search_datef) $sql .= " AND c.FechaRegistro >= '".$db->escape($search_datei)."' AND c.FechaRegistro <= '".$db->escape($search_datef)."'";
if ($search_padre) $sql .= " AND sc.codigo LIKE '%".$db->escape($search_padre)."%'";
//if ($cuadre==1) $sql .= " AND p.Cargos <> p.Abonos";

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
    $sql.= " ORDER BY c.FechaRegistro DESC ";
	$sql2 = $sql;		//esta consulta no tiene limites es la completa
	$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
}
else
{
    $sql.= " ORDER BY $sortfield $sortorder ";
	$sql2 = $sql;	//esta consulta no tiene limites es la completa
	$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
}
//print $sql;
$result = $db->query($sql);

if($mode==10) {		//se va a exportar a excel la consulta
	$titles_col = array('Folio','Tipo','Fecha','Periodo','Ejercicio','Concepto','Cargos','Abonos');
	$fields_hide = array(0,0,0,0,0,0,0,0,1,1,1);
	$report_name = "Concentrado de Pólizas";
	$filename = "Concentrado_polizas";
	$stat_cols = array(8,9);	//columnas donde se sacarn las estadisticas	
	$exportaxls = exportSQLXls($sql2,$fields_hide,$titles_col,$report_name,$filename,NULL,$stat_cols);
	//print $sql2;
}

$titre = $langs->trans("Listado de Cuentas");
if (! empty($text)) $titre.= " $text";

$title = $langs->trans("Listado de Cuentas");
llxHeader("",$langs->trans("Cuentas Contables"),$helpurl);

$form=new Form($db);

if ($mode==3) {
	$aviso = 'Confirme la eliminación de póliza';
	$question = '¿Está seguro de eliminar la(s) póliza(s) seleccionada(s)?';
	$formconfirm=$form->form_confirm($_SERVER['PHP_SELF'].'?matriz='.$idsel_del,$aviso,$question,'confirm_delete','',0,1);	
}

if ($result)
{
	$cuenta = new Cuentas($db);  //llama a la clase del objeto

	$num = $db->num_rows($result);
    $i = 0;
	
	//Parametros para las busquedas de mas de una pagina
    $param='';
    if ($search_codigo)       	$param.='&search_codigo=' .$search_codigo;
    if ($search_nombre)    		$param.='&search_nombre=' .$search_nombre;
    if ($search_tipocta)     	$param.='&search_tipocta=' .$search_tipocta;
    if ($search_nivel)     		$param.='&search_nivel=' .$search_nivel;
    if ($search_nat)   			$param.='&search_nat='.$search_nat;
	if ($search_datei) 			$param.='&search_datei='.$search_datei;
	if ($search_datef)			$param.='&search_datef='.$search_datef;	
    if ($search_padre) 		  	$param.='&search_padre='.$search_padre;
    if ($search_afecta)		  	$param.='&search_afecta='.$search_afecta;	
//	if ($cuadre)				$param.='&cuadre='.$cuadre;	

    print '<form method="post" name="formlist" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="view" value="'.$view.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" id="cancel_filter" name="cancel_filter" value="">';		
	print '<input type="hidden" id="mode" name="mode" value="">';
	if ($cuadre==0) print '<input type="hidden" id="cuadre" name="cuadre" value="0">';	
	else print '<input type="hidden" id="cuadre" name="cuadre" value="1">';	
	
//botones de accion
/*    print "\n".'<div>'."\n";
    print '<input type="image" value="add" onClick="sub_mode(1)" src="'.DOL_URL_ROOT.'/media/imgs/interface/add-1b.png" name="add" id="add" title="'.dol_escape_htmltag($langs->trans("Agregar Cuenta")).'">';	
    //print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="image" value="delete" onClick="sub_mode(2)" src="'.DOL_URL_ROOT.'/media/imgs/interface/garbage-2c.png" name="button_delete" id="delete" title="'.dol_escape_htmltag($langs->trans("Eliminar Cuenta")).'">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
	print '<input type="image" value="button_search" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-2c.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';
    print '<input type="image" value="button_removefilter"onClick="sub_mode(3)" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-cancel-2a.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="image" value="cdre" onClick="sub_mode(11)" src="'.DOL_URL_ROOT.'/media/imgs/interface/exclamation.png" name="cdre" id="cdre" title="Mostrar inconcistencias en las cuentas"))">';
	print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
    print '<input type="image" value="xls" onClick="sub_mode(10)" src="'.DOL_URL_ROOT.'/media/imgs/interface/xls.png" name="xls" id="xls" title="Exportar a Excel"))">';
//   	if ($mode==10 && $exportaxls) print '<input width="31" type="image" value="dwn" onClick="sub_mode(11)" src="'.DOL_URL_ROOT.'/media/imgs/interface/download.png" name="dwn" id="dwn" title="Exportar a Excel"))">';	
//	if ($mode==10 && $exportaxls) {
//		print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=export&file='.$filename.'.xlsx"><img width="31" src="'.DOL_URL_ROOT.'/media/imgs/interface/download.png" name="dwnld" title="Descargar archivo">'; 
//		$mode==1;
//	}
    print "</div>";*/
		
	
//
    print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);
    print "\n".'<div>'."\n"; //botones de accion
    print '<input type="button" class="butAction" name="add" id="add" value="Agregar" onClick="sub_mode(1)">';
    print '<input type="button" class="butActionDelete" name="button_delete" id="delete" value="Eliminar" onClick="sub_mode(2)">';
    print '<table class="liste" width="100%">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="submit" class="butAction" name="button_search" id="button_search" value="Filtrar">';
    print '<input type="button" class="butAction" name="button_removefilter" id="button_removefilter" value="Quitar Filtro" onClick="sub_mode(3)">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="button" class="butAction" name="xls" id="xls" value="Exportar a Excel" onClick="sub_mode(10)">';
    print "</div>";
    // Ligne des titres
    print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
    print_liste_field_titre($langs->trans("Codigo"),$_SERVER["PHP_SELF"],"c.codigo", $begin, $param, '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Cuenta"),$_SERVER["PHP_SELF"],"c.nombre", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print '<td class="fieldrequired" align="center">Saldo Deudor</td>';
	print '<td class="fieldrequired" align="center">Saldo Acreed.</td>';	
    print_liste_field_titre($langs->trans("Tipo"),$_SERVER["PHP_SELF"],"tc.label", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Nivel"),$_SERVER["PHP_SELF"],"nc.abrev", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Nat"),$_SERVER["PHP_SELF"],"c.nat", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Afectable"),$_SERVER["PHP_SELF"],"c.afectable", $begin, $param, 'align="center"', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("F. Alta"),$_SERVER["PHP_SELF"],"c.FechaRegistro", $begin, $param, '', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Subcuenta de"),$_SERVER["PHP_SELF"],"sc.codigo", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print '<td class="fieldrequired" align="center">Movtos.</td>';
    print "</tr>\n";

    // Ligne des champs de filtres
    print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="center"><input type="checkbox" name="checkall" id="checkall"></td>';	//ALL/NONE
    print '<td class="liste_titre"><input class="flat" type="text" name="search_codigo" size="8" value="'.$search_codigo.'"></td>';
    print '<td class="liste_titre"><input class="flat" type="text" name="search_nombre" size="16" value="'.$search_nombre.'"></td>';	
	print '<td class="liste_titre" colspan="2">&nbsp;</td>';
	print '<td class="liste_titre" align="center">';
	print selectTipoCta($search_tipocta,'search_tipocta');
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print selectNivelCta($search_nivel,'search_nivel');
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print select_simple('search_nat',$search_nat,array(1,-1),array('Deudora','Acreedora'),2,-1);
	print '</td>';
	print '<td class="liste_titre" align="center">';
	print select_simple('search_afecta',$search_afecta,array(-1,1),array('No','Si'),2,-1);
	print '</td>';
	print '<td class="liste_titre" align="center">De ';	
	print $form->select_date($search_datei,'rei','','',1,'',1);
	print 'Al ';
	print $form->select_date($search_datef,'ref','','',1,'',1);	
	print '</td>';
    print '<td class="liste_titre"><input class="flat" type="text" name="search_padre" size="20" value="'.$search_padre.'"></td>';
	print '<td class="liste_titre" colspan="2">&nbsp;</td>';
//    print '&nbsp; ';
//    print '</td>';
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
		print '<td valign="middle">';
		$cuenta->id=$obj->rowid;
		$cuenta->ref=$obj->codigo;
		print $cuenta->getNomUrl(1,'',10);
		print '</td>';
		print '<td align="left" nowrap="nowrap">'.dol_trunc($obj->nombre,30)."</td>";
		if ($obj->nat==1) {
			print '<td align="right">'."$".number_format($obj->saldo,2).'</td>';
			print '<td>&nbsp;</td>';
			$debe = $debe + $obj->saldo;
		}
		else {
			print '<td>&nbsp;</td>';		
			print '<td align="right">'."$".number_format($obj->saldo,2).'</td>';
			$haber = $haber + $obj->saldo;
		}
		print '<td align="center">'.$obj->tipocta.'</td>';
		print '<td align="center">'.$obj->nivelcta.'</td>';	
		print '<td align="center">'.($obj->nat==1?"Deudora":"Acreedora").'</td>';
		print '<td align="center">'.($obj->afectable==1?"Si":"No").'</td>';	
		print '<td align="center" nowrap="nowrap">'.$obj->FechaRegistro."</td>";
        print '<td align="left" nowrap="nowrap">'.dol_trunc($obj->codpadre." ".$obj->nompadre,36)."</td>";
		$movs = getNxusMov($obj->rowid,'IdCuenta','contab_polizas_movs',array('Ejercicio'),array($conf->global->FISCAL_YEAR),'/contab/polizas/listmov.php','search_codigo',$obj->codigo);
		if ($movs) print '<td align="center">'.$movs.'</td>';
		else print '<td>&nbsp;</td>';
        print "</tr>\n";

        $i++;
    }
	
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="2">&nbsp;</td>';
	print '<td class="liste_total" align="right">'."SUMAS".'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($debe,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($haber,2).'</td>';
	print '<td class="liste_total" colspan="7">&nbsp;</td>';

	print "</tr>\n";

    print "</table>";

    print '</form>';	//fin del formulario
	
	print '<br>';

    print print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

    $db->free($result);
	
}
else
{
    dol_print_error($db);
}
/*
$formfile = new FormFile($db);
	print '</table>';

    print '<table width="100%"><tr><td width="50%">';

    if (! is_dir($conf->export->dir_temp)) dol_mkdir($conf->export->dir_temp);
	$upload_dir = DOL_DATA_ROOT.'/export/temp/1/';

    // Affiche liste des documents
    // NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
    $formfile->show_documents('export','',$upload_dir,$_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport,$liste,1,(! empty($_POST['model'])?$_POST['model']:'csv'),1,1);

    print '</td><td width="50%">&nbsp;</td></tr>';
    print '</table>';
*/
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
		if (mode == '11') {
			document.getElementById("cuadre").value = 1;
			document.formlist.submit();
		}									
	}	
   
</script>