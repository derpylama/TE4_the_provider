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
