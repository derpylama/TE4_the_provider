***

# Title

**Endpoint:** `exempel/test`  
**Method:** `POST`

## Description
Write your endpoint description here.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| param1   | string | yes/no | explanation of what it does |
| param2   | number | yes/no | another explanation |


## Example JSON Return

```json
{
  "example": "value"
}
```

---

# Title

**Endpoint:** `exempel/test`  
**Method:** `POST`

## Description
Write your endpoint description here.

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| param1   | string | yes/no | explanation of what it does |
| param2   | number | yes/no | another explanation |


## Example JSON Return

```json
{
  "example": "value"
}
```
---

# Create Wiki

**Endpoint:** `http://localhost:8080/TE4_the_provider/api/wiki-api/create-wiki.php`  
**Method:** `Post`

## Description
creates a wiki

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| Token | string | yes | auth token |
| title | string | yes | title of the wiki |
| content | string | no | creating the wiki with content |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki created successfully"
}
```













---

# Edit user

**Endpoint:** `/api/user-api/edit-user.php`  
**Method:** `POST`

## Description


## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| id | int | yes | id of user that is to be edited |
| mail | string | no | edited mail |
| adress | string | no | edited adress |
| employment_number | int | no |  edited employmentnumber |
| birthdate | string | no | edited birthdate |
| username | string | no | edited username |
| password | string | no | edited password |
| type | string | no |  |

## Example JSON Return

```json
{
    "status": "success",
    "message": "User edited"
}
```
