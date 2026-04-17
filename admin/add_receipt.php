<?php
include "../config/db.php";

if(isset($_POST['add'])){

$receipt=$_POST['receipt'];
$event=$_POST['event'];

$stmt=$conn->prepare("INSERT INTO receipts(receipt_no,event_id) VALUES(?,?)");
$stmt->bind_param("si",$receipt,$event);
$stmt->execute();

echo "Receipt Added";

}
?>

<form method="POST">

Receipt Number<br>
<input name="receipt"><br>

Event ID<br>
<input name="event"><br><br>

<button name="add">Add Receipt</button>

</form>