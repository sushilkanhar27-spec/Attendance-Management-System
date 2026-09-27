<?php

include "db_connect.php";

$data=[];

$result=mysqli_query($conn,"
SELECT holiday_date
FROM holidays
");

while($row=mysqli_fetch_assoc($result))
{

$data[]=$row['holiday_date'];

}

header("Content-Type: application/json");

echo json_encode($data);

?>