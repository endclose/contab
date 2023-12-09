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
		$id = getNextId('Id_Poliza');
		
		
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
		
		//obtenemos el siguiente numero de poliza
		$sql = "SELECT p.Folio FROM ".MAIN_DB_PREFIX."polizas as p";
		$sql.= " WHERE Periodo=".$datos_pol[3]." AND Ejercicio=".$datos_pol[2]." AND TipoPol=".$datos_pol[1];
		$sql.= " ORDER BY p.Folio DESC LIMIT 0,1";//print $sql;exit;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
		$folio = $obj->Folio + 1;
		
		$row = array();
		$row[0] = $id; $row[1] = $rowversion; $row[2] = $datos_pol[2]; $row[3] = $datos_pol[3]; $row[4] = $datos_pol[1]; $row[5] = $folio; $row[6] = '1'; 
		$row[7] = 0; $row[8] = $datos_pol[4]; $row[9] = $datos_pol[5]; $row[10] = $datos_pol[6]; $row[11] = $datos_pol[7]; $row[12] = '0'; $row[13] = '11';
		$row[14] = 0; $row[15] = '1'; $row[16] = 1; $row[17] = 0; $row[18] = ''; $row[19] = ''; $row[20] = ''; $row[21] = $guid; $row[22] = 0;
//consulta MySQL		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."polizas (";
		$sql.= "rowid";  				//0
		$sql.= ", RowVersion";  		//1
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
		$sql.=$row[0];
		$sql.= ", '".$row[1]."'";
		$sql.= ", '".$row[2]."'";
		$sql.= ", '".$row[3]."'";
		$sql.= ", '".$row[4]."'";
		$sql.= ", '".$row[5]."'";
		$sql.= ", '".$row[6]."'";
		$sql.= ", ".$row[7];
		$sql.= ", '".$row[8]."'";
		$sql.= ", '".$row[9]."'";
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
		$sql.= ")";//print $sql; exit;

		//insertamos la poliza
		$result = $this->db->query($sql);
		if ( $result )
		{
			//$id = $this->db->last_insert_id(MAIN_DB_PREFIX."polizas");

			//consulta SQL Server y agregamos la poliza a contpaq
			$sql2 = "INSERT INTO dbo.Polizas (";
			$sql2.= "Id";		  			//0
			$sql2.= ", RowVersion";  		//1
			$sql2.= ", Ejercicio"; 			//2
			$sql2.= ", Periodo";  			//3
			$sql2.= ", TipoPol";  			//4
			$sql2.= ", Folio";  			//5
			$sql2.= ", Clase";				//6
			$sql2.= ", Impresa";			//7
			$sql2.= ", Concepto";			//8
			$sql2.= ", Fecha";				//9
			$sql2.= ", Cargos";				//10
			$sql2.= ", Abonos";				//11
			$sql2.= ", IdDiario";			//12
			$sql2.= ", SistOrig";			//13
			$sql2.= ", Ajuste";				//14
			$sql2.= ", IdUsuario";			//15
			$sql2.= ", ConFlujo";			//16
			$sql2.= ", ConCuadre";			//17
			$sql2.= ", TimeStamp";			//18
			$sql2.= ", RutaAnexo";			//19
			$sql2.= ", ArchivoAnexo";		//20
			$sql2.= ", Guid";				//21
			$sql2.= ", tieneDoctoBancario";	//22
			$sql2.= ") VALUES (";
			$sql2.= $row[0];
			$sql2.= ", '".$row[1]."'";
			$sql2.= ", '".$row[2]."'";
			$sql2.= ", '".$row[3]."'";
			$sql2.= ", '".$row[4]."'";
			$sql2.= ", '".$row[5]."'";
			$sql2.= ", '".$row[6]."'";
			$sql2.= ", ".$row[7];
			$sql2.= ", '".$row[8]."'";
			$sql2.= ", '".$row[9]."'";
			$sql2.= ", '".$row[10]."'";
			$sql2.= ", '".$row[11]."'";
			$sql2.= ", '".$row[12]."'";
			$sql2.= ", '".$row[13]."'";
			$sql2.= ", ".$row[14];
			$sql2.= ", '".$row[15]."'";
			$sql2.= ", ".$row[16];
			$sql2.= ", ".$row[17];
			$sql2.= ", '".$row[18]."'";
			$sql2.= ", '".$row[19]."'";
			$sql2.= ", '".$row[20]."'";
			$sql2.= ", '".$row[21]."'";		
			$sql2.= ", ".$row[22];
			$sql2.= ")";

			conexion_sqlsrv();
			$result = mssql_query($sql2);
		
			if ($result) {
//Insertamos los movimientos de la poliza				
				
				$nm = 1;		//variable con el numero de movimiento
				
				foreach($datosc_pol[0] as $cuenta) {
					//Obtenemos el Id Next de los movimientos de la poliza
					$idm = getNextId('Id_MovimientoPoliza');				

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
					$rw[0] = $idm; $rw[1] = $rowversion; $rw[2] = $id; $rw[3] = $datos_pol[2]; $rw[4] = $datos_pol[3]; 
					$rw[5] = $datos_pol[1];	$rw[6] = $folio; $rw[7] = $nm; $rw[8] = $datosc_pol[0][$nm]; $rw[9] = $datosc_pol[2][$nm]; 
					$rw[10] = $datosc_pol[1][$nm]; $rw[11] = 0; $rw[12] = $datosc_pol[3][$nm]; $rw[13] = $datos_pol[4]; $rw[14] = 0; 
					$rw[15] = $datos_pol[5]; $rw[16] = 0; $rw[17] = ''; $rw[18] = $guid; 
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."movimientospoliza (";
					$sql.= "rowid";		  			//0
					$sql.= ", RowVersion"; 			//1
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
					$sql.= $rw[0];
					$sql.= ", '".$rw[1]."'";
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
						//$id = $this->db->last_insert_id(MAIN_DB_PREFIX."movimientospoliza");
						//insertamos el registro en contpaq
						$sql = "INSERT INTO dbo.movimientospoliza (";
						$sql.= "Id";		  			//0						
						$sql.= ", RowVersion"; 			//1
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
						$sql.=$rw[0];
						$sql.= ", '".$rw[1]."'";
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
						$sql.= ")";//if ($nm==2) {print $sql; exit;}
						conexion_sqlsrv();
						$result = mssql_query($sql);
						if ($result) {
							upIdNext('Id_MovimientoPoliza',$idm);
						}
						else {
							die("Error al insertar movimiento en contpaq. Contacte al Administrador".$id);
						}
					}
					else {
						die("Error al insertar movimiento en dolibar. Contacte al Administrador".$id);
					}
					$nm++;
				}	//fin del ciclo de insertar movimiento
			
			}
			else {
				print "Error al insertar la poliza en contpaq".$sql2; exit; //return -1;
			}
			
		}
		else
		{
			print "Error al insertar la poliza en dolibarr".$sql;exit;
		}
		upIdNext('Id_Poliza',$id);
		return $id;
	} //fin de la funcion de contabilizar
	

/*FUNCION PARA OBTENER LOS DATOS DE LAS POLIZAS Y SUS MOVIMIENTOS */
	function getPolizas($idpoliza) {
		//				0			1		2			3			4			5					
		$sql = 'SELECT p.rowid, p.Cargos, p.Abonos, m.IdCuenta, m.TipoMovto, m.Importe';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'polizas as p';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'movimientospoliza as m ON p.rowid = m.IdPoliza';
		$sql.= ' WHERE p.rowid = '.$idpoliza; //print $sql; exit;		
		$resql=$this->db->query($sql);
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				$mov = ord($row[4]);  //como es un campo tipo bit con la funcion ord la convertimos a un entero. Actualizacion converti mejor el campo a Tinyint
				$rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
				$i++;	
			}
		}
		else {
			print "Error al tratar de obtener informacion de la poliza y sus movimientos. Intente de nuevo o Contacte al administrador: ".$idpoliza; exit;
		}
		$this->db->free($resql);
		return $rows;
		
	} //fin de la funcion getPolizasVentas	

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
        $sql.= ' FROM '.MAIN_DB_PREFIX.'polizas as p';		
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
