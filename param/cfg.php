<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/core/tools.php
 *       \brief      Home page for top menu tools
 */

require '../../../main.inc.php';  //cargar el modulo principal
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/contab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/class/commoncontab.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/lib/contab.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/polizas/class/polizas.class.php';

$contab_com = new CommonContab($db);

llxHeader("","Parámteros Contabilidad","");  //titulo de la pestaña del navegador

$text="Configuración";

//print_fiche_titre($text);  //titulo de la pagina 

print '<div >';  //contendor principal

	print '<div id="nombre_opcion">
				<img height="35" src="'.DOL_URL_ROOT.'/media/imgs/interface/folder-9.png">Parámetros
			</div>';
	
	print '<table id="formularios">
			<tr>
				<td id="margen">&nbsp;</td>
				<td align="center" id="pestana" name="pestana" onClick="pestanas(1)">'.'Ciclo Contable'.'</td>
				<td align="center" id="pestana_no" name="pestana" onClick="pestanas(2)">'.'Cuentas predefinidas'.'</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
				<td id="pestana_no">&nbsp;</td>
			</tr>';
	print '</table>';
	
	print '<div id="cards" name="cards">';  //contendor de los formularios
		print '<table id="tabla">
				<tr>
					<td id="label">'.'Periodo contable acutal'.'</td>
					<td id="separa_celda">&nbsp;</td>
					<td id="label">'.'Ejercicio acutal'.'</td>
				</tr>
				<tr>
					<td>
						<div id="select-field">'.
							selectPeriodo($conf->global->PERIOD_CONTAB).'
						</div>
					</td>
					<td id="separa_celda">&nbsp;</td>
					<td>
						<div id="select-field">'.
							selectEjercicio($conf->global->FISCAL_YEAR).'
						</div>
					</td>
					
				</tr>';
		print '</table>';
	print '</div>';  //fin del contendor de los formularios

/*	print '<div>';  //contendor de los formularios
		print '<div id="tabla">
				<div>
					<p id="label">'.'Ejercicio actual'.'</p>
					<p id="label">'.'Periodo contable actual'.'</p>					
				</div>
				<div id="columna">
					<div id="select-field">'.
							selectPeriodo($conf->global->PERIOD_CONTAB).'
					</div>
				</div>';
		print '</div>';
	print '</div>';  //fin del contendor de los formularios
*/	
print '</div>'; //fin contenedor principal




//llxFooter();

?>

<style type="text/css">
body {
	padding:0;
	margin:0;
}
#columna {
	top:0px;
	left:280px;
	position:relative;
}
#main_div {
	width:100%;
	height:300px;
	background-color:#fff;
}
#cards {
	top:30px;
	position:relative;
}
#tabla {
	padding:0px;
}
#label {
	display:block;
	margin:5px 0;
	padding:10px 50px 10px 10px;
	background-color:rgba(59,89,152,1.00);
	color:#FFFFFF;
	font-weight:bold;
	font-size:14px;
	width:200px;
}
#select-field {
	display: inline-block;
	width: 100%;
	max-width: 450px;
	height:40px;
	margin: 0px;
	padding: 0px;
	background-color: #3d4655;
	border-radius: 4px;
	position: relative;
	color:#FFFFFF;
	z-index: 1;
}
#select-field:after {
	content: "";
	width: 0px;
	height: 0px;

	border-top: 9px solid #bdc3d1;
	border-right: 8px solid transparent;
	border-left: 8px solid transparent;

	position: absolute;
	top: 27px;
	right: 15px;
	z-index: 2;
}
#select-field > select {
	float: left;
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px 45px 0px 15px;
	border: 0px;
	background-color: #3d4655;
	font-size: 16px;
	color: #fff;
	position: relative;
	z-index: 3;
}
#select-field > select > option {
	padding: 10px;
}

#select {
	-webkit-appearance:none;
	-moz-appearance:none;
	-o-appearance:none;
	-ms-appareance:none;
	appearance:none;
	
	display:block;
	margin:30px 0;
	padding:10px 50px 10px 10px;
	border-style:solid;
	background:url(../../media/imgs/interface/select.png) no-repeat 95% center;
	background-color:#3d4655;
	border-radius:4px;
	border: 2px solid #9a9a9a;
	color:#FFFFFF;
	width:100px;

	
}
#formularios{
	border-collapse:collapse;
	border:none;
	cursor:default;
}
#pestana{
	border-color:#C6C6C6;
	border-left-style:solid;
	border-top-style:solid;
	border-right-style:solid;
	font-size:13px;
	width:150px;
	height:40px;
	font-weight:bolder;
	color:#3B5DE0;
	padding:0;
}
#pestana_no {
	border-bottom-color:#C6C6C6;
	border-bottom-style:solid;
	font-size:13px;
	width:150px;
	margin:0;
}
#margen {
	border-bottom-color:#C6C6C6;
	border-bottom-style:solid;
	width:10px;
}
#nombre_opcion{
	height:50px;
	font-size:16px;
	color:#9A9A9A;
	font-weight:bold;
	
}
#separa_celda {
	padding-left:100px;	
}* {
	-webkit-appearance:none;
	-o-appearance:none;
	-ms-appareance:none;
	appearance:none;
    -moz-appearance: none;
    moz-appearance: none;	
}
</style>

<script type="application/javascript">
		$(document).ready(function(){
			var labels = document.getElementsByName('pestana');
			$("#pestana").click(function(){
				labels[0].id='pestana';
				labels[1].id='pestana_no';
				$("#cards").prop('hidden',false);
			})
			$("#pestana_no").click(function(){
				labels[0].id='pestana_no';
				labels[1].id='pestana';
				$("#cards").prop('hidden',true);
			})
//funcion abierta
		})

</script>

