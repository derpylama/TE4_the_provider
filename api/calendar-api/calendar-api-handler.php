<?php

require_once('../api-handler.php');
class CalendarApiHandler extends BaseApiHandler{

    protected function checkServiceAndToken($token, $service="calendar"){
        return parent::checkServiceAndToken($token, $service);
    }

    public function getUsers() {//example method
        $stmt = $this->conn->query("SELECT * FROM users");
        return $stmt->fetchAll();
    }

    function addEvent($title, $token, $eventInfo, $startTime, $endTime) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];

        try{
            $error = $this->checkForError($userId, null, null, "addEvent");
            if ($error) {
                return $error;
            }
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

    function getUserEvents($token) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, null, null, "getUserEvents");
            if ($error) {
                return $error;
            }
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
                return json_encode(["status" => "success", "events" => $events, "eventsNoRights" => $eventsNoRights]);
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

    function getUserEventsBy($token, $span, $year, $month, $week, $day) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, null, null, false, "getEventsBy");
            if ($error) {
                return $error;
            }
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

    function inviteUserToEvent($token, $invitedUserId, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, $invitedUserId, "inviteUserToEvent");
            if ($error) {
                return $error;
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

    function handleInvites($token, $accepted, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "handleInvites");
            if ($error) {
                return $error;
            }

            if($accepted == 0){
                $stmt = $this->conn->prepare("DELETE FROM event_invite WHERE invited_user_id = :userId AND event_id = :eventId");
                $stmt->execute(['userId' => $userId, 'eventId' => $eventId]);
                
                return json_encode([
                    "status" => "success", 
                    "message" => "event invite declined successfully"
                ]);
            }
            else if($accepted == 1){
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

    function deleteEvent($token, $eventId, $editEvent) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try {
            $error = $this->checkForError($userId, $eventId, $editEvent, "deleteEvent");
            if ($error) {
                return $error;
            }

            // deletes the event if the user can edit this event
            $stmt = $this->conn->prepare("DELETE FROM event WHERE id = :eventId");
            $stmt->execute(['eventId' => $eventId]);

            return json_encode([
                "status" => "success",
                "message" => "event deleted successfully"
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

    function editEvent($token, $eventId, $title, $content, $startTime, $endTime, $editEvent) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try {
            $error = $this->checkForError($userId, $eventId, $editEvent, "editEvent");
            if ($error) {
                return $error;
            }

            // $updateSqlQuery .= " WHERE id = :eventId";
            $updateSqlQuery = "UPDATE event SET ";
            $params = ['eventId' => $eventId];
            
            $setParts = [];
            
            if (!empty($title)) {
                $setParts[] = "title = :title";
                $params['title'] = $title;
            }
            if (!empty($content)) {
                $setParts[] = "event_info = :event_info";
                $params['event_info'] = $content;
            }
            if (!empty($startTime)) {
                $setParts[] = "startTime = :start_time";
                $params['start_time'] = $startTime;
            }
            if (!empty($endTime)) {
                $setParts[] = "endTime = :end_time";
                $params['end_time'] = $endTime;
            }

            if (empty($setParts)) {
                return json_encode([
                    "status" => "error",
                    "message" => "no fields to update"
                ]);
            }
            
            $updateSqlQuery .= implode(", ", $setParts) . " WHERE id = :eventId";
            $stmt = $this->conn->prepare($updateSqlQuery);
            $stmt->execute($params);

            return json_encode([
                "status" => "success",
                "message" => "event edited successfully"
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

    function deleteInvitation($token, $invitedUserId, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try {
            $error = $this->checkForError($userId, $eventId, $invitedUserId, "deleteInvitation");
            if ($error) {
                return $error;
            }


            // checks if the user is invited to this event
            $stmt = $this->conn->prepare("SELECT invited_user_id FROM event_invite WHERE invited_user_id = :invitedUserId");
            $stmt->execute(['invitedUserId' => $invitedUserId]);
            $row = $stmt->fetch();
            if($row){
                if($row['invited_user_id'] != $invitedUserId){
                    return json_encode([
                        "status" => "error",
                        "message" => "user is not invited to this event"
                    ]);
                }
            }
            else if(empty($row)){
                return json_encode([
                    "status" => "error",
                    "message" => "user is not invited to this event or user does not exist"
                ]);
            }



            // deletes the event if the user can edit this event
            $stmt = $this->conn->prepare("DELETE FROM event_invite WHERE invited_user_id = :invitedUserId AND event_id = :eventId");
            $stmt->execute(['invitedUserId' => $invitedUserId, 'eventId' => $eventId]);

            return json_encode([
                "status" => "success",
                "message" => "invitation deleted successfully"
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

    function getSpecificEvent($token, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "getSpecificEvent");
            if ($error) {
                return $error;
            }

            // gets the events that the user owns
            $stmt = $this->conn->prepare("SELECT * FROM event WHERE user_id = :user_id AND id = :eventId");
            $stmt->execute([":user_id" => $userId, "eventId" => $eventId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // gets the events that the user is invited to
            $stmt = $this->conn->prepare("SELECT e.* FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND ei.event_id = :eventId");
            $stmt->execute([":user_id" => $userId, "eventId" => $eventId]);
            $eventsNoRights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($events) && empty($eventsNoRights)){
                return json_encode(["status" => "success", "message" => "no event found"]);
            }
            else{
                return json_encode(["status" => "success", "events" => $events, "eventsNoRights" => $eventsNoRights]);
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

    function getInvitations($token, $eventId, $sortInvitesBy) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            return json_encode($tokeninfo);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            return json_encode([
                "status" => "error",
                "message" => "Insufficient permissions"
            ]);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "getInvitations");
            if ($error) {
                return $error;
            }
            
            if($sortInvitesBy == "all"){
                $stmt = $this->conn->prepare("SELECT * FROM event_invite WHERE event_id = :eventId");
            }
            else if($sortInvitesBy == "accepted"){
                $stmt = $this->conn->prepare("SELECT * FROM event_invite WHERE event_id = :eventId AND accepted = 1");
            }
            else if($sortInvitesBy == "pending"){
                $stmt = $this->conn->prepare("SELECT * FROM event_invite WHERE event_id = :eventId AND accepted = 0");
            }
            $stmt->execute(['eventId' => $eventId]);
            $invites = $stmt->fetchAll();

            if(empty($invites)){
                return json_encode([
                    "status" => "success", 
                    "message" => "no invites found"
                ]);
            }

            return json_encode([
                "status" => "success",
                "message" => "event invitations retrieved",
                "invites" => $invites
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

    function checkForError($userId = null, $eventId = null, $invitedUserId = null, $eventAction) {
        try{
            // check if the event exists
            if(!empty($eventId)){
                //checks if the event exists
                $stmt = $this->conn->prepare("SELECT id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if(empty($row)){
                    return json_encode([
                        "status" => "error",
                        "message" => "event does not exist"
                    ]);
                }
            }
            // get all events a user has access to
            if($eventAction == "getUserEvents"){

            }
            // get events within a specific year, month, week or day
            if($eventAction == "getEventsBy"){
                
            }
            // Invite user
            if($eventAction == "inviteUserToEvent"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        return json_encode([
                            "status" => "error",
                            "message" => "user can not edit this event"
                        ]);
                    }
                }
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

                //checks if the invited user already has an invite for the event
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
            }
            // accept/decline invite
            if($eventAction == "handleinvites"){
                // checks if the user accepted the invite
                $stmt = $this->conn->prepare("SELECT accepted FROM event_invite WHERE invited_user_id = :inviteduserId");
                $stmt->execute(['inviteduserId' => $userId]);
                if($stmt->fetchColumn()) {
                    return json_encode([
                        "status" => "error",
                        "message" => "user already accepted the invite"
                    ]);
                }
            }
            // delete event
            if($eventAction == "deleteEvent"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        return json_encode([
                            "status" => "error",
                            "message" => "user can not edit this event"
                        ]);
                    }
                }
            }
            // edit event
            if($eventAction == "editEvent"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        return json_encode([
                            "status" => "error",
                            "message" => "user can not edit this event"
                        ]);
                    }
                }
            }
            // delete invitation
            if($eventAction == "deleteInvitation"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        return json_encode([
                            "status" => "error",
                            "message" => "user can not edit this event"
                        ]);
                    }
                }
                // checks if the invited user id is invited to the event
                $stmt = $this->conn->prepare("SELECT invited_user_id FROM event_invite WHERE event_id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['invited_user_id'] != $invitedUserId){
                        return json_encode([
                            "status" => "error",
                            "message" => "user is not invited to this event"
                        ]);
                    }
                }
            }
            // get a specific event
            if($eventAction == "getSpecificEvent"){
                // $stmt = $this->conn->prepare("SELECT event.user_id FROM event JOIN event_invite ON event.id = event_invite.event_id WHERE event.id = :eventId");
                // $stmt->execute(["eventId" => $eventId]);
                // $row = $stmt->fetchAll();
                // echo $row['user_id'];
                // if($row){
                //     if($row[0]['user_id'] != $userId){
                //         return json_encode([
                //             "status" => "error",
                //             "message" => "user does not have access to this event"
                //         ]);
                //     }
                // }
            }
            // get invitations for an event
            if($eventAction == "getInvitations"){

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