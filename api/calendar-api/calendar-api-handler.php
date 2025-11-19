<?php

require_once('../api-handler.php');
class CalendarApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    function addEvent($title, $userId, $eventInfo, $startTime, $endTime) {
        try{
            // implement the requeired fields
            $fields = ['user_id' => $userId, 'title' => $title, 'end_time' => $endTime];

            // check the oprional fields
            if (!empty($eventInfo)) {
                $fields['event_info'] = $eventInfo;
            }
        
            if (!empty($startTime)) {
                $fields['start_time'] = $startTime;
            }

            // add commas between different values
            $columns = implode(", ", array_keys($fields));
            $placeholders = ":" . implode(", :", array_keys($fields));

            // initiate the sql query
            $addEventQuery = "INSERT INTO event ($columns) VALUES ($placeholders)";

            $stmt = $this->conn->prepare($addEventQuery);

            // bind the parameters
            foreach ($fields as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();

            // return if statsus is success
            return json_encode([
                "status" => "success",
                "message" => "event added successfully"
            ]);
        }
        catch(PDOException $e){
            // return error with the database
            return json_encode([
                "status" => "error", 
                "message" => "database error" . $e->getMessage()
            ]);
        }
    }

    function getUserEvents($userId) {
        try{
            $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id");
            $stmt->execute([":user_id" => $userId]);
    
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($events)){
                return json_encode(["status" => "success", "message" => "no events found"]);
            }
            else{
                return json_encode($events);
            }
        }
        catch(PDOException $e){
            // return error with the database
            return json_encode([
                "status" => "error", 
                "message" => "database error" . $e->getMessage()
            ]);
        }
    }

    function getUserEventsBy($userId, $span, $year, $month, $week, $day) {
        try{
            // if an event spans over different years, months or weeks it will show up if the input is either year, month or week that the event spans over
            if($span == "year"){
                // gets all events for a user where the year in the event is the selected year
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year]);
            }else if($span == "month"){
                // gets all events for a user where the month in the event is the selected month
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :month BETWEEN MONTH(start_time) AND MONTH(end_time)");
                $stmt->execute([":user_id" => $userId, ":month" => $month, ":year" => $year]);
            }else if($span == "week"){
                // gets all events for a user where the week in the event is the selected week
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year, ":week" => $week]);
            }else if($span == "day"){
                // gets all events for a user where the day in the event is the selected day
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time) AND :day BETWEEN WEEKDAY(start_time) AND WEEKDAY(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year, ":day" => $day, ":week" => $week]);
            }
        
            $events = $stmt->fetchAll();

            // reutrn if events where found or not
            if(empty($events)){
                return json_encode(["status" => "success", "message" => "no events found"]);
            }
            else{
                return json_encode($events);
            }
        }
        catch(PDOException $e){
            // return error with the database
            return json_encode([
                "status" => "error", 
                "message" => "database error" . $e->getMessage()
            ]);
        }
    }

    function inviteUserToEvent($invitedUserId, $eventId) {
        try{
            // checks if the invited user has eccess to the calendar
            $stmt = $this->conn->prepare("SELECT type FROM user WHERE id = :id");
            $stmt->execute(['id' => $invitedUserId]);
            $row = $stmt->fetch();
            if($row){
                if($row['type'] == 'user'){
                    return json_encode([
                        "status" => "error", 
                        "message" => "invited user does not have access to the calendar"
                    ]);
                }
            }

            //checks if the invited user already has an invite for a specific event
            $stmt = $this->conn->prepare("SELECT event_id, invited_user_id FROM event_invite WHERE invited_user_id = :id");
            $stmt->execute(['id' => $invitedUserId]);
            $row = $stmt->fetch();
            if($row){
                if($row['event_id'] == $eventId && $row['invited_user_id'] == $invitedUserId){
                    return json_encode([
                        "status" => "error", 
                        "message" => "user is already invited to this event"
                    ]);
                }
            }

            // implement the requeired fields
            $fields = ['event_id' => $eventId, 'invited_user_id' => $invitedUserId];


            // add commas between different values
            $columns = implode(", ", array_keys($fields));
            $placeholders = ":" . implode(", :", array_keys($fields));

            // initiate the sql query
            $addEventQuery = "INSERT INTO event_invite ($columns) VALUES ($placeholders)";

            $stmt = $this->conn->prepare($addEventQuery);

            // bind the parameters
            foreach ($fields as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();

            // return if statsus is success
            return json_encode([
                "status" => "success",
                "message" => "event invite sent successfully"
            ]);
        }
        catch(PDOException $e){
            // return error with the database
            return json_encode([
                "status" => "error", 
                "message" => "database error" . $e->getMessage()
            ]);
        }
    }
}

?>