<?php
/* Copyright (C) 2017 Leopoldo Campos Carrillo
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
 *       \file       
 *       \brief      
 *		mode=1 modo vista / mode=2 modo alta de poliza / mode=3 modo de edicion / modo = 4 prodcedimiento alta poliza / modo=5 procedimiento acutalizacion poliza
 *		mode=6 confirmar eliminar poliza / modo=7 procedimiento de eliminación de poliza / mode=8 agregar movimiento /
 */
 
require '../../../main.inc.php';  //cargar el modulo principal
require_once DOL_DOCUMENT_ROOT.'/custom/contab/polizas/class/polizas.class.php';
require DOL_DOCUMENT_ROOT.'/custom/contab/class/contab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/commoncontab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/lib/contab.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/nxus/core/lib/nxos.lib.php';

$mode		=(GETPOST('mode','int') ? GETPOST('mode','int') : '1');
$modem		=GETPOST('modem','int');
$id			=GETPOST('id','int');
$action		=GETPOST('action','alpha');
$selected	=GETPOST('selected');

//datos generales de la poliza
$fecha		= GETPOST('red');
$tipopol	= GETPOST('idtipopol','int');
$periodo	= GETPOST('idperiodo','int');
$ejercicio	= GETPOST('ejercicio','int');
$concepto	= GETPOST('concepto','alpha');
$debe		= GETPOST('debe');
$haber		= GETPOST('haber');

//datos de los movimientos obtenidos por el la ventana modal
$idcuenta	= GETPOST('idcuenta');
$tipomov	= GETPOST('idtipomov','int');
$importe	= GETPOST('importe');
$ref		= GETPOST('ref','alpha');

$form=new Form($db);
$object = new Polizas($db);
$contab = new Contabilidad($db);
$contab_com = new CommonContab($db);

$cuenta_code = GETPOST('cuenta');

//print var_dump($cuenta_code, GETPOST('idcuenta'));

/* Listado de las variables de modos (mode, modem)
// mode     1: Modo vista, 2:Modificar Datos poliza 3: Modificar Movimientos 
//          4:procedimiento para el alta del registro 5:procedimiento de actualizar registro mode
//          6:
*/
//print var_dump($mode);
if ($id) {		//existe un id
	$poliza=$object->bringPoliza($id);
	if ($poliza < 0) { dol_print_error($db,$object->error); exit; }
}
else if ($mode == 1) {		//no existe un $id de cuenta y estamos en el modo de visualzación de cuenta, entonces nos dirigimos a la pagina de Cuenta contables
	header("Location: ".DOL_URL_ROOT."/contab/polizas/list.php?leftmenu=contab&type=0");
	exit;	
}

if ($action=='confirm_delete') {  //se eliminará la póliza
	$ejecutar = $contab->borrarPoliza($id);
	if($ejecutar>0) {
		header("Location: ".DOL_URL_ROOT."/contab/polizas/list.php?leftmenu=contab&type=0");
		setEventMessage('Poliza '.$poliza[1][5].' eliminada correctamente');
		exit;		
	}
	else {
		setEventMessage('Ocurrió un error al tratar de elimiar la póliza. Contacte al administrador: '.$id,'errors');
		$mode=1;	
	}
}
//print $mode.":".$modem.":".$selected;
if ($mode == 3 && $modem == 3) {
	if($selected){	//guardamos en un arreglo los id seleccionados
		foreach($selected as $id) {
			$ejecutar = $contab->borrarMovimiento($id);
			$sumas_iguales = calculaSumas($ejecutar);
			$total_cargos = $sumas_iguales[1][1];
			$total_abonos = $sumas_iguales[2][1];
			$exec = $contab->actualizaSumas($ejecutar,round($total_cargos,2),round($total_abonos,2));			
		}
		setEventMessage("Movimiento(s) eliminado(s) correctamente.");
		$poliza=$object->bringPoliza($ejecutar);
	}
	else {
		setEventMessage("Debe seleccionar al menos un movimiento para eliminar.",'errors');
	}
	$mode = 3; $modem = 0;
}

if ($mode == 3 && $modem == 2) {
    
    if (!$idcuenta) { // No tiene valor la variable idcuenta buscamos el id con base a la variable $cuenta_code
        if ($cuenta_code) {
            $idcuenta = $contab->getIdCuentaContab($cuenta_code);
            if (!$idcuenta) {
                $mensajeerror = "ERROR: No se pudo encontrar el Id de la cuenta";
                setEventMessage($mensajeerror, 'errors'); 
            }
        }
        else {
            $mensajeerror = "ERROR: El campo CUENTA no debe estar vacio";
            setEventMessage($mensajeerror, 'errors'); 
        }
    }    
    if ($idcuenta) {
        $nr = count($poliza);
        if ($nr == 1 && !$poliza[1][23]) $nummov = $nr;
        else $nummov = $nr + 1;
        $poliza_mov = array(	$poliza[1][0], $poliza[1][1], $poliza[1][2], $poliza[1][3], $poliza[1][4], $poliza[1][5],
                                $poliza[1][6], $poliza[1][7], $poliza[1][8], $poliza[1][9], $poliza[1][10], $poliza[1][11],
                                $poliza[1][12], $poliza[1][13], $poliza[1][14], $poliza[1][15], $poliza[1][16], $poliza[1][17],
                                $poliza[1][18], $poliza[1][19], $poliza[1][20], $poliza[1][21],$poliza[1][22],

                                $poliza[1][23], 0, $poliza[1][0], $poliza[1][2], $poliza[1][3], $poliza[1][4],
                                $poliza[1][5], $nummov, $idcuenta, $tipomov, $importe, 0,
                                $ref, $poliza[1][8], 0, $poliza[1][9], $poliza[1][39], $poliza[1][40], $poliza[1][41],

                                $poliza[1][42], $poliza[1][43]);
        $ejecutar = $contab->agregarMovimiento($poliza_mov);	//agregamos el movimiento
        $sumas_iguales = calculaSumas($poliza[1][0]);
        $total_cargos = $sumas_iguales[1][1];
        $total_abonos = $sumas_iguales[2][1];
        $exec = $contab->actualizaSumas($poliza[1][0],round($total_cargos,2),round($total_abonos,2));
        //Si no ocurre ningun error en la contabilizacion actualizamos el campo de fk_poliza y fk_status_contab 
        if ($ejecutar>0) {
            setEventMessage("Movimiento agregado correctamente. no:".$nummov);
        }
        else {
            print "Ocurrio un error al agregar el movimiento a la póliza. Intente de nuevo o contacte al administrador";exit;
        }
    }
		
	$poliza=$object->bringPoliza($id);	
/*	if ($nr == 1) {	//solo existe un registro y no tiene movimientos 
		//Agregamos los datos del movimiento al primer registro
	}
	else if (!$poliza[1][23]) {
			$poliza_nva[1] = array(	$poliza[1][0], $poliza[1][1], $poliza[1][2], $poliza[1][3], $poliza[1][4], $poliza[1][5],
									$poliza[1][6], $poliza[1][7], $poliza[1][8], $poliza[1][9], $poliza[1][10], $poliza[1][11],
									$poliza[1][12], $poliza[1][13], $poliza[1][14], $poliza[1][15], $poliza[1][16], $poliza[1][17],
									$poliza[1][18], $poliza[1][19], $poliza[1][20], $poliza[1][21],$poliza[1][22],
									
									$poliza[1][23], 0, $poliza[1][0], $poliza[1][2], $poliza[1][3], $poliza[1][4],
									$poliza[1][5], $nummov, $idcuenta, $tipomov, $importe, 0,
									$ref, $poliza[1][8], 0, $poliza[1][9], $poliza[1][39], $poliza[1][40], $poliza[1][41],
									
									$poliza[1][42], $poliza[1][43]);
			$poliza = $poliza_nva;
			//setEventMessage($poliza[1][31]);
	}
	else {
		$nummov = $nr + 1;
		$poliza[$nummov] = array(	$poliza[1][0], $poliza[1][1], $poliza[1][2], $poliza[1][3], $poliza[1][4], $poliza[1][5],
									$poliza[1][6], $poliza[1][7], $poliza[1][8], $poliza[1][9], $poliza[1][10], $poliza[1][11],
									$poliza[1][12], $poliza[1][13], $poliza[1][14], $poliza[1][15], $poliza[1][16], $poliza[1][17],
									$poliza[1][18], $poliza[1][19], $poliza[1][20], $poliza[1][21],$poliza[1][22],
									
									$poliza[1][23], 0, $poliza[1][0], $poliza[1][2], $poliza[1][3], $poliza[1][4],
									$poliza[1][5], $nummov, $idcuenta, $tipomov, $importe, 0,
									$ref, $poliza[1][8], 0, $poliza[1][9], $poliza[1][39], $poliza[1][40], $poliza[1][41],
									
									$poliza[1][42], $poliza[1][43]);
		die($poliza[$nummov][31].":".count($poliza));
	}*/
	$mode = 3; $modem = 0;
	//print "id:".$id."-cuenta:".$idcuenta."-tipomov:".$tipomov."-importe:".$importe."-nr:".count($poliza);exit;
	//agregarmos los datos nuevos al arreglo $poliza[]
}

if ($mode==4 || $mode==5)	//procedimiento para el alta del registro mode 4, procedimiento de actualizar registro mode 5
{ 
    //El modo 5  ya esta descontinuado
    $fechapol = dol_mktime($_POST["rehour"], $_POST["remin"], $_POST["resec"], $_POST['remonth'], $_POST['reday'], $_POST['reyear']); //print var_dump($fechapol);
	//Validamos los campos 
	if ($fechapol == '') {
		setEventMessage("El campo FECHA no debe estar vacío", 'warnings');
		if ($mode == 4) $mode=2;
		//if ($mode == 5) $mode=3;
	}
	else if ($_POST['idtipopol'] == '') {
		setEventMessage("El campo TIPO POLIZA no debe estar vacío", 'warnings');
		if ($mode == 4) $mode=2;
		//if ($mode == 5) $mode=3;
	}
	else if ($_POST['ejercicio'] == '') {
		setEventMessage("El campo EJERCICIO no debe estar vacío", 'warnings');
		if ($mode == 4) $mode=2;
		//if ($mode == 5) $mode=3;
	}
	else if ($_POST['concepto'] == '') {
		setEventMessage("El campo CONCEPTO no debe estar vacío", 'warnings');
		if ($mode == 4) $mode=2;
		//if ($mode == 5) $mode=3;
	}	
	else {
		
		//datos generales de la poliza	
		//					 0 		1		2			3		  4	 	  	5	   6	 7
		$datosg_poliza=array(0,$tipopol,$ejercicio,$periodo, $concepto,$fechapol,$debe,$haber);
		//como solo vamos a dar de alta los datos generales de la poliza, ponemos en 0 los valores del arreglo de los datosc
		$cuentas = array(); $montos = array(); $tipomovs = array(); $refs = array();
		//datos conceptos poliza
		//						0	  	  1		  2			3
		$datosc_poliza=array($cuentas, $montos, $tipomovs, $refs);
		//Contabilizamos la factura
		if ($mode==4) $ejecutar = $contab->contabilizar($datosg_poliza, $datosc_poliza);
        //Se descontinuo  este modo para no modificar los datos generales de la poliza y crear malas practicas contables y errores en el sistema
		//if ($mode==5) $ejecutar = $contab->actualizar($datosg_poliza, $datosc_poliza,$id); 

		//Si no ocurre ningun error en la contabilizacion actualizamos el campo de fk_poliza y fk_status_contab
		if ($ejecutar>0) {
			$idpol = $contab->getPol($ejecutar);//print $objp->rowid.":".$ejecutar;exit;	//obtenemos los datos de la poliza
			setEventMessage("Póliza contabilizada correctamente. Poliza folio:".$idpol[1]);
		}
		else {
			print "Ocurrio un error al contabilizar la póliza. Intente de nuevo o contacte al administrador";exit;
		}

		$poliza=$object->bringPoliza(($mode==4?$ejecutar:$id));
		if ($mode==4) $mode = 3;
		else $mode = 1;
	}
}

llxHeader("",$langs->trans("Pólizas"),"");  //Carga el marco principal de la aplicacion

//Botones de los formularios
print "\n".'<div class="tabsAction">'."\n";

// Botones del modo de vista
if ($mode == 1 || $mode == 6) //estamos en el modo de vista
{
    print '<input type="button" class="butAction" name="addb" value="Nueva Poliza" id="addb" onClick="sub_mode(this.name)">';
    print '<input type="button" class="butAction" name="editb" value="Modificar" id="editb" onClick="sub_mode(this.name)">';
    print '<input type="button" class="butActionDelete" name="deleteb" value="Eliminar" id="deleteb" onClick="sub_mode(this.name)">';
}
if ($mode == 2) //estamos en el modo de agregar cuenta
{
    print '<input type="button" class="butAction" name="saveb" value="Crear Póliza" id="saveb" onClick="sub_mode(this.name)">';
}
if ($mode == 3) //estamos en el modo de edicion
{
    print '<input type="button" class="butAction" name="cancelbe" value="Salir" id="cancelbe" onClick="sub_mode(this.name)">';
}

print "</div>"; 

$head = poliza_prepare_head($object); //prepara las pestañas del contenedor de las polizas
$titre = 'Pólizas';
dol_fiche_head($head, 'card', $titre, 0, $picto);	//Control tipo contendedor de pestañas
$formconfirm='';

if ($mode == 6) {
	$aviso = 'Confirme la eliminación de póliza';
	$question = '¿Está seguro de eliminar la póliza folio: '.$poliza[1][5].'?';
	$formconfirm=$form->form_confirm2($_SERVER['PHP_SELF'].'?id='.$id,$aviso,$question,'confirm_delete','',0,1);
}
if ($modem == 1) {
	$aviso = '¿Agregar Movimiento?';
	$formquestion = array(								
							//array('type' => 'other','name' => 'idcuenta','label' => 'Cuenta Contable','value' => $contab_com->selectCuentaContab('','idcuenta',0,'codigo','',0)),
                            array('type' => 'other','name' => 'cuenta','label' => 'Cuenta','value' => $form->select_ajax_contab($cuenta_code, 'cuenta', '', 50, 0, '', 'maxwidth100 quatrevingtpercent','cuentas')),
                            //array('type' => 'other','name' => 'cuenta','label' => 'Cuenta','value' => $form->select_cuentas($cuenta, 'cuentaid')),
							array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'other','name' => 'idtipomov','label' => 'Tipo Movimiento','value' => $contab_com->selectTipoMov('','idtipomov',0)),
							array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'text','name' => 'importe','label' => 'Importe','value' => $importe),
							array('type' => 'other','name' => 'l1','label' => '&nbsp;','value' => '&nbsp;'),
							array('type' => 'text','name' => 'ref','label' => 'Referencia','value' => $ref)
							);
	$formconfirm = $form->form_confirm2($_SERVER['PHP_SELF'] . '?id='.$id.'&mode=3&modem=2' . $object->id, $aviso, $text, 'add_mov', $formquestion, "yes", 1,250,700);	
}



/** Formularios ***/
//$form = new Form($db);

print '<table class="border" width="100%">';

if ($mode==1 || $mode==6 || $mode==3)
{
	//formulario en el modo de vista de poliza
    if ($mode <> 3) {
	   print '<form action="card.php" method="post" name="view">';  //metodo post las variables no salen en la url
	   print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';  //el token de la sesion
	   print '<input type="hidden" id="modeview" name="mode" value="">'; //el modo del formulario
	   print '<input type="hidden" name="id" value="'.$poliza[1][0].'">'; //el id del objeto mostrado en el formulario
    }
	//Datos poliza
	$etiquetas = array('Folio', 'Fecha', 'Tipo Póliza', 'Periodo', 'Ejercicio', 'Concepto','Estado');
	$campos = array($poliza[1][5],$poliza[1][9], $object->getTipoPol($poliza[1][4]), $object->getPeriodo($poliza[1][3]), $poliza[1][2], $poliza[1][8],$poliza[1][44]);
	print_label($etiquetas,$campos,'DATOS PÓLIZA',7);
	print '<tr><td style="border:0px">'.'&nbsp;</td></tr>';
	print '<tr><td colspan="6" align="left" bgcolor="#3B5998" style="color:white;" style="font-style:bold"><h10>'."MOVIMIENTOS".'&nbsp;</h10></td></tr>';
}

if ($mode==2) {  //si estamos en el modo de agregar cuenta eliminamos los $id del objeto $object exceptuando el de la cuenta actual
	unset($poliza);
	$poliza[1][9] = date('Y-m-d'); $poliza[1][4] = 1; $poliza[1][3] = $conf->global->PERIOD_CONTAB; $poliza[1][2] = $conf->global->FISCAL_YEAR; 
	$poliza[1][8]=''; $poliza[1][5] = '';
	print '<form action="card.php" method="post" name="add">'; 
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" id="modeadd" name="mode" value="">';
	if ($mode==2) print '<input type="hidden" name="id" value="'.$poliza[1][0].'">';
    else print '<input type="hidden" name="id" value="'.$id.'">';
}

if ($mode==3)
{
	print '<form action="card.php" method="post" name="update">'; 
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" id="modeedit" name="mode" value="">';
	print '<input type="hidden" id="modeeditm" name="modem" value="">';
	print '<input type="hidden" name="id" value="'.$poliza[1][0].'">';
}
if ($mode==2)
{
	//Datos cuenta
	print '<tr><td align="center" bgcolor="#3B5998" style="color:white;" style="font-style:bold"><h6>'.'DATOS PÓLIZA'.'&nbsp;</h6></td></tr>';
	print '<tr><td width="20%" class="fielrequired">'.'Folio'.'</td>';
	print '<td colspan="2">'.$poliza[1][5].'</td></tr>';
	print '<tr><td width="20%" class="fielrequired">'.'Fecha'.'</td>';
	print '<td colspan="2">';
    $form->select_date2($poliza[1][9],'re',1,1,1,0,"add_poliza",1,1); 
	print '</td></tr>';	
	print '<tr><td width="20%" class="fielrequired">'.'Tipo Póliza'.'</td>';	
	print '<td>';selectTipoPol($poliza[1][4],'idtipopol'); print '</td></tr>';
	print '<tr><td width="20%">'.'Periodo'.'</td>';	
	print '<td>';
	print selectPeriodo($poliza[1][3],'idperiodo'); 
	print '</td></tr>';
	print '<tr><td width="20%">'.'Ejercicio'.'</td>';
	print '<td>';
	print selectEjercicio($poliza[1][2],'ejercicio'); 
	print '</td></tr>';
	print '<tr><td width="20%">'.'Concepto'.'</td>';
	print '<td colspan="2"><input name="concepto" size="50" value="'.$poliza[1][8].'"></td></tr>';
    print '<tr><td width="20%">'.'Estado'.'</td>';
	print '<td colspan="2"></td></tr>';
}

print '</table>';

dol_fiche_end();  //fin del control contendeor de pestañas

//botones de accion
	print "\n".'<div class="tabsAction">'."\n";
	if ($mode == 3) //estamos en el modo de vista
	{	
//		print '<input type="image" onClick="sub_mode(9)" height="30" src="'.DOL_URL_ROOT.'/media/imgs/interface/edit.png" name="editm" value="editm" id="editm" title="'.'Editar Movimiento'.'">';
//		print '<img height="30" width="10" src="'.DOL_URL_ROOT.'/media/imgs/interface/substract.png">';	
        print '<input type="button" class="butAction" name="addm" value="Agregar Movimiento" id="addm" onClick="sub_mode(8)">';
        print '<input type="button" class="butActionDelete" name="deletem" value="Eliminar Movimiento" id="deletem" onClick="sub_mode(10)">';
	}
	print "</div>";

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="center">';
	print '<input type="checkbox" name="checkall" id="checkall">';
	print '</td>';
	print_liste_field_titre("No.",$_SERVER["PHP_SELF"],"p.folio", $begin, $param, '', $sortfield,$sortorder);
	print_liste_field_titre("Cuenta",$_SERVER["PHP_SELF"],"p.Fecha", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre("Referencia",$_SERVER["PHP_SELF"],"p.TipoPol", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre("Cargos",$_SERVER["PHP_SELF"],"p.Periodo", $begin, $param, 'align="center"', $sortfield,$sortorder);
	print_liste_field_titre("Abonos",$_SERVER["PHP_SELF"],"p.Ejercicio", $begin, $param, 'align="center"', $sortfield,$sortorder);

    print "</tr>\n";

	$debe = 0;
	$haber = 0;
	
    $var=True;
	$i = 1; 
    
    if($poliza[1][0]) {
        $object->idpoliza = $poliza[1][0];
        $nummovs = $object->noMovsPol();
    }
    else $nummovs = false;
    //print var_dump($mode,$id,$nummovs,$poliza[1][0]);
    if ($nummovs) {
        foreach($poliza as $mov) {

            $var=!$var;

            print "<tr $bc[$var]>";
            // Checkbox
            print '<td align="center" width="5%">';
            print '<input class="flat checkformerge" onClick="mostrarVentana" type="checkbox" name="selected[]" value="'.$mov[23].'">';
            print '</td>';
            print '<td valign="middle" align="center">'.($mov[30]?$mov[30]:$i).'</td>';
            print '<td valign="middle" align="left">';
            $cuentas = $contab_com->getCuentaContab($mov[31]);
            foreach($cuentas as $cuenta) $nombre = $cuenta[2];
            print getNxusUrl($mov[31],'codigo','contab_cuentas','/custom/contab/cuentas/card.php')." ".$nombre;
            print '</td>';
            print '<td valign="middle" align="center" class="liste_total" >'.$mov[35].'</td>';
            if ($mov[32] == 0) {	//es un cargo
                print '<td valign="middle" align="right">'.price($mov[33]).'</td>';
                print '<td>&nbsp;</td>';
                $debe = $debe + $mov[33];
            }
            else {
                print '<td>&nbsp;</td>';
                print '<td valign="middle" align="right">'.price($mov[33]).'</td>';
                $haber = $haber + $mov[33];
            }
            print "</tr>\n";
            $debe = $debe + $obj->Cargos;
            $haber = $haber + $obj->Abonos;
            $i++;
        }
    }
	
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="3">&nbsp;</td>';
	print '<td class="liste_total" align="right">'.'SUMAS IGUALES'.'</td>';
	print '<td class="liste_total" align="right"><h6>'.'$ '.price($debe).'</h6></td>';
	print '<td class="liste_total" align="right"><h6>'.'$ '.price($haber).'</h6></td>';	
	print "</tr>\n";	

	$difsumas = $debe - $haber;
	if ($difsumas>0.009) {
		setEventMessage("ADVERTENCIA: Las sumas de cargos y abonos no coinciden.",'warnings');
		print '<tr>';
		print '<td colspan="3">&nbsp;</td>';
		print '<td align="right">'.'DIF.'.'</td>';
		if ($difsumas<0) print '<td bgcolor="#F1F802" align="right"><h6>'.'$ '.price(abs($difsumas)).'</h6></td>';
		else print '<td class="liste_total" align="right"><h6>'.''.'</h6></td>';	
		if ($difsumas>0) print '<td bgcolor="#F1F802" align="right"><h6>'.'$ '.price(abs($difsumas)).'</h6></td>';
		else print '<td class="liste_total" align="right"><h6>'.''.'</h6></td>';	
		print "</tr>\n";	
	}

	
	print '</table>';

	print '</form>';

//Nexus
	print '<table class="noborder nohover" width="30%" align="left">';
	print listNexus($id,8);
	print '</table>';
//	

llxFooter();

?>

<script type="text/javascript">
		$(document).ready(function(){
//funcion abierta
		})

		function sub_mode(mode)
		{
			if (mode == 'addb') {
				document.getElementById("modeview").value = 2;
				document.view.submit();
			}
			if (mode == 'editb') {
				document.getElementById("modeview").value = 3;
				document.view.submit();
			}
			if (mode == 'deleteb') {
				document.getElementById("modeview").value = 6;
				document.view.submit();
			}			
			if (mode == 'saveb') {
				document.getElementById("modeadd").value = 4;
				document.add.submit();
			}
			if (mode == 'cancelba') {
				document.getElementById("modeadd").value = 1;								
				document.add.submit();
			}
            //Sin uso
/*			if (mode == 'updateb') {
				document.getElementById("modeedit").value = 5;
				document.update.submit();
			}*/						
			if (mode == 'cancelbe') {
				document.getElementById("modeedit").value = 1;								
				document.update.submit();
			}
			if (mode == '8') {
				document.getElementById("modeedit").value = 3;
				document.getElementById("modeeditm").value = 1;
				document.update.submit();
			}
			if (mode == '10') {
				document.getElementById("modeedit").value = 3;
				document.getElementById("modeeditm").value = 3;
				document.update.submit();
			}
		}
		function reload_page()
		{
			reload()
		}
</script>;