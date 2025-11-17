<?php

require_once('../api-handler.php');
class CalendarApiHandler extends BaseApiHandler{

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    function addEvent($title, $userId, $eventInfo, $startTime, $endTime) {
        try{
            $fields = ['user_id' => $userId, 'title' => $title, 'end_time' => $endTime];

            if (!empty($eventInfo)) {
                $fields['event_info'] = $eventInfo;
            }
        
            if (!empty($startTime)) {
                $fields['start_time'] = $startTime;
            }


            $columns = implode(", ", array_keys($fields));
            $placeholders = ":" . implode(", :", array_keys($fields));

            $sql = "INSERT INTO event ($columns) VALUES ($placeholders)";

            $stmt = $this->conn->prepare($sql);

            // Bind named params dynamically
            foreach ($fields as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();

            return json_encode([
                "status" => "success",
                "message" => "event added successfully"
            ]);
        }
        catch(PDOException $e){
            return json_encode([
                "status" => "error", 
                "message" => "database error" . $e->getMessage()
            ]);
        }
    }
}

?>