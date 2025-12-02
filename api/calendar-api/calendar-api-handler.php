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

    function addEvent($title, $token, $eventInfo, $startTime, $endTime, $comment = "", string $general) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
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

            if (!empty($general)) {
                $fields['general'] = $general;
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

            $creationDate = date('y-m-d H:i:s');
            //echo $creationDate;
            $stmt = $this->conn->prepare("SELECT id FROM event WHERE creation_date = :creationDate");
            $stmt->execute(["creationDate" => $creationDate]);
            $lastIdResult = $stmt->fetch();
            $lastId = $lastIdResult["id"];


            $stmt = $this->conn->prepare("INSERT INTO event_invite (event_id, invited_user_id, accepted, comment) VALUES (:eventId, :userId, 1, :comment)");
            $stmt->execute(["eventId" => $lastId, "userId" => $userId, "comment" => $comment]);

            // return if status is success
            $responsData=["event_id" => $lastId];
            $message="event added successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function getUserEvents($token, $orderBy, $orderDirection, $amount, $offset = 0) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, null, null, "getUserEvents");
            if ($error) {
                return $error;
            }

            $allEvents = [];

            // gets the events that the user owns
            $limit = "";
            if($amount != "" && $offset != 0){
                $limit = " LIMIT " . intval($amount);
            }
            //echo $limit;
            $offsetAmount = "";
            if($offset != ""){
                $offsetAmount = " OFFSET " . intval($offset);
            }
            if($offset != "" && $amount == ""){
                $message="Cannot set an offset without a specified amount";
                $this->error($message, [], 400);
            }
            $stmt = $this->conn->prepare("
                SELECT e.*, ei.comment, 'own' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE e.user_id = :user_id_own

                UNION ALL

                SELECT e.*, ei.comment, 'invited' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE ei.invited_user_id = :user_id_invited AND ei.invited_user_id != e.user_id

                ORDER BY $orderBy $orderDirection $limit $offsetAmount
            ");
            $stmt->execute([
                ":user_id_own" => $userId,
                ":user_id_invited" => $userId
            ]);
            $allEvents = $stmt->fetchAll();

            $responsData=["events" => $allEvents];
            $message="events retrieved successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function getUserEventsBy($token, $span, $year, $month, $week, $day, $orderBy, $orderDirection, $amount, $offset) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, null, null, false, "getEventsBy");
            if ($error) {
                return $error;
            }
            // gets the events that the user owns
            $limit = "";
            if($amount != ""){
                $limit = " LIMIT " . intval($amount);
            }
            //echo $limit;
            $offsetAmount = "";
            if($offset != "" && $amount != ""){
                $offsetAmount = " OFFSET " . intval($offset);
            }
            if($offset != "" && $amount == ""){
                $message="Cannot set an offset without a specified amount";
                $this->error($message, [], 400);
            }
            // if an event spans over different years, months or weeks it will show up if the input is either year, month or week that the event spans over
            if($span == "year"){
                // gets all events for a user where the year in the event is the selected year
                $sql = "
                SELECT e.*, ei.comment, 'own' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE e.user_id = :user_id_own
                AND :year_own BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                AND e.user_id = ei.invited_user_id

                UNION ALL

                SELECT e.*, ei.comment, 'invited' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE ei.invited_user_id = :user_id_invited
                AND :year_invited BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                AND e.user_id != ei.invited_user_id

                ORDER BY $orderBy $orderDirection
                $limit
                $offsetAmount
                ";

                $stmt = $this->conn->prepare($sql);

                $stmt->execute([
                    ":user_id_own"     => $userId,
                    ":year_own"        => $year,
                    ":user_id_invited" => $userId,
                    ":year_invited"    => $year
                ]);
                $allEvents = $stmt->fetchAll();

                // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND e.user_id != ei.invited_user_id ORDER BY $orderBy $orderDirection");
                // $stmt->execute([":user_id" => $userId, ":year" => $year]);
                // $eventsNoRights = $stmt->fetchAll();
            }else if($span == "month"){
                // gets all events for a user where the month in the event is the selected month
                $sql = "
                SELECT e.*, ei.comment, 'own' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE e.user_id = :user_id_own
                AND :year_own BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                AND :month_own BETWEEN MONTH(e.start_time) AND MONTH(e.end_time)
                AND e.user_id = ei.invited_user_id

                UNION ALL

                SELECT e.*, ei.comment, 'invited' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE ei.invited_user_id = :user_id_invited
                AND :year_invited BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                AND :month_invited BETWEEN MONTH(e.start_time) AND MONTH(e.end_time)
                AND e.user_id != ei.invited_user_id

                ORDER BY $orderBy $orderDirection      
                $limit
                $offsetAmount
                ";

                $stmt = $this->conn->prepare($sql);

                $stmt->execute([
                    ":user_id_own"     => $userId,
                    ":year_own"        => $year,
                    ":month_own"       => $month,
                    ":user_id_invited" => $userId,
                    ":year_invited"    => $year,
                    ":month_invited"   => $month
                ]);

                $allEvents = $stmt->fetchAll();
                // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :month BETWEEN MONTH(start_time) AND MONTH(end_time) AND e.user_id = ei.invited_user_id ORDER BY $orderBy $orderDirection");
                // $stmt->execute([":user_id" => $userId, ":month" => $month, ":year" => $year]);
                // $events = $stmt->fetchAll();

                // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :month BETWEEN MONTH(start_time) AND MONTH(end_time) AND e.user_id != ei.invited_user_id ORDER BY $orderBy $orderDirection");
                // $stmt->execute([":user_id" => $userId, ":month" => $month, ":year" => $year]);
                // $eventsNoRights = $stmt->fetchAll();
            }else if($span == "week"){
                // gets all events for a user where the week in the event is the selected week
                $sql = "
                SELECT e.*, ei.comment, 'own' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE e.user_id = :user_id_own
                    AND :year_own BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                    AND :week_own BETWEEN WEEKOFYEAR(e.start_time) AND WEEKOFYEAR(e.end_time)
                    AND e.user_id = ei.invited_user_id

                UNION ALL

                SELECT e.*, ei.comment, 'invited' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE ei.invited_user_id = :user_id_invited
                    AND :year_invited BETWEEN YEAR(e.start_time) AND YEAR(e.end_time)
                    AND :week_invited BETWEEN WEEKOFYEAR(e.start_time) AND WEEKOFYEAR(e.end_time)
                    AND e.user_id != ei.invited_user_id

                ORDER BY $orderBy $orderDirection 
                $limit 
                $offsetAmount
                ";
                
                $stmt = $this->conn->prepare($sql);
                
                $stmt->execute([
                    ":user_id_own"     => $userId,
                    ":year_own"        => $year,
                    ":week_own"        => $week,
                    ":user_id_invited" => $userId,
                    ":year_invited"    => $year,
                    ":week_invited"    => $week
                ]);
                
                $allEvents = $stmt->fetchAll();

                // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time) AND e.user_id = ei.invited_user_id ORDER BY $orderBy $orderDirection");
                // $stmt->execute([":user_id" => $userId, ":year" => $year, ":week" => $week]);
                // $events = $stmt->fetchAll();

                // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND :year BETWEEN YEAR(start_time) AND YEAR(end_time) AND :week BETWEEN WEEKOFYEAR(start_time) AND WEEKOFYEAR(end_time) AND e.user_id != ei.invited_user_id ORDER BY $orderBy $orderDirection");
                // $stmt->execute([":user_id" => $userId, ":year" => $year, ":week" => $week]);
                // $eventsNoRights = $stmt->fetchAll();
            }else if ($span == "day") {
                $week = (int)$week;
                $day  = (int)$day;

                $isoWeekString = sprintf('%04d-W%02d-%d', (int)$year, $week, $day);

                $targetDate = date('Y-m-d', strtotime($isoWeekString));
                if (!$targetDate) {
                    $events = [];
                    $eventsNoRights = [];
                } else {
                    $sql = "
                    SELECT e.*, ei.comment, 'own' AS source
                    FROM event e
                    INNER JOIN event_invite ei ON e.id = ei.event_id
                    WHERE e.user_id = :user_id_own
                    AND DATE(:target_own) BETWEEN DATE(e.start_time) AND DATE(e.end_time)
                    AND e.user_id = ei.invited_user_id

                    UNION ALL

                    SELECT e.*, ei.comment, 'invited' AS source
                    FROM event e
                    INNER JOIN event_invite ei ON e.id = ei.event_id
                    WHERE ei.invited_user_id = :user_id_invited
                    AND DATE(:target_invited) BETWEEN DATE(e.start_time) AND DATE(e.end_time)
                    AND e.user_id != ei.invited_user_id

                    ORDER BY $orderBy $orderDirection 
                    $limit
                    $offsetAmount
                    ";

                    $stmt = $this->conn->prepare($sql);

                    $stmt->execute([
                        ":user_id_own"      => $userId,
                        ":target_own"       => $targetDate,
                        ":user_id_invited"  => $userId,
                        ":target_invited"   => $targetDate
                    ]);

                    $allEvents = $stmt->fetchAll();
                    // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE user_id = :user_id AND DATE(:target) BETWEEN DATE(start_time) AND DATE(end_time) AND e.user_id = ei.invited_user_id ORDER BY $orderBy $orderDirection");
                    // $stmt->execute([":user_id" => $userId, ":target"  => $targetDate]);
                    // $events = $stmt->fetchAll();

                    // // Invited events
                    // $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND DATE(:target) BETWEEN DATE(e.start_time) AND DATE(e.end_time) AND e.user_id != ei.invited_user_id ORDER BY $orderBy $orderDirection");
                    // $stmt->execute([":user_id" => $userId, ":target"  => $targetDate]);
                    // $eventsNoRights = $stmt->fetchAll();
                }
            }
            // reutrn if events where found or not
            $responsData=["events" => $allEvents];
            $message="event retrieved successfully";
            $this->success($message, $responsData, 200);
            // if(empty($events) && empty($eventsNoRights)){
            //     $responsData=["events" => $events, "eventsNoRights" => $eventsNoRights];
            //     $message="no events found";
            //     $this->success($message, $responsData, 200);
            // }
            // else{
            //     $responsData=["events" => $allEvents];
            //     $message="event retrieved successfully";
            //     $this->success($message, $responsData, 200);
            // }
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function inviteUserToEvent($token, $invitedUserId, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
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
            $responsData=[];
            $message="event invite sent successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function handleInvites($token, $accepted, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
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
                
                $responsData=[];
                $message="event invite declined successfully";
                $this->success($message, $responsData, 200);
            }
            else if($accepted == 1){
                // initiate the sql query
                $addEventQuery = "UPDATE event_invite SET accepted = :accepted WHERE invited_user_id = :userId AND event_id = :eventId";

                $stmt = $this->conn->prepare($addEventQuery);


                $stmt->execute(['accepted' => $accepted, 'userId' => $userId, 'eventId' => $eventId]);

                // return if statsus is success
                $responsData=[];
                $message="event invite accepted successfully";
                $this->success($message, $responsData, 200);
            }
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function deleteEvent($token, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try {
            $error = $this->checkForError($userId, $eventId, true, "deleteEvent");
            if ($error) {
                return $error;
            }

            // deletes the event if the user can edit this event
            $stmt = $this->conn->prepare("DELETE FROM event WHERE id = :eventId");
            $stmt->execute(['eventId' => $eventId]);

            $responsData=[];
            $message="event deleted successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function editEvent($token, $eventId, $title, $content, $startTime, $endTime, $editEvent, $general) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
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

            if (!empty($general)) {
                $setParts[] = "general = :general";
                $params['general'] = $general;
            }

            if (empty($setParts)) {
                $message="no fields to update";
                $this->error($message, [], 400);
            }
            
            $updateSqlQuery .= implode(", ", $setParts) . " WHERE id = :eventId";
            $stmt = $this->conn->prepare($updateSqlQuery);
            $stmt->execute($params);

            $responsData=[];
            $message="event edited successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function deleteInvitation($token, $invitedUserId, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
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
                    $message="user is not invited to this event";
                    $this->error($message, [], 400);
                }
            }
            else if(empty($row)){
                $stmt = $this->conn->prepare("SELECT id FROM user WHERE id = :invitedUserId");
                $stmt->execute(['invitedUserId' => $invitedUserId]);
                $row = $stmt->fetch();
                if($row){
                    $message="user is not invited to this event";
                    $this->error($message, [], 400);
                }
                else if(empty($row)){
                    $message="user does not exist";
                    $this->error($message, [], 400);
                }
            }



            // deletes the event if the user can edit this event
            $stmt = $this->conn->prepare("DELETE FROM event_invite WHERE invited_user_id = :invitedUserId AND event_id = :eventId");
            $stmt->execute(['invitedUserId' => $invitedUserId, 'eventId' => $eventId]);

            $responsData=[];
            $message="invitation deleted successfully";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function getSpecificEvent($token, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "getSpecificEvent");
            if ($error) {
                return $error;
            }

            // gets the events that the user owns
            $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.user_id = ei.invited_user_id WHERE e.user_id = :user_id AND e.id = :eventId AND e.id = ei.event_id");
            $stmt->execute([":user_id" => $userId, "eventId" => $eventId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // gets the events that the user is invited to
            $stmt = $this->conn->prepare("SELECT e.*, ei.comment FROM event e INNER JOIN event_invite ei ON e.id = ei.event_id WHERE ei.invited_user_id = :user_id AND ei.event_id = :eventId AND ei.invited_user_id != e.user_id");
            $stmt->execute([":user_id" => $userId, "eventId" => $eventId]);
            $eventsNoRights = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(empty($events) && empty($eventsNoRights)){
                $responsData=["events" => $events, "eventsNoRights" => $eventsNoRights];
                $message="no event found";
                $this->success($message, $responsData, 200);
            }
            else{
                $responsData=["events" => $events, "eventsNoRights" => $eventsNoRights];
                $message="event retrieved successfully";
                $this->success($message, $responsData, 200);
            }
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function getInvitations($token, $eventId, $sortInvitesBy) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "getInvitations");
            if ($error) {
                return $error;
            }
            
            if($sortInvitesBy == "all"){
                $stmt = $this->conn->prepare("SELECT ei.id, ei.event_id, ei.invited_user_id, ei.accepted, ei.creation_date FROM event_invite ei INNER JOIN event e ON ei.event_id = e.id WHERE event_id = :eventId AND ei.invited_user_id != e.user_id");
            }
            else if($sortInvitesBy == "accepted"){
                $stmt = $this->conn->prepare("SELECT ei.id, ei.event_id, ei.invited_user_id, ei.accepted, ei.creation_date FROM event_invite ei INNER JOIN event e ON ei.event_id = e.id WHERE event_id = :eventId AND ei.invited_user_id != e.user_id AND ei.accepted = 1");
            }
            else if($sortInvitesBy == "pending"){
                $stmt = $this->conn->prepare("SELECT ei.id, ei.event_id, ei.invited_user_id, ei.accepted, ei.creation_date FROM event_invite ei INNER JOIN event e ON ei.event_id = e.id WHERE event_id = :eventId AND ei.invited_user_id != e.user_id AND ei.accepted = 0");
            }
            $stmt->execute(['eventId' => $eventId]);
            $invites = $stmt->fetchAll();

            if(empty($invites)){

                $responsData=[];
                $message="no invites found";
                $this->success($message, $responsData, 200);
            }


            $responsData=["invites" => $invites];
            $message="event invitations retrieved";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }

    function addPersonalComment($token, $eventId, $comment, $edit = false) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }
 
        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }
 
        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "addComment");
            if ($error) {
                return $error;
            }

            $stmt = $this->conn->prepare("UPDATE event_invite SET comment = :comment WHERE event_id = :eventId AND invited_user_id = :userId ");
            $stmt->execute(["comment" => $comment, "eventId" => $eventId, "userId" => $userId]);
            
            if($edit){

                $responsData=[];
                $message="event comment edited";
                $this->success($message, $responsData, 200);
            }
            else{

                $responsData=[];
                $message="event comment added";
                $this->success($message, $responsData, 200);
            }
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }
    
    function deletePersonalComment($token, $eventId) {
        //Token---------------------------------------------------------------
        $tokeninfo=$this->checkServiceAndToken($token); 
        if($tokeninfo['status']!="success"){
            $message=$tokeninfo["message"];
            $this->error($message, [], 400);
        }

        //check user permissions
        if ($tokeninfo['type'] == 'user') {
            $message="Insufficient permissions";
            $this->error($message, [], 400);
        }

        //---------------------------------------------------------------------
        $userId=$tokeninfo["userId"];
        try{
            $error = $this->checkForError($userId, $eventId, null, "deleteComment");
            if ($error) {
                return $error;
            }

            $stmt = $this->conn->prepare("UPDATE event_invite SET comment = null WHERE event_id = :eventId AND invited_user_id = :userId ");
            $stmt->execute(["eventId" => $eventId, "userId" => $userId]);
            
            $responsData=[];
            $message="event comment deleted";
            $this->success($message, $responsData, 200);
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
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
                    $message="event does not exist";
                    $this->error($message, [], 400);
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
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
                    }
                }
                // checks if the invited user has eccess to the calendar
                $stmt = $this->conn->prepare("SELECT type FROM user WHERE id = :id");
                $stmt->execute(['id' => $invitedUserId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['type'] == 'user'){
                        $message="invited user does not have access to the calendar";
                        $this->error($message, [], 400);
                    }
                }

                //checks if the invited user already has an invite for the event
                $stmt = $this->conn->prepare("SELECT event_id, invited_user_id FROM event_invite WHERE invited_user_id = :id");
                $stmt->execute(['id' => $invitedUserId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['event_id'] == $eventId && $row['invited_user_id'] == $invitedUserId){
                        $message="user is already invited to this event";
                        $this->error($message, [], 400);
                    }
                }
            }
            // accept/decline invite
            if($eventAction == "handleinvites"){
                // checks if the user accepted the invite
                $stmt = $this->conn->prepare("SELECT accepted FROM event_invite WHERE invited_user_id = :inviteduserId");
                $stmt->execute(['inviteduserId' => $userId]);
                if($stmt->fetchColumn()) {
                    $message="user already accepted the invite";
                    $this->error($message, [], 400);
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
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
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
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
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
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
                    }
                }
                // checks if the invited user id is invited to the event
                $stmt = $this->conn->prepare("SELECT invited_user_id FROM event_invite WHERE event_id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['invited_user_id'] != $invitedUserId){
                        $message="user is not invited to this event";
                        $this->error($message, [], 400);
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
                //         return jsonencode([
                //             "status" => "error",
                //             "message" => "user does not have access to this event"
                //         ]);
                //     }
                // }
            }
            // get invitations for an event
            if($eventAction == "getInvitations"){

            }
            // add a comment for an event
            if($eventAction == "addComent"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
                    }
                }
            }
            //delete a comment for an event
            if($eventAction == "deleteComment"){
                // checks if the user is allowed to edit this event
                $stmt = $this->conn->prepare("SELECT user_id FROM event WHERE id = :eventId");
                $stmt->execute(['eventId' => $eventId]);
                $row = $stmt->fetch();
                if($row){
                    if($row['user_id'] != $userId){
                        $message="user can not edit this event";
                        $this->error($message, [], 400);
                    }
                }
            }
        }
        catch(PDOException $e){
            // return error with the database
            $message="Database error: " . $e->getMessage();
            $this->error($message, [], 400);
        }
    }


    function searchForEvent($token, $searchQuery, $orderBy, $orderDirection, $amount, $offset, $searchFilter = []) {
        // -------------------------------
        // Token validation
        $tokenInfo = $this->checkServiceAndToken($token); 
        if ($tokenInfo['status'] != "success") {
            $this->error($tokenInfo["message"], [], 400);
        }
    
        // Check permissions
        if ($tokenInfo['type'] == 'user') {
            $this->error("Insufficient permissions", [], 400);
        }
    
        $userId = $tokenInfo["userId"];
    
        try {
            $error = $this->checkForError($userId, null, null, "searchEvent");
            if ($error) return $error;
    
            // -------------------------------
            // Pagination
            $limitSql = "";
            if ($amount !== "" && is_numeric($amount) && intval($amount) > 0) {
                $limitSql = " LIMIT " . intval($amount);
            }
    
            $offsetSql = "";
            if ($offset !== "" && $limitSql !== "") {
                if (!is_numeric($offset) || intval($offset) < 0) {
                    $this->error("Offset must be a non-negative integer", [], 400);
                }
                $offsetSql = " OFFSET " . intval($offset);
            }
    
            // -------------------------------
            // Validate ordering
            $allowedOrderBy = ['title', 'start_time', 'end_time', 'creation_date', 'user_id'];
            $allowedDirection = ['ASC', 'DESC'];
    
            if (!in_array($orderBy, $allowedOrderBy)) $orderBy = 'creation_date';
            if (!in_array(strtoupper($orderDirection), $allowedDirection)) $orderDirection = 'ASC';
    
            // -------------------------------
            // Escape LIKE characters
            $searchQuery = trim($searchQuery);
            $searchLike = "%$searchQuery%";
    
            // -------------------------------
            // Validate search filters
            $allowedFilters = ['title', 'start_time', 'end_time', 'creation_date', 'user_id', 'event_info', 'general'];
    
            if (!is_array($searchFilter)) {
                $this->error("searchFilter must be an array", [], 400);
            }
    
            if (!empty(array_diff($searchFilter, $allowedFilters))) {
                $this->error("Invalid search filter", [], 400);
            }
    
            // -------------------------------
            // Build dynamic search conditions
            $filterConditionsOwn = [];
            $filterConditionsInv = [];
            $params = [
                ":user_id_own" => $userId,
                ":user_id_inv" => $userId
            ];
            
            if (!empty($searchFilter) && $searchQuery !== "") {
                foreach ($searchFilter as $filter) {
                    $paramOwn = ":own_$filter";
                    $paramInv = ":inv_$filter";
                    $filterConditionsOwn[] = "e.$filter LIKE $paramOwn";
                    $filterConditionsInv[] = "e.$filter LIKE $paramInv";
                    $params[$paramOwn] = "%$searchQuery%";
                    $params[$paramInv] = "%$searchQuery%";
                }
            }
            
            $filterSqlOwn = !empty($filterConditionsOwn) ? " AND (" . implode(" OR ", $filterConditionsOwn) . ")" : "";
            $filterSqlInv = !empty($filterConditionsInv) ? " AND (" . implode(" OR ", $filterConditionsInv) . ")" : "";
    
            // -------------------------------
            // Prepare SQL
            $stmt = $this->conn->prepare("
                SELECT 
                    e.*, 
                    ei.comment,
                    'own' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE e.user_id = :user_id_own
                AND e.user_id = ei.invited_user_id
                $filterSqlOwn
    
                UNION ALL
    
                SELECT 
                    e.*, 
                    ei.comment,
                    'invited' AS source
                FROM event e
                INNER JOIN event_invite ei ON e.id = ei.event_id
                WHERE ei.invited_user_id = :user_id_inv
                AND ei.invited_user_id != e.user_id
                $filterSqlInv
    
                ORDER BY $orderBy $orderDirection
                $limitSql
                $offsetSql
            ");
    
            // -------------------------------
            // Debugging: Uncomment to see final SQL and params
            // echo $stmt->queryString;
            // print_r($params);
    
            $stmt->execute($params);
            $allEvents = $stmt->fetchAll();
    
            $this->success("Events retrieved successfully", ["events" => $allEvents], 200);
    
        } catch (PDOException $e) {
            // Optionally log internally: $e->getMessage()
            $this->error("Database error occurred", [], 400);
        }
    }
}

?>