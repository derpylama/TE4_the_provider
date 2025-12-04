***

# Add event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/add-event.php`  
**Method:** `POST`

## Description
An endpoint to create an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | the title of the event |
| event_info | string | no | info about the event |
| start_time | string | no | the start time for an event |
| end_time | string | yes | the end time for an event |
| comment | string | no | a personal comment for an event |
| general | string | no | general info about an event |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event added successfully",
    "data": {
        "event_id": 70
    }
}
```

---

# Edit event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/edit-event.php`  
**Method:** `POST`

## Description
An endpoint to edit an event (an event can only be edited by the user that owns the event)

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event to be edited |
| title | string | no | the edited title for the event |
| event_info | string | no | the edited info for the event |
| start_time | string | no | the edited start time for the event |
| end_time | string | no | the edited end time for the event |
| general | string | no | the edited general info for the event |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event edited successfully",
    "data": {}
}
```

---

# Delete event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/delete-event.php`  
**Method:** `POST`

## Description
An endpoint to delete an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event that will be deleted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event deleted successfully",
    "data": {}
}
```

---

# Add personal comment

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/add-personal-comment.php`  
**Method:** `POST`

## Description
An endpoint to set a personal comment

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event the user adds a comment to |
| comment | string | yes | the comment that the user sets |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event comment added",
    "data": {}
}
```

---

# Edit personal comment

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/edit-personal-comment.php`  
**Method:** `POST`

## Description
An endpoint to edit a personal comment

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the event id  of which the comment will be edited for |
| comment | string | yes | the edited comment for an event |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event comment edited",
    "data": {}
}
```

---

# Delete personal comment

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/delete-personal-comment.php`  
**Method:** `POST`

## Description
An endpoint to delete a personal comment for an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | The id of the event that the comment will be deleted for |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event comment deleted",
    "data": {}
}
```

---

# Invite user to event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/invite-to-event.php`  
**Method:** `POST`

## Description
An endpoint to invite a user to an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | The id of the event the user will be invited to |
| invited_user_id | int | yes | The id of the user that will be invited |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invite sent successfully",
    "data": {}
}
```

---

# Accept/decline event invite

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/handle-invites.php`  
**Method:** `POST`

## Description
An endpoint to accept or declina an event invite

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event the user will accept or decline an invitation for |
| accepted | int | yes | input 1 means accept and input 0 means decline |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invite accepted successfully",
    "data": {}
}
```

---

# Delete invitation

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/delete-invitation.php`  
**Method:** `POST`

## Description
An endpoint to delete an invitation to an event for a specific user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event the invitation will be deleted for |
| invited_user_id | int | yes | the user of which the invitation will be deleted for |

## Example JSON Return

```json
{
    "status": "success",
    "message": "invitation deleted successfully",
    "data": {}
}
```

---

# Get invitations

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/get-invitations.php`  
**Method:** `GET`

## Description
An endpoint to get the invitations for an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | id | yes | the event id of which the invitations will be retrieved for |
| sort_invites_by | string | no | an option to get only accepted invites or pending invites |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invitations retrieved",
    "data": {
        "invites": [
            {
                "id": 95,
                "event_id": 1,
                "invited_user_id": 3,
                "accepted": 0,
                "creation_date": "2025-12-04 08:50:40"
            }
        ]
    }
}
```

---

# Get events

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/get-events.php`  
**Method:** `GET`

## Description
An endpoint to retrieve events for a user in different ways

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| mode | string | yes | selects in which way the events will be retrieved, valid inputs are "all", "range", "specific", "search" |
| start_time | string | no | for mode "range", the starting date of the timespan events will be selected between |
| end_time | string | no | for mode "range", the ending date of the timespan events will be selected between |
| event_id | int | no | for mode "specific", input an event id to retrieve that specific event |
| search_query | string | no | for mode "search", the search query to search for an event |
| search_filter | array, string | no | for mode "search", selects what part of the event the search query will search for, valid filters are "title", "start_time", "end_time", "creation_date", "user_id", "event_info", "general" |
| order_by | string | no | for all modes, selects what the returned events will be ordered by, valid inputs are "title", "start_time", "end_time", "creation_date", "user_id", "event_info", "general" |
| order_direction | string | no | for all modes, selects in which direction the returned events will be ordered by, valid inputs are "ASC" and "DESC" |
| amount | int | no | for all modes, selects how many events will be retrieved to a maximum |
| offset | int | no | for all modes, dependant on amount, skips a selected amount of events on the get from the first event retrieved |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Events retrieved successfully",
    "data": {
        "events": [
            {
                "id": 95,
                "user_id": 179,
                "start_time": "2025-12-12 14:14:14",
                "event_info": "event that exists",
                "title": "My event",
                "end_time": "2025-12-31 14:14:14",
                "creation_date": "2025-12-04 08:43:41",
                "latest_update": "2025-12-04 08:43:41",
                "general": "general info about event",
                "comment": "I like this event",
                "source": "own"
            }
        ]
    }
}
```
