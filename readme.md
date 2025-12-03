***
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
