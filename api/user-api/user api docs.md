***
# ban user

**Endpoint:** `/api/user-api/ban-user.php`  
**Method:** `POST`

## Description
Bans a user specified by id

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token can be used |
| user_id | int | yes | id of user that is to be banned |
| expiration_date | string | yes | Setting the date of when the ban is to expire. yyyy/mm/dd/mm |
| blog_ban | bolean | no |  |
| wiki_ban | bolean | no |  |
| calendar_ban | bolean | no |  |
| reason | string | no | Optional text field for the admin to write their reason for banning user |

## Example JSON Return

```json
{"status":"success","message":"grubLarva has been banned successfully."}
```
---

# edit user

**Endpoint:** `/api/user-api/edit-user.php`  
**Method:** `POST`

## Description
Edits either you own accounts details or as an admin, edit end user account details.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes |  |
| user_id | int | yes |  |
| mail | string | no |  |
| adress | string | no |  |
| employment_number | int | no |  |
| birthdate | string | no |  |
| username | string | no |  |
| password | string | no |  |
| type | string | no | Only admin can edit this |

## Example JSON Return

```json
{
    "status": "success",
    "message": "User edited"
}
```

---

# get all bannned users

**Endpoint:** `/api/user-api/get-all-banned-users`  
**Method:** `GET`

## Description
Retrives all users within company that has a ban and their info

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token is accepted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "retrived all users belonging to this orginisation",
    "data": [
        {
            "id": "11",
            "customer_id": "999",
            "mail": "",
            "adress": "",
            "employment_number": "0",
            "birthdate": "0000-00-00",
            "username": "user_test",
            "type": "end_user",
            "creation_date": "2025-11-20 13:50:18",
            "latest_update": "2025-11-20 13:50:18"
        }
    ]
}
```
---

# get all users

**Endpoint:** `/api/user-api/get-all-banned-users`  
**Method:** `GET`

## Description
Retrives all users within company with their info

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token is accepted |

## Example JSON Return

```json
{
    "status": "success",
    "message": "retrived all users belonging to this orginisation",
    "data": [
        {
            "id": "10",
            "customer_id": "999",
            "mail": "",
            "adress": "",
            "employment_number": "0",
            "birthdate": "0000-00-00",
            "username": "admin_user_test",
            "type": "admin",
            "creation_date": "2025-11-20 13:48:24",
            "latest_update": "2025-11-20 13:48:24"
        },
        {
            "id": "11",
            "customer_id": "999",
            "mail": "",
            "adress": "",
            "employment_number": "0",
            "birthdate": "0000-00-00",
            "username": "user_test",
            "type": "end_user",
            "creation_date": "2025-11-20 13:50:18",
            "latest_update": "2025-11-20 13:50:18"
        }
    ]
}
```
---

# get user

**Endpoint:** `/api/user-api/get-user`  
**Method:** `GET`

## Description
Retrives info about a specific user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token is accepted |
| user_id | int | no/yes |  |
| username | string | no/yes |  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "retrived user:userdata",
    "data": {
        "id": "12",
        "customer_id": "999",
        "mail": "",
        "adress": "",
        "employment_number": "0",
        "birthdate": "0000-00-00",
        "username": "user",
        "type": "end_user",
        "creation_date": "2025-11-20 13:54:58",
        "latest_update": "2025-11-20 13:54:58"
    }
}
```

---

# remove ban

**Endpoint:** `/api/user-api/remove-ban.php`  
**Method:** `post`

## Description
removes a specific ban on a user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token is accepted |
| ban_id | int | yes | id of the ban to be removed |

## Example JSON Return

```json
{"status":"success","message":"removed user"}
```

---

# remove user

**Endpoint:** `/api/user-api/remove-user.php`  
**Method:** `post`

## Description
removes a user from database

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token is accepted |
| user_id | int | yes | user id of the to be removed user |

## Example JSON Return

```json
{"status":"success","message":"removed user"}
```

---

# Add user

**Endpoint:** `/api/user-api/add-usser.php`  
**Method:** `POST`

## Description


## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | Only an admin token can add a user |
| mail | string | no |  |
| adress | string | no |  |
| employment_number | int | no |  |
| birthdate | string | no |  |
| username | string | yes |  |
| password | string | yes |  |
| type | string | yes |  |

## Example JSON Return

```json
{"status":"success","message":"User added"}
```
