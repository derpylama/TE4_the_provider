***

# Add event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/add-event.php`  
**Method:** `POST`

## Description
Adds an event to a users calendar

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | Sets the title for the event |
| endTime | string | yes | Sets the endtime for the event |
| event_info | string | no | Adds the possibility to add a descripton for an event |
| token | string | yes | a verification token used that ensures restricted access |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event added successfully"
}
```

---

# Get user events

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/get-user-events.php`  
**Method:** `GET`

## Description
Gets the events that is related to a user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | a verification token used that ensures restricted access |

## Example JSON Return

```json
{
    "id": 13,
    "user_id": 1,
    "start_time": "2025-11-18 14:18:55",
    "event_info": "Här ska vi göra en API",
    "title": "Skapa API",
    "end_time": "2025-12-19 13:23:44",
    "creation_date": "2025-11-17 14:18:55",
    "latest_update": "2025-11-17 14:18:55"
}
```

---

# Get events by

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/get-events-by.php`  
**Method:** `GET`

## Description
Gets the events for a user for a specified year, month, week or day. Some parameters may be requered depending on the selected span. 

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes |  a verification token used that ensures restricted access |
| span | string | yes | determines if the user gets events by day, month, week or year |
| year | int | yes | determines which year to get events for |
| day_number | int | no | determines a day of the week, 1 for monday, 7 for sunday |
| week_number | int | no | determines a specified week, 1 for the first week of the year |
| month_number | int | no | determines a specified month, 1 for january, 12 for december |

## Example JSON Return

```json
{
    "id": 14,
    "user_id": 1,
    "start_time": "2025-12-17 14:51:34",
    "event_info": "Här ska vi göra en API igen",
    "title": "test2",
    "end_time": "2025-12-19 13:23:45",
    "creation_date": "2025-11-17 14:51:34",
    "latest_update": "2025-11-17 14:51:34"
}
```

---

# Invite user to event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/invite-to-event.php`  
**Method:** `POST`

## Description
Invite a selected user to an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes |  a verification token used that ensures restricted access |
| invited_user_id | int | yes | the id of the user that is invited |
| event_id | int | yes | the id of the event the invite is sent for |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invite sent successfully"
}
```

---

# Handle event invites

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/handle-invites.php`  
**Method:** `POST`

## Description
An endpoint that handles accepts and declines to event invitations

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | a verification token used that ensures restricted access |
| accepted | int | yes | 2 different values are valid, 0 for decline, 1 for accept |
| event_id | id | yes | the id for the event |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invite accepted successfully"
}
```

---

# Delete event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/delete-event.php`  
**Method:** `POST`

## Description
Deletes a selected event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | a verification token used that ensures restricted access |
| event_id | int | yes | the id for the event that is being deleted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event deleted successfully"
}
```

---

# Edit event

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/edit-event.php`  
**Method:** `POST`

## Description
An endpoint to edit an event. No changes is required to make the api call but the event will not change if no changes are sent. 

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes |  a verification token used that ensures restricted access |
| event_id | int | yes | the id for the event to be edited |
| title | string | no | the updated title for the event |
| event_info | string | no | the updated  information for the event |
| start_time | string | no | the updated start time for the event (datetime format) |
| end_time | string | no | the updated endtome for the event (endtime format) |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event edited successfully"
}
```

---

# Delete event invitation

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/calendar-api/delete-invitation.php`  
**Method:** `POST`

## Description
An ednpoint to delete a sent invitation or invited user. 

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | a verification token used that ensures restricted access |
| invited_user_id | int | yes | the id of the invited user to the event |
| event_id | int | yes | the id of the event that the invitation will be removed from |

## Example JSON Return

```json
{
    "status": "success",
    "message": "invitation deleted successfully"
}
```

---

# Get invitations

**Endpoint:** ` http://localhost:8080/TE4_the_provider/api/calendar-api/get-invitations.php`  
**Method:** `POST`

## Description
An endpoint to get all the invited users for an event

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| event_id | int | yes | the event id to get the invitations for |
| sort_invites_by | string | no | 3 valid inputs: "accpeted", "pending" and "all". Defaults to all if no other parameter is given |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event invitations retrieved",
    "invites": [
        {
            "id": 53,
            "event_id": 31,
            "invited_user_id": 20,
            "accepted": 0,
            "creation_date": "2025-11-21 08:53:30"
        }
    ]
}
```
