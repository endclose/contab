<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent		<jmenent@2byte.es>
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
 *       \file       htdocs/admin/pdf.php
 *       \brief      Page to setup PDF options
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

require_once DOL_DOCUMENT_ROOT.'/contab/polizas/class/polizas.class.php';
require DOL_DOCUMENT_ROOT.'/contab/class/contab.class.php';
require_once DOL_DOCUMENT_ROOT.'/contab/class/commoncontab.class.php';
require_once DOL_DOCUMENT_ROOT.'/contab/lib/contab.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contab/cuentas/class/cuentas.class.php';
require_once DOL_DOCUMENT_ROOT.'/nxus/core/lib/nxos.lib.php';

$langs->load("admin");
$langs->load("languages");
$langs->load("other");

$langs->load("companies");
$langs->load("products");
$langs->load("members");
//print var_dump($user->admin);

if ($user->admin || $user->super) $z=0; 
else accessforbidden();

$action = GETPOST('action','alpha');
$idejercicio = GETPOST('idejercicio');
$idperiodo = GETPOST('idperiodo');
$idejercicio_in = GETPOST('idejercicio_in');
$idperiodo_in = GETPOST('idperiodo_in');

//print var_dump($action, $idejercicio, $idperiodo, $idejercicio_in,$idperiodo_in);

/*
 * Actions
 */
if ($action == 'updateperiodcontab')	// Edit
{
    $contab=new Contabilidad($db);
    
    if (empty($idejercicio) || empty($idperiodo) || empty($idejercicio_in) || empty($idperiodo_in)) {
        $mesgs = "Los valores de los Parametros no deben estar vacios";
        setEventMessage($mesgs,'errors');
    }
    else if (($idejercicio<$idejercicio_in) || ($idejercicio == $idejercicio_in && $idperiodo<$idperiodo_in)) {
        $mesgs = "Los valores del Periodo Contable Actual no puede ser anteriores al del Periodo Contable Inicial";
        setEventMessage($mesgs,'errors');       
    }
    else {
        $contab->ejercicio_act =  $idejercicio;
        $contab->periodo_act =  $idperiodo;
        $contab->ejercicio_in =  $idejercicio_in;
        $contab->periodo_in =  $idperiodo_in;
        $result = $contab->actualPeriodContab();
    }
    $action = '';
    
}

/*
 * View
 */

$wikihelp='EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$wikihelp);

$form=new Form($db);
$formother=new FormOther($db);
$formadmin=new FormAdmin($db);

print_fiche_titre($langs->trans("Ejercicios y Periodos"),'','setup');

print $langs->trans("Puede definir aquí las opciones globales para la visualización y contabilización de los registros contables y sus informes")."<br>\n";
print "<br>\n";

if ($action=='' || $action == 'editperiodcontab')	// Show
{
    $var=true;

    // Misc options
    print '<table summary="more" class="noborder" width="100%">';
    
	print '<table class="noborder nohover" width="30%" align="left">';
    if ($action == 'editperiodcontab') print showEjercPeriodContab('idlistperiod', array('Parámetro'), 2, 1, 1);
    else if (!$action) print showEjercPeriodContab('idlistperiod', array('Parámetro'), 1, 1, 1);
	print '</table>';    

    print '<br>';
    
	print '</table>';

	print '<br>';

}


llxFooter();

$db->close();
?>
