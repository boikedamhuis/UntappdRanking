

<head>
<link rel="stylesheet" type="text/css" href="style.css">
<script src="sortTable.js"></script>
<?php include 'getData.php';?>
</head>

<body onload="sortTable()">
		

	<center>
	<table id="myTable">
		<?php 	include 'filename.php'; ?>
		<tr>
			<!--When a header is clicked, run the sortTable function, with a parameter, 0 for sorting by names, 1 for sorting by country:-->  
			<th></th>
			<th>Points</th>
			<th>Latest beer</th>
			<th></th>
  		</tr>
  		<? 
	    
	     for ($i = 1; $i <= count($untappd); $i++) {
	     echo("<tr><td>" .$usernames[$i] ."</td>");
	     echo("<td>" .(($beers[$i]+$badges[$i])/2) ."</td>");
		 echo("<td>" .$lastbeer[$i] ."</td>");
	     echo("<td><img src=\"" .$picture[$i] ."\" style=\"width:100px;height:100px;\"></td>");
		 }
     
  ?>
   </tr>
   </table>
   </center>
</body>