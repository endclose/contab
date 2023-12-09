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
class Cuentas extends CommonContab
{
	public $element='cuenta';
	public $table_element='cuenta';
	public $fk_element='fk_cuenta'; 
	protected $childtables=array('propaldet','commandedet','facturedet','contratdet');    // To test if we can delete object


	var $id;
	var $codigo;
	var $nombre;
	var $tipo;
	var $naturaleza;
	var $afectable;
	var $active;
	var $nivel;
	var $efe;
	var $datec;
	var $currency;
	var $digito_agrup;
	var $idagrupasat;
	var $idpcgver;
	var $idctasup;		//subcuenta de
	
//variables para compatibilidad con contpaq
	var $rowversion;
	var $ctaefectivo;
	var $sistorigen;
	var $idmoneda;
	var $digagrup;
	var $idsegneg;
	var $segnegmovtos;
	var $timestamp;
	var $idrubro;
	var $consume;
	var $idagrupadorsat;
	var $conceptosconsume;
	
	var $idtipocta;
	var $namtipocta;
	var $tipocta;//etiqueta del tipo de cuenta del catalo de cuentas de contpaq
	var $nat; //valor de naturaleza de la cuenta del catalogo de cuenta de contpaq
	
	var $idpadre;
	var $codpadre;
	var $nompadre;
	
	var $namenivel;
		
	var $fechaalta;
	var $dlu; //datelastupdat field
	var $tms; //timestamp

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
		$this->status_buy = 0;
		$this->nbphoto = 0;
		$this->stock_reel = 0;
		$this->seuil_stock_alerte = 0;
		$this->canvas = '';
	}


	/**
	 *	Insert product into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param	int		$notrigger		Disable triggers
	 *	@return int			     		Id of product/service if OK or number of error < 0
	 */
	function create($object)
	{
		global $conf, $langs;
		
        $error=0;

		// Produit non deja existant
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contab_cuentas (";
		$sql.= "RowVersion";		
		$sql.= ", codigo";
		$sql.= ", nombre";
		$sql.= ", NomIdioma";
		$sql.= ", fk_tipocta";
		$sql.= ", active";
		$sql.= ", fk_nivel";
		$sql.= ", CtaEfectivo";
		$sql.= ", FechaRegistro";
		$sql.= ", SistOrigen";
		$sql.= ", IdMoneda";
		$sql.= ", DigAgrup";
		$sql.= ", IdSegneg";
		$sql.= ", SegNegMovtos";																				
		$sql.= ", afectable";
		$sql.= ", TimeStamp";
		$sql.= ", IdRubro";
		$sql.= ", Consume";
		$sql.= ", IdAgrupadorSAT";								
		$sql.= ", ConceptosConsume";
		$sql.= ", nat";		
		$sql.= ") VALUES (";
		$sql.= "'".$object->rowversion."'";
		$sql.= ", '".$object->codigo."'";
		$sql.= ", '".$object->nombre."'";
		$sql.= ", '".$object->nomidioma."'";
		$sql.= ", '".$object->tipo."'";
		$sql.= ", '".$object->active."'";
		$sql.= ", '".$object->nivel."'";		
		$sql.= ", '".$object->ctaefectivo."'";				
		$sql.= ", '".$object->FechaRegistro."'";		
		$sql.= ", '".$object->sistorigen."'";
		$sql.= ", '".$object->idmoneda."'";
		$sql.= ", '".$object->digagrup."'";
		$sql.= ", '".$object->idsegneg."'";
		$sql.= ", '".$object->segnegmovtos."'";
		$sql.= ", '".$object->afectable."'";
		$sql.= ", null";
		$sql.= ", '".$object->idrubro."'";
		$sql.= ", '".$object->consume."'";
		$sql.= ", '".$object->idagrupadorsat."'";
		$sql.= ", null";
		$sql.= ", '".$object->nat."'";
		$sql.= ")"; //print $sql;exit;

		dol_syslog(get_class($this)."::Create sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."contab_cuentas");
			
			return $id;
		}
		else
		{
			return null;
		}
	}

/*
	function agrega_cta_ctpq($sql,$sql3)
	{
		global $conf, $langs;

        $error=0;
		
		$connection = mssql_connect('leo.leonx.net', 'sa', 'danikle97');
		
		if (!$connection) return -10;
		
		if (!mssql_select_db('prueba', $connection)) return -11;
		
		$result = mssql_query($sql);

		if ( $result )
		{
			$result2 = mssql_query($sql3);
			if ($result2) return 1;
			else return -14;
		}
		else
		{
			$error++;
		    $this->error=$this->db->lasterror();
			$this->db->rollback();
			return -12;
		}
	}
*/
	/**
	 *	Update a record into database
	 *
	 *	@param	int		$id         Id of product
	 *	@param  User	$user       Object user making update
	 *	@param	int		$notrigger	Disable triggers
	 *	@param	string	$action		Current action for hookmanager
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($object,$id)
	{
		global $langs, $conf;

		$error=0;

        $sql = "UPDATE ".MAIN_DB_PREFIX."contab_cuentas SET";
		$sql.= " codigo='".$object->codigo."'";
		$sql.= ", nombre='".$object->nombre."'";
		$sql.= ", fk_tipocta='".$object->tipo."'";
		$sql.= ", active=".$object->active;
		$sql.= ", fk_nivel=".$object->nivel;
		$sql.= ", afectable=".$object->afectable;
		$sql.= ", nat=".$object->nat;
        $sql.= " WHERE rowid=".$id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $id;

			return $id;
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$langs->trans("Error")." : ".$langs->trans("Error Cuenta ya existe",$this->ref);
				$this->db->rollback();
				return -1;
			}
			else
			{
				$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
				$this->db->rollback();
				return -2;
			}
		}

	}

	function update_asoc($idhijo, $idpadre, $rowversion)
	{
		global $langs, $conf;

		$error=0;
		
		//este procedimiento de encontrar el nombre de la cuenta del padre e hijo solo la utilizo para compatibilidad con el contpaq
		//ecnontramos el nombre del padre 
		$sql = "SELECT c.codigo FROM ".MAIN_DB_PREFIX."contab_cuentas as c WHERE c.rowid='".$idpadre."'";
		$resql=$this->db->query($sql);
		$objctap = $this->db->fetch_object($resql);
		$ctapadre = $objctap->codigo;
		$sql = "SELECT c.codigo FROM ".MAIN_DB_PREFIX."contab_cuentas as c WHERE c.rowid='".$idhijo."'";
		$resql=$this->db->query($sql);
		$objctah = $this->db->fetch_object($resql);
		$ctahijo = $objctah->codigo;
	
		//buscamos primero si existe ya la relación con otra cuenta
		$sql = "SELECT a.fk_hijo FROM ".MAIN_DB_PREFIX."contab_ctanxus as a WHERE a.fk_hijo='".$idhijo."'";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) == 0) {	//no existe una relacion de esa cuenta
				//insertamos la nueva relacion el tabla asociaciones 
				$res = $this->insert_asoc($idhijo,$idpadre,$ctapadre,$ctahijo,$rowversion);
				if ($res > 0) return 1;
				else return -1;				
			}
			else {
				$sql = "UPDATE ".MAIN_DB_PREFIX."contab_ctanxus SET";
				$sql.= " fk_padre=".($idpadre?"'".$idpadre."'":"null");
				$sql.= ", CtaSup=".($ctapadre?"'".$ctapadre."'":"null");				
				$sql.= ", SubCtade=".($ctahijo?"'".$ctahijo."'":"null");
				$sql.= " WHERE fk_hijo=".$idhijo;
			
				$resql=$this->db->query($sql);
				if ($resql)
				{
					return 1;
				}
				else
				{
					$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
					$this->db->rollback();
					return -2;
				}
			}
		}
		else
		{	
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			$this->db->rollback();
			return -2;
		}
		return 1;
	}

	function insert_asoc($idhijo,$idpadre,$ctapadre,$ctahijo,$rowversion)
	{
		global $conf, $langs;

        $error=0;
//echo strftime("%F", strtotime($fechaalta));
		// Produit non deja existant
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contab_ctanxus (";
		$sql.= " RowVersion";
		$sql.= ", fk_padre";
		$sql.= ", fk_hijo";
		$sql.= ", CtaSup";
		$sql.= ", SubCtade";
		$sql.= ", TipoRel";		
		$sql.= ") VALUES (";
		$sql.= " '".$rowversion."'";
		$sql.= ", '".$idpadre."'";
		$sql.= ", '".$idhijo."'";
		$sql.= ", '".$ctapadre."'";
		$sql.= ", '".$ctahijo."'";		
		$sql.= ", 1";
		$sql.= ")";

		dol_syslog(get_class($this)."::Create sql=".$sql);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$id_new = $this->db->last_insert_id(MAIN_DB_PREFIX."contab_cuentas");
			
			return $id_new;
		}
		else
		{
			$error++;
		    $this->error=$this->db->lasterror();
			$this->db->rollback();
			return -5;
		}
	}


	/**
	 *  Load a product in memory from database
	 *
	 *  @param	int		$id      	Id of product/service to load
	 *  @param  string	$ref     	Ref of product/service to load
	 *  @param	string	$ref_ext	Ref ext of product/service to load
	 *  @return int     			<0 if KO, >0 if OK
	 */
	function fetch($id='',$ref='',$ref_ext='')
	{
		global $langs, $conf;

		// Check parameters
		if (! $id && ! $ref && ! $ref_ext)
		{
			$this->error='ErrorWrongParameters';
			dol_print_error(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}

		$sql = "SELECT c.rowid, c.codigo, c.nombre, c.NomIdioma, c.Tipo, c.active,"; 
		$sql.= " c.CtaEfectivo, c.FechaRegistro, c.SistOrigen, c.IdMoneda, c.DigAgrup, c.IdSegNeg, c.SegNegMovtos,";
		$sql.= " c.afectable, c.TimeStamp, c.IdRubro, c.Consume, c.IdAgrupadorSAT, c.ConceptosConsume,";
		$sql.= " c.datelastupdate, c.tms, c.nat, c.fk_nivel as nivel, nc.label as namenivel,";
		$sql.= " tc.label as namtipocta, tc.rowid as tipocta, tc.abrev,";
		$sql.= " a.fk_hijo as idhijo, a.fk_padre as idpadre,";
		$sql.= " cp.rowid as idpadre, cp.codigo as codpadre, cp.nombre as nompadre";
		$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as c";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_tiposcta as tc ON c.fk_tipocta = tc.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_ctanxus as a ON c.rowid = a.fk_hijo';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_nivelcta as nc ON nc.rowid = c.fk_nivel';				
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contab_cuentas as cp ON a.fk_padre = cp.rowid';
		$sql.= ' WHERE c.rowid = '.$id;//die($sql);
		//$sql.= " ORDER BY rowid c.active = 0";

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id						= $obj->rowid;
				$this->codigo					= $obj->codigo;
				$this->nombre					= $obj->nombre;		

				$this->fechaalta				= $obj->FechaRegistro;
				$this->dlu						= $obj->datelastupdate;
				$this->tms						= $obj->tms;
				
				$this->afectable				= $obj->afectable;
				$this->active					= $obj->active;
				$this->tipocta					= $obj->tipocta;
				$this->namtipocta				= $obj->namtipocta;
				$this->nat						= $obj->nat;
				$this->nivel					= $obj->nivel;
				$this->namenivel				= $obj->namenivel;
				
//Variables contpaq
		 		$this->ctaefectivo				= $obj->CtaEfectivo;
				$this->sistorigen				= $obj->SistOrigen;
				$this->idmoneda					= $obj->IdMoneda;
				$this->digagrup					= $obj->DigAgrup;
				$this->idsegneg					= $obj->IdSegNeg;
				$this->segnegmovtos				= $obj->SegNegMovtos;
				$this->timestamp				= $obj->TimeStamp;
				$this->idrubro					= $obj->IdRubro;
				$this->consume					= $obj->Consume;
				$this->idagrupadorsat			= $obj->IdAgrupadorSAT;
				$this->conceptosconsume			= $obj->ConceptosConsume;
				
				$this->idpadre					= $obj->idpadre;
				$this->codpadre					= $obj->codpadre;
				$this->nompadre					= $obj->nompadre;
			
				$this->idtipocta				= $obj->idtipocta;
				$this->tipo						= $obj->Tipo;
							
				$this->db->free($resql);
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
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

		$lien = '<a href="'.DOL_URL_ROOT.'/custom/contab/cuentas/card.php?mode=1&amp;id='.$this->id.'">';
		$lienfin='</a>';

		$newref=$this->ref;
		if ($maxlength) $newref=dol_trunc($newref,$maxlength,'middle');

		if ($withpicto)	$result.=($lien.img_object($langs->trans("Ir a cuenta").' '.$this->ref,'order').$lienfin.' ');
		$result.=$lien.$newref.$lienfin;
		return $result;
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int	$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@param      int	$type       0=Shell, 1=Buy
	 *	@return     string      	Label of status
	 */
	function getLibStatut($mode=0, $type=0)
	{
		if($type==0)
			return $this->LibStatut($this->status,$mode,$type);
		else
			return $this->LibStatut($this->status_buy,$mode,$type);
	}

	/**
	 *	Return label of a given status
	 *
	 *	@param      int		$status     Statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@param      int		$type       0=Status "to sell", 1=Status "to buy"
	 *	@return     string      		Label of status
	 */
	function LibStatut($status,$mode=0,$type=0)
	{
		global $langs;
		$langs->load('products');

		if ($mode == 0)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('Cuenta no Afectable'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return ($type==0 ? $langs->trans('Cuenta Afectable'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 1)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto(($type==0 ? $langs->trans('ProductStatusNotOnSell') : $langs->trans('ProductStatusNotOnBuy')),'statut5');
			if ($status == 1) return img_picto(($type==0 ? $langs->trans('ProductStatusOnSell') : $langs->trans('ProductStatusOnBuy')),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 5)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('No Afectable'):$langs->trans('No Activa')).' '.img_picto(($type==0 ? $langs->trans('No es cuenta utilizable'):$langs->trans('No esta activa la cuenta')),'statut5');
			if ($status == 1) return ($type==0 ? $langs->trans('Afectable'):$langs->trans('Activa')).' '.img_picto(($type==0 ? $langs->trans('Es una cuenta ultilizable'):$langs->trans('Esta activa')),'statut4');
		}
		return $langs->trans('Unknown');
	}

}
?>
