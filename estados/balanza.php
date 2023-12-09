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
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/commoncontab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/nxus/core/lib/nxos.excel.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/nxus/core/lib/nxos.lib.php';

$form=new Form($db);
$contab = new Contabilidad($db);
$contab_com = new CommonContab($db);

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
if ($_POST['reimonth'] && $_POST['reiday'] && $_POST['reiyear'] && $_POST['reimonth'] && $_POST['reiday'] && $_POST['reiyear']) {
	$search_datei=dol_mktime(12,0,0,$_POST['reimonth'],$_POST['reiday'],$_POST['reiyear']);
	$search_datef=dol_mktime(12,0,0,$_POST['refmonth'],$_POST['refday'],$_POST['refyear']);
	$search_datei=strftime("%F",strtotime($db->idate($search_datei)));
	$search_datef=strftime("%F",strtotime($db->idate($search_datef)));
}
$mode = GETPOST('mode','int');		//valor del boton de accion seleccionado
$ejercicio = GETPOST('idejercicio');
$periodo = GETPOST('idperiodo');
$ejer = GETPOST('ejer');
$per = GETPOST('per');

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

if($mode==10) {		//se va a exportar a excel la consulta
	$titles_col = null;
	$fields_hide = null;
	$report_name = "Balanza de Comprobación";
	$filename = "Balanza_comprobacion";
	$stat_cols = null;	//columnas donde se sacarn las estadisticas	
	$exportaxls = exportBalanzaXls($ejer, $per, $fields_hide,$titles_col,$report_name,$filename,NULL,$stat_cols);
	//print $sql2;
}

$titre = $langs->trans("Balanza de Comprobación");
if (! empty($text)) $titre.= " $text";

$title = $langs->trans("Balanza de Comprobación");
llxHeader("",$langs->trans("Balanza de Comprobación"),$helpurl);

//Ventana emergente para seleccionar el periodo
if ($mode==3) {
	$aviso = 'Seleccione el Ejercicio y el Periodo';
	$formquestion = array(								
							array('type' => 'other','name' => 'idejercicio','label' => 'Ejercicio','value' => selectEjercicio($conf->global->FISCAL_YEAR)),
							array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'other','name' => 'idperiodo','label' => 'Periodo','value' => selectPeriodo($conf->global->PERIOD_CONTAB))
							);
$formconfirm = $form->form_confirm2($_SERVER['PHP_SELF'] . '?ejercicio=idejercicio&periodo=idperiodo&mode=1', $aviso, $text, 'add_mov', $formquestion, "yes", 1,210,300);
}

if ($mode==1) {

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
        print '<input type="hidden" name="ejer" value="'.$ejercicio.'">';
        print '<input type="hidden" name="per" value="'.$periodo.'">';
        if ($cuadre==0) print '<input type="hidden" id="cuadre" name="cuadre" value="0">';	
        else print '<input type="hidden" id="cuadre" name="cuadre" value="1">';	

    //botones de accion
        //print "\n".'<div>'."\n";
    /*    print '<input type="image" value="add" onClick="sub_mode(1)" src="'.DOL_URL_ROOT.'/media/imgs/interface/add-1b.png" name="add" id="add" title="'.dol_escape_htmltag($langs->trans("Agregar Cuenta")).'">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<input type="image" value="delete" onClick="sub_mode(2)" src="'.DOL_URL_ROOT.'/media/imgs/interface/garbage-2c.png" name="button_delete" id="delete" title="'.dol_escape_htmltag($langs->trans("Eliminar Cuenta")).'">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			*/
/*        print '<input type="image" value="button_search" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-2c.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';
        print '<input type="image" value="button_removefilter"onClick="sub_mode(3)" src="'.DOL_URL_ROOT.'/media/imgs/interface/zoom-cancel-2a.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
    //    print '<input type="image" value="cdre" onClick="sub_mode(11)" src="'.DOL_URL_ROOT.'/media/imgs/interface/exclamation.png" name="cdre" id="cdre" title="Mostrar inconcistencias en las cuentas"))">';
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
        print '<input type="image" value="xls" onClick="sub_mode(10)" src="'.DOL_URL_ROOT.'/media/imgs/interface/xls.png" name="xls" id="xls" title="Exportar a Excel"))">';
        print "</div>";*/
    //
        print_barre_liste($titre, $page, $_SERVER["PHP_SELF"],'','','','','','');
        print "\n".'<div>'."\n"; //botones de accion
        print '<input type="submit" class="butAction" name="button_search" id="button_search" value="Filtrar">';
        print '<input type="button" class="butAction" name="button_removefilter" id="button_removefilter" value="Quitar Filtro" onClick="sub_mode(3)">';
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<input type="button" class="butAction" name="xls" id="xls" value="Exportar a Excel" onClick="sub_mode(10)">';
        print "</div>";
        print '<table class="liste" width="100%">';
        // Ligne des titres
        print '<tr><td colspan="2">Ejercicio: '.$ejercicio.'</td><td>Periodo: '.selectPeriodo($periodo,'',1).'</td></tr>';
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
        print '<td class="fieldrequired" align="center" colspan="2">Saldo Final Periodo</td>';
        print '<td class="fieldrequired" align="center" colspan="2">Saldos Inic. Ejercicio</td>';
        print '<td class="fieldrequired" align="center" colspan="2">Movimientos del Ejercicio</td>';
        print '<td class="fieldrequired" align="center" colspan="2">Saldos Final Ejercicio</td>';
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
        print '<td class="fieldrequired" align="center">Deudor</td>';
        print '<td class="fieldrequired" align="center">Acreedor</td>';
        print '<td class="liste_titre" colspan="2">&nbsp;</td>';
    //    print '&nbsp; ';
    //    print '</td>';
        print '</tr>';

        $var=True;

        $suma_salinid = 0; $suma_salinia = 0;
        $suma_debe = 0;	$suma_haber = 0;
        $suma_salfind = 0; $suma_salfina= 0;
        $suma_salinide = 0; $suma_saliniae= 0;
        $suma_debe_ej = 0; $suma_haber_ej = 0;	
        $suma_sald = 0; $suma_sala= 0;	

        while ($i < $db->num_rows($result))
        {
            $obj = $db->fetch_object($result);

            $var=!$var;

            $movs = getNxusMov($obj->rowid,'IdCuenta','contab_polizas_movs',array('Ejercicio'),array($ejercicio),'/custom/contab/polizas/listmov.php','search_codigo',$obj->codigo);
            if ($movs) {
                print "<tr $bc[$var]>";

            //Calculamos los movimientos de la cuenta por ejercicio
                $totalcap=0;

                //Obtenemos los saldos, cargos y abonos de la cuenta
                $saldos = $contab->getSaldo($obj->rowid,$ejercicio,$periodo);
                $debe_ej = $saldos[2] + $saldos[4];
                $haber_ej = $saldos[3] + $saldos[5];

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
                    print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($saldos[10],2).'</td>';
                    print '<td bgcolor="#A7EBEF">&nbsp;</td>';
                    $suma_salinid = $suma_salinid + $saldos[10];
                }
                else {
                    print '<td bgcolor="#A7EBEF">&nbsp;</td>';		
                    print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($saldos[10],2).'</td>';
                    $suma_salinia = $suma_salinia + $saldos[10];
                }
                print '<td align="right">'."$".number_format($saldos[4],2).'</td>'; //debe ejercicio
                print '<td align="right">'."$".number_format($saldos[5],2).'</td>'; //haber ejercicio
                $suma_debe = $suma_debe + $saldos[4];
                $suma_haber = $suma_haber + $saldos[5];

                if ($obj->nat==1) {
                    print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($saldos[11],2).'</td>'; //debe ejercicio
                    print '<td bgcolor="#A7EBEF">&nbsp;</td>';
                    $suma_salfind=$suma_salfind+$saldos[11];
                }
                else {
                    print '<td bgcolor="#A7EBEF">&nbsp;</td>';
                    print '<td align="right" bgcolor="#A7EBEF">'."$".number_format($saldos[11],2).'</td>'; //haber ejercicio
                    $suma_salfina=$suma_salfina+$saldos[11];
                }
                if ($obj->nat==1) {
                    print '<td align="right" bgcolor="#F79985">'."$".number_format($saldos[8],2).'</td>'; //debe ejercicio
                    print '<td bgcolor="#F79985">&nbsp;</td>';
                    $suma_salinide=$suma_salinide+$saldos[8];
                }
                else {
                    print '<td bgcolor="#F79985">&nbsp;</td>';
                    print '<td align="right" bgcolor="#F79985">'."$".number_format($saldos[8],2).'</td>'; //haber ejercicio
                    $suma_saliniae=$suma_saliniae+$saldos[8];
                }
                print '<td align="right">'."$".number_format($debe_ej,2).'</td>'; //debe ejercicio
                print '<td align="right">'."$".number_format($haber_ej,2).'</td>'; //haber ejercicio
                $suma_debe_ej = $suma_debe_ej + $debe_ej;
                $suma_haber_ej = $suma_haber_ej + $haber_ej;

                if ($obj->nat==1) {
                    print '<td align="right" bgcolor="#F79985">'."$".number_format($saldos[11],2).'</td>'; //debe ejercicio
                    print '<td bgcolor="#F79985">&nbsp;</td>';
                    $suma_sald=$suma_sald+$saldos[11];
                }
                else {
                    print '<td bgcolor="#F79985">&nbsp;</td>';
                    print '<td align="right" bgcolor="#F79985">'."$".number_format($saldos[11],2).'</td>'; //haber ejercicio
                    $suma_sala=$suma_sala+$saldos[11];
                }
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
        print '<td class="liste_total" align="right">'."$".number_format($suma_debe,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_haber,2).'</td>';	
        print '<td class="liste_total" align="right">'."$".number_format($suma_salfind,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_salfina,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_salinide,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_saliniae,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_debe_ej,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_haber_ej,2).'</td>';		
        print '<td class="liste_total" align="right">'."$".number_format($suma_sald,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($suma_sala,2).'</td>';	
        print '<td class="liste_total">&nbsp;</td>';

        print "</tr>\n";

        print "</table>";

        print '</form>';	//fin del formulario

        print '<br>';

        $db->free($result);

    }
    else {
        dol_print_error($db);
    }
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