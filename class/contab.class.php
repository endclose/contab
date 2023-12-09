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
class Contabilidad
{
	public $element='contabildiad';
	public $table_element='contabilidad';
	public $fk_element='fk_contabilidad'; 

	//! Identifiant unique
	var $id ;
	var $nombre;
	
	var $FechaRegistro;
	var $Afectable;
	
    var $ejercicio_act; //ejercicio contable int
    var $periodo_act; //periodo contable int

    var $ejercicio_in; //ejercicio contable int
    var $periodo_in; //periodo contable int

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
	
	/* FUNCION QUE CREA UNA POLIZA PREDEFINIDA*/
	// $datos_pol 				array: arreglo conteniendo los datos para la contabilizacion
	//							(0): tipo de operacion (0:ventas, 1:compras 2:bancos)
	//							(1): tipo_poliza (2:ingresos, 1:egresos, 3:diario)
	//							(2): ejercicio contable
	//							(3): periodo contable
	//							(4): concepto de la poliza
	//							(5): fecha de la poliza
	//							(6): total de cargos
	//							(7): total de abonos
	function contabilizar($datos_pol, $datosc_pol) {
		
		//Obtenemos el Id Next de la Poliza
//		$id = getNextId('Id_Poliza');
		
		
		//Generamos el RowVersion solo para compatibilidad con contpaq
/*		$rowversion = mt_rand();
		
		//Generamos la cadena hexadecimal (guid) solo para compatibilidad con contpaq
		$parte1 = dechex(mt_rand(0,4294967295));
		$parte2 = dechex(mt_rand(0,42949));
		$parte3 = dechex(mt_rand(0,42949));
		$parte4 = dechex(mt_rand(0,42949));						
		$parte5 = dechex(mt_rand(0,4294967295));
		$parte6 = dechex(mt_rand(0,42949));
		$guid = strtoupper($parte1."-".$parte2."-".$parte3."-".$parte4."-".$parte5.$parte6);*/
		$rowversion = "";
		$guid ="";
		
		//obtenemos el siguiente numero de poliza
		$sql = "SELECT p.Folio FROM ".MAIN_DB_PREFIX."contab_polizas as p";
		$sql.= " WHERE Periodo=".$datos_pol[3]." AND Ejercicio=".$datos_pol[2]." AND TipoPol=".$datos_pol[1];
		$sql.= " ORDER BY p.Folio DESC LIMIT 0,1";//print $sql;exit;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
		$folio = $obj->Folio + 1;
		
		$row = array();
//		$row[0] = $id; 
		$row[1] = $rowversion; $row[2] = $datos_pol[2]; $row[3] = $datos_pol[3]; $row[4] = $datos_pol[1]; $row[5] = $folio; $row[6] = '1'; 
		$row[7] = 0; $row[8] = $datos_pol[4]; $row[9] = $datos_pol[5]; $row[10] = $datos_pol[6]; $row[11] = $datos_pol[7]; $row[12] = '0'; $row[13] = '11';
		$row[14] = 0; $row[15] = '1'; $row[16] = 1; $row[17] = 0; $row[18] = ''; $row[19] = ''; $row[20] = ''; $row[21] = $guid; $row[22] = 0;
//consulta MySQL		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contab_polizas (";
//		$sql.= "rowid";  				//0
		$sql.= "RowVersion";	  		//1
		$sql.= ", Ejercicio"; 			//2
		$sql.= ", Periodo";  			//3
		$sql.= ", TipoPol";  			//4
		$sql.= ", Folio";  				//5
		$sql.= ", Clase";				//6
		$sql.= ", Impresa";				//7
		$sql.= ", Concepto";			//8
		$sql.= ", Fecha";				//9
		$sql.= ", Cargos";				//10
		$sql.= ", Abonos";				//11
		$sql.= ", IdDiario";			//12
		$sql.= ", SistOrig";			//13
		$sql.= ", Ajuste";				//14
		$sql.= ", IdUsuario";			//15
		$sql.= ", ConFlujo";			//16
		$sql.= ", ConCuadre";			//17
		$sql.= ", TimeStamp";			//18
		$sql.= ", RutaAnexo";			//19
		$sql.= ", ArchivoAnexo";		//20
		$sql.= ", Guid";				//21
		$sql.= ", tieneDoctoBancario";	//22
		$sql.= ") VALUES (";
//		$sql.=$row[0];
		$sql.= "'".$row[1]."'";
		$sql.= ", '".$row[2]."'";
		$sql.= ", '".$row[3]."'";
		$sql.= ", '".$row[4]."'";
		$sql.= ", '".$row[5]."'";
		$sql.= ", '".$row[6]."'";
		$sql.= ", ".$row[7];
		$sql.= ", '".$row[8]."'";
		$sql.= ", '".$this->db->idate($row[9])."'";
		$sql.= ", '".$row[10]."'";
		$sql.= ", '".$row[11]."'";
		$sql.= ", '".$row[12]."'";
		$sql.= ", '".$row[13]."'";
		$sql.= ", ".$row[14];
		$sql.= ", '".$row[15]."'";
		$sql.= ", ".$row[16];
		$sql.= ", ".$row[17];
		$sql.= ", '".$row[18]."'";
		$sql.= ", '".$row[19]."'";
		$sql.= ", '".$row[20]."'";
		$sql.= ", '".$row[21]."'";		
		$sql.= ", ".$row[22];
		$sql.= ")";//print $sql; //exit;

		//insertamos la poliza
		$result = $this->db->query($sql);
		if ( $result )
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."contab_polizas");

//Insertamos los movimientos de la poliza				
				
			$nm = 1;		//variable con el numero de movimiento
				
			foreach($datosc_pol[0] as $cuenta) {
					//Obtenemos el Id Next de los movimientos de la poliza
//					$idm = getNextId('Id_MovimientoPoliza');				

				//Generamos el RowVersion solo para compatibilidad con contpaq
/*				$rowversion = mt_rand();
				//Generamos la cadena hexadecimal (guid) solo para compatibilidad con contpaq
				$parte1 = dechex(mt_rand(0,4294967295));
				$parte2 = dechex(mt_rand(0,42949));
				$parte3 = dechex(mt_rand(0,42949));
				$parte4 = dechex(mt_rand(0,42949));						
				$parte5 = dechex(mt_rand(0,4294967295));
				$parte6 = dechex(mt_rand(0,42949));
				$guid = strtoupper($parte1."-".$parte2."-".$parte3."-".$parte4."-".$parte5.$parte6);*/ 
					
				$rw = array();
//				$rw[0] = $idm; 
				$rw[1] = $rowversion; $rw[2] = $id; $rw[3] = $datos_pol[2]; $rw[4] = $datos_pol[3]; 
				$rw[5] = $datos_pol[1];	$rw[6] = $folio; $rw[7] = $nm; $rw[8] = $datosc_pol[0][$nm]; $rw[9] = $datosc_pol[2][$nm]; 
				$rw[10] = $datosc_pol[1][$nm]; $rw[11] = 0; $rw[12] = $datosc_pol[3][$nm]; $rw[13] = $datos_pol[4]; $rw[14] = 0; 
				$rw[15] = $datos_pol[5]; $rw[16] = 0; $rw[17] = ''; $rw[18] = $guid; 
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."contab_polizas_movs (";
//				$sql.= "rowid";		  			//0
				$sql.= "RowVersion"; 			//1
				$sql.= ", IdPoliza"; 			//2						
				$sql.= ", Ejercicio"; 			//3
				$sql.= ", Periodo";  			//4
				$sql.= ", TipoPol";  			//5
				$sql.= ", Folio";  				//6
				$sql.= ", NumMovto";			//7
				$sql.= ", IdCuenta";			//8
				$sql.= ", TipoMovto";			//9
				$sql.= ", Importe";				//10
				$sql.= ", ImporteME";			//11
				$sql.= ", Referencia";			//12
				$sql.= ", Concepto";			//13
				$sql.= ", IdDiario";			//14
				$sql.= ", Fecha";				//15
				$sql.= ", IdSegNeg";			//16
				$sql.= ", TimeStamp";			//17
				$sql.= ", Guid";				//18
				$sql.= ") VALUES (";
//				$sql.= $rw[0];
				$sql.= "'".$rw[1]."'";
				$sql.= ", '".$rw[2]."'";
				$sql.= ", '".$rw[3]."'";
				$sql.= ", '".$rw[4]."'";
				$sql.= ", '".$rw[5]."'";
				$sql.= ", '".$rw[6]."'";
				$sql.= ", ".$rw[7];
				$sql.= ", '".$rw[8]."'";
				$sql.= ", ".$rw[9];
				$sql.= ", '".$rw[10]."'";
				$sql.= ", ".$rw[11];
				$sql.= ", '".$rw[12]."'";
				$sql.= ", '".$rw[13]."'";
				$sql.= ", ".$rw[14];
				$sql.= ", '".$rw[15]."'";
				$sql.= ", ".$rw[16];
				$sql.= ", '".$rw[17]."'";
				$sql.= ", '".$rw[18]."'";
				$sql.= ")";//print $sql; exit;
				$result = $this->db->query($sql);
				if ($result) {
					$idm = $this->db->last_insert_id(MAIN_DB_PREFIX."contab_polizas_movs");
					$actual = $this->ActualizaSaldosCuenta($rw[8]);
				}
				else {
					die("Error al insertar movimiento en dolibar. Contacte al Administrador".$id);
				}
				$nm++;
			}	//fin del ciclo de insertar movimiento	
		}
		else
		{
			print "Error al insertar la poliza en dolibarr".$sql;exit;
		}
		return $id;
	} //fin de la funcion de contabilizar

	/* FUNCION QUE CREA UNA POLIZA PREDEFINIDA*/
	// $datos_pol 				array: arreglo conteniendo los datos para la contabilizacion
	// $idpol					int: id de la poliza a actualizar
	//							(0): tipo de operacion (0:ventas, 1:compras 2:bancos)
	//							(1): tipo_poliza (2:ingresos, 1:egresos, 3:diario)
	//							(2): ejercicio contable
	//							(3): periodo contable
	//							(4): concepto de la poliza
	//							(5): fecha de la poliza
	//							(6): total de cargos
	//							(7): total de abonos
	function actualizar($datos_pol, $datosc_pol,$id) {
		
		//Generamos el RowVersion solo para compatibilidad con contpaq
		$rowversion = mt_rand();
		
		$row = array();
		$row[0] = $id; $row[2] = $datos_pol[2]; $row[3] = $datos_pol[3]; $row[4] = $datos_pol[1]; $row[6] = '1'; $row[7] = 0; $row[8] = $datos_pol[4];  
		$row[9] = $datos_pol[5]; $row[12] = '0'; $row[13] = '11';
		$row[14] = 0; $row[15] = '1'; $row[16] = 1; $row[17] = 0; $row[18] = ''; $row[19] = ''; $row[20] = ''; $row[22] = 0; 
		//$row[1] = $rowversion; $row[5] = $folio; $row[21] = $guid;  $row[10] = ($datos_pol[6]?$datos_pol[6]:0); $row[11] = ($datos_pol[7]?$datos_pol[7]:0);
//consulta MySQL		
		$sql = "UPDATE ".MAIN_DB_PREFIX."contab_polizas SET";
//		$sql.= " RowVersion=".$row[1]."'";  		//1
		$sql.= " Ejercicio=".$row[2];				//2
		$sql.= ", Periodo=".$row[3];	  			//3
		$sql.= ", TipoPol=".$row[4];	 			//4
//		$sql.= ", Folio='".$row[5]."'";	 			//5
		$sql.= ", Clase='".$row[6]."'";				//6
		$sql.= ", Impresa=".$row[7];				//7
		$sql.= ", Concepto='".$row[8]."'";			//8
		$sql.= ", Fecha='".$row[9]."'";				//9
//		$sql.= ", Cargos=".$row[10];				//10
//		$sql.= ", Abonos=".$row[11];				//11
		$sql.= ", IdDiario='".$row[12]."'";			//12
		$sql.= ", SistOrig='".$row[13]."'";			//13
		$sql.= ", Ajuste=".$row[14];				//14
		$sql.= ", IdUsuario='".$row[15]."'";		//15
		$sql.= ", ConFlujo=".$row[16];				//16
		$sql.= ", ConCuadre=".$row[17];				//17
		$sql.= ", TimeStamp='".$row[18]."'";		//18
		$sql.= ", RutaAnexo='".$row[19]."'";		//19
		$sql.= ", ArchivoAnexo='".$row[20]."'";		//20
//		$sql.= ", Guid=".$row[21]."'";				//21
		$sql.= ", tieneDoctoBancario=".$row[22];	//22
		$sql.= " WHERE rowid=".$id;
//print $sql; exit;

		//insertamos la poliza
		$result = $this->db->query($sql);
		if ( $result )
		{
			$nm = 1;		//variable con el numero de movimiento
		}
		else
		{
			print "Error al actualizar la poliza en dolibarr".$sql;exit;
		}
		//actualizamos el debe y el haber
		$sumas_iguales = calculaSumas($id);
		$total_cargos = $sumas_iguales[1][1];
		$total_abonos = $sumas_iguales[2][1];
		$this->actualizaSumas($id,round($total_cargos,2),round($total_abonos,2));			
		return $id;
	} //fin de la funcion de actualizar
	
	/* FUNCION QUE AGREGA UN MOVIMIENTO A UNA POLIZA*/
	function agregarMovimiento($datos_pol) {
		
		//Obtenemos el Id Next de los movimientos de la poliza
//		$idm = getNextId('Id_MovimientoPoliza');				
				
		//Generamos el RowVersion solo para compatibilidad con contpaq
		$rowversion = mt_rand();
		//Generamos la cadena hexadecimal (guid) solo para compatibilidad con contpaq
		$parte1 = dechex(mt_rand(0,4294967295));
		$parte2 = dechex(mt_rand(0,42949));
		$parte3 = dechex(mt_rand(0,42949));
		$parte4 = dechex(mt_rand(0,42949));						
		$parte5 = dechex(mt_rand(0,4294967295));
		$parte6 = dechex(mt_rand(0,42949));
		$guid = strtoupper($parte1."-".$parte2."-".$parte3."-".$parte4."-".$parte5.$parte6);
		
		$rw = array();
//		$rw[0] = $idm; 
		$rw[1] = $rowversion; $rw[2] = $datos_pol[0]; $rw[3] = $datos_pol[2]; $rw[4] = $datos_pol[3]; 
		$rw[5] = $datos_pol[4];	$rw[6] = $datos_pol[5]; $rw[7] = $datos_pol[30]; $rw[8] = $datos_pol[31]; $rw[9] = $datos_pol[32]; 
		$rw[10] = $datos_pol[33]; $rw[11] = 0; $rw[12] = $datos_pol[35]; $rw[13] = $datos_pol[8]; $rw[14] = 0; 
		$rw[15] = $datos_pol[9]; $rw[16] = 0; $rw[17] = ''; $rw[18] = $guid;//consulta MySQL		
	
//Insertamos los movimientos de la poliza				
			
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contab_polizas_movs (";
//		$sql.= "rowid";		  			//0
		$sql.= "RowVersion"; 			//1
		$sql.= ", IdPoliza"; 			//2						
		$sql.= ", Ejercicio"; 			//3
		$sql.= ", Periodo";  			//4
		$sql.= ", TipoPol";  			//5
		$sql.= ", Folio";  				//6
		$sql.= ", NumMovto";			//7
		$sql.= ", IdCuenta";			//8
		$sql.= ", TipoMovto";			//9
		$sql.= ", Importe";				//10
		$sql.= ", ImporteME";			//11
		$sql.= ", Referencia";			//12
		$sql.= ", Concepto";			//13
		$sql.= ", IdDiario";			//14
		$sql.= ", Fecha";				//15
		$sql.= ", IdSegNeg";			//16
		$sql.= ", TimeStamp";			//17
		$sql.= ", Guid";				//18
		$sql.= ") VALUES (";
//		$sql.= $rw[0];
		$sql.= "'".$rw[1]."'";
		$sql.= ", '".$rw[2]."'";
		$sql.= ", '".$rw[3]."'";
		$sql.= ", '".$rw[4]."'";
		$sql.= ", '".$rw[5]."'";
		$sql.= ", '".$rw[6]."'";
		$sql.= ", ".$rw[7];
		$sql.= ", '".$rw[8]."'";
		$sql.= ", ".$rw[9];
		$sql.= ", '".$rw[10]."'";
		$sql.= ", ".$rw[11];
		$sql.= ", '".$rw[12]."'";
		$sql.= ", '".$rw[13]."'";
		$sql.= ", ".$rw[14];
		$sql.= ", '".$rw[15]."'";
		$sql.= ", ".$rw[16];
		$sql.= ", '".$rw[17]."'";
		$sql.= ", '".$rw[18]."'";
		$sql.= ")";//print $sql; exit;
		$result = $this->db->query($sql);
		if ($result) {
			$idm = $this->db->last_insert_id(MAIN_DB_PREFIX."contab_polizas_movs");
			$actual = $this->ActualizaSaldosCuenta($rw[8]);
		}
		else {
			die("Error al insertar movimiento en dolibar. Contacte al Administrador".$id);
		}
	
	return $idm;
	} //fin de la funcion de contabilizar


/*FUNCION PARA ACTUALIZAR EL DEBE Y HABER DE LAS POLIZAS */
	function actualizaSumas($id, $debe, $haber) {

		//actualizamos dolibarr
        $sql = "UPDATE ".MAIN_DB_PREFIX."contab_polizas SET";
		$sql.= " Cargos=".$debe;
		$sql.= ", Abonos=".$haber;
		$sql.= " WHERE rowid=".$id;//print $sql; exit;
        $resql = $this->db->query($sql);

        if ($resql) {	
			return 1;
		}
		else {
			die("Error al actualizar los cargos y abonos en dolibar. Intente de nuevo.:".$id);	
		}
		
	}	//fin de la funcion de actualizar
	

/*FUNCION PARA BORRAR LAS POLIZAS Y SUS MOVIMIENTOS */
	function borrarPoliza($id) {

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'contab_polizas WHERE rowid = '.$id;
		$resql=$this->db->query($sql);
		if ($resql) {  //Si se pudo borrar la póliza ahora borramos la poliza de contpaq
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'contab_polizas_movs WHERE IdPoliza = '.$id;
			$resql2=$this->db->query($sql);
			if ($resql2) {  //Si se pudo borrar los movimientos de la póliza ahora los borramos de contpaq
				//Borramos sus nexos de la poliza
				//obtenemos sus nexos
				$nxus = getNexus($id,8);
				if ($nxus) { //si tiene nexos
					foreach($nxus as $nxo) $nxus = delNxus($nxo[1]);	
				}
				$actual = $this->ActualizaSaldosCuenta(null,1);
				return 1;
			}
			else {
				print "Error al eliminar los movimientos de la poliza en dolibar: ".$sql;exit;
			}
		}
		else {
			print "Error al eliminar la poliza en dolibarr: ".$sql;exit;
		}
		
	}

/*FUNCION PARA BORRAR LOS MOVIMIENTOS DE LAS POLIZAS Y SUS MOVIMIENTOS */
//$idmov			int:id del movimiento de la poliza
function borrarMovimiento($idmov) {
	//primero obtengo el id de la poliza de la que voy a eliminar el movimiento
	$sql = 'SELECT mp.IdPoliza, mp.IdCuenta FROM '.MAIN_DB_PREFIX.'contab_polizas_movs as mp WHERE mp.rowid = '.$idmov;
	$resql=$this->db->query($sql);
	$obj = $this->db->fetch_object($resql);
	$idpol = $obj->IdPoliza;
	$idcuenta = $obj->IdCuenta;
	//eliminamos el movimiento	
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'contab_polizas_movs WHERE rowid = '.$idmov;
	$resql=$this->db->query($sql);
	if ($resql) {	//se elimino el movimiento exitosamente procedemos a recalcular los num movimientos que quedaron y reasignarlos
		$sql = 'SELECT mp.rowid FROM '.MAIN_DB_PREFIX.'contab_polizas_movs as mp WHERE mp.IdPoliza = '.$idpol;	//seleccionamos los movimientos que quedan de la poliza
		$sql.= ' ORDER BY mp.rowid';
		$resql2=$this->db->query($sql);
		$numov=$this->db->num_rows($resql2);
		if ($resql2) {
			if ($numov > 0) {		//despues de la eliminacion del movimiento quedaron mas de un registro procedemos al reasignacion del num de movimiento
				$i=0;
				while ($row = $this->db->fetch_array($resql2))  {
					$rows[$i] = $row[0];
					$i++;	
				}
				//procedemos a la actualizacion de los num de movimientos restantes
				$i=1;
				foreach ($rows as $row) {
					$sql = "UPDATE ".MAIN_DB_PREFIX."contab_polizas_movs SET NumMovto=".$i." WHERE rowid = ".$row; 
					$resql3=$this->db->query($sql);
					$i++;
				}
				$actual = $this->ActualizaSaldosCuenta($idcuenta);			
			}
		}
		else {
			print "Error al tratar de obtener informacion de la poliza y sus movimientos. Modulo borrar movimiento. Intente de nuevo o Contacte al administrador: ".$idmov; exit;
		}
	}
	else {
		print "Error al tratar de eliminar el movimiento en dolibarr. Intente de nuevo o Contacte al administrador: ".$idmov; exit;
	}
	
	return $idpol;
	

} //fin funcion borrar movimiento

/*FUNCION PARA OBTENER LOS DATOS DE LAS POLIZAS Y SUS MOVIMIENTOS */
	function getPolizas($idpoliza) {
		//				0			1		2			3			4			5			6        7		
		$sql = 'SELECT p.rowid, p.Cargos, p.Abonos, m.IdCuenta, m.TipoMovto, m.Importe, m.NumMovto, p.status';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'contab_polizas as p';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_polizas_movs as m ON p.rowid = m.IdPoliza';
		$sql.= ' WHERE p.rowid = '.$idpoliza; //print $sql; exit;		
		$resql=$this->db->query($sql);
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				$mov = ord($row[4]);  //como es un campo tipo bit con la funcion ord la convertimos a un entero. Actualizacion converti mejor el campo a Tinyint
				$rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
				$i++;	
			}
		}
		else {
			print "Error al tratar de obtener informacion de la poliza y sus movimientos. Intente de nuevo o Contacte al administrador: ".$idpoliza; exit;
		}
		$this->db->free($resql);
		return $rows;
		
	} //fin de la funcion getPolizasVentas	
	
/*FUNCION PARA OBTENER EL TOTAL DE CARGOS Y ABONOS DE UNA CUENTA POR PERIODO */
//$idcuenta						int:id de la cuenta de la que se quiere obtener los cargos y abonos, si el valor es 0 se conultan los movs de todas las cuentas
//$periodo						int: periodo del que se quiere conocer el total de cargos y abonos 
//$ejercicio					int: ejercico del que se quiere conocer el total de cargos y abonos
//								null: se calculara el total de todos los movimientos que existan en la tabla de movimientos y de todas las cuentas
//$date							int: 1: se calculara a una fecha determinada 0: se calcula normal
	function getCargosAbonos($idcuenta=null, $periodo=null, $ejercicio=null) {
		
		$sql = "SELECT mp.TipoMovto";
		if($ejercicio) $sql .= ", mp.Ejercicio";
		if($periodo) $sql.= ", mp.Periodo";
		if($idcuenta) $sql.= ", mp.IdCuenta";				
		$sql.= ", SUM(mp.Importe) as sumaimporte";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
		if($idcuenta && $idcuenta>0)	$sql.= ' WHERE mp.IdCuenta = '.$idcuenta; //print $sql; exit;
		if($ejercicio) $sql.= " AND mp.Ejercicio='".$ejercicio."'";
		if($periodo) $sql.= " AND mp.Periodo='".$periodo."'";
		$sql.= " GROUP BY";
		if($ejercicio) $sql.= " mp.Ejercicio,";
		if($periodo) $sql.= " mp.Periodo,";
		if($idcuenta) $sql.= " mp.IdCuenta,";
		$sql.=" mp.TipoMovto";
		$sql.= " ORDER BY";		
		if($idcuenta) $sql.= " mp.IdCuenta,";
		if($ejercicio) $sql.= " mp.Ejercicio,";
		if($periodo) $sql.= " mp.Periodo,";
		$sql.= " mp.TipoMovto"; //print $sql;//exit;
		$resql=$this->db->query($sql);
		if ($resql) {
			$results_fields = $resql->fetch_fields();
			$i = 0;
    		while ($obj = $resql->fetch_object()) {
				if($obj->TipoMovto==0) $rows[$i][1] = $obj->sumaimporte; //es un cargo
				else $rows[$i][2] = $obj->sumaimporte;	//es un abono
				if (!$rows[$i][1]) $rows[$i][1] = 0;
				if (!$rows[$i][2]) $rows[$i][2] = 0;
				if ($ejercicio) $rows[$i][3] = $ejercicio;
				if ($periodo) $rows[$i][4] = $periodo;				
				if(!$idcuenta) $rows[$i][0] = 'Total movimientos'; //no se establecio el tipo de cuenta, se va a poner solamente el total de movimientos
				else {	//si se selecciono una cuenta
					if ($rows[$i][0] != $obj->IdCuenta) $rows[$i][0] = $obj->IdCuenta;
					else $i++;					
				}
				//print "i:".$i."cuenta: ".$rows[$i][0]."cargos: ".$rows[$i][1]."abonos: ".$rows[$i][2]." tipo movto:".$obj->TipoMovto;print '<br>';
			}	
		}
		else {
			//print "Error al tratar de obtener los cargos y abonos. Intente de nuevo o Contacte al administrador: ".$idpoliza; exit;
			return null;
		}
		$this->db->free($resql);
		return $rows;
		
	} //fin de la funcion getCargosAbonos	


/* funcion para obtener el saldo de una cuenta a una fecha determinada*/
// $idcuenta				int: id de la cuenta a la que se busca el saldo
// $ejercicio				int: ejercicio al que se obtendra el saldo
// $periodo					int: numero del periodo al que se obtendra el saldo
// $date					date: fecha especifica a la que se obtendra el saldo
	function getSaldo($idcuenta=null, $ejercicio=null, $periodo=null, $date=null) {
		
		//Calculamos el debe y haber acumulado hasta el ejercicio anterior
		$ejercicio_ant = $ejercicio - 1;
		
		$debeAcumAnt=0; $haberAcumAnt=0;	//Reiniciamos los Saldos acumulados al ejercicio anterior
		$debeAcumAct=0; $haberAcumAct=0;	//Reiniciamos los Saldos acumulados del ejericio actual
		$debe = 0; $haber=0;				//Reiniciamos el debe y el haber del periodo actual
		
		//					0			1				2								3
		$sql = "SELECT mp.TipoMovto, mp.Ejercicio, mp.IdCuenta, SUM(mp.Importe) as sumaimporte";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
		$sql.= ' WHERE mp.IdCuenta = '.$idcuenta;
		$sql.= " AND mp.Ejercicio<='".$ejercicio_ant."'";
		$sql.= " GROUP BY mp.Ejercicio, mp.IdCuenta, mp.TipoMovto";
		$sql.= " ORDER BY mp.IdCuenta, mp.Periodo, mp.TipoMovto"; //print $sql;exit;
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0; 
			while ($row = $this->db->fetch_array($resql))  {
				if ($row[0]==0) $debeAcumAnt = $debeAcumAnt + $row[3];
				else $haberAcumAnt = $haberAcumAnt + $row[3];
				$i++;
			}
		}
		else return false;

		//Calculamos el debe y haber acumulado  del ejercicio actual
		if ($periodo > 1) {			//Verificamos que el periodo actual sea diferente a enero
			$periodo_ant = $periodo - 1;
			//					0			1				2			3								4
			$sql2 = "SELECT mp.TipoMovto, mp.Ejercicio, mp.Periodo, mp.IdCuenta, SUM(mp.Importe) as sumaimporte";
			$sql2.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
			$sql2.= ' WHERE mp.IdCuenta = '.$idcuenta;
			$sql2.= " AND mp.Ejercicio='".$ejercicio."'";
			$sql2.= " AND mp.Periodo<'".$periodo."'";
			$sql2.= " GROUP BY mp.Ejercicio, mp.Periodo, mp.IdCuenta, mp.TipoMovto";
			$sql2.= " ORDER BY mp.IdCuenta, mp.Ejercicio, mp.Periodo, mp.TipoMovto"; //print $sql2;exit;
			$resql2 = $this->db->query($sql2);
			if ($resql2) {
				$i=0;
				while ($row2 = $this->db->fetch_array($resql2))  {
					if ($row2[0]==0) $debeAcumAct = $debeAcumAct + $row2[4];
					else $haberAcumAct = $haberAcumAct + $row2[4];
					$i++;
				}			
			}
			else return false;
		}

		//Calculamos el debe y haber del periodo actual
		//					0			1				2			3								4
		$sql3 = "SELECT mp.TipoMovto, mp.Ejercicio, mp.Periodo, mp.IdCuenta, SUM(mp.Importe) as sumaimporte";
		$sql3.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
		$sql3.= ' WHERE mp.IdCuenta = '.$idcuenta;
		$sql3.= " AND mp.Ejercicio='".$ejercicio."'";
		$sql3.= " AND mp.Periodo='".$periodo."'";
		$sql3.= " GROUP BY mp.Ejercicio, mp.Periodo, mp.IdCuenta, mp.TipoMovto";
		$sql3.= " ORDER BY mp.IdCuenta, mp.Ejercicio, mp.Periodo, mp.TipoMovto"; //print $sql3; exit;		
		$resql3 = $this->db->query($sql3);
		if ($resql3) {
			$i=0;
			while ($row3 = $this->db->fetch_array($resql3))  {
				if ($row3[0]==0) $debe = $debe + $row3[4];
				else $haber = $haber + $row3[4];
				$i++; 
			}			
		}
		else return false;

		//Calculamos los saldos iniciales y finales
		$sql4 = "SELECT cc.rowid, cc.nat";
		$sql4.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as cc";
		$sql4.= ' WHERE cc.rowid = '.$idcuenta;
		$resql4 = $this->db->query($sql4);
		if ($resql4) {
			$obj = $this->db->fetch_object($resql4);
			$nat = $obj->nat;			
		}
		else return false;

		//Reiniciamos saldos antes del ejercicio actual
		$saldoInicAnt = 0; $saldoFinAnt = 0; 
		//Reiniciamos saldos antes del periodo actual
		$saldoInicAcum = 0; $saldoFinAcum = 0; 
		//Reiniciamos saldos del periodo
		$saldoInic = 0; $saldoFin = 0;
		
		//Calculamos el saldo inicial y final acumualdo anterior al ejercicio
		if ($nat == 1) $saldoFinAnt = $saldoInicAnt + $debeAcumAnt - $haberAcumAnt;
		else $saldoFinAnt = $saldoInicAnt + $haberAcumAnt - $debeAcumAnt;
		
		//Calculamos el saldo inicial y final acumulado antes del periodo
		$saldoInicAcum = $saldoFinAnt;
		if ($nat == 1) $saldoFinAcum = $saldoInicAcum + $debeAcumAct - $haberAcumAct;
		else $saldoFinAcum = $saldoInicAcum + $haberAcumAct - $debeAcumAct;
		
		//Calculamos el saldo inicial y final del periodo
		$saldoInic = $saldoFinAcum;
		if ($nat == 1) $saldoFin = $saldoInic + $debe - $haber;
		else $saldoFin = $saldoInic + $haber - $debe;
		
//die($debeAcumAnt." ".$haberAcumAnt." ".$debeAcumAct." ".$haberAcumAct." ".$debe." ".$haber." ".$saldoInicAnt." ".$saldoFinAnt." ".$saldoInicAcum." ".$saldoFinAcum." ".$saldoInic." ".$saldoFin);
		//				0				1				2			   3		 4		5			6				7	
		return array($debeAcumAnt, $haberAcumAnt, $debeAcumAct, $haberAcumAct, $debe, $haber, $saldoInicAnt, $saldoFinAnt, $saldoInicAcum, $saldoFinAcum, $saldoInic, $saldoFin);
	//	8				9			10			11
		
	} //fin de la funcion obtiene saldo
	
	
	/*Funcion para actualizar los saldos de una cuenta en base al movimiento de una poliza*/
	// $idcuenta			int:id de la cuenta a actualizar el saldo	
	// $all					int: se actualizaran todas las cuentas $all=1, $all=null se actualizara solo la cuenta $idcuenta
	function ActualizaSaldosCuenta($idcuenta=null,$all=null) {
		//verificamos que si se selecciono actualizar el saldo de una cuenta, verificar que si tengamos el id de la cuenta
		if (!$all && !$idcuenta) return false;
		
		
		//creamos la consulta para actualizar el saldo de la cuenta
		//					0			1			2			 3			4			5		 6			7			 8				9	 	10
		$sql.= "SELECT mp.rowid, mp.IdCuenta, mp.Periodo, mp.Ejercicio, mp.Fecha, mp.TipoPol, mp.Folio, mp.NumMovto, mp.TipoMovto, mp.Importe, cc.nat";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs as mp";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_cuentas as cc ON mp.IdCuenta = cc.rowid";
		if (!$all) $sql.= " WHERE mp.IdCuenta=".$idcuenta;
		$sql.= " ORDER BY mp.IdCuenta, mp.Periodo, mp.Ejercicio, mp.Fecha, mp.TipoPol, mp.Folio, mp.NumMovto";

		//En esta parte calculamos el saldo completo de la consulta sin limites
		$resql = $this->db->query($sql);
		if ($resql) {
			//esta parte actualiza los saldos de todos los movimientos de las cuentas
            $numov=$this->db->num_rows($resql);
            ini_set('max_execution_time', '300'); 
			$cuenta_ant=null;
			$saldo_acum=0;
			while ($row = $this->db->fetch_array($resql))  {
				if ($all) {  //procedimiento para actualizar todas las cuentas
					if ($cuenta_ant != $row[1]) {
						if($cuenta_ant) {
							//Actualizamos el saldo de la cuenta anterior
							$sql = "UPDATE ".MAIN_DB_PREFIX."contab_cuentas SET";
							$sql.= " saldo=".$saldo_acum;
							$sql.= " WHERE rowid=".$cuenta_ant;
							$result = $this->db->query($sql);
							if (!$result) {
								print "Error al actualizar saldos de la cuenta. Informe al Administrador id: ".$row[1];
								exit;
							}
						}
						$cuenta_ant = $row[1];
						$saldo_acum = 0;
					}
				}
				
				$cargo = 0;
				$abono = 0;
		
				if ($row[8]==0) $cargo = $row[9];
				else $abono = $row[9];
				
				if($row[10]==1) $saldo_acum = $saldo_acum + $cargo - $abono;
				else $saldo_acum = $saldo_acum + $abono - $cargo;
				
				//Actualizamos el saldo del movimiento
	
				$sql = "UPDATE ".MAIN_DB_PREFIX."contab_polizas_movs SET";
				$sql.= " saldo=".$saldo_acum;
				$sql.= " WHERE rowid=".$row[0];
				$result = $this->db->query($sql);
				if (!$result) {
					print "Error al actualizar saldos de movimientos. Informe al Administrador id: ".$row[0];
					exit;
				}
			}
			if ($idcuenta) {
				//Actualizamos el saldo de la cuenta anterior
				$sql = "UPDATE ".MAIN_DB_PREFIX."contab_cuentas SET";
				$sql.= " saldo=".$saldo_acum;
				$sql.= " WHERE rowid=".$idcuenta;
				$result = $this->db->query($sql);
				if (!$result) {
					print "Error al actualizar saldos de la cuenta. Informe al Administrador id: ".$idcuenta;
					exit;
				}				
			}
			if ($all) {
				if(!$this->ActualizaSaldosCtasSup()) return false;
			}
			else {
				if(!$this->ActualizaSaldosCtasSup($idcuenta)) return false;
			}
		}
		else return false;
		return true;
		
	} //fin de la funcion AcualizaSaldosCuenta
	
	/*funcion que actualiza los saldos de las cuentas padre de las cuentas afectables*/
	// $idcuenta				int: id de la cuenta afectable que se actualizran los saldos de sus cuentas superiores si es null se actualizan todas
	function ActualizaSaldosCtasSup($idcuenta=null) {
		//						0						1						2					3
		$sql = "SELECT cp.rowid as idpadre, cp.fk_nivel as nivpadre, cp.saldo as salpadre, cp.nat as natpadre";
		//					4						5					6				7			  8
		$sql.= ", cc.rowid as idcuenta, cc.fk_nivel as nivcuenta, cc.afectable, cc.saldo as saldo, cc.nat";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as cc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_ctanxus as a ON cc.rowid = a.fk_hijo";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_cuentas as cp ON a.fk_padre = cp.rowid";
		$sql.= " WHERE cc.afectable=1";
		if ($idcuenta) $sql.= " AND cc.rowid=".$idcuenta;
		$sql.= " ORDER BY cp.rowid ASC";//print $sql; exit; 
		$resql = $this->db->query($sql);
		if ($resql) {
			$cuenta_ant=null;
			$saldo_acum=0;
			while ($row = $this->db->fetch_array($resql))  {
				if ($cuenta_ant != $row[0]) {
					//Actualizamos el saldo de la cuenta anterior
					if ($cuenta_ant) {
						$sql = "UPDATE ".MAIN_DB_PREFIX."contab_cuentas SET";
						$sql.= " saldo=".$saldo_acum;
						$sql.= " WHERE rowid=".$cuenta_ant;
						$result = $this->db->query($sql);
						if (!$result) {
							print "Error al actualizar saldos de la cuenta. Informe al Administrador id: ".$row[1];
							exit;
						}
					}
					$cuenta_ant = $row[0];
					$saldo_acum = 0;
				}
				
				if($row[3]==1) {//la cuenta acumulable es de naturaleza deudora
					if ($row[8]==1) $saldo_acum = $saldo_acum + $row[7];
					else $saldo_acum = $saldo_acum - $row[7];
				}
				else {
					if ($row[8]==1) $saldo_acum = $saldo_acum - $row[7];
					else $saldo_acum = $saldo_acum + $row[7];
				}
			}
		}
		else return false;	

		//Actualizamos los saldos de las cuentas de mayor
		//						0						1						2					3
		$sql = "SELECT cp.rowid as idpadre, cp.fk_nivel as nivpadre, cp.saldo as salpadre, cp.nat as natpadre";
		//					4						5					6				7			  8
		$sql.= ", cc.rowid as idcuenta, cc.fk_nivel as nivcuenta, cc.afectable, cc.saldo as saldo, cc.nat";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as cc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_ctanxus as a ON cc.rowid = a.fk_hijo";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_cuentas as cp ON a.fk_padre = cp.rowid";
		$sql.= " WHERE cp.fk_nivel=3 AND cc.afectable=0";
		$sql.= " ORDER BY cp.rowid ASC";
		$resql = $this->db->query($sql);
		if ($resql) {
			$cuenta_ant=null;
			$saldo_acum=0;
			while ($row = $this->db->fetch_array($resql))  {
				if ($cuenta_ant != $row[0]) {
					//Actualizamos el saldo de la cuenta anterior
					if ($cuenta_ant) {
						$sql = "UPDATE ".MAIN_DB_PREFIX."contab_cuentas SET";
						$sql.= " saldo=".$saldo_acum;
						$sql.= " WHERE rowid=".$cuenta_ant;
						$result = $this->db->query($sql);
						if (!$result) {
							print "Error al actualizar saldos de la cuenta. Informe al Administrador id: ".$row[1];
							exit;
						}
					}
					$cuenta_ant = $row[0];
					$saldo_acum = 0;
				}
				
				if($row[3]==1) {//la cuenta acumulable es de naturaleza deudora
					if ($row[8]==1) $saldo_acum = $saldo_acum + $row[7];
					else $saldo_acum = $saldo_acum - $row[7];
				}
				else {
					if ($row[8]==1) $saldo_acum = $saldo_acum - $row[7];
					else $saldo_acum = $saldo_acum + $row[7];
				}
			}
		}
		else return false;
		return true;
		
	} //fin funcion ActualizaSaldosCtasSup
	
	/*FUNCION QUE LISTA LOS SALDOS POR EJERCICIO Y PERIODO ASI COMO SUS MOVIMIENTOS POR CUENTA CONTABLE*/
	//$idcuenta					int: id de la cuenta contable
	//$nat						int: naturaleza de l cuenta -1 Acreedora, 1 Deudora
	//$ejercicio				int: ejercicio del que se calculara los saldos
	//$periodo					int: periodo del que se caluclara los saldos
	function listSaldosCuenta($idcuenta, $nat, $ejercicio=null, $periodo=null) {
		global $conf;
		
		$html = '';
	
		$html.= '<tr>';
		$html.= '<td align="left" bgcolor="#3B5998" style="color:white;" style="font-style:bold" colspan="6">SALDOS</td>';
		$html.= '</tr>';
		$html.= '<tr class="liste_titre">';
		$html.=	'<td align="center" style="font-weight:bold">Ejercicio</td>';
		$html.=	'<td align="center" style="font-weight:bold">Inicial</td>';
		$html.=	'<td align="center" style="font-weight:bold">Debe</td>';
		$html.=	'<td align="center" style="font-weight:bold">Haber</td>';		
		$html.=	'<td align="center" style="font-weight:bold">Saldo</td>';
		$html.= '</tr>';
		if($ejercicio) {	//se indico un ejercicio especifico para el calculo de saldos
			
		}
		else	{		//no se indico ejercicio especifico asi que calculamos el saldod e todos los ejercicios comenzando por el ejerc inicial de la empresa
			$ejein = $conf->global->FISCAL_YEAR_IN;
			$ejefin = $conf->global->FISCAL_YEAR;
			if (!$periodo) $periodo = $conf->global->PERIOD_CONTAB;	//si no se especifico el periodo, entonces el periodo se estable el actual
			$range = $ejefin-$ejein+1;
			$salini = 0;
			for ($i=0; $i<$range; $i++) {
				$ejeact = $ejein+$i;
				$html.=	'<td align="center" style="font-weight:bold">'.$ejeact.'</td>';
				$html.=	'<td align="right" style="font-weight:bold">'."$".price($salini,2).'</td>';
				$debehaber=$this->getCargosAbonos($idcuenta,null,$ejeact);
				if ($debehaber) {
					$html.=	'<td align="right">'."$".number_format($debehaber[0][1],2).'</td>'; //cargos
					$html.=	'<td align="right">'."$".number_format($debehaber[0][2],2).'</td>'; //abonos
				}
				if ($nat==1) $salfin = $salini + $debehaber[0][1]-$debehaber[0][2];	//saldo deudor
				else $salfin = $salini + $debehaber[0][2]-$debehaber[0][1];	//saldo acreedor
				$html.=	'<td align="right" style="font-weight:bold">'."$".number_format($salfin,2).'</td>';
				$html.= '</tr>';
				$salini = $salfin;
			}
			//el calculo de los saldos del periodo se hace al reves primero calculamos el saldo final y de ahi quitamos los cargos y abonos y tenemos el saldo incial
			$totalca=$this->getCargosAbonos($idcuenta);//calculamos el total de cargos y abonos de la cuenta
			if ($nat==1) $salfin = $totalca[0][1]-$totalca[0][2];	//saldo deudor
			else $salfin = $totalca[0][2]-$totalca[0][1];	//saldo acreedor
			$totalcap = $this->getCargosAbonos($idcuenta,$periodo,$ejeact);
			$salini = $salfin - abs($totalcap[0][1]-$totalcap[0][2]);
			$html.=	'<td align="center" style="font-weight:bold">'.'Periodo actual'.'</td>';
			$html.=	'<td align="right" style="font-weight:bold">'."$".number_format($salini,2).'</td>';
			$html.=	'<td align="right">'."$".number_format($totalcap[0][1],2).'</td>';
			$html.=	'<td align="right">'."$".number_format($totalcap[0][2],2).'</td>';
			$html.=	'<td align="right" style="font-weight:bold">'."$".number_format($salfin,2).'</td>';
			$html.= '</tr>';		
		}
		
		return $html;
		
	}

/*FUNCION PARA OBTENER el ID DE LA CUENTA CONTABLE DE UN ELEMENTO */
//	$idelemento				int: id del elemento del cual queremos obtener su id de la cuenta contable
//	$tabla					string: nombre de la tabla donde obtendremos el id de la cuenta contable
	function getIdCuenta($idelemento, $tabla) {
		//				0			1		2			3			4			5					
		$sql = 'SELECT t.rowid, t.fk_cta_contab';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$tabla.' as t';
		$sql.= ' WHERE t.rowid = '.$idelemento; //print $sql; exit;		
		$res=$this->db->query($sql);
		if (!$this->db->num_rows($res)) die ("Error grave. Un elemento no tiene asociada una cuenta contable. Contacte al administrador: ".$idelemento."-".$tabla);
		$obj = $this->db->fetch_object($res);
		$idcuenta = $obj->fk_cta_contab;
		$this->db->free($res);
		return $idcuenta;
		
	} //fin de la funcion getPolizasVentas	
    
/*FUNCION PARA OBTENER el ID DE LA CUENTA CONTABLE CON BASE AL CODIGO DE LA CUENTA */
//	$idelemento				int: id del elemento del cual queremos obtener su id de la cuenta contable
//	$tabla					string: nombre de la tabla donde obtendremos el id de la cuenta contable
	function getIdCuentaContab($code) {
		//				0			1		2			3			4			5					
		$sql = 'SELECT cc.rowid, cc.codigo';
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as cc";
		$sql.= ' WHERE cc.codigo = '.$code; //print var_dump($sql); //exit;		
		$res=$this->db->query($sql);
		if (!$this->db->num_rows($res)) die ("Error grave. Un elemento no tiene asociada una cuenta contable. Contacte al administrador");
		$obj = $this->db->fetch_object($res);
		$idcuenta = $obj->rowid;
		$this->db->free($res);
		return $idcuenta;
	}

/* FUNCION PARA CAMBIAR O AGREGAR EL STATUS DE UN ELEMENTO SEGUN LA POLIZA QUE SE HAYA GENERADO*/
//$id						int:id del elemento que se le va a agregar el estatus y el numero de poliza
//$idpoliza					int:id de la poliza
//$statpol					int: status de la poliza
//$tabla					string: nombre de la tabla que se va a actualizar
	function upStatPol($id,$idpoliza,$statpol,$tabla) {
		global $conf;
        $this->db->begin();
        // Validate
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$tabla;
        $sql.= " SET fk_poliza=".$idpoliza.", fk_status_contab=".$statpol;
        $sql.= ' WHERE rowid = '.$id; //print $sql;exit;

        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_print_error($this->db);
            $error=-1;
        }
        $this->db->commit();
        return 1;				
		
	}	
	
/* FUNCION PARA OBTENER LOS DATOS DE LA POLIZA (NO INCLUYE MOVIMIENTOS)*/
//$idpol					int:id de la poliza
	function getPol($idpol) {
		global $conf;
        $this->db->begin();
        // Validate
		//				  0		  1
        $sql = 'SELECT p.rowid, p.Folio';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'contab_polizas as p';		
        $sql.= ' WHERE p.rowid = '.$idpol; //print $sql;exit;
        $res=$this->db->query($sql);
        if ($res) {
			if (!$this->db->num_rows($res)) die ("Error grave. La poliza no contiene datos. Contacte al administrador: ".$idpol);
			while ($row = $this->db->fetch_array($res))  {
				$rows = array($row[0], $row[1]);//print $row[0].":".$row[1].'<br>';
			}
		}
		else {
			return null;			
		}
		return $rows;
	
	}	
	
    
    /* Funcion para actualizar el ejercicio y periodo contable
    */
    function actualPeriodContab() {
        global $conf;

        dolibarr_set_const($this->db, "FISCAL_YEAR", $this->ejercicio_act,'chaine',0,'',$conf->entity);
        dolibarr_set_const($this->db, "PERIOD_CONTAB",$this->periodo_act,'chaine',0,'',$conf->entity); 
        dolibarr_set_const($this->db, "FISCAL_YEAR_IN", $this->ejercicio_in,'chaine',0,'',$conf->entity);
        dolibarr_set_const($this->db, "PERIOD_CONTAB_IN",$this->periodo_in,'chaine',0,'',$conf->entity);        
        return true;
        
    }
	
//fin de la clase contabilidad
}

function conexion_sqlsrv() {
	global $conf;
	$connection = mssql_connect($conf->global->SRV_SLMS, $conf->global->USR_SQSR, $conf->global->CNT_MSS);
	if (!$connection) {
		print 'No se pudo conectar al Servidor. Favor de intentar la operación nuevamente o contacte al Administrador ... ';
		exit;
	}
	else
	{
		$cnn = mssql_select_db($conf->global->DB_CTPQ, $connection);
		if (!$cnn) {
			print 'No se pudo conectar a la Base de Datos. Favor de intentar la operación nuevamente o contacte al Administrador ... ';
			exit;
		}
		else {
			return;
		}
	}
}

/* Funcion para obtener el ultimo Id de los contadores (para uso de compatibilidad con contpaq*/
function getNextId($field) {
	global $conf,$db;
	//obtenemos el Id Next de la tabla de mysql
/*	$sql = "SELECT c.Name, c.Next FROM ".MAIN_DB_PREFIX."counters as c WHERE c.Name='".$field."'";
	$resql=$db->query($sql);
	$objc = $db->fetch_object($resql);
	$rwid = $objc->Next;	
	$db->free($resql);*/
	//obtenemos ahora el Id Next de la tabla de contpaq
	$sql = "SELECT c.Name, c.Next FROM dbo.Counters as c WHERE c.Name='".$field."'";
	conexion_sqlsrv();	
	$res = mssql_query($sql);
	$row = mssql_fetch_row($res);
	$rwid2 = $row[1];
	mssql_free_result($res);
	//Verficiamos si coinciden los campos y si es asi regresamos el valor
	if ($rwid2) return $rwid2;	
	else die("Error: Inconsistencia en las base de datos (Counters). Contacte al Admnistrador: ".$field.":".$rwid2);
}

/* Funcion para actualziar el ultimo Id de los contadores (para uso de compatibilidad con contpaq*/
function upIdNext($field,$idma) {
	global $conf,$db;

	$idmn = $idma+1; //aumentamos el id anterior idma con el id siguiente idmn
	$sql = "UPDATE ".MAIN_DB_PREFIX."counters SET Next=".$idmn;
    $sql.= " WHERE Name='".$field."'";		
	$resql=$db->query($sql);
	if(!$resql) die("Error al actualizar counters en dolibar.Contacte al Administrador:".$field."-".$idma);	
	$sql = "UPDATE dbo.Counters SET Next=".$idmn;
    $sql.= " WHERE Name='".$field."'";		
	conexion_sqlsrv();	
	$res = mssql_query($sql);	
	if(!$res) die("Error al actualizar counters en contpaq.Contacte al Administrador:".$field."-".$idma);	
	return;
}
?>
