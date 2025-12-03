***

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

# get-all-users

**Endpoint:** `/api/user-api/get-all-users.php`  
**Method:** `get`

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

