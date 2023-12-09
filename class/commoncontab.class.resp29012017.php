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
	
	/* FUNCION QUE OBTIENE EL ID DE LAS FACTURAS,MONTOS DE LOS PAGOS Y ID DE LAS POLIZAS QUE ABARCAN UN PAGO DE CLIENTES O DE PROVEEDORES*/
	// $idpago				el id del pago de cliente o proveedor
	// $tipopago			0: pago de cliente		1: pago a proveedor
	function getIdFacPago($idpago, $tipopago) {
//							0		1			   2			3			4		5			6				7
		$sql = 'SELECT f.rowid, f.facnumber, f.fk_statut, f.fk_poliza, pf.amount, s.nom, s.rowid as socid, f.total_ttc';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE pf.fk_facture = f.rowid';
		$sql.= ' AND f.fk_soc = s.rowid';
		$sql.= ' AND pf.fk_paiement = '.$idpago;
		$resql=$this->db->query($sql);
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				$rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
				if (!$row[3]) {
					print "Error al contabilizar el pago. La factura ".$row[1]." NO esta contabilizada. Corrija e intente de nuevo"	; die();
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
	
	/* FUNCION PARA OBTENER LOS TOTALES DE VENTA EN CADA FACTURA POR LINEA DE PRODUCTO */
	// $idfac				int: el id de la factura
	function getVentaLinea($idfac) {
		//primero verificamos que la factura contenga movimientos
		$sql = 'SELECT fd.rowid FROM '.MAIN_DB_PREFIX.'facturedet as fd WHERE fd.fk_facture='.$idfac;
		$res=$this->db->query($sql);
		if (!$this->db->num_rows($res)) die ("Error grave. La factura no contiene movimientos. Contacte al administrador: ".$idfac);				
		
//						  0			1			2						3	
		$sql = 'SELECT f.rowid, p.fk_linea, cc.rowid as idcc, SUM(fd.total_ht) AS totallinea';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON f.rowid = fd.fk_facture';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_lineas_prod as lp ON p.fk_linea = lp.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_cuentas_contab as cc ON lp.fk_cta_contab = cc.rowid';
		$sql.= ' GROUP BY f.rowid, p.fk_linea, cc.rowid';
		$sql.= ' HAVING f.rowid='.$idfac;//print $sql.'<br>';
		$resql=$this->db->query($sql);		
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				if ($row[1]==null) $row[2] = 1402;	//existe un movimiento en la factura que es libre
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

//fin de la clase contabilidad
}


?>
 

