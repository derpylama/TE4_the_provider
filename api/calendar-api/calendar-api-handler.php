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

    function getUserEvents($userId, $editRights) {
        try{
            // gets the events that the user owns
            $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id");
            $stmt->execute([":user_id" => $userId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // gets the events that the user is invited to
            $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id");
            $stmt->execute([":user_id" => $userId]);
            $eventsNoRights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($events) && empty($eventsNoRights)){
                return json_encode(["status" => "success", "message" => "no events found"]);
            }
            else{
                return json_encode(["status" => "success", $events, $eventsNoRights]);
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
                $events = $stmt->fetchAll();

                $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year]);
                $eventsNoRights = $stmt->fetchAll();
            }else if($span == "month"){
                // gets all events for a user where the month in the event is the selected month
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :month BETWEEN MONTH(start_time) AND MONTH(end_time)");
                $stmt->execute([":user_id" => $userId, ":month" => $month, ":year" => $year]);
                $events = $stmt->fetchAll();

                $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :month BETWEEN MONTH(start_time) AND MONTH(end_time)");
                $stmt->execute([":user_id" => $userId, ":month" => $month, ":year" => $year]);
                $eventsNoRights = $stmt->fetchAll();
            }else if($span == "week"){
                // gets all events for a user where the week in the event is the selected week
                $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year, ":week" => $week]);
                $events = $stmt->fetchAll();

                $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time)");
                $stmt->execute([":user_id" => $userId, ":year" => $year, ":week" => $week]);
                $eventsNoRights = $stmt->fetchAll();
            }else if ($span == "day") {
                $week = (int)$week;
                $day  = (int)$day;

                $isoWeekString = sprintf('%04d-W%02d-%d', (int)$year, $week, $day);

                $targetDate = date('Y-m-d', strtotime($isoWeekString));
                if (!$targetDate) {
                    $events = [];
                    $eventsNoRights = [];
                } else {

                    $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND DATE(:target) BETWEEN DATE(start_time) AND DATE(end_time)");
                    $stmt->execute([":user_id" => $userId, ":target"  => $targetDate]);
                    $events = $stmt->fetchAll();

                    // Invited events
                    $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND DATE(:target) BETWEEN DATE(e.start_time) AND DATE(e.end_time)");
                    $stmt->execute([":user_id" => $userId, ":target"  => $targetDate]);
                    $eventsNoRights = $stmt->fetchAll();
                }
            }
            // reutrn if events where found or not
            if(empty($events) && empty($eventsNoRights)){
                return json_encode(["status" => "success", "message" => "no events found"]);
            }
            else{
                return json_encode(["status" => "success", $events, $eventsNoRights]);
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

    function handleInvites($userId, $accepted, $eventId) {
        try{
            if($accepted == 0){
                $stmt = $this->conn->prepare("DELETE FROM event_invite WHERE invited_user_id = :userId AND event_id = :eventId");
                $stmt->execute(['userId' => $userId, 'eventId' => $eventId]);
                
                return json_encode([
                    "status" => "success", 
                    "message" => "event invite declined successfully"
                ]);
            }
            else if($accepted == 1){
                //checks if the user already accepted the invite
                $stmt = $this->conn->prepare("SELECT accepted FROM event_invite WHERE invited_user_id = :id AND event_id = :eventId");
                $stmt->execute(['id' => $userId, 'eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['accepted'] == 1){
                        return json_encode([
                            "status" => "error", 
                            "message" => "user already accepted this invitation"
                        ]);
                    }
                }

                // initiate the sql query
                $addEventQuery = "UPDATE event_invite SET accepted = :accepted WHERE invited_user_id = :userId AND event_id = :eventId";

                $stmt = $this->conn->prepare($addEventQuery);


                $stmt->execute(['accepted' => $accepted, 'userId' => $userId, 'eventId' => $eventId]);

                // return if statsus is success
                return json_encode([
                    "status" => "success",
                    "message" => "event invite accepted successfully"
                ]);
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
}

?>