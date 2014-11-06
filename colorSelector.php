<?php

require_once("printipp/CupsPrintIPP.php");
require_once("tcpdf/tcpdf.php");

	$dbhost = "localhost";
	$dbuser = "root";
	$dbpass = "masterj";
//	$dbpass = "jagode4";
	$dbname = "virtuait";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
	if(mysqli_connect_errno()) {
		die("Database connection failed: " . 
		     mysqli_connect_error() . 
		     " (" . mysqli_connect_errno() . ")"
		);
	}

	if(!isset($_GET['nanosId'])) {
		$nanosId = 1;
	} else {
		$nanosId = $_GET['nanosId'];
	}
	
	if(!isset($_GET['tipId'])) {
		$tipId = 1;
	} else {
		$tipId = $_GET['tipId'];
	}

	if(!isset($_GET['selectedColor'])) {
		$selectedColor = getSelectedColorId($connection, $nanosId, $tipId);
	} else {
		$selectedColor = $_GET['selectedColor'];
	}

	if(isset($_GET['runPy']) && $_GET['runPy'] == 1) {
		runPyScript();
	}

    if(isset($_GET['printViaIpp']) && $_GET['printViaIpp'] == 1) {
        printFileViaIpp();
    }

	function runPyScript() {
		exec('sudo python /home/pi/tmapi2/tmapi.py > /dev/null &');
	}

    function printFileViaIpp() {
        preparePDFforPrint();
        $ipp = new ExtendedPrintIPP();
        $ipp->setHost( 'localhost' );
        $ipp->setPrinterURI( '/printers/Brother_QL-570');
        $ipp->resumePrinter();
        $ipp->setData( 'files/testPdfBarcode.pdf' );
        $ipp->setAttribute("orientation-requested", "landscape");
        $ipp->printJob();
    }

    function preparePDFforPrint() {
        global $connection;
        global $nanosId;
        global $tipId;
        global $selectedColor;

        $pdfLayout = array(95, 90); // brother dimensions
        $pdf = new TCPDF("L", "mm", $pdfLayout, true, "UTF-8", false);

        $pdf->SetCreator("RPi");
        $pdf->SetAuthor("Pi User");
        $pdf->SetTitle("Barcode test pdf print");
        $pdf->SetSubject("Print test");
        $pdf->SetKeywords("barcode, test, print, rpi, raspberry, demo");

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // add a page
        $pdf->AddPage();

        $imgSize = "15";

        $html = '<img src="img/can.png" border="0" height="' . $imgSize . '" width="' . $imgSize . '" />';
        $pdf->writeHTMLCell(20, 0, 12, 20,  $html, 0, 1, 0, true, '', true);

        $html = '<img src="img/color.png" border="0" height="' . $imgSize . '" width="' . $imgSize . '" />';
        $pdf->writeHTMLCell(20, 0, 12, 28,  $html, 0, 1, 0, true, '', true);

        $html = '<img src="img/warning.png" border="0" height="' . $imgSize . '" width="' . $imgSize . '" />';
        $pdf->writeHTMLCell(20, 0, 12, 34,  $html, 0, 1, 0, true, '', true);

        $html = '<img src="img/cart.png" border="0" height="' . $imgSize . '" width="' . $imgSize . '" />';
        $pdf->writeHTMLCell(20, 0, 12, 46,  $html, 0, 1, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 15);
        $html = '<span><b>' . getProductById($connection, $nanosId) . '</b></span>';
        $pdf->writeHTMLCell(90, 0, 21, 20,  $html, 0, 0, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 11);
        $html = '<span><b>' . getCollectionById($connection, $tipId) . ': ' . getColorById($connection, $selectedColor) . '</b></span>';
        $pdf->writeHTMLCell(90, 0, 21, 28,  $html, 0, 0, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 9);
        $html = '<span><b>Opozorilo: obvezen 2x nanos barve!</b><br>Datum proizvodnje: 26.09.2014, 13:36</span>';
        $pdf->writeHTMLCell(90, 0, 21, 34,  $html, 0, 0, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 9);
        $html = '<span><b>JUMIX Trade d.o.o.</b></span>';
        $pdf->writeHTMLCell(90, 0, 21, 46,  $html, 0, 1, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 8);
        $html = '<span>Dol pti Ljubljani 28, 1262 Dol pri Ljubljani<br>T: 01 5884 330; M: 051 669 348;<br>E: jumix@jub.eu</span>';
        $pdf->writeHTMLCell(90, 0, 21, 50,  $html, 0, 1, 0, true, '', true);

        $pdf->SetFont('helvetica', '', 6);
        $html = '<span><i>HVALA ZA NAKUP! ŽELIMO VAM BARVITO UGODJE BIVANJA.</i></span>';
        $pdf->writeHTMLCell(90, 0, 21, 64,  $html, 0, 1, 0, true, '', true);

        $pdf->writeHTMLCell(80, 50, 10, 18,  '', 1, 1, 0, true, '', true);

//        $pdf->Output("testPdfBarcode.pdf", 'I');
        $pdf->Output("files/testPdfBarcode.pdf", 'F');
    }

	function getSelectedColorId($conn, $idNanos, $idTip) {
		$query  = "SELECT * ";
		$query .= "FROM barve ";
		$query .= "WHERE nanosid=" . $idNanos . " ";
		$query .= "AND tipid=" . $idTip . " ";
		$query .= "ORDER BY id ASC ";
		$query .= "LIMIT 1";

		$result = mysqli_query($conn, $query);
		if (!$result) {
			die("Database query failed.");
		}
		$selCid =  mysqli_fetch_assoc($result)["id"];
		mysqli_free_result($result);
		return $selCid;
	}

	function getPlasterTypeDiv($conn) {
		$plasterDiv = "";
		$query  = "SELECT * ";
		$query .= "FROM nacin_nanosa ";
		$query .= "ORDER BY id ASC";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			die("Database query failed.");
		}

		while($plasterTypes = mysqli_fetch_assoc($result)) {
			global $tipId, $nanosId;
			$readyDivTag = "<div class=\"item";
			$plasterDiv .= "<a href=\""; 
			if($nanosId == $plasterTypes["id"]) {
				$readyDivTag .= " selectedItem\">";
				$plasterDiv .= "#\">";
			} else {
				$readyDivTag .= "\">";
				$plasterDiv .= "colorSelector.php?nanosId=" . $plasterTypes["id"] . "&tipId=" . $tipId . "\">";
			}

			$plasterDiv .=  $readyDivTag;
			$plasterDiv .=  $plasterTypes["name"] . "</div></a>";
		}
		mysqli_free_result($result);
		return $plasterDiv;
	}

	function getColorTypeDiv($conn) {
		$colorTypeDiv = "";
		$query  = "SELECT * ";
		$query .= "FROM tip_barve ";
		$query .= "ORDER BY id ASC";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			die("Database query failed.");
		}

		while($colorTypes = mysqli_fetch_assoc($result)) {
			global $tipId, $nanosId;
			$readyDivTag = "<div class=\"item";
			$colorTypeDiv .= "<a href=\""; 
			if($tipId == $colorTypes["id"]) {
				$readyDivTag .= " selectedItem\">";
				$colorTypeDiv .= "#\">";
			} else {
				$readyDivTag .= "\">";
				$colorTypeDiv .= "colorSelector.php?nanosId=" . $nanosId  . "&tipId=" . $colorTypes["id"] . "\">";
			}
			$colorTypeDiv .=  $readyDivTag;
			$colorTypeDiv .=  $colorTypes["name"] . "</div></a>";
		}
		mysqli_free_result($result);
		return $colorTypeDiv;
	}

	function getMainInfo($conn, $sColorId) {

		$query  = "SELECT * ";
		$query .= "FROM barve ";
		$query .= "WHERE id=" . $sColorId . " ";
		$query .= "LIMIT 1";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			die("Database query failed.");
		}

		$colorMainInfoData = mysqli_fetch_assoc($result);
		$colorMainInfoDiv = "<div class=\"mainInfo\" style=\"background:#" . $colorMainInfoData["colorhex"] . "\">";
		$colorMainInfoDiv .= "<div class=\"priceMain\">";
			$colorMainInfoDiv .= "<div class=\"priceBig\">$" . $colorMainInfoData["pricevar"] . "</div>";
			$colorMainInfoDiv .= "<div class=\"priceColorName\">Name of selected color is: " . $colorMainInfoData["name"] . "</div>";
		$colorMainInfoDiv .= "</div>";
		$colorMainInfoDiv .= "<div class=\"priceDetail\">";
			$colorMainInfoDiv .= "<div class=\"priceInfo\">Excluding VAT<br/>VAT<br />Including VAT<br />Price Group<br />Price List<br /></div>";
			$colorMainInfoDiv .= "<div class=\"priceData\">" . $colorMainInfoData["pricevar"] . "<br />";
				$colorMainInfoDiv .= $colorMainInfoData["vat"] ."<br />";
				$colorMainInfoDiv .= $colorMainInfoData["price"] ."<br />";
				$colorMainInfoDiv .= $colorMainInfoData["pricegroup"] ."<br />";
			$colorMainInfoDiv .= $colorMainInfoData["pricelist"] ."</div>";
		$colorMainInfoDiv .= "</div>";
		mysqli_free_result($result);
		$colorMainInfoDiv .= "</div>";
		return $colorMainInfoDiv;
	}

	function getAllColors($conn, $idNanos, $idTip) {
		$colorsListDiv = "<div class=\"allColors\">";

		$query  = "SELECT * ";
		$query .= "FROM barve ";
		$query .= "WHERE nanosid=" . $idNanos . " ";
		$query .= "AND tipid=" . $idTip . " ";
		$query .= "ORDER BY id ASC";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			die("Database query failed.");
		}

		$countColors = 0;
		while($allColors = mysqli_fetch_assoc($result)) {
			global $nanosId, $tipId, $selectedColor;
			$readyDivTag = "<div style=\"background:#" . $allColors["colorhex"] . "\" class=\"colorIcon";
			$colorsListDiv .= "<a href=\"";
			if($selectedColor ==  $allColors["id"]) {
				$readyDivTag .= " selectedColorIcon\">";
				$colorsListDiv .= "#\">";
			} else {
				$readyDivTag .= "\">";
				$colorsListDiv .= "colorSelector.php?nanosId=" . $nanosId  . "&tipId=" . $tipId . "&selectedColor=" . $allColors["id"] . "\">";
			}
			$colorsListDiv .= $readyDivTag;
			$colorsListDiv .= "<span>" . $allColors["name"] . "</span></div></a>";
			$countColors++;
		}
		mysqli_free_result($result);
		for($i = $countColors; $i < 14; $i++) {
			$colorsListDiv .= "<div class=\"colorIcon unusedIcon\" style=\"background:#1d1d1d\"></div>";
		 }
		$colorsListDiv .= "</div>";
		return $colorsListDiv;
	}

	function getComponentsDiv($conn, $idColor) {

		$compDiv = "<div class=\"tableRowHeader\">";
		$compDiv .= "<div class=\"clm1\">Component</div>";
		$compDiv .= "<div class=\"clm2\">Amount</div>";
		$compDiv .= "<div class=\"clm3\">Component Price</div>";
		$compDiv .= "</div>";

		$query  = "SELECT * ";
		$query .= "FROM barve ";
		$query .= "WHERE id=". $idColor . " ";
		$query .= "LIMIT 1";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			die("Database query failed.");
		}

		$colorData =  mysqli_fetch_assoc($result);
		$compArray[0] = $colorData["compid1"];
		$compArray[1] = $colorData["compid2"];
		$compArray[2] = $colorData["compid3"];
		$compArray[3] = $colorData["compid4"];
		$compArray[4] = $colorData["compid5"];
		mysqli_free_result($result);

		$query  = "SELECT * ";
		$query .= "FROM komponente ";
		for($count = 0; $count < count($compArray); $count++)  {
			if($compArray[$count] != 0 && $count == 0) {
				$query .= "WHERE id=". $compArray[$count] . " ";
			} elseif ($compArray[$count] != 0) {
				$query .= "OR id=". $compArray[$count] . " ";
			}
		}
		$query .= "ORDER BY id ASC";
		$result = mysqli_query($conn, $query);
		if (!$result) {
			die("Database query failed.");
		}

		while($comps = mysqli_fetch_assoc($result)) {
			$compDiv .= "<div class=\"tableRow\">";
			$compDiv .= "<div class=\"clm1\">" . $comps["name"] . "</div>";
			$compDiv .= "<div class=\"clm2\">" . $comps["amount"] . "</div>";
			$compDiv .= "<div class=\"clm3\">" . $comps["compprice"] . "</div>";
			$compDiv .= "</div>";
		}
		mysqli_free_result($result);
		return $compDiv;
	}

    function getProductById($conn, $id) {
        $query  = "SELECT name ";
        $query .= "FROM nacin_nanosa ";
        $query .= "WHERE id=" . $id . " ";
        $query .= "LIMIT 1 ";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("Database query failed.");
        }
        $data =  mysqli_fetch_assoc($result);
        return $data["name"];
    }

    function getCollectionById($conn, $id) {
        $query  = "SELECT name ";
        $query .= "FROM tip_barve ";
        $query .= "WHERE id=" . $id . " ";
        $query .= "LIMIT 1 ";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("Database query failed.");
        }
        $data =  mysqli_fetch_assoc($result);
        return $data["name"];
    }

    function getColorById($conn, $id) {
        $query  = "SELECT name ";
        $query .= "FROM barve ";
        $query .= "WHERE id=" . $id . " ";
        $query .= "LIMIT 1 ";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            die("Database query failed.");
        }
        $data =  mysqli_fetch_assoc($result);
        return $data["name"];
    }

?>


<!doctype html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Start page - Color selector</title>
		<link rel="stylesheet" href="css/cssreset.css" type="text/css" charset="utf-8" />
    	<link rel="stylesheet" href="css/main.css" type="text/css" charset="utf-8" />
    	<script type="text/javascript" src="js/jquery-2.0.3.js" ></script>
    	<script type="text/javascript" src="js/mainScript.js"></script>
	</head>
	<body>
		<div class="main">

		<div class="mainInfoArea">

			<div class="menuTop">
				<div class="iconTop"><img class="icon" src="img/edit.svg" width="25px" height="25px"></div>
				<?php echo "<a href=\"colorSelector.php?nanosId=" . $nanosId  . "&tipId=" . $tipId . "&selectedColor=" . $selectedColor . "&runPy=1\">" ?>
				<div class="iconTopPy"><img class="icon" src="img/mixer.svg" width="25px" height="25px"></div></a>
				<div class="logo"><img src="img/jubLogo.svg" width="105px" height="70px"></div>
			</div>

			<div class="colorDetail">
				
				<?php  echo getMainInfo($connection, $selectedColor) ?>

				<?php  echo getAllColors($connection, $nanosId, $tipId) ?>

			</div>

			<br /><br />
			<div class="colorComps">
				<?php echo getComponentsDiv($connection, $selectedColor) ?>
			</div>

			<div class="menuBottom">
				<div class="btmIcons">
				<div class="iconTop"><img class="icon" src="img/trash.svg" width="25px" height="25px"></div>
                    <?php echo "<a href=\"colorSelector.php?nanosId=" . $nanosId  . "&tipId=" . $tipId . "&selectedColor=" . $selectedColor . "&printViaIpp=1\">" ?>
				<div class="iconTopPrint"><img class="icon" src="img/print.svg" width="25px" height="25px"></div></a>
				</div>
			</div>

		</div>

		<div class="menuRight">
			<div class="sideDropDown">
				<p class="sideTitle">Product</p>
				<div class="itemsBackground">
					<?php echo getPlasterTypeDiv($connection) ?>
				</div>
			</div>

			<div class="sideDropDown">
				<p class="sideTitle">Collection</p>
				<div class="itemsBackground">
					<?php echo getColorTypeDiv($connection) ?>
				</div>
			</div>

			<div class="sideDropDown">
				<p class="sideTitle">Can Size</p>
				<div class="itemsBackground">
					<a href="#"><div class="itemNoLink">2.00 KG</div></a>
					<a href="#"><div class="itemNoLink">5.00 KG</div></a>
					<a href="#"><div class="itemNoLink">10.00 KG</div></a>
					<a href="#"><div class="itemNoLink">12.00 KG</div></a>
					<a href="#"><div class="itemNoLink">15.00 KG</div></a>
				</div>
			</div>

			<div class="sideDropDown">
				<p class="sideTitle">Enter Custom Can Size</p><br />
				<form action="" method="post">
			  		<input type="text" name="customCanSize" value="" /> KG
			  		<input type="submit" value="OK" id="okBtn" />
				</form>
			</div>
		</div>

	</div>
	</body>
</html>

<?php mysqli_close($connection); ?>
