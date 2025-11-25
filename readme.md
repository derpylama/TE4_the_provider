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

---

# Create Wiki

**Endpoint:** `wiki-api/create-wiki.php`  
**Method:** `POST`

## Description
Creates a wiki for the current user

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| title | string  | yes | Title of the created wiki |
| content | string | no | The content for the wiki |
| general | string | no | A place to store other info that needs to be part of the wiki ex (comments, tags) |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki created successfully.",
    "wiki_id": "4"
}
```

---

# Edit wiki

**Endpoint:** `/wiki-api/edit-wiki.php`  
**Method:** `POST`

## Description
Edits a specifik wiki

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| title | string  | no | New title  |
| content | string | no | New content |
| general | string | no | A place to store other info that needs to be part of the wiki |
| Wiki id  | int | yes | the id of the wiki that is to be edited |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wiki edited successfully."
}
```

---

# Get wiki

**Endpoint:** `/wiki-api/get-wiki.php`  
**Method:** `GET`

## Description
Gets the latest version of a wiki and is able to search after wikis

## Parameters

| Parameter | Type | Required | Description |
|----------|------|----------|-------------|
| token | string | yes | auth token |
| query | string | no | Search query for getting specific wikis |

## Example JSON Return

```json
{
    "status": "success",
    "message": "Wikis retrieved successfully.",
    "wikis": [
        {
            "id": 4,
            "user_id": 1,
            "title": "potato234242",
            "creation_date": "2025-11-24 14:21:07",
            "general": "teererer0"
        }
    ]
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
