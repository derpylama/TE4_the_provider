***

# User types

There exists three diffrent tiers of user in the system these include user, enduser and admin.

User is the base user that can only view blogs and wiki. A normal user doesen't have access to a calendar.

Enduser can create and delete a personal blog, wiki and calendar event. They can also edit every wiki that is within the same company, blog and calendar event. Invite other enduser to a event and accept an invitation. Add a comment to a event that can only be seen by them.

Admin 


# Authentication

## Getting a auth token

In order to get a auth token you need to send a POST request to

`/api/user-api/login.php`

This endpoint has the required inputs

    { 
        username: the username of the user trying to login 
        password: the password of the user trying to login 

        customer_username: the username of the customers account
        customer_password: the password of the customers account
    }

This returns

    {
        "status": "success",
        "message": "Token retrieved successfully",
        "data": {
            "token": "auth-token"
        }
    }

---

## Using the auth token

All endpoints exluding (login and logout) must have the auth token sent in order to be allowed to use the endpoint.

The token i sent in the header in every request under the Authorization header and in this format:

    Authorization Bearer <auth-token>

---

# General

Blog, Wiki, User and event have a extra space where it is possible to store extra metadata or other data that is needs to be stored. A exemple for this is likes or comments for blogs or wiki. The recomended way to store general data is using json that is sent with the creation or edit of media.

General is sent as an array or assoative array.

---

# get-all-users

**Endpoint:** `/api/user-api/get-all-users.php`  
**Method:** `GET`

## Description
Gets info about either multiple users, or about a specific user. 
An end_user has can retrive this list of info about other users.  
   
    private $getUserEndUser = [
        "username",
        "id"
    ];
An admin can retrive this:  

    private $getUserAdmin = [
        "id", 
        "customer_id",
        "main_mail",
        "phone_number",
        "first_name",
        "last_name",
        "main_adress",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
    ];
A user can retrive this about their own data: 

    private $getOwnUserData = [
        "main_mail",
        "first_name",
        "last_name",
        "main_adress",
        "phone_number",
        "employment_number",
        "birthdate",
        "username",
        "type",
        "creation_date",
        "latest_update",
        "extra_mail",
        "extra_adress",
        "extra_phone_number"
    ];


## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | Can be used if you want to get info about a specific user. |
| result_amount | int | no | Irrelevant if used_id is defined. Defines how many users you want to return. |
| offset | int | no | Only applicable if the result_amount is used. Offsets from where the get starts. |

## Example JSON Return

```json SKA ÄNDRAS
{"status":"success","message":"removed user"}
```


---

# add-user

**Endpoint:** `/api/user-api/add-user.php`  
**Method:** `POST`

## Description
Adds a user under the same company that the current admin user is. Admin type users are the only one allowed to add users

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| username | string | yes | username of the created user |
| password | string | yes | password of the created user |
| type | string | yes | if the user should be a admin/end user/user |
| first_name | string | no | first name of the person that will use the created user |
| last_name | string | no | last name of the person that will use the created user |
| phone_number | string  | no | main phone number of the person that will use the account |
| adress | string | no | main adress of the person that will use the account |
| employment_number | string | no | the employment number of the person using this account |
| birthdate | string | no | birthdate of the person using this account |
| mail | string | no | The main mail that is associated with the created account |
| general | json string | no | A place to store any extra infomration for a user ex (user preferences) |
| extra_mail | array | no | A place to store if the user has multiple emails that they want stored |
| extra_phone_number | array | no | A place to store if the user has multiple phone number that they want stored |
| extra_adress | array | no | A place to store if the user has multiple adresses that they want stored |

## Example JSON Return

```json
{
    "status": "success",
    "message": "User added",
    "data": {
        "username": "<username>",
        "type": "admin",
        "id": "<user id>"
    }
}
```

---

# ban-user

**Endpoint:** `/api/user-api/ban-user.php`  
**Method:** `POST`

## Description
Ban a user fron using one of the services (wiki, blog, calendar)

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | yes | the id of the user that is being banned |
| exiration_date | string | yes | The date and time the ban expires on |
| blog_ban | 1 or 0 | no | If the user should be banned from using the blog |
| wiki_ban | 1 or 0 | no | If the user should be banned from using the wiki |
| calendar_ban | 1 or 0 | no | If the user should be banned from using the calendar |
| reason | string | no | The reason for the ban |

## Example JSON Return

```json
{
    "status": "success",
    "message": "user1 has been banned successfully.",
    "data": {}
}
```

---

# edit-user

**Endpoint:** `/api/user-api/edit-user.php`  
**Method:** `POST`

## Description
Edit a existing user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | Is used if a admin is trying to edit a another user in the company |
| mail | array | no | A array with the email that i wanted to be changed |
| first_name | string | no |  |
| last_name | string | no |  |
| phone_number | array | no |  |
| adress | array | no |  |
| employment_number | array | no |  |
| birthdate | string | no |  |
| username | string | no |  |
| password | string | no |  |
| type | string | no |  |
| general | json string | no |  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "User edited",
    "data": {}
}
```

---


---

# remove-ban

**Endpoint:** `/api/user-api/remove-ban.php`  
**Method:** `POST`

## Description
Remove a ban from a user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| ban_id | int | yes | The id of the ban that is to be removed |

## Example JSON Return

```json
{
    "status": "success",
    "message": "removed ban",
    "data": {}
}
```

---

# remove-user

**Endpoint:** `/api/user-api/remove-user.php`  
**Method:** `POST`

## Description
Removes the specified user from the current orginasation

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | yes | the id of the user to be deleted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "removed user",
    "data": {}
}
```

---

# create-blog

**Endpoint:** `/api/blog-api/create-blog.php`  
**Method:** `POST`

## Description
Creates a blog for the current user.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| content | string | yes | The content of the blog in ex html format |
| title | string | yes | The title of the blog |
| general | json string | no | general info attached to a blog ex comment |

## Example JSON Return

```json
{
    "status": "success",
    "message": "blog created",
    "data": {
        "blog_id": "5"
    }
}
```

---

# delete-blog

**Endpoint:** `/api/blog-api/delete-blog.php`  
**Method:** `POST`

## Description
Removes a blog. Default is removing your own blog but admins can remove another user blog

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | The users blog that is to be deleted. only admins can do this |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog deleted successfully",
    "data": {}
}
```

---

# edit-blog

**Endpoint:** `/api/blog-api/edit-blog.php`  
**Method:** `POST`

## Description
Edit the content, title or general data for a blog. A admin can edit another users blog if they are in the same company

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | no | New title for the blog |
| content | string | no | New content for the blog |
| user_id | string | no | Used when a admin wants to change a blog for another user |
| general | json string | no | change general data  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog updated successfully",
    "data": {}
}
```

---

# get-blog

**Endpoint:** `/api/blog-api/get-blog.php`  
**Method:** `GET`

## Description
gets default 10 blog from the same comapny as the current user. Possible to change the amount of blogs that are returned and at what offset to get them from. It's also possible to search for diffrent parts of a blog ex (title, content, general).

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| blog_id | int | no | for a user to get a specifik blog |
| search_query | string | no | user to search after blogs |
| search_filter | array [string] | no | What part of the blog that the search query is appilied to |
| amount | int | no | Sets the amount of blogs that are retrived |
| offset | int | no | at what start index the get returns from |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched blogs",
    "data": [
        {
            "id": 2,
            "content": "hello im content",
            "title": "imTitle2",
            "user_id": 3,
            "general": null,
            "creation_date": "2025-11-28 13:36:32",
            "latest_update": "2025-11-28 13:36:32",
            "customer_id": 999
        }
    ]
}
```

***

# Add event

**Endpoint:** `/api/calendar-api/add-event.php`  
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

**Endpoint:** `/api/calendar-api/edit-event.php`  
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

**Endpoint:** `/api/calendar-api/delete-event.php`  
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

**Endpoint:** `/api/calendar-api/add-personal-comment.php`  
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

**Endpoint:** `/api/calendar-api/edit-personal-comment.php`  
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

**Endpoint:** `/api/calendar-api/delete-personal-comment.php`  
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

**Endpoint:** `/api/calendar-api/invite-to-event.php`  
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

**Endpoint:** `/api/calendar-api/handle-invites.php`  
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

**Endpoint:** `/api/calendar-api/delete-invitation.php`  
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

**Endpoint:** `/api/calendar-api/get-invitations.php`  
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

**Endpoint:** `/api/calendar-api/get-events.php`  
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



---

# create-wiki

**Endpoint:** `/api/wiki-api/create-wiki.php`  
**Method:** `POST`

## Description
Creates a wiki for the current user.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | The title of the created wiki |
| content | string | no | Content for the wiki formated in ex HTML |
| general | string | no | General ex metadata to be stored with the wiki post |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki created successfully.",
    "data": {
        "wiki_id": 3
    }
}
```

---

# delete-wiki

**Endpoint:** `/api/wiki-api/delete-wiki.php`  
**Method:** `POST`

## Description
Deletes a wiki. Only admins can delete another users wiki if they are under the same company

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_id | int | yes | The id of the wiki that is to be deleted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki deleted successfully.",
    "data": {}
}
```

---

# Edit-wiki

**Endpoint:** `/api/wiki-api/edit-wiki.php`  
**Method:** `POST`

## Description
Allows a enduser to edit a wiki. A enduser can edit any wiki that is part of the same company.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_id | int | yes | The wiki that the user wants to edit |
| content | strign | no  | The new content of the wiki |
| title | string | no | New title |
| general | array | no | General data |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki edited successfully.",
    "data": {}
}
```

---

# Get-all-version

**Endpoint:** `/api/wiki-api/get-all-version.php`  
**Method:** `GET`

## Description
Returns all versions for a specific wiki.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_id | int | yes | The wiki to get all versions of |

## Example JSON Return

```json
{
    "status": "success",
    "message": "successfully retrieved all versions",
    "data": {
        "versions": [
            {
                "id": 7,
                "wiki_id": 4,
                "time": "2025-12-04 09:20:54",
                "content": "testetetetete",
                "user_id": 2
            },
            {
                "id": 5,
                "wiki_id": 4,
                "time": "2025-12-04 09:20:28",
                "content": "Example",
                "user_id": 18
            },
            {
                "id": 6,
                "wiki_id": 4,
                "time": "2025-12-04 09:20:28",
                "content": "Updated content",
                "user_id": 18
            }
        ]
    }
}
```

---

# Get-wiki

**Endpoint:** `/api/wiki-api/get-wiki.php`  
**Method:** `GET`

## Description
Gets the latest version of the specified wiki.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| search_query | string | yes | What the user wants to search for |
| search_filter | [] | yes | Sets what part of the wiki to search ex ['title', 'content', 'general'] |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wikis retrieved successfully.",
    "data": {
        "wikis": [
            {
                "id": 4,
                "user_id": 18,
                "title": "Updated Title",
                "creation_date": "2025-12-04 09:20:28",
                "general": ""
            }
        ]
    }
}
```

---

# restore-wiki-changes

**Endpoint:** `/api/wiki-api/restore-wiki-changes.php`  
**Method:** `POST`

## Description
Restore a wiki to a previous.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wikiChange_id | int | yes | The version to restore to |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Restored successfully (newer changes removed).",
    "data": {}
}
```