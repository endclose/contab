<?php
/* Copyright (C) 2017 Leopoldo Campos <leo@leonx.net>
*
*/

/**
*	\file       
*	\ingroup    
*	\brief      
*/ 
 
require DOL_DOCUMENT_ROOT.'/custom/contab/includes/phpexcel2/Classes/PHPExcel.php';
  
 
/* FUNCION QUE GENERA UN ARCHIVO DE EXCEL EN BASE A UNA CONSULTA SQL */
// $sql					string: consulta SQL que se va a exportar a excel
// $fields_hide			int:optional numero de los campos que no se exportaran
// $fields_name			array:optional nombre de los campos de la consulta (en lugar de los nombres originales)
// $report_name			string: Nombre del Reporte
// $filename			string: Nombre del archivo a exportar
// $sep_ca				array: Arreglo que contiene las columnas de importe y tipo de movimiento que separan los cargos y abonos
//								primer elemento:posicion de la columna del tipo de movimiento segundo elemento posicion de la columna del importe
// $stat_cols			array: columnas que se obtendran las estadisticas al final
function exportSQLXls($sql, $fields_hide=0, $fields_name=null, $report_name=null, $filename=null, $sep_ca=null, $stat_cols=null) {

global $db, $conf, $user;

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Nxus")
							 ->setLastModifiedBy("Nxus")
							 ->setTitle("Reporte Nxus")
							 ->setSubject("Reporte exportado desde Nxus")
							 ->setDescription("Reporte del listado de Pólizas");
$result = $db->query($sql);

if ($result) {
	//obtenemos el nombre del campo y su tipo	
	$nofields = $db->num_fields($result);	//numero de campos de la consulta
	$types = array();
	$total_cols = 0;	//total de columnas del reporte (total de campos de la consulta menos los campos ocultos)
	for($i=0; $i<$nofields; $i++) {
		$field = $db->fetch_field_direct($result,$i);
		$types[$i] = $field->type;	
		if ($fields_hide[$i]==0) $total_cols++;
	}
}
if($fields_name) {  //si existen encabezado definidos el total numero de columnas van a ser el total de encabezados
	$total_cols = 0;
	foreach($fields_name as $name) {
	 $total_cols++;
	}
}
//Nombre de la empresa
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun($conf->global->MAIN_INFO_SOCIETE_NOM);
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setSize(18);
$objPHPExcel->getActiveSheet()->setCellValue('A1', $objRichText);
$col = chr(64+$total_cols);
$range = 'A1:'.$col.'1';
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//nombre del reporte en el renglon 2 en caso de que se indique el nombre
if($report_name){
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun($report_name);
$objPayable->getFont()->setItalic(true);
$objPayable->getFont()->setSize(16);
$objPHPExcel->getActiveSheet()->setCellValue('A2', $objRichText);
$col = chr(64+$total_cols);
$range = 'A2:'.$col.'2';
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
//Fecha y RFC de la empresa en el renglo 3
$objPHPExcel->getActiveSheet()->setCellValue('A3', 'Fecha:');
$dateTimeNow = time();
$objPHPExcel->getActiveSheet()->setCellValue('B3', PHPExcel_Shared_Date::PHPToExcel( $dateTimeNow ));
$objPHPExcel->getActiveSheet()->getStyle('B3')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX15);
$col = chr(63+$total_cols);
$ref = $col.'3';
$objPHPExcel->getActiveSheet()->setCellValue($ref, 'RFC:');
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$col = chr(64+$total_cols);
$ref = $col.'3';
$objPHPExcel->getActiveSheet()->setCellValue($ref, $conf->global->MAIN_INFO_SIREN);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
$objPHPExcel->getActiveSheet()->freezePaneByColumnAndRow(2,6);	// inmovilizamos los paneles
//colocamos los autofilitros a los encabezados
$col = chr(64+$total_cols);
$range = 'A5:'.$col.'5';
$objPHPExcel->getActiveSheet()->setAutoFilter($range);
$objPHPExcel->getActiveSheet()->getStyle($range)->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'color'		=> array('argb' => '3B5998')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 )
	);


$ren = 5; 

//ponemos el encabezado de las columnas con los nombres de los campos
if ($fields_name) {		//si existe un nombre para las columnas se ponen 
	$c=65;		//creamos el contexto de las referencias de las columnas
	foreach($fields_name as $name) {
		$objRichText = new PHPExcel_RichText();
		//creamos la referencia
		$col = chr($c);
		if ($i>25) $ref = 'A'.chr($c).$ren;
		else $ref = chr($c).$ren;
		$objPayable = $objRichText->createTextRun($name);
		$objPayable->getFont()->setBold(true);
		$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
		//colocamos el valor en la hoja
		$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
		$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		if ($c>90) $c=65;
		else $c++;
	}
}
else {	//no tiene un arreglo de las columnas
	$c=65;		//creamos el contexto de las referencias de las columnas
	for($i=0; $i<$nofields; $i++) {
		$objRichText = new PHPExcel_RichText();
		//creamos la referencia
		if ($i>25) $ref = 'A'.chr($c).$ren;
		else $ref = chr($c).$ren;
		//obtenemos el nombre del campo
		$field = $db->fetch_field_direct($result,$i);
		$name = $field->name;
		$objPayable = $objRichText->createTextRun($name);
		$objPayable->getFont()->setBold(true);
		$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
		//colocamos el valor en la hoja
		$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
		if ($c>90) $c=65;
		else $c++;
	}
}

//$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')
//                                      		->setSize(11);
//colocamos el resultado de la consulta
$i=0; $c=65; $ren = 6; //print $db->num_rows($result);print '<br>';
while ($row = $db->fetch_array($result))  {
	for ($j=0; $j<$nofields; $j++) {
		if ($fields_hide[$j]==0) {
			//separacion de cargos y abonos
			if($sep_ca) {
				if ($j==$sep_ca[1]) { //estamos en la columna de importe
					if($row[$sep_ca[0]]==1)	$c++;//es un abono
				}
			}
			if ($i>25) $ref = 'A'.chr($c).$ren;
			else $ref = chr($c).$ren;
	//		print $ref.":".$types[$j]." ";
			$objPHPExcel->getActiveSheet()->setCellValue($ref, $row[$j]);
			if ($types[$j] == 3) $objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
			if ($types[$j] == 5) $objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
			if ($types[$j] == 12) {
				$objPHPExcel->getActiveSheet()->setCellValue($ref, PHPExcel_Shared_Date::PHPToExcel(strtotime($row[$j])));
				$objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);				
			}
			if ($c>90) $c=65;
			else $c++;
		}
	}
	$ren++;
	$c=65;
//	print '<br>';
}
//colocamos las estadisticas en caso de que se haya solicitado
if($stat_cols) {
	//Ponemos los titulos de las estadisticas
	$ren2 = $ren + 5;	//colocamos los totale 5 renglones mas abajo
	$col = chr(63 + $total_cols - count($stat_cols) + 1);
	$col2 = chr(63 + $total_cols + 1);
	$ref = $col.$ren2;
	$objRichText = new PHPExcel_RichText();
	$objPayable = $objRichText->createTextRun('Estadísticas');
	$objPayable->getFont()->setBold(true);
	$objPayable->getFont()->setSize(14);
	$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
	$range = $col.$ren2.':'.$col2.$ren2;
	$objPHPExcel->getActiveSheet()->mergeCells($range);
	$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$ren2++; $ref = $col.$ren2; $objPHPExcel->getActiveSheet()->setCellValue($ref, 'CONTAR:');
	$ren2++; $ref = $col.$ren2; $objPHPExcel->getActiveSheet()->setCellValue($ref, 'SUMA:');	
	$ren2++; $ref = $col.$ren2; $objPHPExcel->getActiveSheet()->setCellValue($ref, 'PROMEDIO:');
	$ren2++; $ref = $col.$ren2; $objPHPExcel->getActiveSheet()->setCellValue($ref, 'MÁX:');
	$ren2++; $ref = $col.$ren2; $objPHPExcel->getActiveSheet()->setCellValue($ref, 'MÍN:');	
	//ponemos los resultados de los calculos por cada columna solicitada
	foreach($stat_cols as $stat_col){
		$col = chr(63+$stat_col);
		$ren3 = $ren+6;
		$ref = $col.$ren3;
		$contar = '=COUNT('.$col.'6:'.$col.($ren-1).')';	
		$objPHPExcel->getActiveSheet()->setCellValue($ref, $contar);
		$ren3++; $ref = $col.$ren3; $suma = '=SUM('.$col.'6:'.$col.($ren-1).')'; $objPHPExcel->getActiveSheet()->setCellValue($ref, $suma);
		$ren3++; $ref = $col.$ren3; $prom = '=AVERAGE('.$col.'6:'.$col.($ren-1).')'; $objPHPExcel->getActiveSheet()->setCellValue($ref, $prom);
		$ren3++; $ref = $col.$ren3; $max = '=MAX('.$col.'6:'.$col.($ren-1).')'; $objPHPExcel->getActiveSheet()->setCellValue($ref, $max);
		$ren3++; $ref = $col.$ren3; $min = '=MIN('.$col.'6:'.$col.($ren-1).')'; $objPHPExcel->getActiveSheet()->setCellValue($ref, $min);
		//formato de numero a las celdas de suma, promedio, maximo y minimo
		for ($i=7; $i<10; $i++){
			$col = chr(63+$stat_col);
			$ren4 = $ren+$i;
			$ref = $col.$ren4;
			$objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);			
		}
	}
/*
	
	//calculamos los totales
	$ref = 'K'.$ren;
	$subtotal = '=SUM(K6:K'.$ren.')';
	$objPHPExcel->getActiveSheet()->setCellValue($ref, $subtotal);
*/
	
}

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Polizas');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

if (!$filename) $filename = 'export_xlsx';

$dire = file_exists($conf->export->dir_temp.'/'.$user->id);
if (!$dire) mkdir($conf->export->dir_temp.'/'.$user->id, 0700);	
$fullfilename = $conf->export->dir_temp.'/'.$user->id.'/'.$filename.'.xlsx';
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save($fullfilename);

$download = header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$filename.".xlsx");

return true;

}
 
/* FUNCION QUE GENERA UN ARCHIVO DE EXCEL EN BASE A UNA CONSULTA SQL */
// $sql					string: consulta SQL que se va a exportar a excel
// $fields_hide			int:optional numero de los campos que no se exportaran
// $fields_name			array:optional nombre de los campos de la consulta (en lugar de los nombres originales)
// $report_name			string: Nombre del Reporte
// $filename			string: Nombre del archivo a exportar
// $sep_ca				array: Arreglo que contiene las columnas de importe y tipo de movimiento que separan los cargos y abonos
//								primer elemento:posicion de la columna del tipo de movimiento segundo elemento posicion de la columna del importe
// $stat_cols			array: columnas que se obtendran las estadisticas al final
function exportBalanzaXls($ejercicio, $periodo, $fields_hide=0, $fields_name=null, $report_name=null, $filename=null, $sep_ca=null, $stat_cols=null) {
require_once DOL_DOCUMENT_ROOT.'/contab/class/contab.class.php';
$contab = new Contabilidad($db);

global $db, $conf, $user;

$contab = new Contabilidad($db);

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Nxus")
							 ->setLastModifiedBy("Nxus")
							 ->setTitle("Reporte Nxus")
							 ->setSubject("Reporte exportado desde Nxus")
							 ->setDescription("Reporte del listado de Pólizas");
							 
//Buscamos el mes en base al periodo (spanish)
switch ($periodo) {
    case 1:
        $mes_periodo = "Enero"; break;
    case 2:
        $mes_periodo = "Febrero"; break;
    case 3:
        $mes_periodo = "Marzo"; break;
    case 4:
        $mes_periodo = "Abril"; break;
    case 5:
        $mes_periodo = "Mayo"; break;
    case 6:
        $mes_periodo = "Junio"; break;
    case 7:
        $mes_periodo = "Julio"; break;
    case 8:
        $mes_periodo = "Agosto"; break;
    case 9:
        $mes_periodo = "Septiembre"; break;
    case 10:
        $mes_periodo = "Octubre"; break;
    case 11:
        $mes_periodo = "Noviembre"; break;
    case 12:
        $mes_periodo = "Diciembre"; break;
}							 
							 
//				  0			1		2				 3					 4				5		 6			7
$sql = "SELECT c.rowid, c.codigo, c.nombre, tc.label as tipocta, nc.abrev as nivelcta, c.nat, c.afectable, c.saldo";
$sql.= " FROM ".MAIN_DB_PREFIX."contab_cuentas as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_tiposcta as tc ON tc.rowid = c.fk_tipocta";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contab_nivelcta as nc ON nc.rowid = c.fk_nivel";
$sql.= " WHERE c.rowid IS NOT NULL";
$sql.= " ORDER BY c.codigo ASC ";
							 
$result = $db->query($sql);

if ($result) {
	//obtenemos el nombre del campo y su tipo	
	$nofields = $db->num_fields($result);	//numero de campos de la consulta
	$types = array();
	$total_cols = 0;	//total de columnas del reporte (total de campos de la consulta menos los campos ocultos)
	for($i=0; $i<$nofields; $i++) {
		$field = $db->fetch_field_direct($result,$i);
		$types[$i] = $field->type;	
		if ($fields_hide[$i]==0) $total_cols++;
	}
}

$total_cols = 18;
$fields_name = array('Codigo','Nombre','Tipo','Nivel','Naturaleza','Afectable','Deudor','Acreedor','Cargos','Abonos','Deudor','Acreedor','Deudor','Acreedor','Debe','Haber','Deudor','Acreedor');
//Nombre de la empresa
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun($conf->global->MAIN_INFO_SOCIETE_NOM);
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setSize(18);
$objPHPExcel->getActiveSheet()->setCellValue('A1', $objRichText);
$col = chr(64+$total_cols);
$range = 'A1:'.$col.'1';
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//nombre del reporte en el renglon 2 en caso de que se indique el nombre
if($report_name){
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun($report_name);
$objPayable->getFont()->setItalic(true);
$objPayable->getFont()->setSize(16);
$objPHPExcel->getActiveSheet()->setCellValue('A2', $objRichText);
$col = chr(64+$total_cols);
$range = 'A2:'.$col.'2';
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
//Ejercicio
$objPHPExcel->getActiveSheet()->setCellValue('G3', 'Ejercicio:');
$objPHPExcel->getActiveSheet()->setCellValue('H3', $ejercicio);
$objPHPExcel->getActiveSheet()->getStyle('H3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

//Periodo
$objPHPExcel->getActiveSheet()->setCellValue('I3', 'Periodo:');
$objPHPExcel->getActiveSheet()->setCellValue('J3', $mes_periodo);
$objPHPExcel->getActiveSheet()->getStyle('J3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

//Fecha y RFC de la empresa en el renglo 3
$objPHPExcel->getActiveSheet()->setCellValue('O3', 'Fecha:');
$dateTimeNow = time();
$objPHPExcel->getActiveSheet()->setCellValue('P3', PHPExcel_Shared_Date::PHPToExcel( $dateTimeNow ));
$objPHPExcel->getActiveSheet()->getStyle('P3')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX15);
$objPHPExcel->getActiveSheet()->getStyle('P3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

$col = chr(63+$total_cols);
$ref = $col.'3';
$objPHPExcel->getActiveSheet()->setCellValue($ref, 'RFC:');
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$col = chr(64+$total_cols);
$ref = $col.'3';
$objPHPExcel->getActiveSheet()->setCellValue($ref, $conf->global->MAIN_INFO_SIREN);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
$objPHPExcel->getActiveSheet()->freezePaneByColumnAndRow(2,6);	// inmovilizamos los paneles
//colocamos los autofilitros a los encabezados
$col = chr(64+$total_cols);
$range = 'A5:'.$col.'5';
$objPHPExcel->getActiveSheet()->setAutoFilter($range);
$objPHPExcel->getActiveSheet()->getStyle($range)->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'color'		=> array('argb' => '3B5998')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 )
	);

$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Saldo Inic. Periodo');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'G4'; $range = 'G4:H4';
$objPHPExcel->getActiveSheet()->getStyle('G4:H4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('G4:H4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Movimientos Periodo');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'I4'; $range = 'I4:J4';
$objPHPExcel->getActiveSheet()->getStyle('I4:J4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('I4:J4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Saldo Final Periodo');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'K4'; $range = 'K4:L4';
$objPHPExcel->getActiveSheet()->getStyle('K4:L4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('K4:L4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Saldo Inicial Ejercicio');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'M4'; $range = 'M4:N4';
$objPHPExcel->getActiveSheet()->getStyle('M4:N4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('M4:N4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Movimientos Ejercicio');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'O4'; $range = 'O4:P4';
$objPHPExcel->getActiveSheet()->getStyle('O4:P4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('O4:P4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
$objRichText = new PHPExcel_RichText();
$objPayable = $objRichText->createTextRun('Saldo Final Ejercicio');
$objPayable->getFont()->setBold(true);
$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
$ref = 'Q4'; $range = 'Q4:R4';
$objPHPExcel->getActiveSheet()->getStyle('Q4:R4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle('Q4:R4')->getFill()->getStartColor()->setARGB('3B5998');
$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
$objPHPExcel->getActiveSheet()->mergeCells($range);
$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);

		
$ren = 5; 

//ponemos el encabezado de las columnas con los nombres de los campos
if ($fields_name) {		//si existe un nombre para las columnas se ponen 
	$c=65;		//creamos el contexto de las referencias de las columnas
	foreach($fields_name as $name) {
		$objRichText = new PHPExcel_RichText();
		//creamos la referencia
		$col = chr($c);
		if ($i>25) $ref = 'A'.chr($c).$ren;
		else $ref = chr($c).$ren;
		$objPayable = $objRichText->createTextRun($name);
		$objPayable->getFont()->setBold(true);
		$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
		//colocamos el valor en la hoja
		$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
		$objPHPExcel->getActiveSheet()->getStyle($ref)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		if ($c>90) $c=65;
		else $c++;
	}
}
else {	//no tiene un arreglo de las columnas
	$c=65;		//creamos el contexto de las referencias de las columnas
	for($i=0; $i<$nofields; $i++) {
		$objRichText = new PHPExcel_RichText();
		//creamos la referencia
		if ($i>25) $ref = 'A'.chr($c).$ren;
		else $ref = chr($c).$ren;
		//obtenemos el nombre del campo
		$field = $db->fetch_field_direct($result,$i);
		$name = $field->name;
		$objPayable = $objRichText->createTextRun($name);
		$objPayable->getFont()->setBold(true);
		$objPayable->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
		//colocamos el valor en la hoja
		$objPHPExcel->getActiveSheet()->setCellValue($ref, $objRichText);
		if ($c>90) $c=65;
		else $c++;
	}
}


//$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')
//                                      		->setSize(11);

//colocamos el resultado de la consulta
$i=0; $c=65; $ren = 6; //print $db->num_rows($result);print '<br>';

//Reiniciamos totales
$suma_salinid = 0; $suma_salinia = 0;
$suma_debe = 0;	$suma_haber = 0;
$suma_salfind = 0; $suma_salfina= 0;
$suma_salinide = 0; $suma_saliniae= 0;
$suma_debe_ej = 0; $suma_haber_ej = 0;	
$suma_sald = 0; $suma_sala= 0;				

while ($row = $db->fetch_array($result))  {
	$movs = getNxusMov($row[0],'IdCuenta','contab_polizas_movs',array('Ejercicio'),array($ejercicio),'/contab/polizas/listmov.php','search_codigo',$row[1]);
	if($movs) {
		//Obtenemos los saldos, cargos y abonos de la cuenta
		$saldos = $contab->getSaldo($row[0],$ejercicio,$periodo);
		$debe_ej = $saldos[2] + $saldos[4];
		$haber_ej = $saldos[3] + $saldos[5];
						
		for ($j=0; $j<$total_cols+1; $j++) {
			if ($j>0) {
				$ref = chr($c).$ren;
		//		print $ref.":".$types[$j]." ";
				if ($j<=4) {
					$objPHPExcel->getActiveSheet()->setCellValue($ref, $row[$j]);
				}
				else {
					if ($j==5) {	//Naturaleza
						if ($row[$j]==1) $texto = 'DEUDORA';
						else $texto = 'ACREEDORA';
					}
					if ($j==6) {	//Afectable
						if ($row[$j]==0) $texto = 'NO';
						else $texto = 'SI';
					}
					if ($j>6) {
	
						if ($j==7) {	//Saldo al inicio del periodo
							if ($row[5]==1) {	//Saldo inicial deudor
								$texto=$saldos[10];
								$suma_salinid = $suma_salinid + $saldos[10];
							}
							else $texto='';
						}
						if ($j==8) {	//Saldo inicial acreedor
							if ($row[5]==-1) {
								$texto=$saldos[10];;
								$suma_salinia = $suma_salinia + $saldos[10];
							}
							else $texto='';
						}
						if ($j==9) { $texto=$saldos[4]; $suma_debe = $suma_debe + $saldos[4]; }	//Cargos del Periodo
						if ($j==10) { $texto=$saldos[5]; $suma_haber = $suma_haber + $saldos[5]; }	//Haber del Periodo
						if ($j==11) {
							if ($row[5]==1) {	//Saldo final deudor
								$texto=$saldos[11];
								$suma_salfind=$suma_salfind+$saldos[11];
							}
							else $texto='';
						}
						if ($j==12) {
							if ($row[5]==-1) {	//Saldo final acreedor
								$texto=$saldos[11];
								$suma_salfina=$suma_salfina+$saldos[11];
							}
							else $texto='';
						}
						if ($j==13) {
							if ($row[5]==1) {	//Saldo inicial ejericio deudor
								$texto=$saldos[8];
								$suma_salinide=$suma_salinide+$saldos[8];
							}
							else $texto='';
						}
						if ($j==14) {
							if ($row[5]==-1) {	//Saldo inicial ejercicio acreedor
								$texto=$saldos[8];
								$suma_saliniae=$suma_saliniae+$saldos[8];
							}
							else $texto='';
						}												
						if ($j==15) { $texto=$debe_ej; $suma_debe_ej = $suma_debe_ej + $debe_ej; }
						if ($j==16) { $texto=$haber_ej; $suma_haber_ej = $suma_haber_ej + $haber_ej; }
						if ($j==17) {	//Saldo final ejercicio deudor
							if ($row[5]==1) {
								$texto=$saldos[11];
								$suma_sald=$suma_sald+$saldos[11];
							}
							else $texto='';
						}
						if ($j==18) {	//Saldo final ejercicio deudor
							if ($row[5]==-1) {
								$texto=$saldos[11];
								$suma_sala=$suma_sala+$saldos[11];
							}
							else $texto='';
						}						
					}
					$objPHPExcel->getActiveSheet()->setCellValue($ref, $texto); 
				}
				if ($j >= 7) $objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
				$c++;
			}
		}
		$ren++;
		$c=65;
	}
}
$range = 'G'.$ren.':R'.$ren;
$objPHPExcel->getActiveSheet()->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
$objPHPExcel->getActiveSheet()->getStyle($range)->getFill()->getStartColor()->setARGB('3B5998');

$c = 71;
$ren;
$ren2 = $ren-1;
for ($i=1; $i<=10; $i++) {
	$col = chr($c);
	$range = $col.'6:'.$col.$ren2;
	$ref = $col.$ren;
	$suma = '=SUM('.$range.')'; 
	$objPHPExcel->getActiveSheet()->setCellValue($ref, $suma);
	$objPHPExcel->getActiveSheet()->getStyle($ref)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
	$objPHPExcel->getActiveSheet()->getStyle($ref)->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_WHITE ) );
	$objPHPExcel->getActiveSheet()->getStyle($ref)->getFont()->setBold(true);
	$c++;
}


// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Polizas');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

if (!$filename) $filename = 'export_xlsx';

$dire = file_exists($conf->export->dir_temp.'/'.$user->id);
if (!$dire) mkdir($conf->export->dir_temp.'/'.$user->id, 0700);	
$fullfilename = $conf->export->dir_temp.'/'.$user->id.'/'.$filename.'.xlsx';
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save($fullfilename);

$download = header("Location: ".DOL_URL_ROOT."/document.php?modulepart=export&file=".$filename.".xlsx");

return true;

}

 
?>

