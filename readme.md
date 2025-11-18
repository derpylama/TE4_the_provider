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
    "message": "wiki created"
}
```
