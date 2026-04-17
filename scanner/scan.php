<?php
include "../config/db.php";

if(isset($_GET['code'])){

$code=$_GET['code'];

$stmt=$conn->prepare("SELECT * FROM tickets WHERE qr_code=?");
$stmt->bind_param("s",$code);
$stmt->execute();

$result=$stmt->get_result();

if($result->num_rows>0){

$row=$result->fetch_assoc();

if($row['scan_status']=="Unused"){

$conn->query("UPDATE tickets SET scan_status='Used' WHERE qr_code='$code'");

echo "Entry Allowed";

}else{

echo "Ticket Already Used";

}

}else{

echo "Invalid Ticket";

}

}
?>