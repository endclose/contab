<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/compta/paiement/rapport.php
 *	\ingroup    facture
 *	\brief      Payment reports page
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/contab/cuentas/class/cuentas.class.php';
//require_once DOL_DOCUMENT_ROOT.'/core/modules/rapport/pdf_paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Security check
if (! $user->rights->facture->lire) accessforbidden();

$action=GETPOST('action');

$dir = $conf->facture->dir_output.'/smc';

$socid=0;
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
    //$dir = $conf->facture->dir_output.'/payments/private/'.$user->id;
	$dir = $conf->facture->dir_output.'/smc/'.$user->id;
}

$year = $_GET["year"];
if (! $year) { $year=date("Y"); }


/*
 * Actions
 */


/*
 * View
 */

$formother=new FormOther($db);
$form=new Form($db);

llxHeader();

//$titre=($year?$langs->trans("PaymentsReportsForYear",$year):$langs->trans("PaymentsReports"));   
$titre=($langs->trans("Actualizar cuentas contables con cuentas de Contpaq"));
print load_fiche_titre($titre);

print 'IMPORTANTE:';print '<br>';
print "- Este proceso puede tardar varios minutos";print '<br>';
print "- Si ocurre un error durante la sincronización intentelo de nuevo.";print '<br>';
print "- En caso de persistir el error contacte al administrador e informe del error.";print '<br>';

// Formulaire de generation
//print '<form method="post" action="actual_cuentas.php?year='.$year.'">';
print '<form method="post" action="actual_cuentas.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="builddoc">';
//$dates = GETPOST("redi")?GETPOST("redi"):date("n", time());
//$datee = GETPOST("redf")?GETPOST("redf"):date("Y", time());

//print $formother->select_dayofweek($dayth,'redayth');

//print $formother->select_month($cmonth,'remonth');
 
//print $formother->select_year($syear,'reyear');

print '<table class="border" width="30%">';

/*
// Date 1
print '<tr><td class="fieldrequired">'.$langs->trans('Fecha Inicial').'</td><td colspan="2">';
$form->select_date('','redi','','','',"datei",1,1,'');
print '</td></tr>';

// Date 2
print '<tr><td class="fieldrequired">'.$langs->trans('Fecha Final').'</td><td colspan="2">';
$form->select_date('','redf','','','',"datef",1,1,'');
print '</td></tr>';
print "</table>";
*/
print '<br>';

print '<input type="submit" class="button" value="'.$langs->trans("Sincronizar").'">';
//print '</td>';

print '</form>';


print '<br>';print '<br>';print '<br>';


print '<table width="100%"><tr><td width="50%" valign="top">';
print '<a name="builddoc"></a>'; // ancre

print "Consola:";print '<br>';


/*
* Sincronizacion
*/

if ($action=='builddoc') 
	{
	    $cuenta = new Cuentas($db);
		//conectamos con la base de datos de sql server
		$connection = mssql_connect('leo.leonx.net', 'sa', 'danikle97');
		
		if (!$connection) {print 'No se pudo conectar al Servidor';print '<br>'; exit;}
		else {print 'Conexion al Servidor Exitosa... ';print '<br>';}
		
		if (!mssql_select_db('ctMaria_Candelaria_Rodriguez_Solis_17', $connection)) {print 'No se pudo conectar al base de datos...';exit;}
		else {print 'Conexión a la base de datos exitosa... ';print '<br>';}
		
		print 'Ejecutando consulta... ';print '<br>';
		$result = mssql_query('SELECT * FROM dbo.Cuentas Order By Id');
		$result2 = mssql_query('SELECT * FROM dbo.Asociaciones Order By Id');
/*		
		//actualizamos primero la tabla de llx_c_cuentas_contab
		while ($row = mssql_fetch_array($result)) {
		  //var_dump($row);
		  	//echo($row['Id']);
			$actualiza=0;
			$sql = "SELECT c.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_cuentas_contab as c";
			$sql.= " WHERE c.rowid = ".$row['Id'];
			$resultado = $db->query($sql);
			$num = $db->num_rows($resultado);
			if ($num == 0) 
			{
				//Agrega cuentas nuevas
				print "No existe la cuenta->".$row['Id'];print '<br>';
				print "Agregando cuenta: ".$row['Codigo']."-".$row['Nombre'];print '<br>';
				$cuenta->create($row['Id'], $row['Codigo'], $row['Nombre'], $row['FechaRegistro'], $row['Afectable'], $row['EsBaja']); //agregamos la cuenta nueva
				if ($error==0) {print "Cuenta agregada exitosamente...";print '<br>';}
				else {print "ERROR: Cuenta no agregada"; print '<br>';}
			}
			else 
			{
				//Actualiza cambios en cuentas
//				$cuenta->update_from_contpaq($row['Id'], $row['Codigo'], $row['Nombre'], $row['FechaRegistro'], $row['Afectable'], $row['EsBaja']); //actualizamos la cuenta
				$actualiza = $cuenta->update_from_contpaq($row, $row['Id']); //actualizamos la cuenta
				if ($actualiza<0) 
				{
					print "ERROR: Cuenta no actualizada:".$row['Codigo']."-".$row['Nombre']; print '<br>';
				}
				else {
					print $row['Id'];print '<br>';
				}
			}
			$db->free($resultado);
		}
*/		
		//actualizamos la tabla de llx_asociaciones
/*		
		//vaciamos la tabla de asociaciones
		print "Vaciando la tabla de asociaciones..."; print '<br>';
		$sql = "TRUNCATE llx_asociaciones";
		$consulta = $db->query($sql);
		$db->free($resultado);
		//agregamos los nuevos datos desde la tabla de contpaq dbo.Asociaciones		
		while ($row = mssql_fetch_array($result2)) {
		  	//echo($row['Id']);
			print "Agregando la asociacion: ".$row['Id'];print '<br>';
				$cuenta->create_asoc_ctpq($row['Id'], $row['RowVersion'], $row['IdCtaSup'], $row['IdSubCtade'], $row['CtaSup'], $row['SubCtade'], $row['TipoRel'], $row['TimeStamp']); //agregamos la cuenta nueva 
			if ($error==0) {print "Asociacion agregada exitosamente...";print '<br>';}
			else {print "ERROR: Asociacion no agregada"; print '<br>';}
		}
*/			

		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_cuentas_contab as c";
		$resultado = $db->query($sql);
		$resql = $db->query($sql);
		while ($row = $db->fetch_array($resql)) {
			$j=0;
			print "Registro ".$i.": ";
//			foreach ($row as $rw) {
//				print "Campo ".$j."-valor:".$rw." / ";
//				$j++;
//			}
			for ($j = 0; $j <= 20; $j++) {
				print "Campo ".$j."-valor:".$row[$j]." / ";
			}
		 	print '******'.'<br>';
			$i++;
		}
		
/*		$i=0;	
		while ($row = mssql_fetch_array($result)) {
			$j=0;
			print "Registro ".$i.": ";
//			foreach ($row as $rw) {
//				print "Campo ".$j."-valor:".$rw." / ";
//				$j++;
//			}
			for ($j = 0; $j <= 20; $j++) {
				print "Campo ".$j."-valor:".$row[$j]." / ";
			}
		 	print '******'.'<br>';
			$i++;
		}
*/
		mssql_free_result($result);
		mssql_free_result($result2);
		print 'PROCESO FINALIZADO';
				
	}
else 
	{
		print "Listo";
	}

llxFooter();

$db->close();
?>
