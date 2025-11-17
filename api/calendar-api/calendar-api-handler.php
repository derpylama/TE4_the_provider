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
        $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id");
        $stmt->execute([":user_id" => $userId]);
    
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode($events);
    }
}

?>