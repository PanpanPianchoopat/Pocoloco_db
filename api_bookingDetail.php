<?php 
require_once 'connect.php';
$request_data=json_decode(file_get_contents("php://input"));

if($request_data -> action == "getRoomType"){
  
  // query
  $sql = "SELECT roomTypeID,roomType FROM roomdescription";
  $query = $connect->query($sql);
  
  while($row = $query -> fetch(PDO::FETCH_ASSOC)){
      $data[] = $row;
  }

  echo json_encode($data);
}

if($request_data -> action == "getRoomNumber"){
  
  $roomType = $request_data -> roomType;
  $checkIn = $request_data -> checkIn;
  $checkOut = $request_data -> checkOut;

  $dataCheck = $checkIn;
  
  while($dataCheck <= $checkOut){
    // query
    $sql = "SELECT roomID 
    FROM bookingdetail 
    WHERE roomID IN
      (SELECT roomID 
      FROM hotelroom
      WHERE roomTypeID = '$roomType' AND 
            (status = 'R' OR status = 'I') AND
            ('$dataCheck' BETWEEN checkIn AND checkOut))";
            
    $query = $connect->query($sql);

    while($row = $query -> fetch(PDO::FETCH_ASSOC)){
    $data[] = $row["roomID"];
    }

    $dataCheck = date('Y-m-d',strtotime($dataCheck. '1 days'));
  }
   
  if($query->rowCount() == 0){
    $out['message']= "NO"; 
    $sql_room = "SELECT roomID
            FROM hotelroom 
            WHERE roomTypeID = '$roomType'";

    $query = $connect->query($sql_room);
    
    while($row = $query -> fetch(PDO::FETCH_ASSOC)){ 
        $data_room[] = $row;
    }
            
  }
  else{
    // roomID that reserve
    $result = array_keys(array_flip($data));

    // Select roomID that available from hotelroom
    $sql_room = "SELECT roomID 
                FROM hotelroom
                WHERE roomTypeID = '$roomType' AND
                      roomID NOT IN ('" . implode( "', '" , $result ) . "')";
      $query = $connect->query($sql_room);
      
      while($row = $query -> fetch(PDO::FETCH_ASSOC)){ 
          $data_room[] = $row;
      }
    
  }
  
  echo json_encode($data_room);
}

if($request_data -> action == "addBookingDetail"){
    
    function check_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
      }
      
    // From User
    $bookingID = $request_data -> bookingID;
    $guestFirstName = check_input($request_data -> guestFirstName);
    $guestLastName = check_input($request_data -> guestLastName);
    $roomID = $request_data -> roomNumber;               
    $checkIn = $request_data -> checkIn;
    $checkOut = $request_data -> checkOut;

   
    // Set Name
    $guestFirstName = ucfirst($guestFirstName);
    $guestLastName = ucfirst($guestLastName);
    $bookingID = intval($bookingID);

    $i = 0;
    while($i < count($roomID)){
      // Add Booking Detail in DB
      $sql = "INSERT INTO bookingdetail(`bookingID`, `roomID`, `checkIn`, `checkOut`, `guestFirstName`, `guestLastName`, `status`, `dateTime`) 
              VALUES ('$bookingID','$roomID[$i]','$checkIn','$checkOut','$guestFirstName','$guestLastName','R',CURRENT_TIMESTAMP)";
      $query = $connect->query($sql);

      if($query){
        $out['success'] = true;
      }
      else{
        $out['error'] = true;
        $out['message'] = "Could not add";
      }
      $i += 1;
    }
    
    echo json_encode($out);
}



?>