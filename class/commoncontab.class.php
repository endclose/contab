<?php
/* Copyright (C) 2017 Leopoldo Campos <leo@leonx.net>
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
 *	\ingroup    
 *	\brief      
 */



/**
 * Class to manage products or services
 */
class CommonContab
{
	public $element='contabildiad';
	public $table_element='contabilidad';
	public $fk_element='fk_contabilidad'; 

	//! Identifiant unique
	var $id ;
	var $nombre;
	
	var $FechaRegistro;
	var $Afectable;
	


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */

	function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$this->status = 0;
		
		//conectamos con la base datos
	}
	
	/* FUNCION QUE OBTIENE EL ID DE LAS FACTURAS,MONTOS DE LOS PAGOS e ID DE LAS POLIZAS QUE ABARCAN UN PAGO DE CLIENTES O DE PROVEEDORES*/
	// $idpago				el id del pago de cliente o proveedor
	// $tipopago			0: pago de cliente		1: pago a proveedor
	function getIdFacPago($idpago, $tipopago) {
		if ($tipopago == 0) {
			$tablap = 'paiement_facture';
			$tablaf = 'facture';
			$fieldf = 'fk_facture';
			$fieldp = 'fk_paiement';
		}
		if ($tipopago == 1) {
			$tablap = 'paiementfourn_facturefourn';
			$tablaf = 'facture_fourn';
			$fieldf = 'fk_facturefourn';
			$fieldp = 'fk_paiementfourn';
		}		
//							0		1			   2		3			4		5			6				7
		$sql = 'SELECT f.rowid, f.facnumber, f.fk_statut, f.datef, pf.amount, s.nom, s.rowid as socid, f.total_ttc';
		//									8
		if ($tipopago == 1) $sql.= ', fk_cta_egreso';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$tablap.' as pf,'.MAIN_DB_PREFIX.$tablaf.' as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE pf.'.$fieldf.' = f.rowid';
		$sql.= ' AND f.fk_soc = s.rowid';
		$sql.= ' AND pf.'.$fieldp.' = '.$idpago;
		$resql=$this->db->query($sql);
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				if ($tipopago == 0) $rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
				if ($tipopago == 1) $rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]);
				if (!$row[3]) {
					//print "Error al contabilizar el pago. La factura ".$row[1]." NO esta contabilizada. Corrija e intente de nuevo"	; die();
				}
				//print $row[0]."-".$row[1]."-".$row[2]."-".$row[3]."-".$row[4]."-".$row[5]."-".$row[6];print '<br>';
				$i++;	
			}
		}
		else {
			print "Error al tratar de obtener informacion del pago - facturas. Intente de nuevo o Contacte al administrador: ".$idpago; exit;
		}
		$this->db->free($resql);
		return $rows;


	} //fin de la funcion getIdFacPago
	
	/* FUNCION PARA OBTENER LOS TOTALES DE VENTA/COMPRA EN CADA FACTURA POR LINEA DE PRODUCTO */
	// $idfac				int: el id de la factura
	// $tipofac				int: 0: factura de venta 1:FACTURA DE COMPRAS
	function getFacLinea($idfac, $tipofac) {
		if ($tipofac == 0) {
			$tablaf = 'facture';
			$tablafd = 'facturedet';
			$fieldfd = 'fk_facture';
		}
		if ($tipofac == 1) {
			$tablaf = 'facture_fourn';
			$tablafd = 'facture_fourn_det';
			$fieldfd = 'fk_facture_fourn';
		}
		//primero verificamos que la factura contenga movimientos
		$sql = 'SELECT fd.rowid FROM '.MAIN_DB_PREFIX.$tablafd.' as fd WHERE fd.'.$fieldfd.'='.$idfac;
		$res=$this->db->query($sql);
		if (!$this->db->num_rows($res)) die ("Error grave. La factura no contiene movimientos. Contacte al administrador: ".$idfac);				
//						  0			1			2						3	
		$sql = 'SELECT f.rowid, p.fk_linea, cc.rowid as idcc, SUM(fd.total_ht) AS totallinea';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$tablaf.' as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.$tablafd.' as fd ON f.rowid = fd.'.$fieldfd;
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_lineas_prod as lp ON p.fk_linea = lp.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_cuentas as cc ON lp.fk_cta_contab = cc.rowid';
		$sql.= ' GROUP BY f.rowid, p.fk_linea, cc.rowid';
		$sql.= ' HAVING f.rowid='.$idfac;
		$resql=$this->db->query($sql);		
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				if ($row[1]==null && $tipofac==0) $row[2] = 1402;	//existe un movimiento en la factura que es libre
				if ($row[1]==null && $tipofac==1) $row[2] = 1379;	//existe un movimiento en la factura que es libre
				$rows[$i] = array($row[0], $row[1], $row[2], $row[3]);
				$i++;	
			}
		}
		else {
			print "Error al tratar de obtener informacion del total de linea de producto en facturas. Intente de nuevo o Contacte al administrador: ".$idfac; exit;
		}
		$this->db->free($resql);
		return $rows;		
		
	} //fin de la funcion getVentaLinea
	
	/*FUNCION PARA EL CONTROL SELECT DE LAS CUENTAS CONTABLES*/
	// $selected				int: id del elemento que estara seleccionado por default
	// $htmlname				string: id y name del control select
	// $active					int:0: no se mostraran las cuentas que tengan estatus baja 1: si se muestran todas las cuentas
	// $orderby					string: campo por el cual se ordenaran las cuentas
	// $firsvalue				string: La primera opcion mostrada en el control select
	// $disabled				int:0: activado		1:desactivado
	// $afectdisabled			int:0: desactivar las cuentas no afectables 	1:activar todas las cuentas afectables y no afectables
	function selectCuentaContab($selected='',$htmlname='idccontab',$active=0,$orderby='codigo',$firsvalue='',$disabled=0,$afectdisabled=0) {
		$out='';
//		$out.= ajax_combobox($htmlname, $event, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
		$sql = "SELECT rowid, codigo, nombre, afectable";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas";
		if ($active == 0) $sql.= " WHERE active = 0";
		$sql.= " ORDER BY ".$orderby; //print $sql;exit;
		$resql2 = $this->db->query($sql);//die($sql);
		if ($resql2)
		{
			if ($disabled == 0) $out.= '<select name="'.$htmlname.'" id="'.$htmlname.'">';
			if ($disabled == 1) $out.= '<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';
			if ($firstvalue == '') $out.= '<option value="" style="font-style:italic">'."SELECCIONE Cuenta contable...".'</option>';
			$num = $this->db->num_rows($resql2);
			$i = 1;
			while ($i < $num+1)
			{
				$obj2 = $this->db->fetch_object($resql2);
                if ($selected == $obj2->rowid) {
					$out.= '<option value="'.$obj2->rowid.'" selected ="true">'.$obj2->codigo." ".$obj2->nombre.'</option>';
				}
				else {
					if ($afectdisabled == 0) {	//se van a desactivar las cuentas que no son afectables
						if ($obj2->afectable == 0) $out.= '<option disabled value="'.$obj2->rowid.'">'.$obj2->codigo." ".$obj2->nombre.'</option>';
						else $out.= '<option value="'.$obj2->rowid.'">'.$obj2->codigo." ".$obj2->nombre.'</option>';
					}
					else $out.= '<option value="'.$obj2->rowid.'">'.$obj2->codigo." ".$obj2->nombre.'</option>';
				}
				$i++;	
			}
			$out.= '</select>';	
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
		
		return $out;
	} //fin de la funcion selectCuentaContab

	/* FUNCION QUE SIRVE PARA OBTENER EL CODIGO Y NOMBRE DE UNA CUENTA CONTABLE */
	//$idcuenta				int: id de la cuenta que se quiere obtener la informacion
	function getCuentaContab($idcuenta=null) {
		//				0		1		2
		$sql = "SELECT rowid, codigo, nombre";
		$sql.=  " FROM ".MAIN_DB_PREFIX."contab_cuentas";
		if ($idcuenta) $sql.=" WHERE rowid='".$idcuenta."'";
		$sql.= " ORDER BY codigo"; 
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($row = $this->db->fetch_array($resql))  {
				$rows[$i] = array($row[0], $row[1], $row[2]);
				$i++;
			}
			return $rows;
		}
		else {
			return null;	
		}
	}

	function selectTipoMov($selected=1,$htmlname='idtipomov',$disabled=0) {
		global $db;
		$out = '';
		if ($disabled == 0) $out.= '<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) $out.= '<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';
		$out.='<option value="0" selected ="true">'.'Cargo'.'</option>';
		$out.='<option value="1">'.'Abono'.'</option>';
		print '</select>';
	
		return $out;
	}		

//fin de la clase contabilidad
}


//***funciones independientes***// 


function calculaSumas($id) {
	global $db;
	
	$sql = "SELECT mp.TipoMovto, Sum(mp.Importe) AS SumaDeImporte";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
	$sql.= " GROUP BY mp.IdPoliza, mp.TipoMovto";
	$sql.= " HAVING mp.IdPoliza=".$id;
	$resql=$db->query($sql);
	if ($resql) {
		$i=1;
		while ($row = $db->fetch_array($resql))  {
			$rows[$i] = array($row[0], $row[1]);
			$i++;	
		}
	}
	else {
		print "Error al tratar de obtener informacion de la poliza y sus movimientos. Intente de nuevo o Contacte al administrador: ".$idpoliza; exit;
	}
	$db->free($resql);
	return $rows;	
}

function selectPeriodo($selected='',$htmlname='idperiodo',$disabled=0,$firstline=true) {
	global $db;
	
	$html='';
	$sql = "SELECT rowid, number, month";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_periodos";
	$sql.= " ORDER BY number";
	$resql2 = $db->query($sql);//die($sql);
	if ($resql2) {			
		if ($disabled == 0) $html.= '<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) $html.= '<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';	
		if ($firstline) $html.= '<option value="" style="font-style:italic">'."".'</option>';
		$num = $db->num_rows($resql2);
		$i = 1;
		while ($i < $num+1)
		{
			$obj2 = $db->fetch_object($resql2);
	        if ($selected == $obj2->rowid) {
				$html.= '<option value="'.$obj2->rowid.'" selected ="true">'.$obj2->month.'</option>';
			}
			else {
				$html.= '<option value="'.$obj2->rowid.'">'.$obj2->month.'</option>';
			}
			$i++;	
		}
		$html.= '</select>';
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
	return $html;		
}

function selectEjercicio($selected='',$htmlname='idejercicio',$disabled=0,$firstline=true) {
	global $db;
	
	$html = '';
	
	$sql = "SELECT rowid, number, year";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_ejercicios";
	$sql.= " ORDER BY year";
	$resql2 = $db->query($sql);//die($sql);
	if ($resql2) {			
		if ($disabled == 0) $html.= '<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) $html.= '<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';	
		if ($firstline) $html.= '<option value="" style="font-style:italic">'."".'</option>';
		$num = $db->num_rows($resql2);
		$i = 1;
		while ($i < $num+1)
		{
			$obj2 = $db->fetch_object($resql2);
	        if ($selected == $obj2->year) {
				$html.= '<option value="'.$obj2->year.'" selected ="true">'.$obj2->year.'</option>';
			}
			else {
				$html.= '<option value="'.$obj2->year.'">'.$obj2->year.'</option>';
			}
			$i++;	
		}
		$html.= '</select>';
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
	return $html; 
}

function selectTipoPol($selected=1,$htmlname='idtipopol',$disabled=0) {
	global $db;
	$out = '';
	
	$sql = "SELECT rowid, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_tipospol";
	$sql.= " ORDER BY rowid";
	$resql2 = $db->query($sql);//die($sql);
	if ($resql2) {			
		if ($disabled == 0) print '<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) print '<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';
		print '<option value="" style="font-style:italic">'."".'</option>';	
		$num = $db->num_rows($resql2);
		$i = 1;
		while ($i < $num+1)
		{
			$obj2 = $db->fetch_object($resql2);
	        if ($selected == $obj2->rowid) {
				print '<option value="'.$obj2->rowid.'" selected ="true">'.$obj2->label.'</option>';
			}
			else {
				print '<option value="'.$obj2->rowid.'">'.$obj2->label.'</option>';
			}
			$i++;	
		}
		print '</select>';
	}
	else
	{
		dol_print_error($db);
		return -1;
	}		
}

function selectTipoCta($selected=1,$htmlname='idtipocta',$disabled=0) {
	global $db;

	$html='';
	$sql = "SELECT rowid, abrev, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_tiposcta";
	$sql.= " ORDER BY abrev";
	$resql2 = $db->query($sql);//die($sql);
	if ($resql2) {			
		if ($disabled == 0) $html.='<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) $html.='<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';
		$html.='<option value="" style="font-style:italic">'."".'</option>';	
		$num = $db->num_rows($resql2);
		$i = 1;
		while ($i < $num+1)
		{
			$obj2 = $db->fetch_object($resql2);
	        if ($selected == $obj2->rowid) {
				$html.='<option value="'.$obj2->rowid.'" selected ="true">'.$obj2->label.'</option>';
			}
			else {
				$html.='<option value="'.$obj2->rowid.'">'.$obj2->label.'</option>';
			}
			$i++;	
		}
		$html.='</select>';
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
	return $html;
}

function selectNivelCta($selected=1,$htmlname='idnivelcta',$disabled=0) {
	global $db;
	$html = '';
	
	$sql = "SELECT rowid, code, abrev, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_nivelcta";
	$sql.= " ORDER BY abrev";
	$resql2 = $db->query($sql);//die($sql);
	if ($resql2) {			
		if ($disabled == 0) $html.='<select name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($disabled == 1) $html.='<select name="'.$htmlname.'" id="'.$htmlname.'" disabled>';
		$html.='<option value="" style="font-style:italic">'."".'</option>';	
		$num = $db->num_rows($resql2);
		$i = 1;
		while ($i < $num+1)
		{
			$obj2 = $db->fetch_object($resql2);
	        if ($selected == $obj2->rowid) {
				$html.='<option value="'.$obj2->rowid.'" selected ="true">'.$obj2->label.'</option>';
			}
			else {
				$html.='<option value="'.$obj2->rowid.'">'.$obj2->label.'</option>';
			}
			$i++;	
		}
		$html.='</select>';
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
	return $html;
}

/**funciones**/
/**argumentos**/
	//$etiquetas: array: los titulos de los campos / $campos: array: los valores de cada campo / $titgpo: str: el titulo de cada grupo
	//$ren: int: el total de campos a mostrar por cada grupo
	//$tipocontrol: array: los tipo de control a mostrar (0:etiqueta, 1: input, 2: select(con opciones), 3: select(con catalogo)
	//$datos_sel: array: los parametros del campo select / $datos_idactiva:array: los campos de id de los datos del select 
	//$datos_nactiva:array:las etiquetas de los datos del select
	//$names:array: nombre de los campos opcionales del control select
function print_label($etiquetas,$campos,$titgpo,$ren)
{
	print '<tr><td align="center" colspan="2" bgcolor="#3B5998" style="color:white;" style="font-style:bold">'.$titgpo.'</td></tr>';
	for ($i = 0; $i < $ren; $i++) {
		print '<tr><td class="titlefield">'.$etiquetas[$i].'</td>';
		print '<td class="valuefield">'.$campos[$i].'</td></tr>';
	}	
	
}

function select_simple($namesel, $idelem='', $idelem_arr, $name_arr, $numren,$mostrar_seleccione='')
{
	$html='';
	$html.= '<select name="'.$namesel.'" id="'.$namesel.'">';
	if ($mostrar_seleccione) $html.='<option value="" style="font-style:italic">'."".'</option>';
	$i = 0;
	while ($i < $numren)
	{
        if ($idelem_arr[$i]==trim($idelem)) $html.='<option selected="true" value="'.$idelem_arr[$i].'">'.$name_arr[$i].'</option>';
		else $html.='<option value="'.$idelem_arr[$i].'">'.$name_arr[$i].'</option>';
		$i++;//echo $idelem;

	}
	$html.='</select>';
	return $html;
}

// namesel: nombre e id del control sel, table: tabla, idfield: id del registro seleccionado, code:primer campo de la lista, fieldcnd: campo de la condicion de busqueda
// cnd: condicion de la busqueda, fieldsort: campo por el ordenamiento de la list, fieldid: nombre del campo principal que contiene la llave primaria
//						1		2		3		4		5		6			7			8			9
function select_cat($namesel,$table,$idfield,$code,$names='',$fieldcnds='',$cnds='',$fieldsort='',$fieldid)
{
	global $db;	//acceso a las funciones de base de datos
	
	$db->begin();
	
	$sql = "SELECT ".$fieldid." as rowid,".$code." as code";
	if ($names) {
		$i=1;//asigno un indice
		foreach($names as $name) {
			$sql.= ",".$name." as name".$i;
			$i++;
		}
	}
	$sql.= " FROM ".$table;
	if ($fieldcnds) 
	{
		$i=0;//asigno un indice
		foreach($fieldcnds as $fieldcnd) {
			if ($i==0) 	$sql.= " WHERE ".$fieldcnd." = ".$cnds[$i];
			else $sql.= " AND ".$fieldcnd." = ".$cnds[$i];
			$i++;
		}
	}
	if ($fieldsort) $sql.= " ORDER BY ".$fieldsort;
	
	$resql2 = $db->query($sql);
	if ($resql2)
	{
		print '<select name="'.$namesel.'" id="'.$namesel.'">';
		print '<option value="" style="font-style:italic">'."SELECCIONE...".'</option>';
		while ($array = $db->fetch_array($resql2)) {	
			if ($array['rowid']==$idfield) 
			{
				print '<option selected="true" value="'.$array['rowid'].'">'.$array['code'];
				if ($names) {
					$i=2;
					foreach($names as $name) {
						print "-".$array[$i];
						$i++;
					}
				}
				print '</option>';
			}
			else
			{ 
				print '<option value="'.$array['rowid'].'">'.$array['code'];
				if ($names) {
					$i=2;//asigno un indice
					foreach($names as $name) {					
						print "-".$array[$i];
						$i++;
					}
				}
				print '</option>';
			}
			$j++;	
		}
	}
	else
	{
		dol_print_error($db);
		return -1;
	}		
	print '</select>';
	
} 

/* Funcion para mostrar en un cuadro la lista del Ejericio y Periodo Actual
*/
function showEjercPeriodContab($idelem, $titulos, $modify=1, $peractual='', $perclosed='') {
	global $db, $conf,  $langs;
	
	$html = '';

	$colspan = count($titulos)+1;
	$html.= '<tr>';
	$html.= '<td align="left" bgcolor="#3B5998" style="color:white;" style="font-style:bold" colspan="'.$colspan.'">Periodos Contables</td>';
	$html.= '</tr>';
	$html.= '<tr class="liste_titre">';
	foreach($titulos as $titulo) {
		$html.=	'<td align="center" style="font-weight:bold">'.$titulo.'</td>';
	}
	$html.=	'<td align="center" style="font-weight:bold">Valor</td>';
	$html.= '</tr>';
	$var = true;
    
    $html.= '<form name="edit" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    $html.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    $html.= '<input type="hidden" name="action" value="'.($modify==1?'editperiodcontab':'updateperiodcontab').'">';
    
    $html.= '<tr '.$bc[$var].'>';
    $html.=	'<td align="left" style="font-weight:bold">Ejercicio Actual</td>';
    $html.=	'<td align="right" style="font-weight:bold">'.($modify==2?selectEjercicio($conf->global->FISCAL_YEAR):$conf->global->FISCAL_YEAR).'</td>';
    $html.= "</tr>\n";

    $var=!$var;
    $html.= '<tr '.$bc[$var].'>';
    $html.=	'<td align="left" style="font-weight:bold">Periodo Actual</td>';
    $html.=	'<td align="right" style="font-weight:bold">'.($modify==2?selectPeriodo($conf->global->PERIOD_CONTAB):$langs->trans(date('F',mktime(0, 0, 0, $conf->global->PERIOD_CONTAB, 1, 2000)))).'</td>';
    $html.= "</tr>\n"; 
    
    if ($peractual) {
        $html.= '<tr><td>&nbsp;</td></tr>';
        $html.= '<tr '.$bc[$var].'>';
        $html.=	'<td align="left" style="font-weight:bold">Ejercicio Inicial Empresa</td>';
        $html.=	'<td align="right" style="font-weight:bold">'.($modify==2?selectEjercicio($conf->global->FISCAL_YEAR_IN,'idejercicio_in'):$conf->global->FISCAL_YEAR_IN).'</td>';
        $html.= "</tr>\n";

        $var=!$var;
        $html.= '<tr '.$bc[$var].'>';
        $html.=	'<td align="left" style="font-weight:bold">Periodo Inicial Empresa</td>';
        $html.=	'<td align="right" style="font-weight:bold">'.($modify==2?selectPeriodo($conf->global->PERIOD_CONTAB_IN,'idperiodo_in'):$langs->trans(date('F',mktime(0, 0, 0, $conf->global->PERIOD_CONTAB_IN, 1, 2000)))).'</td>';
        $html.= "</tr>\n";
    }
    
    if ($perclosed) {
        $html.= '<tr><td>&nbsp;</td></tr>';
        $html.= '<tr '.$bc[$var].'>';
        $html.=	'<td align="left" style="font-weight:bold">Último Ejercicio Cerrado</td>';
        $html.=	'<td align="right" style="font-weight:bold">'.getLastPerClosed('ejercicio').'</td>';
        $html.= "</tr>\n";

        $var=!$var;
        $html.= '<tr '.$bc[$var].'>';
        $html.=	'<td align="left" style="font-weight:bold">Último Periodo Cerrado</td>';
        $html.=	'<td align="right" style="font-weight:bold">'.$langs->trans(date('F',mktime(0, 0, 0, getLastPerClosed('periodo'), 1, 2000))).'</td>';
        $html.= "</tr>\n";
    }
    
    if ($modify) {
        $html.= '<tr><td>&nbsp;</td></tr>'; 
        $html.= '<tr class="tabsAction">';
        $html.= '<td>&nbsp;</td>';
        $html.= '<td align="right"><input type="submit" class="button" value="'.($modify==1?$langs->trans('Modify'):$langs->trans('Save')).'">';
        if ($modify==2) $html.= '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Cancelar").'</a>';
        $html.= '</td>';
        $html.= '</tr>';
    }
    
    $html.= '</form>';
    
    $html.= '<tr><td>&nbsp;</td></tr>';    
	
    return $html;      
}


/* Funcion para devoler el último Ejericio o Periodo contable CERRADO */
function getLastPerClosed($period) {
    global $db;
    
    $db->begin();
    $sql = "SELECT ejercicio, periodo, cierre, status";
	$sql.= " FROM ".MAIN_DB_PREFIX."contab_cierres";
    $sql.= " WHERE 1";
    $SQL.= " AND cierre = '1'";
	$sql.= " ORDER BY ejercicio DESC, periodo DESC";
    $sql.= " LIMIT 1"; //print $sql;
    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result)) {
            $obj = $db->fetch_object($result);
            $ejercicio = $obj->ejercicio;
            $periodo = $obj->periodo;
        }
        else {
            return null;
        }
    }
    else {
        dol_print_error($this->db);
        return -1;				
    }
    
    $db->free($result);
    
    if ($period == 'ejercicio') return $ejercicio;
    else if ($period == 'periodo') return $periodo;
}

?>






 

