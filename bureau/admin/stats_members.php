<?php
/*
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
*/

/**
 * Show the statistics of all members.
 *
 * @copyright AlternC-Team 2000-2017 https://alternc.com/
 */

@require_once("../class/config.php");
if ((@include_once 'Image/Graph.php') === FALSE) {
  echo "<p class=\"alert alert-warning\">". __("Image_Graph not installed. use 'aptitude install php-pear' then 'pear install --alldeps Image_Graph-devel' to see the graph.", "alternc", true)."</p>";
  exit(0);
}

$db->query("SELECT COUNT(login) AS count,date_format(created, '%Y-%m') as month FROM `membres` where created is NOT null GROUP BY month ORDER BY month ASC");

$Graph =& Image_Graph::factory('graph', array(800, 600)); 
$Graph->add(
    Image_Graph::vertical(
        Image_Graph::factory('title', array(__('Account creation per month', "alternc", true), 12)),        
        Image_Graph::vertical(
            $Plotarea = Image_Graph::factory('plotarea'),
            $Legend = Image_Graph::factory('legend'),
            90
        ),
        5
    )
);   

$Legend->setPlotarea($Plotarea);        

$total =& Image_Graph::factory('Image_Graph_Dataset_Trivial');
$total->setName(__('before the month', "alternc", true));
$units =& Image_Graph::factory('Image_Graph_Dataset_Trivial');
$units->setName(__('during the month', "alternc", true));

$i = 0;
while ($db->next_record()) {
  $units->addPoint($db->f('month'), $db->f('count'));
  $total->addPoint($db->f('month'), $i);
  $i += $db->f('count');
}
$Datasets[]= $total;
$Datasets[]= $units;
$Plot =& $Plotarea->addNew('bar', array($Datasets, 'stacked'));

$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
$AxisX->setLabelOption('showoffset', 1);
$AxisX->setLabelInterval(2);

// set a line color
$Plot->setLineColor('gray');

// create a fill array   
$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
$FillArray->addColor('blue@0.2');
$FillArray->addColor('yellow@0.2');
$FillArray->addColor('green@0.2');

// set a standard fill style
$Plot->setFillStyle($FillArray);

// create a Y data value marker
$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_Y);
// and use the marker on the 1st plot
$Plot->setMarker($Marker);	

$Plot->setDataSelector(Image_Graph::factory('Image_Graph_DataSelector_NoZeros'));

$Graph->Done();

?>
