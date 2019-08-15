<?php

function transformForInsert($row) {
	$row[0] = '"' . $row[0] . '"'; // Wrap with quotes for INSERT VALUES ("X", "Y")
	$row[1] = '"' . $row[1] . '"';
	$row[3] = '"' . $row[3] . '"';
	$row[4] = 'STR_TO_DATE("' . $row[4] . '", "%d/%m/%Y")';
	preg_match('/£([\d.]+)/', $row[6], $regexmatch); // Get rid of £ for money amounts
	$row[6] = $regexmatch[1];
	$rowStr = "(" . implode(", ", $row) . ")"; // Create bracket enclosed str for SQL INSERT
	return $rowStr;
}

function grouper($row) { // Extract first 5 rows, add 2 0s for sales value/units, and last column to join on - str concat
	return array($row[0], $row[1], $row[2], $row[3], $row[4], 0, 0, implode("|", array($row[0], $row[1], $row[2], $row[3], $row[4])));
}

// $conn = mysqli_connect("127.0.0.1:9906", "user", "password", "retail"); for testing in shell
$conn = mysqli_connect("database", "user", "password", "retail", 3306);

// Waitrose
$waitroseData = array_map("str_getcsv", file("files/waitrose.csv"));
$firstRow = array_shift($waitroseData);
$columns = "(" . implode(", ", $firstRow) . ")";

$waitroseData = array_map("transformForInsert", $waitroseData);
print_r($waitroseData);
$values = implode(", ", $waitroseData);
$sql = "INSERT INTO example_table $columns VALUES $values";
if (mysqli_query($conn, $sql)) {
	echo "Pushed waitrose \n";
} else {
	echo "Failed: " . mysqli_error($conn) . "\n";
}

// Tesco
$tescoData = array_map("str_getcsv", file("files/tesco.csv"));
$firstRow = array_shift($tescoData);
// No reading for columns, we keep it from the waitrose file - Tesco needs transforming first, just do the shift to remove col names

// "Group by" for first 5 cols, create 2 columns as 0 and last to join on, then iterate through raw data and attach
$dataGrouped = array_map("grouper", $tescoData);
$dataGrouped = array_unique($dataGrouped, SORT_REGULAR); // Groups, unique across first 5 cols
foreach($tescoData as $row) {
	$lookupStr = implode("|", array($row[0], $row[1], $row[2], $row[3], $row[4]));
	$groupedRowIndex = array_search($lookupStr, array_column($dataGrouped, 7));
	if($row[5] == 'sales value') { 
		$dataGrouped[$groupedRowIndex][6] = $row[6]; 
	}
	elseif ($row[5] == 'sales units') { 
		$dataGrouped[$groupedRowIndex][5] = $row[6]; 
	}
}

print_r($dataGrouped);
foreach($dataGrouped as &$row){ // Remove join column
	$row = array_slice($row, 0, 7);
}
print_r($dataGrouped);

$dataGrouped = array_map("transformForInsert", $dataGrouped);
print_r($dataGrouped);
$values = implode(", ", $dataGrouped);
$sql = "INSERT INTO example_table $columns VALUES $values";
if (mysqli_query($conn, $sql)) {
	echo "Pushed tesco \n";
} else {
	echo "Failed: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);

echo "Finished"; 

?>