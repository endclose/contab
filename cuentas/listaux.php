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

$search_codpadre=GETPOST("search_codpadre", 'alpha');
$search_nompadre=GETPOST("search_nompadre", 'alpha');
$search_codigo=GETPOST("search_codigo", 'alpha');
$search_nombre=GETPOST("search_nombre", 'alpha');
$search_folio=GETPOST("search_folio", 'int');
$search_tipopol=GETPOST("search_tipopol", 'int');
$search_periodo=GETPOST("search_periodo", 'int');
$search_ejercicio=GETPOST("search_ejercicio", 'int');
$search_concepto=GETPOST("search_concepto", 'alpha');
$search_referencia=GETPOST("search_referencia", 'alpha');
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

//$sortfield = GETPOST('sortfield', 'alpha');
//$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$userid=GETPOST('userid','int');
$begin=GETPOST('begin');

$cancel_filter=GETPOST('cancel_filter');
$exportexcel=GETPOST('exportexcel');

$form=new Form($db);

if ($page < 0) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;

$pageprev = $page - 1;
$pagenext = $page + 1;

if ($cancel_filter)
{
    $search_codpadre="";
    $search_nompadre="";
    $search_codigo="";
    $search_nombre="";
    $search_folio="";
    $search_tipopol="";	
    $search_datei="";
    $search_datef="";	
    $search_periodo="";
    $search_ejercicio="";
    $search_concepto="";		
    $search_referencia="";		
    $mode=1;
}
if ($search_priv < 0) $search_priv='';

if ($mode==2) {
	header("Location: ".DOL_URL_ROOT."/contab/cuentas/card.php?mode=2");
	exit;
}

if (GETPOST('idejercicio')) $search_ejercicio = GETPOST('idejercicio');
if (GETPOST('idperiodo')) $search_periodo = GETPOST('idperiodo');

if (!$search_nompadre && !$search_codpadre && !$search_codigo && !$search_nombre) $nosql = true;
//print var_dump($nosql,$mode, $exportexcel);


$idsel_del = serialize($idsel);
$idsel_del = urlencode($idsel_del);

/* Construimos  la consulta SQL */
            //				0						1					2		3
    $sql = "SELECT ccp.codigo AS codpadre, ccp.nombre AS nompadre, cc.codigo, cc.nombre";
    //			4					5			6				7				8			9				10			11			12			13
    $sql.= ", mp.Folio, tp.label as tipopol, mp.Fecha, p.month as periodo, mp.Ejercicio, mp.Concepto, mp.Referencia, mp.Importe, mp.saldo, mp.TipoMovto";
    //				14						15						16				17		  18
    $sql.= ", ccp.rowid as idpadre, cc.rowid as idcuenta, po.rowid as idpoliza, mp.Periodo, cc.nat";
    $sql.= " FROM (".MAIN_DB_PREFIX."contab_cuentas as cc INNER JOIN (".MAIN_DB_PREFIX."contab_cuentas as ccp INNER JOIN ".MAIN_DB_PREFIX."contab_ctanxus as cn ON ccp.rowid = cn.fk_padre) ON cc.rowid = cn.fk_hijo)";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."contab_polizas_movs as mp ON cc.rowid = mp.IdCuenta";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_tipospol AS tp ON mp.TipoPol = tp.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_periodos AS p ON mp.Periodo = p.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_polizas AS po ON mp.IdPoliza = po.rowid";
    $sql.= " WHERE ccp.CtaMayor<>3";

    if ($search_codpadre) $sql .= " AND ccp.codigo LIKE '%".$db->escape($search_codpadre)."%'";
    if ($search_nompadre) $sql .= " AND ccp.nombre LIKE '%".$db->escape($search_nompadre)."%'";
    if ($search_codigo) $sql .= " AND cc.codigo LIKE '%".$db->escape($search_codigo)."%'";
    if ($search_nombre) $sql .= " AND cc.nombre LIKE '%".$db->escape($search_nombre)."%'";
    if ($search_folio) $sql .= " AND mp.Folio LIKE '%".$db->escape($search_folio)."%'";
    if ($search_tipopol) $sql .= " AND mp.TipoPol LIKE '%".$db->escape($search_tipopol)."%'";
    if ($search_periodo) $sql .= " AND mp.Periodo LIKE '%".$db->escape($search_periodo)."%'";
    if ($search_ejercicio) $sql .= " AND mp.Ejercicio LIKE '%".$db->escape($search_ejercicio)."%'";
    if ($search_concepto) $sql .= " AND mp.Concepto LIKE '%".$db->escape($search_concepto)."%'";
    if ($search_referencia) $sql .= " AND mp.Referencia LIKE '%".$db->escape($search_referencia)."%'";
    if ($search_datei && $search_datef) $sql .= " AND mp.Fecha >= '".$db->escape($search_datei)."' AND mp.Fecha <= '".$db->escape($search_datef)."'";

    // Add order and limit
    //	$sql.= " ORDER BY $sortfield $sortorder ";
    $sql.= " ORDER BY ccp.codigo, cc.codigo, mp.Ejercicio, mp.Periodo, mp.Fecha, mp.TipoPol, mp.Folio ASC";
    $sql2 = $sql;	//esta consulta no tiene limites es la completa
    $sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
    //print $sql;
// FIN DE LA CONSTRUCCIÓN DE LA CONSULTA SQL

    if($exportexcel) {		//se va a exportar a excel la consulta
        $titles_col = array('Cod. Sup.','Nombre Sup.','Cod. Subcta.','Nombre Subcta.','Folio','Tipo','Fecha','Periodo','Ejercicio','Concepto','Referencia','Cargos','Abonos','Saldo');
        $fields_hide = array(0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1);
        $report_name = "Auxiliar de Cuentas";
        $filename = "Auxiliar_cuentas";
        $stat_cols = array(13,14);	//columnas donde se sacarn las estadisticas	
        $sep_ca = array(13,11);	//separa ek cargo y abono dependiendo del tipo de movimiento (tipomovto, importe)	
        $exportaxls = exportSQLXls($sql2,$fields_hide,$titles_col,$report_name,$filename,$sep_ca, $stat_cols);
        //print $sql2;
    }  

/* Comienza la interfase */

$title = $langs->trans("Listado de Auxiliar de Cuentas");
if (! empty($text)) $title.= " $text";
llxHeader("",$langs->trans("Auxiliar de Cuentas"),$helpurl);

//function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0, $height=170, $width=500)
//{
//    print $this->formconfirm($page, $title, $question, $action, $formquestion, $selectedchoice, $useajax, $height, $width);
//}
//Ventana emergente para seleccionar el periodo
if ($mode==3) {
	$aviso = 'Seleccione los filtros de búsqueda';
	$formquestion = array(	
                            array('type' => 'text','name' => 'search_codpadre','label' => 'Cuenta Mayor','size' => '20','value' => ''),
                            array('type' => 'text','name' => 'search_nompadre','label' => 'Nombre Mayor','size' => '50','value' => ''),
                            array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
                            array('type' => 'text','name' => 'search_codigo','label' => 'Subcuenta','size' => '20','value' => ''),
                            array('type' => 'text','name' => 'search_nombre','label' => 'Nombre Subcuenta','size' => '50','value' => ''),
                            array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'other','name' => 'idejercicio','label' => 'Ejercicio','value' => selectEjercicio($conf->global->FISCAL_YEAR)),
							array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'other','name' => 'idperiodo','label' => 'Periodo','value' => selectPeriodo($conf->global->PERIOD_CONTAB))
							);
    $formconfirm = $form->form_confirm($_SERVER['PHP_SELF'] . '?mode=1', $aviso, $text, 'set_filters', $formquestion, "yes", 1,300,500);
}
if ($mode==12) {
    $aviso = 'Actualizar saldos de cuentas';
    $question = 'Este proceso actualizará los saldos de las cuentas en base a los movimientos de las pólizas<br>
                y tardará varios minutos. ¿Está seguro de continuar con el proceso?';
    $formconfirm=$form->form_confirm($_SERVER['PHP_SELF'].'?matriz='.$idsel_del,$aviso,$question,'confirm_saldos','',0,1);	
}

/*
 * View
 */
if ($mode==1 || empty($mode)) {
   
//Formulario de  la interfase
    print '<form method="post" name="formlist" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="view" value="'.$view.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" id="cancel_filter" name="cancel_filter" value="">';	
    print '<input type="hidden" id="exportexcel" name="exportexcel" value="">';
    print '<input type="hidden" id="mode" name="mode" value="">';
    if ($cuadre==0) print '<input type="hidden" id="cuadre" name="cuadre" value="0">';	
    else print '<input type="hidden" id="cuadre" name="cuadre" value="1">';	

    //Parametros para las busquedas de mas de una pagina
    $param='';
    
    if ($search_codpadre)       	$param.='&search_codpadre=' .$search_codpadre;
    if ($search_nompadre)    		$param.='&search_nompadre=' .$search_nompadre;
    if ($search_codigo)  	     	$param.='&search_codigo=' .$search_codigo;
    if ($search_nombre)    			$param.='&search_nombre=' .$search_nombre;
    if ($search_folio)	     		$param.='&search_folio=' .$search_folio;
    if ($search_tipopol)     		$param.='&search_tipopol=' .$search_tipopol;
    if ($search_periodo)   			$param.='&search_periodo=' .$search_periodo;
    if ($search_ejercicio) 			$param.='&search_ejercicio='.$search_ejercicio;
    if ($search_datei) 				$param.='&search_datei='.$search_datei;
    if ($search_datef)				$param.='&search_datef='.$search_datef;	
    if ($search_concepto)	  		$param.='&search_concepto='.$search_concepto;
    if ($search_referencia)	  		$param.='&search_referencia='.$search_referencia;	    

    // Count total nb of records
    $nbtotalofrecords = 0; 
    $result2 = $db->query($sql2);
    $nbtotalofrecords = $db->num_rows($result2);
    $db->free($result2);
    
    
    $result = $db->query($sql); 
    
    if ($result)
    {
        $cuenta = new Cuentas($db);  //llama a la clase del objeto

        $num = $db->num_rows($result);
        $i = 0;

    //botones de accion
/*        print "\n".'<div>'."\n";
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
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';			
        print '<input type="image" value="saldos" onClick="sub_mode(12)" src="'.DOL_URL_ROOT.'/media/imgs/interface/price-tag-16.png" name="saldos" id="saldos" title="Actualizar saldos de cuentas"))">';	
    //   	if ($mode==10 && $exportaxls) print '<input width="31" type="image" value="dwn" onClick="sub_mode(11)" src="'.DOL_URL_ROOT.'/media/imgs/interface/download.png" name="dwn" id="dwn" title="Exportar a Excel"))">';	
    //	if ($mode==10 && $exportaxls) {
    //		print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=export&file='.$filename.'.xlsx"><img width="31" src="'.DOL_URL_ROOT.'/media/imgs/interface/download.png" name="dwnld" title="Descargar archivo">'; 
    //		$mode==1;
    //	}

        print "</div>";*/
    //
        print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords); //print var_dump($param, $num, $nbtotalofrecords);
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
        print '<img src="'.DOL_URL_ROOT.'/media/imgs/interface/substract-2d.png">';	
        print '<input type="button" class="butAction" name="saldos" id="saldos" value="Actualizar Saldos" onClick="sub_mode(12)">';
        print "</div>";
        
        print '<table class="liste" width="100%">';
        // Ligne des titres
        print '<tr class="liste_titre">';
        print '<td class="liste_titre">&nbsp;</td>';
        print '<td class="fieldrequired" align="center">Cod. Sup.</td>';
        print '<td class="fieldrequired" align="center">Nombre Sup.</td>';	
        print '<td class="fieldrequired" align="center">Cod. Subcta.</td>';
        print '<td class="fieldrequired" align="center">Nombre Subcta.</td>';	
        print '<td class="fieldrequired" align="center">Folio</td>';	
        print '<td class="fieldrequired" align="center">Tipo</td>';
        print '<td class="fieldrequired" align="center">Fecha</td>';				
        print '<td class="fieldrequired" align="center">Periodo</td>';	
        print '<td class="fieldrequired" align="center">Ejercicio</td>';	
        print '<td class="fieldrequired" align="center">Concepto</td>';	
        print '<td class="fieldrequired" align="center">Referencia</td>';	
        print '<td class="fieldrequired" align="center">Cargos</td>';
        print '<td class="fieldrequired" align="center">Abonos</td>';
        print '<td class="fieldrequired" align="center">Saldo</td>';
        print "</tr>\n";

        // Ligne des champs de filtres
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" align="center"><input type="checkbox" name="checkall" id="checkall"></td>';	//ALL/NONE
        print '<td class="liste_titre"><input class="flat" type="text" name="search_codpadre" size="8" value="'.$search_codpadre.'"></td>';
        print '<td class="liste_titre"><input class="flat" type="text" name="search_nompadre" size="16" value="'.$search_nompadre.'"></td>';
        print '<td class="liste_titre"><input class="flat" type="text" name="search_codigo" size="8" value="'.$search_codigo.'"></td>';
        print '<td class="liste_titre"><input class="flat" type="text" name="search_nombre" size="16" value="'.$search_nombre.'"></td>';
        print '<td class="liste_titre"><input class="flat" type="text" name="search_folio" align="center" size="8" value="'.$search_folio.'"></td>';	
        print '<td class="liste_titre" align="center">';
        print selectTipoPol($search_tipopol,'search_tipopol');
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
        print '<td class="liste_titre"><input class="flat" type="text" name="search_concepto" align="center" size="20" value="'.$search_concepto.'"></td>';
        print '<td class="liste_titre"><input class="flat" type="text" name="search_referencia" align="center" size="20" value="'.$search_referencia.'"></td>';
        print '<td class="liste_titre" colspan="4">&nbsp;</td>';
        print '</tr>';

        $var=True;
    /*
    //	print $offset;
    //En esta parte calculamos el saldo completo de la consulta sin limites
    $resql = $db->query($sql2);
    if ($resql) {
        $cuenta_ant='';
        $j=0;
        $saldo=array();
        while ($row = $db->fetch_array($resql))  {
            if ($cuenta_ant != $row[14]) {
                $cuenta_ant = $row[14];
                $saldo_acum = 0;
            }

            $cargo = 0;
            $abono = 0;

            if ($row[11]==0) $cargo = $row[12];
            else $abono = $row[12];

            if($row[17]==1) $saldo_acum = $saldo_acum + $cargo - $abono;
            else $saldo_acum = $saldo_acum + $abono - $cargo;

            $saldo[$j] = $saldo_acum;

            $j++;
        }
    }
    // fin algoritmo calculo de saldo

        $ren=$offset;
    */

        $debe = 0;
        $haber = 0;
        while ($i < min($num,$limit))
        {
            $obj = $db->fetch_object($result);

            print "<tr $bc[$var]>";

            // Checkbox
            print '<td align="center">';
            if ($idsel[$obj->idcuenta] == $obj->idcuenta) print '<input class="flat checkformerge" checked onClick="mostrarVentana" type="checkbox" name="selected[]" value="'.$obj->idcuenta.'">';
            else print '<input class="flat checkformerge" onClick="mostrarVentana" type="checkbox" name="selected[]" value="'.$obj->idcuenta.'">';
            print '</td>' ;
            print '<td valign="middle">';
            $cuenta->id=$obj->idpadre;
            $cuenta->ref=$obj->codpadre;
            print $cuenta->getNomUrl(1,'',10);
            print '</td>';
            print '<td align="left" nowrap="nowrap">'.dol_trunc($obj->nompadre,30)."</td>";
            print '<td valign="middle">';
            $cuenta->id=$obj->idcuenta;
            $cuenta->ref=$obj->codigo;
            print $cuenta->getNomUrl(1,'',10);
            print '</td>';		
            print '<td align="left" nowrap="nowrap">'.dol_trunc($obj->nombre,30)."</td>";
            print '<td valign="middle">';
            $cuenta->id=$obj->idpoliza;
            $cuenta->ref=$obj->Folio;
            print $cuenta->getNomUrl(1,'',10);
            print '</td>';
            print '<td align="center">'.$obj->tipopol.'</td>';
            print '<td align="center">'.$obj->Fecha.'</td>';
            print '<td align="center">'.$obj->periodo.'</td>';
            print '<td align="center">'.$obj->Ejercicio.'</td>';
            print '<td align="center">'.dol_trunc($obj->Concepto,30).'</td>';
            print '<td align="center">'.dol_trunc($obj->Referencia,30).'</td>';								
            if ($obj->TipoMovto==0) {
                print '<td align="right" nowrap="nowrap">'."$".number_format($obj->Importe,2)."</td>";
                $debe = $debe + $obj->Importe;
            }
            else print '<td align="right" nowrap="nowrap">&nbsp;</td>';
            if ($obj->TipoMovto==1) {
                print '<td align="right" nowrap="nowrap">'."$".number_format($obj->Importe,2).'</td>';
                $haber = $haber + $obj->Importe;
            }
            else print '<td align="right" nowrap="nowrap">&nbsp;</td>';
            print '<td align="right" nowrap="nowrap">'."$".number_format($obj->saldo,2)."</td>";
            print "</tr>\n";

            $i++;
            $ren++;
        }

        print '<tr class="liste_total">';
        print '<td class="liste_total" colspan="11">&nbsp;</td>';
        print '<td class="liste_total" align="right">'.'SUMAS'.'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($debe,2).'</td>';
        print '<td class="liste_total" align="right">'."$".number_format($haber,2).'</td>';
        print '<td class="liste_total">&nbsp;</td>';

        print "</tr>\n";

        print "</table>";

        print print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

        $db->free($result);

    }
    else {
        dol_print_error($db);
    }
    
    print '</form>';	//fin del formulario

    print '<br>';
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
			document.getElementById("exportexcel").value = true;
			document.formlist.submit();
		}	
		if (mode == '11') {
			document.getElementById("cuadre").value = 1;
			document.formlist.submit();
		}	
		if (mode == '12') {
			document.getElementById("mode").value = 12;
			document.formlist.submit();
		}											
	}	
   
</script>