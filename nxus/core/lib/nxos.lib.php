<?php
/* Copyright (C) 2017 Leopoldo Campos Carrillo */


/* funcion que verifica la relacion o relaciones de un cfdi con otros elementos del sistema */
//$idelem1				int: id del elemento fuente o del elemento del que se requiere conocer sus nexos
//$type_elem1			int: tipo del elemento->cfdi = 2
//$idelem2				int:optional id del elemento 2 si se requiere conocer especificamente la relación del elemento 1 con otro elemento en especifico
//$type_elem2			int:optional tipo del elemento 2 si se requiere conocer la relacion del elemento 1 con un tipo especifico
//$type_rel				int:optional tipo de relacion entre el elemento 1 y el elemento 2 (0 elem1 - elem2, 1 elem1->elem2, 2 elem1<-elem2, 3 elem1<->elem2)
//$status				int:optional tipo del status de la relacion (0 baja pero no eliminable, 1 activo eliminable, 2 activo no eliminable)
//return $rows			array:true devuelve un arreglo que contiene las relaciones y sus elementos
//return null			array:false en caso de que no existan relaciones o haya un error devuelve el valor de nulo

function getNexus($idelem1, $type_elem1, $idelem2 = '', $type_elem2 = '', $type_rel = 0, $status = 1)
{

	global $db;

	//				0			1			2			3				4			5			6
	$sql = "SELECT n.rowid, n.fk_elem1, n.type_elem1, n.fk_elem2, n.type_elem2, n.type_rel, n.status,";
	//				7				8				9			  10			11				12				13				14
	$sql .= " t1.code as c1, t1.abrev as a1, t1.label as l1, t1.status as s1, t1.list as l1, t1.card as c1, t1.table as ta1, t1.ref_field as r1,";
	//				15			  16				17			18					19				20			21					22
	$sql .= " t2.code as c2, t2.abrev as a2, t2.label as l2, t2.status as s2, t2.list as l2, t2.card as c2, t2.table as ta2, t2.ref_field as r2";
	$sql .= " FROM " . MAIN_DB_PREFIX . "nexus as n";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "nexus_type as t1 ON n.type_elem1 = t1.rowid";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "nexus_type as t2 ON n.type_elem2 = t2.rowid";
	$sql .= " WHERE (n.type_elem1 = " . $type_elem1 . " AND n.fk_elem1 = " . $idelem1 . ")"; //busca primero las relaciones de lado izquierdo del elemento 1
	if ($idelem2) "AND n.fk_elem2 = " . $idelem2; //se quiere buscar el nexo con otro elemento
	else if ($type_elem2) "AND n.type_elem2 = " . $type_elem2;	//se quiere buscar el nexo con los elementos que sean del tipo elem2
	else $sql .= " OR (n.type_elem2 = " . $type_elem1 . " AND n.fk_elem2 = " . $idelem1 . ")";  //luego busca las relaciones de lado derecho del elemento 1
	$sql .= " ORDER BY n.rowid"; //print $sql;exit;

	$resql = $db->query($sql);
	if ($resql) {
		$i = 1;
		while ($row = $db->fetch_array($resql)) {
			//arreglo con los datos de los conceptos de la relaciones, el primer elemento del arreglo significa la cantidad de campos de la consulta
			//				  0		1		2		3		  4			5		6		 7
			$rows[$i] = array(
				23, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6],
				//						8		9		10			11		12		13			14
				$row[7], $row[8], $row[9], $row[10], $row[11], $row[12], $row[13],
				//						15		16			17		 18			19		20		21
				$row[14], $row[15], $row[16], $row[17], $row[18], $row[19], $row[20],
				//						22		23
				$row[21], $row[22]
			); //el ultimo elemento del arreglo es el numero de campos totales de la consulta
			$i++;
		}
		if ($i == 1) return null;	//no econtro ninguna relacion, se devueleve el valor de null
	} else {
		// no existe un $id del elemento principal así que regresamos un valor nulo
		//print "Error al tratar de obtener informacion de los nexos del objeto. Intente de nuevo o Contacte al administrador: ".$id; exit;
		return null;
	}
	$db->free($resql);
	return $rows;
}

/*Funcion para obtener la URL de un objeto referenciado */
//$id						int:id del objeto del que se va a obtener el URL
//$ref						string: nombre del campo que contiene la referencia del objeto
//$table					string: la tabla principal del objeto
//$file						string: ubicacion URL del archivo de la tarjeta(card) principal el objeto
function getNxusUrl($id, $ref, $table, $file)
{

	global $db;

	$sql = "SELECT o.rowid, o." . $ref . " as ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . $table . " as o";
	$sql .= " WHERE o.rowid = " . $id; //print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			$obj = $db->fetch_object($resql);
			$lien = '<a href="' . DOL_URL_ROOT . $file . '?id=' . $id . '">' . $obj->ref . '</a>';
		} else {
			return null;
		}
	} else {
		print "Error al tratar de obtener el URL del objeto. Intente de nuevo o Contacte al administrador: " . $sql;
		exit;
	}
	return $lien;
}


/* funcion que muestra los nexos de los documentos */
//$idelem1				int: id del elemento fuente o del elemento del que se requiere conocer sus nexos
//$type_elem1			int: tipo del elemento->cfdi = 2
//$idelem2				int:optional id del elemento 2 si se requiere conocer especificamente la relación del elemento 1 con otro elemento en especifico
//$type_elem2			int:optional tipo del elemento 2 si se requiere conocer la relacion del elemento 1 con un tipo especifico
function listNexus($idelem1, $typelem1, $idelem2 = '', $typelem2 = '', $ref = '')
{

	global $db;

	$html = '';

	$html .= '<tr>';
	$html .= '<td align="left" bgcolor="#3B5998" style="color:white;" style="font-style:bold" colspan="2">NEXOS</td>';
	$html .= '</tr>';
	$html .= '<tr class="liste_titre">';
	$html .=	'<td align="center" style="font-weight:bold">Referencia</td>';
	$html .=	'<td align="center" style="font-weight:bold">Tipo</td>';
	$html .= '</tr>';
	$rels = getNexus($idelem1, $typelem1);
	if ($rels) {
		$var = true;
		foreach ($rels as $rel) {

			$var = !$var;

			$html .= '<tr>';
			if ($rel[3] == $typelem1) {
				$refes = getNxusUrl($rel[4], $rel[23], $rel[22], $rel[21]); //si el tipo del elem1 es del objeto buscado los datos por el lado izquierdo
				$type = $rel[18];
			} else { //print "idelem1:".$idelem1.", typelem1:".$typelem1.", ".$rel[3]; print "id:".$rel[2].", table:".$rel[14].", file".$rel[13];
				$refes = getNxusUrl($rel[2], $rel[15], $rel[14], $rel[13]); //el elem2 es el objeto buscado
				$type = $rel[10];
			}
			$html .= '<td align="center">';
			$html .= $refes;
			$html .= '</td>';
			$html .= '<td align="center">';
			$html .= $type;
			$html .= '</td>';

			$html .= "</tr>\n";
		}
	}

	return $html;
}  //fin de funcion listNexus


/* funcion que muestra los movimientos nexos */
//$idelem1				int: id del elemento fuente o del elemento del que se requiere conocer sus nexos
//$titulos				array: titulo de cada columna de las condiciones requeridas
//range					int: el numero de renglones que tendra la tabla, uno por cada condicion
//rens					array: con los nombres de las condiciones de cada columna
//$fieldcnds			array: arreglo que contiene las condiciones de la busqueda de los nexos
//$cnds					array: arreglo con las condiciones de los campos de $fieldscnds
//$field				string: nombre del campo que contiene la llave foranea que es el nexo con el idelem
//$table				string: nombre de la tabla que contiene los nexos(movimientos) del elemento
//$list					string: ubicacion de la lista de los movimientos del elemento
//$fieldlist			string: nombre del campo de la lista donde se filtrara el elemento
//$searchs				string: variable search de los formularios list
function listNxusMovs($object)
{

	global $conf;

	$list = '/custom/contab/polizas/listmov.php';

	$html = '';

	$html .= '<table class="centpercent noborder">';
	$html .= '<tr>';
	$html .= '<td align="left" bgcolor="#3B5998" style="color:white;" style="font-style:bold" colspan="2">MOVIMIENTOS</td>';
	$html .= '</tr>';
	$html .= '<tr class="liste_titre">';
	$html .=	'<td align="center" style="font-weight:bold">Ejercicio</td>';
	$html .=	'<td align="center" style="font-weight:bold">Movimientos</td>';
	$html .= '</tr>';
	$rels = getNxusMov($object->id);
	$rens = getPeriodos();
	foreach ($rens as $ren) {
		$html .= "<tr>";
		$html .= '<td align="center">';
		$html .= $ren;
		$html .= '</td>';
		$html .= '<td align="center">';
		$html .= $rels[$ren] ? '<a href="' . $list . '?mode=1&search_codigo=' . $object->codigo . '&search_ejercicio=' . $ren . '">' . $rels[$ren] . '</a>' : 0;
		$html .= '</td>';
		$html .= "</tr>\n";
	}
	// Se agrega el periodo actual
	$movsPeriodo = getMovsPeriodo($object->id, $conf->global->PERIOD_CONTAB, $conf->global->FISCAL_YEAR);
	$html .= "<tr>";
	$html .= '<td align="center">';
	$html .= 'Periodo actual';
	$html .= '</td>';
	$html .= '<td align="center">';
	$html .= $movsPeriodo ? '<a href="' . $list . '?mode=1&search_codigo=' . $object->codigo . '&search_ejercicio=' . $conf->global->FISCAL_YEAR . '&search_periodo=' . $conf->global->PERIOD_CONTAB . '">' . $movsPeriodo . '</a>' : 0;
	$html .= "</table>\n";

	return $html;
}  //fin de funcion listNxusMovs

/*Funcion para eliminar el nexo de un elemento */
//$rowid						int:rowid de la table nexus a eliminar
function delNxus($rowid)
{

	global $db;

	$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'nexus WHERE rowid = ' . $rowid;
	$resql = $db->query($sql);
	if ($resql) return 1;
	else return null;
}	//fin de la funciondel Nexus

/*Funcion para crear un nexo de un elemento */
//$idelem1								int:id del elemento 1
//$type_elem1							int:tipo del elemento 1
//$idelem2								int:id del elemento 2
//$type_elem2							int:tipo del elemento 2
//$type_rel								int:tipo de relacion
function creaNxus($idelem1, $type_elem1, $idelem2, $type_elem2, $type_rel = 0)
{

	global $db;

	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "nexus (";
	$sql .= " fk_elem1"; 			//1						
	$sql .= ", type_elem1"; 			//2
	$sql .= ", fk_elem2";  			//3
	$sql .= ", type_elem2"; 			//4
	$sql .= ", type_rel";  			//5	
	$sql .= ") VALUES (";
	$sql .= "'" . $idelem1 . "'";
	$sql .= ", '" . $type_elem1 . "'";
	$sql .= ", '" . $idelem2 . "'";
	$sql .= ", '" . $type_elem2 . "'";
	$sql .= ", '" . $type_rel . "'";
	$sql .= ")"; //print $sql; exit;	
	$result = $this->db->query($sql);
	if ($result) {
		$idnx = $db->last_insert_id(MAIN_DB_PREFIX . "nexus");
		return $idnx;
	} else {
		return null;
	}
}	//fin de la funciondel Nexus

/* funcion para obtener los movimientos de un elemento en una tabla especifica*/
//$idelem						int: id del elemento a obtener los nexos
//$field						string: nombre del campo que contiene la llave foranea que es el nexo con el idelem
//$table						string: nombre de la tabla que contiene los nexos(movimientos) del elemento
//$fieldcnds					array: arreglo que contiene las condiciones de la busqueda de los nexos
//$cnds							array: arreglo con el valor de las condiciones especificadas en $fieldcnds
//$list							string: ubicacion de la lista de los movimientos del elemento
//$fieldlist					string: nombre del campo de la lista donde se filtrara el elemento
//val							string: valor que debe tener la variable $fieldlist para buscar por ese parametro
//$search						string: variable $search del campo list
function getNxusMov($idCuenta)
{

	global $db;

	$sql = "SELECT COUNT(*) AS Movs, Ejercicio FROM " . MAIN_DB_PREFIX . "contab_polizas_movs WHERE IdCuenta = " . sanitizeVal($idCuenta);
	$sql .= " GROUP BY Ejercicio ORDER BY Ejercicio ASC";

	$resql = $db->query($sql);
	$res = array();
	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$movs = $row['Movs'];
			$ejercicio = $row['Ejercicio'];
			$res[$ejercicio] = $movs;
		}
	}
	return $res;
}

function getMovsPeriodo($idCuenta, $periodo, $ejercicio)
{

	global $db;

	$sql = "SELECT COUNT(*) AS Movs FROM " . MAIN_DB_PREFIX . "contab_polizas_movs WHERE IdCuenta = " . sanitizeVal($idCuenta);
	$sql .= " AND Periodo = " . sanitizeVal($periodo);
	$sql .= " AND Ejercicio = " . sanitizeVal($ejercicio);

	$resql = $db->query($sql);
	$res = '';
	if ($resql) {
		while ($row = $db->fetch_array($resql)) {
			$movs = $row['Movs'];
			$res = $movs;
		}
	}
	return $res;
}
function getPeriodos()
{
	global $conf;

	$range = $conf->global->FISCAL_YEAR - $conf->global->FISCAL_YEAR_IN + 1;
	$rows = array();
	$fieldcnds = array();
	$eje = $conf->global->FISCAL_YEAR_IN;
	for ($i = 0; $i < $range; $i++) {
		$rows[$i] = $eje;
		$fieldcnds[$i] = 'Ejercicio';
		$searchs[$i] = 'search_ejercicio';
		$eje++;
	}
	$rens = $rows;
	return $rens;
}
