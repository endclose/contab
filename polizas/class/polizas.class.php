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
 *	\file       
 *	\ingroup    
 *	\brief      
 */
require_once DOL_DOCUMENT_ROOT .'/custom/contab/class/commoncontab.class.php';


/**
 * Class to manage products or services
 */
class Polizas extends CommonContab
{
//variables
var $id;
var $folio;
var $concepto;
var $idpoliza;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

	}


	/*FUNCION PARA TRAER LOS DATOS DE UN REGISTRO DE LA TABLA DE POLIZAS Y SUS DESCENDIENTES */
	function bringPoliza($idpoliza) {
		$this->db->begin;
		//					0			1			2			3			4		5		6
		$sql = "SELECT p.rowid, p.RowVersion, p.Ejercicio, p.Periodo, p.TipoPol, p.Folio, p.Clase";
		//			7			8			9		10			11		12			13			14
		$sql.= ", p.Impresa, p.Concepto, p.Fecha, p.Cargos, p.Abonos, p.IdDiario, p.SistOrig, p.Ajuste";
		//				15			16			17			18			19				20			21				22
		$sql.= ", p.IdUsuario, p.ConFlujo, p.ConCuadre, p.TimeStamp, p.RutaAnexo, p.ArchivoAnexo, p.Guid, p.tieneDoctoBancario";
		//				23					24			  25			26		  	27
		$sql.= ", mp.rowid as idmpoliza, mp.RowVersion, IdPoliza, mp.Ejercicio, mp.Periodo";
		//			 28     	 29    		30		  31				32			33		  	34				 35			
		$sql.= ", mp.TipoPol, mp.Folio, mp.NumMovto, mp.IdCuenta, mp.TipoMovto, mp.Importe, mp.ImporteME, mp.Referencia";
		//				36			37			38		  39			40		   41
		$sql.= ", mp.Concepto, mp.IdDiario, mp.Fecha, mp.IdSegNeg, mp.TimeStamp, mp.Guid";
		//			42		  43         44
		$sql.= ", cp.month, tp.label, ps.label";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas as p";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_polizas_movs as mp ON p.rowid = mp.IdPoliza';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_periodos as cp ON p.Periodo = cp.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_tipospol as tp ON p.TipoPol = tp.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_polizas_status as ps ON p.status = ps.rowid';
		$sql.= " WHERE p.rowid = ".$idpoliza;//print $sql; //exit;
		$resql=$this->db->query($sql);
		if ($resql) {
			$i=1;
			while ($row = $this->db->fetch_array($resql))  {
				//arreglo con los datos generales de la poliza
				$rows[$i] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5],
									$row[6], $row[7], $row[8], $row[9], $row[10], $row[11],
									$row[12], $row[13], $row[14], $row[15], $row[16], $row[17],
									$row[18], $row[19], $row[20], $row[21],$row[22],
									
									$row[23], $row[24], $row[25], $row[26], $row[27], $row[28],
									$row[29], $row[30], $row[31], $row[32], $row[33], $row[34],
									$row[35], $row[36], $row[37], $row[38], $row[39], $row[40], $row[41],
									
									$row[42], $row[43]);
									
									
				$i++;	
			}
		}
		else {
			print "Errr al tratar de obtener informacion de la poliza y sus movimientos. Intente de nuevo o Contacte al administrador: ".$idpoliza; exit;
		}
		$this->db->free($resql);
		return $rows;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@param		int		$maxlength		Maxlength of ref
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/custom/contab/polizas/card.php?mode=1&amp;id='.$this->id.'">';
		$lienfin='</a>';

		$newref=$this->folio;
		if ($maxlength) $newref=dol_trunc($newref,$maxlength,'middle');

		if ($withpicto)	$result.=($lien.img_object($langs->trans("Ir a Póliza").' '.$this->folio,'propal').$lienfin.' ');
		$result.=$lien.$newref.$lienfin;
		return $result;
	}
	
	/* FUNCION PARA OBTENER EL TIPO DE POLIZA*/
	//$rowid			int: el id del tipo de poliza
	function getTipoPol($rowid) {
		$sql = "SELECT t.rowid, t.label FROM ".MAIN_DB_PREFIX."contab_tipospol as t WHERE t.rowid=".$rowid;//print $sql;exit;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
		return $obj->label;
	}
	
	/* FUNCION PARA OBTENER EL EJERCICIO*/
	//$rowid			int: el numero del ejercicio
	function getEjercicio($rowid) {
		$sql = "SELECT e.rowid, e.number, e.year FROM ".MAIN_DB_PREFIX."c_ejer_contab as e WHERE e.number='".$rowid."'";//print $sql;exit;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
		return $obj->year;
	}	
	
	/* FUNCION PARA OBTENER EL PERIODO EN BASE AL NO DE PERIODO*/
	//$noperiodo			int: el numero del ejercicio
	function getPeriodo($noperiodo) {
		if ($noperiodo == 1) $mes = 'Enero';
		if ($noperiodo == 2) $mes = 'Febrero';
		if ($noperiodo == 3) $mes = 'Marzo';
		if ($noperiodo == 4) $mes = 'Abril';
		if ($noperiodo == 5) $mes = 'Mayo';
		if ($noperiodo == 6) $mes = 'Junio';
		if ($noperiodo == 7) $mes = 'Julio';
		if ($noperiodo == 8) $mes = 'Agosto';
		if ($noperiodo == 9) $mes = 'Septiembre';
		if ($noperiodo == 10) $mes = 'Octubre';
		if ($noperiodo == 11) $mes = 'Noviembre';
		if ($noperiodo == 12) $mes = 'Diciembre';
		return $mes;
	}	
	
	/**
	 *	Return EL NUMERO DE MOVIMIENTOS DE UNA POLIZA
	 *
	 *	@param		int		$idpol         El id de la poliza
	 *	@return     int     $movs          El numero de movimientos de la poliza
     */
    function noMovsPol() {
        global $conf;
        
        if ($this->idpoliza) {
            $this->db->begin();
            $sql = "SELECT IdPoliza";
            $sql.= " FROM ".MAIN_DB_PREFIX."contab_polizas_movs";
            $sql.= " WHERE 1";
            $sql.= " AND IdPoliza = '".$this->idpoliza."'"; //print var_dump($sql);
            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->num_rows($resql);
                return $num;
            }
            else {
                dol_print_error($this->db);
                return null;
            }
        }
        else return null;
    }
	

}	//find e la clase Polizas