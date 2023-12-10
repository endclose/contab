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
 */

require '../../../main.inc.php';  //cargar el modulo principal
require_once DOL_DOCUMENT_ROOT . '/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/contab/lib/contab.lib.php';

$mode		= (GETPOST('mode', 'int') ? GETPOST('mode', 'int') : '1');
$id			= GETPOST('id', 'int');

$object = new Cuentas($db);
if ($id) {		//existe un id
	$res = $object->fetch($id);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}
} else if ($mode == 1) {		//no existe un $id de cuenta y estamos en el modo de visualzación de cuenta, entonces nos dirigimos a la pagina de Cuenta contables
	header("Location: " . DOL_URL_ROOT . "/custom/contab/index.php?mainmenu=contabilidad&leftmenu=contab");
	exit;
}

if ($mode == 4 || $mode == 5)	//procedimiento para el alta del registro
{
	//Validamos los campos 
	if ($_POST['codigo'] == '') {
		setEventMessage("El campo CODIGO no debe estar vacío", 'warnings');
		if ($mode == 4) $mode = 2;
		if ($mode == 5) $mode = 3;
	}
	if ($_POST['nombre'] == '') {
		setEventMessage("El campo NOMBRE no debe estar vacío", 'warnings');
		if ($mode == 4) $mode = 2;
		if ($mode == 5) $mode = 3;
	} else {
		//obtenemos el RowVersion solo para efectos de compatibilidad con contpaq
		$rowversion = mt_rand();
		$rowversion2 = mt_rand();

		//asignamos al objeto $object los valores anteriores
		$object = new Cuentas($db);
		if ($mode == 5) $res = $object->fetch($id);	//como estamos en modo de edicion ponemos los valores anteriores no utilizados en el objecto $object

		//Asignar las variables pasadas por el metodo POST al evento submit del formulario modificando los valores del objeto $object
		$caracteres_inval = array("/", "-", "*", "+", "_");
		$_POST['codigo'] = trim($_POST['codigo']);
		$_POST['codigo'] = str_replace($caracteres_inval, "", $_POST['codigo']);
		$object->rowversion = $rowversion;
		$object->codigo = $_POST['codigo'];
		$object->nombre = $_POST['nombre'];
		$object->nomidioma = '';
		$object->tipo = $_POST['tipocta'];
		$object->active = $_POST['estado'];
		$object->nivel = $_POST['nivel'];
		$object->ctaefectivo = '0';
		$object->FechaRegistro = date("Y/m/d");
		$object->sistorigen = '11';
		$object->idmoneda = '1';
		$object->digagrup = '0';
		$object->idsegneg = '0';
		$object->segnegmovtos = '0';
		$object->Afectable = $_POST['afectable'];
		$object->timestamp = '';
		$object->idrubro = '0';
		$object->consume = '0';
		$object->idagrupadorsat = '0';
		$object->conceptosconsume = '';
		$object->idpadre = $_POST['padre'];
		$object->nat = $_POST['nat'];

		if ($mode == 4) $actual = $object->create($object);
		if ($mode == 5) $actual = $object->update($object, $id);
		if ($actual > 0) {
			$id = $actual;
			if ($_POST['padre']) $actual_asoc = $object->update_asoc($id, $object->idpadre, $rowversion2);
			else $actual_asoc = 1;
			if ($actual_asoc > 0) {
				$mesg = "Cuenta actualizada con exito.";
				setEventMessage($mesg);
				$object = new Cuentas($db);
				$res = $object->fetch($id);
				$mode = 1;
			} else {
				$mesg = "Error al actualizar. Actualización cancelada.";
				setEventMessage($mesg, 'errors');
				dol_print_error($db);
				$mode = 3;
			}
		} else if ($mode == 4 || $mode == 5) {
			if ($actual == -1) $mesg = "El codigo contable ya existe: " . $object->codigo . '. Actualización cancelada.';
			else $mesg = "Error al actualizar. Actualización cancelada." . $actual;
			setEventMessage($mesg, 'errors');
			//dol_print_error($db);
			$mode = 3;
		}
	}
}

llxHeader("", $langs->trans("Cuentas contales"), "");  //Carga el marco principal de la aplicacion

//Botones de los formularios
print "\n" . '<div class="tabsAction">' . "\n";

// Botones del modo de vista
if ($mode == 1) //estamos en el modo de vista
{
	print '<input type="button" class="button" name="addb" value="Agregar" id="addb" onClick="sub_mode(this.name)">';
	print '<input type="button" class="button" name="editb" value="Editar" id="editb" onClick="sub_mode(this.name)">';
	//	print '<input type="button" class="button" name="deleteb" value="Eliminar" id="deleteb" onClick="sub_mode(this.name)">';
}
if ($mode == 2) //estamos en el modo de agregar cuenta
{
	print '<input type="button" class="button" name="saveb" value="Guardar" id="saveb" onClick="sub_mode(this.name)">';
	print '<input type="button" class="button" name="cancelba" value="Cancelar" id="cancelba" onClick="sub_mode(this.name)">';
}
if ($mode == 3) //estamos en el modo de edicion
{
	print '<input type="button" class="button" name="updateb" value="Actualizar" id="updateb" onClick="sub_mode(this.name)">';
	print '<input type="button" class="button" name="cancelbe" value="Cancelar" id="cancelbe" onClick="sub_mode(this.name)">';
}

print "</div>";

$head = cuenta_prepare_head($object); //prepara las pestañas del contenedor de las cuentas
$titre = 'Cuentas contables';
dol_fiche_head($head, 'card', $titre, 0, $picto);	//Control tipo contendedor de pestañas

/** Formularios ***/
//$form = new Form($db);

print '<table class="border" width="100%">';

if ($mode == 1) {
	print '<form action="fiche.php" method="post" name="view">';  //metodo post las variables no salen en la url
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';  //el token de la sesion
	print '<input type="hidden" id="modeview" name="mode" value="">'; //el modo del formulario
	print '<input type="hidden" name="id" value="' . $object->id . '">'; //el id del objeto mostrado en el formulario

	//Datos cuenta
	$codigo = substr($object->codigo, 0, 3) . "-" . substr($object->codigo, 3, 2) . "-" . substr($object->codigo, 5, 3);
	$etiquetas = array('Código', 'Nombre', 'Estado');
	$campos = array($codigo, $object->nombre, (($object->active == 0) ? 'ACTIVA' : 'INACTIVA'));
	print_label($etiquetas, $campos, 'DATOS CUENTA', 3);
	//Afectacion
	$etiquetas = array('Tipo de Cuenta', 'Naturaleza');
	$campos = array($object->tipocta, (($object->nat == 0) ? 'ACREEDORA' : 'DEUDORA'));
	print_label($etiquetas, $campos, 'AFECTACIÓN', 2);
	//Ubicacion
	$etiquetas = array('Pertenece a ', 'Afectable', 'Cuenta de Mayor');
	$ctapadre = '<a href="' . DOL_URL_ROOT . '/contab/cuentas/fiche.php?id=' . $object->idpadre . '">' . $object->codpadre . "-" . $object->nompadre;
	$campos = array($ctapadre, (($object->Afectable == 1) ? "Si" : "No"), (($object->nivel == 1) ? "Si" : "No"));
	print_label($etiquetas, $campos, 'UBICACIÓN', 4);
	//Afectacion
	$etiquetas = array('Fecha alta', 'Fecha último Estado', 'Fecha última actualización');
	$campos = array($object->fechaalta, $object->dlu, $object->tms);
	print_label($etiquetas, $campos, 'CRONOLOGÍA', 3);
}

if ($mode == 2) {  //si estamos en el modo de agregar cuenta eliminamos los $id del objeto $object exceptuando el de la cuenta actual
	unset($object->codigo, $object->nombre, $object->active, $object->idpadre, $object->Afectable);
	$object->idtipocta = 1;
	print '<form action="fiche.php" method="post" name="add">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" id="modeadd" name="mode" value="">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
}

if ($mode == 3) {
	print '<form action="fiche.php" method="post" name="update">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" id="modeedit" name="mode" value="">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
}
if ($mode == 2 || $mode == 3) {
	//Datos cuenta
	print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold"><h6>' . 'DATOS CUENTA' . '&nbsp;</h6></td></tr>';
	print '<tr><td width="20%" class="fielrequired">' . 'Código' . '</td>';
	print '<td colspan="2"><input name="codigo" size="40" value="' . $object->codigo . '"></td></tr>';
	print '<tr><td width="20%" class="fielrequired">' . 'Nombre' . '</td>';
	print '<td colspan="2"><input name="nombre" size="40" value="' . $object->nombre . '"></td></tr>';
	print '<tr><td width="20%" class="fielrequired">' . 'Estado' . '</td>';
	$datos_idactiva = array(0, 1);
	$datos_nactiva = array('ACTIVA', 'INACTIVA');
	print '<td>';
	select_simple('estado', $object->active, $datos_idactiva, $datos_nactiva, 2);
	print '</td></tr>';
	//Afectacion
	print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold"><h6>' . 'AFECTACIÓN' . '&nbsp;</h6></td></tr>';
	print '<tr><td width="20%">' . 'Tipo de Cuenta' . '</td>';
	$names = array('etiqueta');
	print '<td>';
	select_cat('tipocta', MAIN_DB_PREFIX . 'tiposcta_contpaq', $object->tipo, 'abrev', $names, '', '', 'abrev', 'abrev');
	print '</td></tr>';
	$nat_ids = array(1, 0);
	$nat_names = array('DEUDORA', 'ACREEDORA');
	print '<tr><td width="20%">' . 'Naturaleza ' . '</td>';
	print '<td>';
	select_simple('nat', $object->nat, $nat_ids, $nat_names, 2);
	print '</td></tr>';
	//Ubicacion
	print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold"><h6>' . 'UBICACIÓN' . '&nbsp;</h6></td></tr>';
	print '<tr><td width="20%">' . 'Pertenece a ' . '</td>';
	$names = array('nombre');
	$fieldcnds = array('active', 'Afectable');
	$cnds = array(0, 0);
	print '<td>';
	select_cat('padre', MAIN_DB_PREFIX . 'c_cuentas_contab', $object->idpadre, 'codigo', $names, $fieldcnds, $cnds, 'codigo', 'rowid');
	print '</td></tr>';
	print '<tr><td width="20%">' . 'Afectable' . '</td>';
	$datos_idactiva = array(0, 1);
	$datos_nactiva = array('No', 'Si');
	print '<td>';
	select_simple('afectable', $object->Afectable, $datos_idactiva, $datos_nactiva, 2);
	print '</td></tr>';
	print '<tr><td width="20%">' . 'Cuenta mayor' . '</td>';
	$datos_idactiva = array(1, 2, 3);
	$datos_nactiva = array('Si', 'No', 'Agrupacion');
	print '<td>';
	select_simple('nivel', $object->nivel, $datos_idactiva, $datos_nactiva, 3);
	print '</td></tr>';


	print '</form>';
}

print '</table>';

dol_fiche_end();  //fin del control contendeor de pestañas

llxFooter();

/**funciones**/
/**argumentos**/
//$etiquetas: array: los titulos de los campos / $campos: array: los valores de cada campo / $titgpo: str: el titulo de cada grupo
//$ren: int: el total de campos a mostrar por cada grupo
//$tipocontrol: array: los tipo de control a mostrar (0:etiqueta, 1: input, 2: select(con opciones), 3: select(con catalogo)
//$datos_sel: array: los parametros del campo select / $datos_idactiva:array: los campos de id de los datos del select 
//$datos_nactiva:array:las etiquetas de los datos del select
//$names:array: nombre de los campos opcionales del control select
/*	
function print_label($etiquetas,$campos,$titgpo,$ren)
{
	print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold"><h6>'.$titgpo.'&nbsp;</h6></td></tr>';
	for ($i = 0; $i < $ren; $i++) {
		print '<tr><td width="20%" class="fielrequired">'.$etiquetas[$i].'</td>';
		print '<td class="fielrequired" colspan="3">'.$campos[$i].'</td></tr>';
	}	
	
}
*/
function select_simple($namesel, $idelem = 0, $idelem_arr, $name_arr, $numren, $mostrar_seleccione = '')
{
	print '<select name="' . $namesel . '" id="' . $namesel . '">';
	if ($mostrar_seleccione) print '<option value="" style="font-style:italic">' . "SELECCIONE..." . '</option>';
	$i = 0;
	while ($i < $numren) {
		if ($idelem_arr[$i] == $idelem) print '<option selected="true" value="' . $idelem_arr[$i] . '">' . $name_arr[$i] . '</option>';
		else print '<option value="' . $idelem_arr[$i] . '">' . $name_arr[$i] . '</option>';
		$i++; //echo $idelem;

	}
	print '</select>';
}

// namesel: nombre e id del control sel, table: tabla, idfield: id del registro seleccionado, code:primer campo de la lista, fieldcnd: campo de la condicion de busqueda
// cnd: condicion de la busqueda, fieldsort: campo por el ordenamiento de la list, fieldid: nombre del campo principal que contiene la llave primaria
//						1		2		3		4		5		6			7			8			9
function select_cat($namesel, $table, $idfield, $code, $names = '', $fieldcnds = '', $cnds = '', $fieldsort = '', $fieldid)
{
	global $db;	//acceso a las funciones de base de datos

	$db->begin();

	$sql = "SELECT " . $fieldid . " as rowid," . $code . " as code";
	if ($names) {
		$i = 1; //asigno un indice
		foreach ($names as $name) {
			$sql .= "," . $name . " as name" . $i;
			$i++;
		}
	}
	$sql .= " FROM " . $table;
	if ($fieldcnds) {
		$i = 0; //asigno un indice
		foreach ($fieldcnds as $fieldcnd) {
			if ($i == 0) 	$sql .= " WHERE " . $fieldcnd . " = " . $cnds[$i];
			else $sql .= " AND " . $fieldcnd . " = " . $cnds[$i];
			$i++;
		}
	}
	if ($fieldsort) $sql .= " ORDER BY " . $fieldsort;

	$resql2 = $db->query($sql);
	if ($resql2) {
		print '<select name="' . $namesel . '" id="' . $namesel . '">';
		print '<option value="" style="font-style:italic">' . "SELECCIONE..." . '</option>';
		while ($array = $db->fetch_array($resql2)) {
			if ($array['rowid'] == $idfield) {
				print '<option selected="true" value="' . $array['rowid'] . '">' . $array['code'];
				if ($names) {
					$i = 2;
					foreach ($names as $name) {
						print "-" . $array[$i];
						$i++;
					}
				}
				print '</option>';
			} else {
				print '<option value="' . $array['rowid'] . '">' . $array['code'];
				if ($names) {
					$i = 2; //asigno un indice
					foreach ($names as $name) {
						print "-" . $array[$i];
						$i++;
					}
				}
				print '</option>';
			}
			$j++;
		}
	} else {
		dol_print_error($db);
		return -1;
	}
	print '</select>';
}

?>

<script type="text/javascript">
	$(document).ready(function() {
		//funcion abierta
	})

	function sub_mode(mode) {
		if (mode == 'addb') {
			document.getElementById("modeview").value = 2;
			document.view.submit();
		}
		if (mode == 'editb') {
			document.getElementById("modeview").value = 3;
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
		if (mode == 'updateb') {
			document.getElementById("modeedit").value = 5;
			document.update.submit();
		}
		if (mode == 'cancelbe') {
			document.getElementById("modeedit").value = 1;
			document.update.submit();
		}
	}

	function reload_page() {
		reload()
	}
</script>;