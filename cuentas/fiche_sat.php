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
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/lib/cuentas.lib.php';


// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;

//$langs->load("companies");
//$langs->load("other");



//$socstatic=new Societe($db);

//print '<div class="tmenu"">'; print '<td>'."hola mundo".'</td>'; print "</div>\n";
llxHeader("",$langs->trans("Cuentas contales"),"");  //Carga el marco principal de la aplicacion


$id			= GETPOST('id','int');

$object = new Cuentas($db);
$res=$object->fetch($id);
if ($res < 0) { dol_print_error($db,$object->error); exit; }
$res=$object->fetch_optionals($object->id,$extralabels); 

$head = cuenta_prepare_head($object); //prepara las pestañas del contenedor de las cuentas
$titre = 'Cuentas contables';
//dol_fiche_head($head, 'card', $langs->trans("Cuentas contables"),0,'company'); //Control tipo contendedor de pestañas 
dol_fiche_head($head, 'sat', $titre, 0, $picto);	//Control tipo contendedor de pestañas

$form = new Form($db);

print '<table class="border" width="100%">';

//Datos generales
print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold">'.'Datos Cuenta'.'&nbsp;</td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Código').'</td><td colspan="3"><h6>'.substr($object->codigo,0,3)."-".substr($object->codigo,3,2)."-".substr($object->codigo,5,3);
//print $form->showrefnav($object, 'id', '', 0, 'rowid', 'nom');
print '</h6></td>';
print '</tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Nombre').'</td><td><h6>'.$object->nombre.'</h6></td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Estado').'</td><td><h6>'.(($object->esbaja == 0)?'ACTIVA':'INACTIVA').'</h6></td></tr>';
//Afectacion
print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold">'.'Contabilidad Electrónica'.'&nbsp;</td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Dígito agrupador SAT').'</td><td><h6>'.$object->tipocta.'</h6></td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Naturaleza').'</td><td><h6>'.(($object->naturaleza == 0)?'DEUDORA':'ACREEDORA').'</h6></td></tr>';
//Ubicacion
print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold">'.'Ubicación'.'&nbsp;</td></tr>';
print '<tr><td width="20%" class="fielrequired">Pertenece a</td><td>';
print '<a href="'.DOL_URL_ROOT.'/custom/contab/cuentas/fiche.php?id='.$object->idpadre.'">'.$object->codpadre."-".$object->nompadre;
print '</td></tr>';
print '<tr><td width="20%" class="fielrequired">Afectable</td><td>'.(($object->Afectable==1)?"Si":"No").'</td></tr>';
print '<tr><td width="20%" class="fielrequired">Nivel de la cuenta</td><td>'.$object->nivel.'</td></tr>';
print '<tr><td width="20%" class="fielrequired">Cuenta de Mayor</td><td>'.(($object->Afectable == 0 && $object->nivel == 2)?"Si":"No").'</td></tr>';
//Cronologia
print '<tr><td align="center" bgcolor="#642EFE" style="color:white;" style="font-style:bold">'.'Cronología'.'&nbsp;</td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Fecha alta').'</td><td>'.$object->fechaalta.'</td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Fecha último estado').'</td><td>'.$object->dlu.'</td></tr>';
print '<tr><td width="20%" class="fielrequired">'.$langs->trans('Fecha última modificación').'</td><td>'.$object->tms.'</td></tr>';

print '</table>';


$db->close();

llxFooter();
?>
