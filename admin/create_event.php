<?php
include "../config/db.php";

if(isset($_POST['create'])){

$name=$_POST['name'];
$date=$_POST['date'];
$price=$_POST['price'];
$capacity=$_POST['capacity'];

$stmt=$conn->prepare("INSERT INTO events(event_name,event_date,ticket_price,capacity) VALUES(?,?,?,?)");
$stmt->bind_param("ssdi",$name,$date,$price,$capacity);
$stmt->execute();

echo "Event Created";

}
?>

<form method="POST">

Event Name<br>
<input name="name"><br>

Date<br>
<input type="date" name="date"><br>

Price<br>
<input name="price"><br>

Capacity<br>
<input name="capacity"><br><br>

<button name="create">Create Event</button>

</form>