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
require_once DOL_DOCUMENT_ROOT.'/nxus/core/lib/nxos.excel.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/nxus/core/lib/nxos.lib.php';

$contab = new Contabilidad($db);

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
    $search_nat="";
    $search_nivel="";
    $search_afecta="";			
	$sortorder="ASC";
	$sortfield="c.codigo";
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

$idsel_del = serialize($idsel);
$idsel_del = urlencode($idsel_del);

/*
 * View
 */
//				  0			1		2				 3					 4				5		 6			7
$sql = "SELECT c.rowid, c.codigo, c.nombre, tc.label as tipocta, nc.abrev as nivelcta, c.nat, c.afectable, c.saldo";
$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_tiposcta as tc ON tc.rowid = c.fk_tipocta";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_nivelcta as nc ON nc.rowid = c.fk_nivel";
$sql.= " WHERE c.rowid IS NOT NULL";

if ($search_codigo) $sql .= " AND c.codigo LIKE '%".$db->escape($search_codigo)."%'";
if ($search_nombre) $sql .= " AND c.nombre LIKE '%".$db->escape($search_nombre)."%'";
if ($search_tipocta) $sql .= " AND tc.rowid LIKE '%".$db->escape($search_tipocta)."%'";
if ($search_nivel) $sql .= " AND nc.rowid LIKE '%".$db->escape($search_nivel)."%'";
if ($search_nat) $sql .= " AND c.nat LIKE '%".$db->escape($search_nat)."%'";
if ($search_afecta==1) $sql .= " AND c.afectable LIKE '1'";
if ($search_afecta==-1) $sql .= " AND c.afectable LIKE '0'";
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
    $sql.= " ORDER BY c.codigo ASC ";
}
else
{
    $sql.= " ORDER BY $sortfield $sortorder ";
}
//print $sql;
$result = $db->query($sql);

if($mode==10) {		//se va a exportar a excel la consulta
	$titles_col = null;
	$fields_hide = null;
	$report_name = "Balanza de Comprobación";
	$filename = "Balanza_comprobacion";
	$stat_cols = null;	//columnas donde se sacarn las estadisticas	
	$exportaxls = exportBalanzaXls($sql,$fields_hide,$titles_col,$report_name,$filename,NULL,$stat_cols);
	//print $sql2;
}

$titre = $langs->trans("Balanza de Comprobación");
if (! empty($text)) $titre.= " $text";

$title = $langs->trans("Balanza de Comprobación");
llxHeader("",$langs->trans("Balanza de Comprobación"),$helpurl);

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
	print "\n".'<div>'."\n";
    print '<input type="image" value="add" onClick="sub_mode(1)" src="'.DOL_URL_ROOT.'/media/imgs/interface/add-1b.png" name="add" id="add" title="'.dol_escape_htmltag($langs->trans("Agregar Cuenta")).'">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<input type="image" value="delete" onClick="sub_mode(2)" src="'.DOL_URL_ROOT.'/media/imgs/interface/garbage-2c.png" name="button_delete" id="delete" title="'.dol_escape_htmltag($langs->trans("Eliminar Cuenta")).'">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
	print '<input type="image" value="button_search" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-2c.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';
    print '<input type="image" value="button_removefilter"onClick="sub_mode(3)" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-cancel-2a.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
//    print '<input type="image" value="cdre" onClick="sub_mode(11)" src="'.DOL_URL_ROOT.'/media/imgs/interface/exclamation.png" name="cdre" id="cdre" title="Mostrar inconcistencias en las cuentas"))">';
	print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
    print '<input type="image" value="xls" onClick="sub_mode(10)" src="'.DOL_URL_ROOT.'/media/imgs/interface/xls.png" name="xls" id="xls" title="Exportar a Excel"))">';
		
	print "</div>";
//
    print_barre_liste($titre, '', $_SERVER["PHP_SELF"]);
    print '<table class="liste" width="100%">';
    // Ligne des titres
    print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
    print_liste_field_titre($langs->trans("Codigo"),$_SERVER["PHP_SELF"],"c.codigo", $begin, $param, '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Cuenta"),$_SERVER["PHP_SELF"],"c.nombre", $begin, $param, 'align="center"', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Tipo"),$_SERVER["PHP_SELF"],"tc.label", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Nivel"),$_SERVER["PHP_SELF"],"nc.abrev", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Nat"),$_SERVER["PHP_SELF"],"c.nat", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Afectable"),$_SERVER["PHP_SELF"],"c.afectable", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print '<td class="fieldrequired" align="center" colspan="2">Saldo Inic. Periodo</td>';
	print '<td class="fieldrequired" align="center" colspan="2">Movimientos Periodo</td>';
	print '<td class="fieldrequired" align="center" colspan="2">Saldo final Periodo</td>';
	print '<td class="fieldrequired" align="center" colspan="2">Movimientos del Ejercicio</td>';
	print '<td class="fieldrequired" align="center" colspan="2">Saldos</td>';
	print '<td class="fieldrequired" align="center">Movtos.</td>';
    print "</tr>\n";

    // Ligne des champs de filtres
    print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="center"><input type="checkbox" name="checkall" id="checkall"></td>';	//ALL/NONE
    print '<td class="liste_titre"><input class="flat" type="text" name="search_codigo" size="8" value="'.$search_codigo.'"></td>';
    print '<td class="liste_titre"><input class="flat" type="text" name="search_nombre" size="16" value="'.$search_nombre.'"></td>';	
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
	print '<td class="fieldrequired" align="center">Deudor</td>';
	print '<td class="fieldrequired" align="center">Acreedor</td>';	
	print '<td class="fieldrequired" align="center">Deudor</td>';
	print '<td class="fieldrequired" align="center">Acreedor</td>';	
	print '<td class="fieldrequired" align="center">Deudor</td>';
	print '<td class="fieldrequired" align="center">Acreedor</td>';
	print '<td class="fieldrequired" align="center">Deudor</td>';
	print '<td class="fieldrequired" align="center">Acreedor</td>';	
	print '<td class="fieldrequired" align="center">Deudor</td>';
	print '<td class="fieldrequired" align="center">Acreedor</td>';
	print '<td class="liste_titre" colspan="2">&nbsp;</td>';
//    print '&nbsp; ';
//    print '</td>';
    print '</tr>';

    $var=True;

	$debe = 0; $haber = 0;
	$suma_debe = 0;	$suma_haber = 0;
	$suma_debep = 0; $suma_haberp = 0;	
	$suma_salinid = 0; $suma_salinia = 0;
	$suma_salfind = 0; $suma_salfina= 0;
	$suma_sald = 0; $suma_sala= 0;	

    while ($i < $db->num_rows($result))
    {
        $obj = $db->fetch_object($result);

        $var=!$var;

		$movs = getNxusMov($obj->rowid,'IdCuenta','contab_polizas_movs',array('Ejercicio'),array($conf->global->FISCAL_YEAR),'/contab/polizas/listmov.php','search_codigo',$obj->codigo);
	if ($movs) {
        print "<tr $bc[$var]>";
		
	//Calculamos los movimientos de la cuenta por ejercicio
		$debe_ej=0;	$haber_ej=0; $debe_p=0; $haber_p=0; $salini = 0; $salfin = 0; $totalcap=0; $salinip = 0; $salfinp=0;
		$ejein = $conf->global->FISCAL_YEAR_IN;
		$ejefin = $conf->global->FISCAL_YEAR;
		if (!$periodo) $periodo = $conf->global->PERIOD_CONTAB;	//si no se especifico el periodo, entonces el periodo se estable el actual
		//Ejercicio
		$range = $ejefin-$ejein+1;
		for ($j=0; $j<$range; $j++) {
			$ejeact = $ejein+$j;
			$debehaber=$contab->getCargosAbonos($obj->rowid,null,$ejeact);
			if ($debehaber) {
				$debe_ej = $debehaber[0][1];
				$haber_ej = $debehaber[0][2];
				$suma_debe = $suma_debe + $debe_ej;
				$suma_haber = $suma_haber + $haber_ej;
			}
			if ($obj->nat==1) $salfin = $salini + $debehaber[0][1]-$debehaber[0][2];	//saldo deudor
			else $salfin = $salini + $debehaber[0][2]-$debehaber[0][1];	//saldo acreedor
			$salini = $salfin;
		}
		//Periodo		
		//calculamos el saldo inicial del periodo
		$periodo_ant = $periodo - 1;
		$ejercicio_ant = $ejefin;
		if ($periodo_ant < 0) {
			$periodo_ant = 12;
			$ejercicio_ant = $ejercicio - 1;
		}
			//calculamos el saldo final del periodo anterior
		$saldos_ant = $contab->getSaldo($obj->rowid,$ejercicio_ant,$periodo_ant);
		if ($saldos_ant) $salinip = $saldos_ant[0][1];
		else $salinip = 0.0000;
		if(!$salinip) $salinip = 0;
		//fin del calculo inicial del periodo
		
		//calculamos los cargos y abonos del periodo
		$totalcap = $contab->getCargosAbonos($obj->rowid,$periodo,$ejeact);
		if ($totalcap) {
			$debe_p = $totalcap[0][1];
			$haber_p = $totalcap[0][2];
			$suma_debep = $suma_debep + $debe_p;
			$suma_haberp = $suma_haberp + $haber_p;
		}
		// fin del calculo de los cargos y abonos del periodo
		
		//calculamos el saldo final del periodo
		if ($obj->nat==1) $salfinp = $salinip + $debe_p - $haber_p;
		else $salfinp = $salinip + $haber_p - $debe_p;
	//
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
		print '<td align="center">'.$obj->tipocta.'</td>';
		print '<td align="center">'.$obj->nivelcta.'</td>';	
		print '<td align="center">'.($obj->nat==1?"Deudora":"Acreedora").'</td>';
		print '<td align="center">'.($obj->afectable==1?"Si":"No").'</td>';	
		if ($obj->nat==1) {
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salinip,2).'</td>';
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';
			$suma_salinid = $suma_salinid + $salinip;
		}
		else {
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';		
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salinip,2).'</td>';
			$suma_salinia = $suma_salinia + $salinip;
		}
		print '<td align="right">'."$".number_format($debe_p,2).'</td>'; //debe ejercicio
		print '<td align="right">'."$".number_format($haber_p,2).'</td>'; //haber ejercicio
		if ($obj->nat==1) {
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salfinp,2).'</td>'; //debe ejercicio
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';
			$suma_salfind=$suma_salfind+$salfinp;
		}
		else {
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salfinp,2).'</td>'; //haber ejercicio
			$suma_salfina=$suma_salfina+$salfinp;
		}
		print '<td align="right">'."$".number_format($debe_ej,2).'</td>'; //debe ejercicio
		print '<td align="right">'."$".number_format($haber_ej,2).'</td>'; //haber ejercicio
		if ($obj->nat==1) {
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salfin,2).'</td>'; //debe ejercicio
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';
			$suma_sald=$suma_sald+$salfin;
		}
		else {
			print '<td bgcolor="#A7EBEF">&nbsp;</td>';
			print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($salfin,2).'</td>'; //haber ejercicio
			$suma_sala=$suma_sala+$salfin;
		}
		$dif = $obj->saldo - $salfin;
//		if ($dif > 0.0001) print '<td bgcolor="#E4D509"align="center">'.number_format($dif,2).'</td>';
//		else print '<td>&nbsp;</td>';
		if ($movs) print '<td align="center">'.$movs.'</td>';
		else print '<td>&nbsp;</td>';
        print "</tr>\n";
	}
        $i++;
    }
	
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="6">&nbsp;</td>';
	print '<td class="liste_total" align="right">'."SUMAS".'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_salinid,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_salinia,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_debep,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_haberp,2).'</td>';	
	print '<td class="liste_total" align="right">'."$".number_format($suma_salfind,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_salfina,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_debe,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_haber,2).'</td>';		
	print '<td class="liste_total" align="right">'."$".number_format($suma_sald,2).'</td>';
	print '<td class="liste_total" align="right">'."$".number_format($suma_sala,2).'</td>';	
	print '<td class="liste_total">&nbsp;</td>';

	print "</tr>\n";

    print "</table>";

    print '</form>';	//fin del formulario
	
	print '<br>';

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