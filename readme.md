***

# User types

There exists three diffrent tiers of users in the system, these include user, end user and admin.

User is the base user that can only view blogs and wiki. A normal user doesn't have access to a calendar.

Enduser can create and delete a personal blog, wiki and calendar event. They can also edit every wiki that is within the same company, and their own blog and calendar event. Invite other end users to an event and accept an invitation. Add a comment to an event that can only be seen by them.

Admins can do the same things as an end user. Additionally an admin can ban users, get information about users within the same company, edit and delete other users blogs and wikis. 


# Authentication

## Getting a auth token

In order to get an auth token you need to send a POST request to

`/api/user-api/login.php`


If the customer that is trying to login and does not already have an admin account this creates one with the provided username and password. This requires that the user sends another login request in order to get the auth token.

This endpoint has the required inputs:
```json
{ 
    "username": "the username of the user trying to login", 
    "password": "the password of the user trying to login", 

    "customer_username": "the username of the customers account",
    "customer_password": "the password of the customers account"
}
```
This returns:
```json
{
    "status": "success",
    "message": "Token retrieved successfully",
    "data": {
        "token": "auth-token"
    }
}
```

---

## Using the auth token

All endpoints exluding (login and logout) must have the auth token sent in order to be allowed to use the endpoint.

The token is sent in the header in every request under the Authorization header and in this format:

    Authorization Bearer <auth-token>

---

# General

Blog, Wiki, User and event have an extra space where it is possible to store extra metadata or other data that is needed to be stored. An exemple for this is likes or comments for blogs or wiki. The recomended way to store general data is using json that is sent with the creation or edit of media.

General is sent as an array or associative array.

---

# get-all-users

**Endpoint:** `/api/user-api/get-all-users.php`  
**Method:** `GET`

## Description
Gets info about either multiple users, or about a specific user. 
An end user can retrieve this list of info about other users.  
   
    [
        "username",
        "id"
    ]
An admin can retrieve this:  

    [
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
    ]
A user can retrieve this about their own data: 

    [
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
    ]

## Header
```
{
    Authorization Bearer <Auth-token>
}
```
## Parameters


| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | Can be used if you want to get info about a specific user. |
| result_amount | int | no | Irrelevant if user_id is defined. Defines how many users you want to return. |
| offset | int | no | Only applicable if the result_amount is used. Offsets from where the get starts. |

## Example JSON Return

```json SKA ÄNDRAS
{
    "status": "success",
    "message": "Successfully retrieved user accounts info.",
    "data": {
        "users": [
            {
                "id": 1,
                "customer_id": 10,
                "first_name": null,
                "last_name": null,
                "employment_number": null,
                "birthdate": null,
                "username": "admin",
                "type": "admin",
                "creation_date": "2025-12-09 11:46:47",
                "latest_update": "2025-12-09 11:46:47",
                "general": null,
                "main_mail": null,
                "extra_mail": null,
                "main_address": null,
                "extra_address": null,
                "main_phone_number": null,
                "extra_phone_number": null
            }
        ]
    }
}
```
---

# Get bans

**Endpoint:** `/api/user-api/get-bans.php`  
**Method:** `GET`

## Description
Get all bans. Only an admin has permission to get all bans, while a user can retrieve their own bans.

Data is retuned and ordered per user.


## Header
```
{
    Authorization Bearer <Auth-token>
}
```
## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | If set, gets all bans of that specified user. Also allows a user to retrieve their own bans. |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Successfully retrieved bans of user accounts.",
    "data": {
        "bans": [
            {
                "id": 6,
                "user_id": 2,
                "creation_date": "2025-12-09 14:24:53",
                "expiration_date": "3999-06-06 00:00:00",
                "blog": 1,
                "wiki": 0,
                "calendar": 0,
                "reason": "Inappropriate content.",
                "username": "KarlSvananen"
            }
        ]
    }
}
```


---

# create-user

**Endpoint:** `/api/user-api/create-user.php`  
**Method:** `POST`

## Description
Adds a user under the same company that the current admin user is. Admin type users are the only one allowed to add users

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| username | string | yes | username of the created user |
| password | string | yes | password of the created user |
| type | string | yes | if the user should be a admin/end_user/user |
| first_name | string | no | first name of the person that will use the created user |
| last_name | string | no | last name of the person that will use the created user |
| phone_number | array  | no | phone number is an array input with 2 optional fields, a main phone number: "main": "1234567890" and extra phone numbers: "extra": ["0987654321"]  |
| adress | array | no | address is an array input with 2 optional fields, a main address: "main": "My main address" and extra addresses: "extra": ["My address 1"] |
| employment_number | string | no | the employment number of the person using this account |
| birthdate | string:"yyyy-mm-dd" | no | birthdate of the person using this account |
| mail | array | no | mail is an array input with 2 optional fields, a main mail: "main": "myMain@mail.com" and extra mails: "extra": ["myFirstExtraMail@gmail.com"] |
| general | array | no | A place to store any extra infomration for a user ex (user preferences) |

## Example JSON Input

```json
{
    "mail": {
        "main": "myMainMail@gmail.com",
        "extra": [
            "myFirstExtraMail@gmail.com",
            "mySecondExtraMail@gmail.com"
        ]
    }
}
```

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

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | yes | the id of the user that is being banned |
| exiration_date | string: yyyy-mm-dd hh:mm:ss | yes | The date and time the ban expires on |
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
Edit an existing user, if no user id is sent, the user updates info about themselves

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | Is used if an admin is trying to edit another user in the company |
| mail | array | no | An array of the updated mails of the user, follow example input to see how the array is created |
| first_name | string | no | The first name of the user |
| last_name | string | no | The last name of the user |
| phone_number | array | no | An array of the updated phone numbers of the user, follow example input to see how the array is created |
| adress | array | no | An array of the updated addresses of the user, follow example input to see how the array is created |
| employment_number | string | no | The updated employment number of the user |
| birthdate | string: yyyy-mm-dd | no | The updated birthdate of the user |
| username | string | no | The updated username of the user |
| password | string | no | The updated password of the user |
| type | string | no | The updated type of the user |
| general | array | no | The updated general info |

## Example JSON Input

```json
{
    "user_id": 4,
    "mail": {
        "main": "myMainMail@gmail.com",
        "add": [
            "myFirstExtraMail@gmail.com",
            "mySecondExtraMail@gmail.com"
        ],
        "update": {
            "myFirstExtraMail@gmail.com": "myUpdatedMail@gmail.com"
        },
        "delete": [
            "mySecondExtraMail@gmail.com"
        ]
    }
}
```

## Example JSON Return

```json
{
    "status": "success",
    "message": "User edited",
    "data": {}
}
```

---

# Remove-ban

**Endpoint:** `/api/user-api/remove-ban.php`  
**Method:** `POST`

## Description
Remove a ban from a user, only an admin has acces to this.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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

# Remove-user

**Endpoint:** `/api/user-api/remove-user.php`  
**Method:** `POST`

## Description
Removes the specified user from the current organisation, only an admin has acces to this.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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

# Create blog

**Endpoint:** `/api/blog-api/create-blog.php`  
**Method:** `POST`

## Description
Creates a blog that allows a user to make blog posts

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| description | string | no | Description for the user blog |
| title | string | yes | title for the created blog |

## Example JSON Return

```json
{
    "status": "success",
    "message": "blog created",
    "data": {
        "blog_id": "5" // id for the created blog
    }
}
```

---

# Delete blog

**Endpoint:** `/api/blog-api/delete-blog.php`  
**Method:** `POST`

## Description
removes a user blog including all blog post associated with it. Admins can remove a blog for an end user.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| user_id | int | no | user for when an admin wants to remove another users blog and blog posts |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog deleted successfully",
    "data": {}
}
```

---

# Edit blog

**Endpoint:** `/api/blog-api/edit-blog.php`  
**Method:** `POST`

## Description
Edit a users blog main page. Admins can edit another end users blog

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | no | New title for the blog |
| content | string | no | New content for the blog |
| user_id | int | no | Used if an admin wants to edit an end users blog |
| general | array | no | used to store extra metadata related to the blog |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog updated successfully",
    "data": {}
}
```

---

# Get blog

**Endpoint:** `/api/blog-api/get-blog.php`  
**Method:** `GET`

## Description
Get all blogs from the same company as the user. Able to get by specific blog id. Also able to limit result by using search query.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| blog_id | int | no | Gets a specific blog |
| search_query | string | no | Used to search after specific blog titles and content |
| search_filter | array | no | Used to filter what part the search query is applied to possible inputs are ["title", "content", "general"] It's possible to use any or all of these when searching |
| amount | int | no | Used to limit the amount to resuts that are returned |
| offset | int | no | At what index the returned results start |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched blogs",
    "data": [
        {
            "id": 3,
            "description": "content changed",
            "title": "yes",
            "user_id": 2,
            "general": "[\"test\"]",
            "creation_date": "2025-12-05 15:07:18",
            "latest_update": "2025-12-05 15:07:18",
            "customer_id": 999
        }
    ]
}
```


---

# Create blog post

**Endpoint:** `/api/blog-api/create-blog-post.php`  
**Method:** `POST`

## Description
Creates a blog post for the current end user if they already have created a blog for the post to be attached to. There is no set limit for how many blog posts a user can have.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| content | string | yes | the content for the created blog post stored as for example HTML |
| title | string | yes | Title for the created blog post |

## Example JSON Return

```json
{
    "status": "success",
    "message": "blog post created",
    "data": {
        "id": "6" //this is the id for the blog post that was created
    }
}
```


---

# Edit blog post

**Endpoint:** `/api/blog-api/edit-blog-post.php`  
**Method:** `edit a blog post`

## Description
Edit an existing blog post. An end user can only edit their own blog posts. Admins can edit other users blog posts.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | no | New title |
| content | string | no | New content |
| blog_post_id | int | no | the blog post that is to be edited |
| general | array | no | new general data for the blog post |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog post updated successfully",
    "data": {}
}
```

---

# Delete blog post

**Endpoint:** `/api/blog-api/delete-blog-post.php`  
**Method:** `POST`

## Description
Remove a blog post. End user is able to delete their own posts and admins can delete endusers blog posts

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| blog_post_id | int | yes | The id of the blog post to be deleted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Blog post deleted successfully",
    "data": {}
}
```

---

# Get blog post

**Endpoint:** `/api/blog-api/get-blog-post.php`  
**Method:** `GET`

## Description
Get all blog posts. By user id, blog post id and limit by search query. If no parameters are input it returns all blog posts under the same company as the logged in user.


## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| owner_user_id | int | no | Used to get all blog posts that are made by a specific user |
| blog_post_id | int | no | Used to get a specific blog post |
| search_query | string | no | Used to search after specific content in title, content, general |
| search_filter | array | no | Used to filter what part the search query is applied to possible inputs are ["title", "content", "general"] It's possible to use any or all of these when searching |
| amount | int | no | Used to limit the amount to resuts that are returned |
| offset | int | no | At what index the returned results start |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched blog posts",
    "data": [
        {
            "id": 8,
            "content": "test",
            "title": "test",
            "blog_id": 3,
            "general": "\"\"",
            "creation_date": "2025-12-09 09:34:36",
            "latest_update": "2025-12-09 09:34:36",
            "user_id": 2,
            "customer_id": 999
        },
        {
            "id": 9,
            "content": "test",
            "title": "test",
            "blog_id": 3,
            "general": "\"\"",
            "creation_date": "2025-12-09 09:34:37",
            "latest_update": "2025-12-09 09:34:37",
            "user_id": 2,
            "customer_id": 999
        },
        {
            "id": 10,
            "content": "test",
            "title": "test",
            "blog_id": 3,
            "general": "\"\"",
            "creation_date": "2025-12-09 09:34:38",
            "latest_update": "2025-12-09 09:34:38",
            "user_id": 2,
            "customer_id": 999
        }
    ]
}
```


***

# Create event

**Endpoint:** `/api/calendar-api/create-event.php`  
**Method:** `POST`

## Description
An endpoint to create an event

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | the title of the event |
| event_info | string | no | info about the event |
| start_time | string: yyyy-mm-dd hh:mm:ss | no | the start time for an event |
| end_time | string: yyyy-mm-dd hh:mm:ss | yes | the end time for an event |
| comment | string | no | a personal comment for an event |
| general | array | no | General ex metadata to be stored with the event |

## Example JSON Return

```json
{
    "status": "success",
    "message": "event added successfully",
    "data": {
        "event_id": "<event id>"
    }
}
```

---

# Edit event

**Endpoint:** `/api/calendar-api/edit-event.php`  
**Method:** `POST`

## Description
An endpoint to edit an event (an event can only be edited by the user that owns the event)

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | yes | the id of the event to be edited |
| title | string | no | the edited title for the event |
| event_info | string | no | the edited info for the event |
| start_time | string: yyyy-mm-dd hh:mm:ss | no | the edited start time for the event |
| end_time | string: yyyy-mm-dd hh:mm:ss | no | the edited end time for the event |
| general | array | no | General ex metadata to be stored with the event |

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
An endpoint to delete an event, only the events owner can delete the event

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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

# Create personal comment

**Endpoint:** `/api/calendar-api/create-personal-comment.php`  
**Method:** `POST`

## Description
An endpoint to set a personal comment

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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
An endpoint to invite an end user to an event

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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
An endpoint to accept or decline an event invite

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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
An endpoint to delete an invitation to an event for a specific user, an end user can delete their own invitation with this endpoint

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

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
An endpoint to get the invitations for an event, if event_id is sent the end user retrieves the invites that the end user sent for that specific event. If event_id is not sent then 
the end user retrieves all the invitations that they have been sent by other end users.

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| event_id | int | no | the event id of which the invitations will be retrieved for |
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
An endpoint to retrieve events for a user in different ways, source "own" means that the user owns the event and can edit the event, source "invited" means th user
can see the event but can not edit the event

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| mode | string | yes | selects in which way the events will be retrieved, valid inputs are "all", "range", "specific", "search" |
| start_time | string: yyyy-mm-dd hh:mm:ss | no | for mode "range", the starting date of the timespan events will be selected between |
| end_time | string: yyyy-mm-dd hh:mm:ss | no | for mode "range", the ending date of the timespan events will be selected between |
| event_id | int | no | for mode "specific", input an event id to retrieve that specific event |
| search_query | string | no | for mode "search", the search query to search for an event |
| search_filter | array | no | for mode "search", selects what part of the event the search query will search for, valid filters are "title", "start_time", "end_time", "creation_date", "user_id", "event_info", "general" |
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

***

# Create Wiki

**Endpoint:** `/api/wiki-api/create-wiki.php`  
**Method:** `POST`

## Description
Creates a wiki for the current user.
every user can only have 1 wiki but multiple articles in a wiki

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | Title of the created wiki |
| description | string | no | description of the wiki. ex what it contains |
| general | array | no | General ex metadata to be stored with the wiki |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki successfully created.",
    "data": {}
}
```
---

# Create Wiki article

**Endpoint:** `/api/wiki-api/create-wiki-article.php`  
**Method:** `POST`

## Description
Creates a wiki article for the user

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| title | string | yes | title of the created article |
| content | string | no | content of the article ex json encoded html or just a string |
| general | array | no | General ex metadata to be stored with the article  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Article created successfully",
    "data": {
        "wiki_id": 6,
        "wiki_article_id": 6,
        "title": "Test Wiki"
    }
}
```
---

# Get Wikis

**Endpoint:** `/api/wiki-api/get-wiki.php`  
**Method:** `GET`

## Description
Gets all titles and descriptions of the wikis from the same company
or the titles and descriptions for the ones matching the search

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| search_query | string | no | what to search for |
| search_filter | array | no | Where to search. defualts to ["title"] but can include "title", "description"  or can include both |
| amount | int | no | how many you can get back. defualt 10 |
| offset | int | no | At what index the returned results start |
| order_direction | string enum ["DESC", "ASC"] | no | which order the list is returned. defualt DESC which is newest -> oldest |

## Example JSON Return

```json
{
"status": "success",
"message": "Fetched wikis",
"data": {
	"wikis": [
		{
			"id": 6,
			"title": "Test Wiki",
			"description": "Example description for wiki",
			"creation_date": "2025-12-09 21:32:06"
		},
		{
			"id": 5,
			"title": "Test Wiki",
			"description": "Example description for wiki",
			"creation_date": "2025-12-09 21:32:03"
		},

	],
	"total_count": 2,
	"offset": 0,
	"amount": 10
}
```
---

# Get Wiki Article

**Endpoint:** `/api/wiki-api/get-wiki-article.php`  
**Method:** `GET`

## Description
GETS either a wiki article or multiple articles from a wiki or from the same company

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | no | if entered always returns just the entered article |
| wiki_id | int | no | if entered only gets articles from this wiki |
| search_query | string | no | search  |
| search_filter  | array | no | filter what to search for |
| amount | int | no | how many you can get back. defualt 10 |
| offset | int | no | At what index the returned results start |
| order_direction | string enum ["DESC", "ASC"] | no | which order the list is returned. defualt DESC which is newest -> oldest |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched wiki articles",
    "data": {
        "articles": [
            {
                "wiki_article_id": 3,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 6,
                "creation_date": "2025-12-09 21:31:58",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 3,
                "wiki_owner": 6,
                "customer_id": 10
            },
            {
                "wiki_article_id": 2,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 4,
                "creation_date": "2025-12-09 21:31:56",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 2,
                "wiki_owner": 4,
                "customer_id": 10
            },
            {
                "wiki_article_id": 1,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 2,
                "creation_date": "2025-12-09 21:29:23",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null,
                "wiki_id": 1,
                "wiki_owner": 2,
                "customer_id": 10
            }
        ],
        "total_count": 24,
        "offset": 0,
        "amount": 3
    }
}
```
---

# Edit Wiki Article

**Endpoint:** `/api/wiki-api/edit-wiki.php`  
**Method:** `POST`

## Description
Edit Article
only changes the provided params

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | The article to updates ID |
| title | string | no | updated title |
| content | string | no | updated content |
| general | array | no | ex updated metadata |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki article edited successfully.",
    "data": {}
}
```
---

# Get Wiki Article History / All versions

**Endpoint:** `/api/wiki-api/get-all-version.php`  
**Method:** `GET`

## Description
GETS all the the versions of an Article

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | id of the article |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Fetched wiki article versions",
    "data": {
        "active_version": {
            "wiki_article_id": 6,
            "title": "Updated Title",
            "content": "Updated content",
            "user_id": 12,
            "creation_date": "2025-12-09 22:27:38",
            "general": "[\"Updated general info\"]",
            "restored_from_backup_id": null
        },
        "old_versions": [
            {
                "old_wiki_change_id": 8,
                "wiki_article_id": 6,
                "title": "Updated Title",
                "content": "Updated content",
                "user_id": 12,
                "creation_date": "2025-12-09 22:27:29",
                "general": "[\"Updated general info\"]",
                "restored_from_backup_id": null
            },
            {
                "old_wiki_change_id": 7,
                "wiki_article_id": 6,
                "title": "Test Wiki",
                "content": "Example content for wiki",
                "user_id": 12,
                "creation_date": "2025-12-09 21:32:06",
                "general": "[\"Some general info for wiki\"]",
                "restored_from_backup_id": null
            },
            {
                "old_wiki_change_id": 6,
                "wiki_article_id": 6,
                "title": "Updated Title",
                "content": "Updated content",
                "user_id": 12,
                "creation_date": "2025-12-09 21:32:06",
                "general": "[\"Updated general info\"]",
                "restored_from_backup_id": null
            }
        ]
    }
}
```
---

# Restore Wiki Article

**Endpoint:** `/api/wiki-api/restore-wiki-changes.php`  
**Method:** `POST`

## Description
sets the active version of a wiki to a previous one

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| old_wiki_change_id | int | yes | id of the old_wiki_change you want to restore to |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki article restored successfully",
    "data": {
        "restored_backup_id": 6
    }
}
```
---

# Delete Wiki Article

**Endpoint:** `/api/wiki-api/delete-wiki-article.php`  
**Method:** `POST`

## Description
Deletes a article fully including the history / versions

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_article_id | int | yes | id of the article to delete |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki article deleted successfully by admin.",
    "data": {}
}
```
---

# Delete Wiki

**Endpoint:** `/api/wiki-api/delete-wiki.php`  
**Method:** `POST`

## Description
Deletes a full wiki including all articles in the wiki

## Header
```
{
    Authorization Bearer <Auth-token>
}
```

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| wiki_id | int | yes | id of the wiki to delete |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki deleted successfully.",
    "data": {}
}
```